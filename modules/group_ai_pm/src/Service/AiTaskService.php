<?php

namespace Drupal\group_ai_pm\Service;

use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group_ai_pm\Entity\TaskInterface;

/**
 * Service for AI-powered task creation from natural language.
 */
class AiTaskService {

  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The AI provider plugin manager (optional).
   *
   * @var \Drupal\ai\AiProviderPluginManager|null
   */
  protected ?AiProviderPluginManager $aiProvider;

  /**
   * Constructs an AiTaskService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\ai\AiProviderPluginManager|null $ai_provider
   *   The AI provider plugin manager, or NULL when the AI module is absent.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, ?AiProviderPluginManager $ai_provider = NULL) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->aiProvider = $ai_provider;
  }

  /**
   * Checks if AI is available and configured.
   *
   * @return bool
   *   TRUE if AI module is installed and configured, FALSE otherwise.
   */
  public function isAvailable(): bool {
    // Check if AI provider plugin manager was injected (AI module installed).
    if ($this->aiProvider === NULL) {
      return FALSE;
    }

    // Check if AI provider is configured.
    $config = $this->configFactory->get('group_ai_pm.settings');
    $provider = $config->get('ai_provider');
    return !empty($provider);
  }

  /**
   * Parses natural language text into structured task fields.
   *
   * @param string $text
   *   The natural language text to parse.
   *
   * @return array
   *   Parsed task fields with keys: title, description, status, priority, assignee_name.
   *
   * @throws \Exception
   *   If parsing fails or AI is unavailable.
   */
  public function parseNaturalLanguage(string $text): array {
    if (!$this->isAvailable()) {
      throw new \Exception('AI is not available or not configured.');
    }

    $config = $this->configFactory->get('group_ai_pm.settings');
    $provider_id = $config->get('ai_provider');
    $model_id = $config->get('ai_model') ?? '';

    // Get the provider instance.
    $provider = $this->aiProvider->createInstance($provider_id);

    // Build chat input with structured JSON schema.
    $input = new ChatInput([
      new ChatMessage('user', $this->buildPrompt($text)),
    ]);

    $input->setSystemPrompt('You are a project management assistant. Parse the task description into structured JSON fields: title (required string), description (optional string), status (one of: todo, in_progress, review, done; default: todo), priority (one of: low, medium, high, critical; default: medium), assignee_name (optional string). Return ONLY valid JSON.');

    $input->setChatStructuredJsonSchema([
      'type' => 'object',
      'properties' => [
        'title' => ['type' => 'string'],
        'description' => ['type' => 'string'],
        'status' => [
          'type' => 'string',
          'enum' => ['todo', 'in_progress', 'review', 'done'],
        ],
        'priority' => [
          'type' => 'string',
          'enum' => ['low', 'medium', 'high', 'critical'],
        ],
        'assignee_name' => ['type' => 'string'],
      ],
      'required' => ['title'],
    ]);

    try {
      $output = $provider->chat($input, $model_id, ['group_ai_pm']);
      $parsed = json_decode($output->getNormalized()->getText(), TRUE);

      if (!is_array($parsed) || empty($parsed['title'])) {
        throw new \Exception('Failed to parse AI response into task fields.');
      }

      return $this->normalizeParsedData($parsed);
    }
    catch (\Exception $e) {
      throw new \Exception('AI parsing error: ' . $e->getMessage());
    }
  }

  /**
   * Creates a Task entity from parsed fields.
   *
   * @param array $parsed
   *   Parsed task fields from parseNaturalLanguage().
   * @param int $project_id
   *   The project ID to assign this task to.
   *
   * @return \Drupal\group_ai_pm\Entity\TaskInterface
   *   The created Task entity.
   *
   * @throws \Exception
   *   If task creation fails.
   */
  public function createTaskFromParsed(array $parsed, int $project_id): TaskInterface {
    $storage = $this->entityTypeManager->getStorage('task');

    $task_data = [
      'title' => $parsed['title'] ?? 'Untitled Task',
      'project' => $project_id,
      'status' => $parsed['status'] ?? 'todo',
    ];

    if (!empty($parsed['description'])) {
      $task_data['description'] = $parsed['description'];
    }

    if (!empty($parsed['priority'])) {
      $task_data['priority'] = $parsed['priority'];
    }

    // Try to resolve assignee by name.
    if (!empty($parsed['assignee_name'])) {
      $assignee_id = $this->resolveAssignee($parsed['assignee_name']);
      if ($assignee_id) {
        $task_data['uid'] = $assignee_id;
      }
    }

    $task = $storage->create($task_data);
    $task->save();

    return $task;
  }

  /**
   * Builds the prompt for parsing natural language.
   *
   * @param string $text
   *   The natural language text.
   *
   * @return string
   *   The formatted prompt.
   */
  protected function buildPrompt(string $text): string {
    return <<<PROMPT
Parse the following natural language task description and extract structured fields.
Focus on identifying:
- The main task title/summary
- Any detailed description or requirements
- Implied status (if mentioned, e.g., "started", "in progress", "pending review")
- Priority level (if mentioned, e.g., "urgent", "high priority", "low priority")
- Any assignee mentioned (e.g., "assign to John", "for Alice")

Default status to 'todo' if not mentioned.
Default priority to 'medium' if not mentioned.
Leave assignee_name empty if no one is mentioned.

Task description:
$text
PROMPT;
  }

  /**
   * Normalizes parsed data.
   *
   * @param array $parsed
   *   The parsed data from AI.
   *
   * @return array
   *   Normalized data.
   */
  protected function normalizeParsedData(array $parsed): array {
    $normalized = [
      'title' => $parsed['title'] ?? '',
      'description' => $parsed['description'] ?? '',
      'status' => $parsed['status'] ?? 'todo',
      'priority' => $parsed['priority'] ?? 'medium',
      'assignee_name' => $parsed['assignee_name'] ?? '',
    ];

    // Validate status.
    $valid_statuses = ['todo', 'in_progress', 'review', 'done'];
    if (!in_array($normalized['status'], $valid_statuses)) {
      $normalized['status'] = 'todo';
    }

    // Validate priority.
    $valid_priorities = ['low', 'medium', 'high', 'critical'];
    if (!in_array($normalized['priority'], $valid_priorities)) {
      $normalized['priority'] = 'medium';
    }

    return $normalized;
  }

  /**
   * Resolves a user ID from a name.
   *
   * @param string $name
   *   The user name or display name to search for.
   *
   * @return int|null
   *   The user ID, or NULL if not found.
   */
  protected function resolveAssignee(string $name): ?int {
    $user_storage = $this->entityTypeManager->getStorage('user');

    // Try to find by username first.
    $users = $user_storage->loadByProperties(['name' => $name]);
    if (!empty($users)) {
      $user = reset($users);
      return (int) $user->id();
    }

    // Try to find by display name / mail.
    $users = $user_storage->loadByProperties(['mail' => $name]);
    if (!empty($users)) {
      $user = reset($users);
      return (int) $user->id();
    }

    return NULL;
  }

}

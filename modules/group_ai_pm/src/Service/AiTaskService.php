<?php

namespace Drupal\group_ai_pm\Service;

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
   * Constructs an AiTaskService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks if AI is available and configured.
   *
   * @return bool
   *   TRUE if AI module is installed and configured, FALSE otherwise.
   */
  public function isAvailable(): bool {
    // Check if AI module is installed.
    $module_handler = \Drupal::moduleHandler();
    if (!$module_handler->moduleExists('ai')) {
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
    $provider = $config->get('ai_provider');
    $model = $config->get('ai_model') ?? 'claude-3-5-sonnet';

    // Build JSON schema for structured output.
    $schema = [
      'type' => 'object',
      'properties' => [
        'title' => [
          'type' => 'string',
          'description' => 'The task title/summary',
        ],
        'description' => [
          'type' => 'string',
          'description' => 'Detailed task description',
        ],
        'status' => [
          'type' => 'string',
          'enum' => ['todo', 'in_progress', 'review', 'done'],
          'description' => 'Task status',
        ],
        'priority' => [
          'type' => 'string',
          'enum' => ['low', 'medium', 'high', 'critical'],
          'description' => 'Task priority',
        ],
        'assignee_name' => [
          'type' => 'string',
          'description' => 'Name of person to assign to (optional)',
        ],
      ],
      'required' => ['title'],
    ];

    try {
      // Use AI module's chat API to parse natural language.
      $client = \Drupal::service('ai.client');
      $response = $client->complete(
        $provider,
        $model,
        [
          [
            'role' => 'user',
            'content' => $this->buildPrompt($text),
          ],
        ],
        [
          'response_format' => [
            'type' => 'json_schema',
            'json_schema' => [
              'name' => 'task_fields',
              'schema' => $schema,
              'strict' => TRUE,
            ],
          ],
        ]
      );

      // Extract and parse the response.
      if ($response && isset($response['content'])) {
        $parsed = json_decode($response['content'], TRUE);
        if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
          return $this->normalizeParsedData($parsed);
        }
      }

      throw new \Exception('Failed to parse AI response.');
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

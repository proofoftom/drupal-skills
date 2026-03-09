<?php

namespace Drupal\group_ai_pm\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group_ai_pm\Entity\TaskInterface;

/**
 * Service for AI-powered task creation and natural language parsing.
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
   * The AI module's chat API service, if available.
   *
   * @var mixed
   */
  protected $aiChatService;

  /**
   * Constructs an AiTaskService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param mixed $ai_chat_service
   *   The AI chat service (optional, injected as @?ai.service.chat).
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    $ai_chat_service = NULL,
  ) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->aiChatService = $ai_chat_service;
  }

  /**
   * Checks if AI task service is available.
   *
   * Returns FALSE if the AI module is not installed or not configured.
   *
   * @return bool
   *   TRUE if AI is available, FALSE otherwise.
   */
  public function isAvailable(): bool {
    if ($this->aiChatService === NULL) {
      return FALSE;
    }

    $config = $this->configFactory->get('group_ai_pm.settings');
    $ai_provider = $config->get('ai_provider');
    return !empty($ai_provider);
  }

  /**
   * Parses natural language text into structured task fields.
   *
   * Uses the AI module's chat API to parse natural language into:
   * - title (string)
   * - description (string)
   * - status (string: todo, in_progress, review, done)
   * - priority (string: low, medium, high)
   * - assignee_name (string, optional user name for lookup)
   *
   * @param string $text
   *   Natural language task description.
   *
   * @return array
   *   Parsed task fields as an associative array.
   *
   * @throws \Exception
   *   If AI parsing fails.
   */
  public function parseNaturalLanguage(string $text): array {
    if (!$this->isAvailable()) {
      throw new \Exception('AI service is not available.');
    }

    // Build a JSON schema for structured output.
    $schema = [
      'type' => 'object',
      'properties' => [
        'title' => [
          'type' => 'string',
          'description' => 'Task title',
        ],
        'description' => [
          'type' => 'string',
          'description' => 'Task description',
        ],
        'status' => [
          'type' => 'string',
          'enum' => ['todo', 'in_progress', 'review', 'done'],
          'description' => 'Task status',
        ],
        'priority' => [
          'type' => 'string',
          'enum' => ['low', 'medium', 'high'],
          'description' => 'Task priority',
        ],
        'assignee_name' => [
          'type' => 'string',
          'description' => 'Name of the assignee (optional)',
        ],
      ],
      'required' => ['title'],
    ];

    // Call AI API to parse the text.
    $prompt = $this->t(
      'Parse the following task description and extract structured fields. Return JSON only, no markdown or other text. Task: @text',
      ['@text' => $text]
    );

    $config = $this->configFactory->get('group_ai_pm.settings');
    $ai_provider = $config->get('ai_provider');
    $ai_model = $config->get('ai_model') ?? 'claude-3-5-sonnet';

    // Use AI service to generate response with JSON schema constraint.
    $response = $this->aiChatService->submitChat(
      (string) $prompt,
      $ai_provider,
      $ai_model,
      TRUE,  // streaming disabled
      $schema  // JSON schema for structured output
    );

    if (empty($response)) {
      throw new \Exception('AI service returned empty response.');
    }

    // Parse the JSON response.
    $parsed = json_decode($response, TRUE);
    if (!is_array($parsed)) {
      throw new \Exception('Failed to parse AI response as JSON.');
    }

    // Ensure required field is present.
    if (empty($parsed['title'])) {
      throw new \Exception('AI response missing required title field.');
    }

    // Set defaults for optional fields.
    $result = [
      'title' => $parsed['title'],
      'description' => $parsed['description'] ?? '',
      'status' => $parsed['status'] ?? 'todo',
      'priority' => $parsed['priority'] ?? 'medium',
      'assignee_name' => $parsed['assignee_name'] ?? NULL,
    ];

    // Validate status and priority values.
    $allowed_statuses = ['todo', 'in_progress', 'review', 'done'];
    $allowed_priorities = ['low', 'medium', 'high'];

    if (!in_array($result['status'], $allowed_statuses)) {
      $result['status'] = 'todo';
    }

    if (!in_array($result['priority'], $allowed_priorities)) {
      $result['priority'] = 'medium';
    }

    return $result;
  }

  /**
   * Creates a Task entity from parsed natural language fields.
   *
   * @param array $parsed
   *   Parsed task fields from parseNaturalLanguage().
   * @param int $project_id
   *   The project entity ID.
   *
   * @return \Drupal\group_ai_pm\Entity\TaskInterface
   *   The created task entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   If the task creation fails.
   */
  public function createTaskFromParsed(array $parsed, int $project_id): TaskInterface {
    $storage = $this->entityTypeManager->getStorage('task');

    // Look up assignee by name if provided.
    $assignee_id = NULL;
    if (!empty($parsed['assignee_name'])) {
      $assignee_id = $this->lookupUserByName($parsed['assignee_name']);
    }

    $task_data = [
      'title' => $parsed['title'],
      'description' => $parsed['description'],
      'status' => $parsed['status'],
      'priority' => $parsed['priority'],
      'project' => $project_id,
    ];

    if ($assignee_id) {
      $task_data['assignee'] = $assignee_id;
    }

    /** @var \Drupal\group_ai_pm\Entity\TaskInterface $task */
    $task = $storage->create($task_data);
    $task->save();

    return $task;
  }

  /**
   * Looks up a user by display name.
   *
   * @param string $name
   *   The user's display name to search for.
   *
   * @return int|null
   *   The user ID if found, NULL otherwise.
   */
  protected function lookupUserByName(string $name): ?int {
    $user_storage = $this->entityTypeManager->getStorage('user');
    $users = $user_storage->loadByProperties(['name' => $name]);

    if (empty($users)) {
      return NULL;
    }

    $user = reset($users);
    return (int) $user->id();
  }

}

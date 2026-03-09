<?php

namespace Drupal\group_ai_pm\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group_ai_pm\Entity\ProjectInterface;
use Drupal\group_ai_pm\Service\AiTaskService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for AI-powered task creation.
 */
class AiTaskController extends ControllerBase {

  /**
   * The AI task service.
   *
   * @var \Drupal\group_ai_pm\Service\AiTaskService
   */
  protected AiTaskService $aiTaskService;

  /**
   * Constructs an AiTaskController object.
   *
   * @param \Drupal\group_ai_pm\Service\AiTaskService $ai_task_service
   *   The AI task service.
   */
  public function __construct(AiTaskService $ai_task_service) {
    $this->aiTaskService = $ai_task_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('group_ai_pm.ai_task_service')
    );
  }

  /**
   * Creates a task from natural language via AI.
   *
   * @param \Drupal\group_ai_pm\Entity\ProjectInterface $project
   *   The project entity.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with created task or error.
   */
  public function aiCreate(ProjectInterface $project, Request $request): JsonResponse {
    // Check project view access.
    if (!$project->access('view')) {
      return new JsonResponse(
        ['error' => 'Access denied'],
        Response::HTTP_FORBIDDEN
      );
    }

    // Check task create access.
    if (!$this->entityTypeManager()->getAccessControlHandler('task')->createAccess()) {
      return new JsonResponse(
        ['error' => 'Access denied'],
        Response::HTTP_FORBIDDEN
      );
    }

    // Check if AI is available.
    if (!$this->aiTaskService->isAvailable()) {
      return new JsonResponse(
        ['error' => 'AI service is not configured'],
        Response::HTTP_SERVICE_UNAVAILABLE
      );
    }

    // Parse request body.
    $data = json_decode($request->getContent(), TRUE);
    $text = $data['text'] ?? NULL;

    if (!$text || empty(trim($text))) {
      return new JsonResponse(
        ['error' => 'Text field is required'],
        Response::HTTP_UNPROCESSABLE_ENTITY
      );
    }

    try {
      // Parse natural language into task fields.
      $parsed = $this->aiTaskService->parseNaturalLanguage(trim($text));

      // Create the task.
      $task = $this->aiTaskService->createTaskFromParsed($parsed, $project->id());

      // Serialize the created task for response.
      $response_data = $this->serializeTask($task);

      return new JsonResponse(
        $response_data,
        Response::HTTP_CREATED
      );
    }
    catch (\Exception $e) {
      return new JsonResponse(
        ['error' => 'Failed to create task: ' . $e->getMessage()],
        Response::HTTP_INTERNAL_SERVER_ERROR
      );
    }
  }

  /**
   * Serializes a task for JSON response.
   *
   * @param \Drupal\group_ai_pm\Entity\TaskInterface $task
   *   The task entity.
   *
   * @return array
   *   Serialized task data.
   */
  protected function serializeTask($task): array {
    $assignee = NULL;
    $assignee_id = $task->get('assignee')->value;
    if ($assignee_id) {
      $user = $this->entityTypeManager()->getStorage('user')->load($assignee_id);
      if ($user) {
        $picture_url = NULL;
        if ($user->hasField('user_picture') && !$user->get('user_picture')->isEmpty()) {
          $file = $user->user_picture->entity;
          if ($file) {
            $picture_url = $file->createFileUrl(FALSE);
          }
        }

        $assignee = [
          'id' => (int) $user->id(),
          'name' => $user->getDisplayName(),
          'pictureUrl' => $picture_url,
        ];
      }
    }

    $due_date = $task->get('due_date')->value;
    $description = NULL;
    if ($task->hasField('description') && !$task->get('description')->isEmpty()) {
      $description = $task->get('description')->value;
    }

    return [
      'id' => (int) $task->id(),
      'title' => $task->getTitle(),
      'status' => $task->get('status')->value ?? 'todo',
      'priority' => $task->get('priority')->value ?? 'medium',
      'assignee' => $assignee,
      'description' => $description,
      'dueDate' => $due_date,
      'created' => $task->getCreatedTime(),
      'changed' => $task->getChangedTime(),
      'editUrl' => $task->toUrl('edit-form')->toString(),
    ];
  }

}

<?php

namespace Drupal\group_ai_pm\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\group_ai_pm\Entity\ProjectInterface;
use Drupal\group_ai_pm\Service\AiTaskService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for AI-powered task creation endpoints.
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
      $container->get('group_ai_pm.ai_task')
    );
  }

  /**
   * Creates a task from natural language text.
   *
   * @param \Drupal\group_ai_pm\Entity\ProjectInterface $project
   *   The project entity.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with the created task or error message.
   */
  public function createFromText(ProjectInterface $project, Request $request) {
    // Parse request body.
    $data = json_decode($request->getContent(), TRUE);

    if (!is_array($data) || empty($data['text'])) {
      return new JsonResponse(
        ['error' => 'Missing required parameter: text'],
        400
      );
    }

    $text = trim($data['text']);
    if (empty($text)) {
      return new JsonResponse(
        ['error' => 'Text parameter cannot be empty'],
        400
      );
    }

    // Check if AI is available.
    if (!$this->aiTaskService->isAvailable()) {
      return new JsonResponse(
        ['error' => 'AI service is not available or not configured'],
        503
      );
    }

    try {
      // Parse natural language and create task.
      $parsed = $this->aiTaskService->parseNaturalLanguage($text);
      $task = $this->aiTaskService->createTaskFromParsed($parsed, $project->id());

      // Serialize task response.
      $task_data = [
        'id' => $task->id(),
        'title' => $task->getTitle(),
        'description' => $task->getDescription(),
        'status' => $task->get('status')->value ?? 'todo',
        'priority' => $task->get('priority')->value ?? 'medium',
      ];

      // Add assignee info if set.
      $uid = $task->getOwnerId();
      if ($uid && $uid !== 0) {
        $user = $this->entityTypeManager()->getStorage('user')->load($uid);
        if ($user) {
          $task_data['assignee'] = [
            'id' => $user->id(),
            'name' => $user->getDisplayName(),
          ];
        }
      }

      // Build cache metadata.
      $cache_metadata = new CacheableMetadata();
      $cache_metadata->addCacheableDependency($project);
      $cache_metadata->addCacheableDependency($task);
      $cache_metadata->addCacheTags(['group_ai_pm_task_list']);
      $cache_metadata->addCacheContexts(['user.permissions']);

      $response = new CacheableJsonResponse($task_data, 201);
      $response->addCacheableMetadata($cache_metadata);

      return $response;
    }
    catch (\Exception $e) {
      return new JsonResponse(
        ['error' => 'Failed to create task: ' . $e->getMessage()],
        400
      );
    }
  }

}

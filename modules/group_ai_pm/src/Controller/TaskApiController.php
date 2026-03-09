<?php

namespace Drupal\group_ai_pm\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\group_ai_pm\Entity\ProjectInterface;
use Drupal\group_ai_pm\Entity\TaskInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller for Kanban board tasks.
 */
class TaskApiController extends ControllerBase {

  /**
   * Returns tasks grouped by status for a project.
   *
   * @param \Drupal\group_ai_pm\Entity\ProjectInterface $project
   *   The project entity.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   JSON response with tasks grouped by status.
   */
  public function kanban(ProjectInterface $project) {
    // Get all tasks for the project.
    $storage = $this->entityTypeManager()->getStorage('task');
    $tasks = $storage->loadByProperties(['project' => $project->id()]);

    // Group tasks by status.
    $columns = [
      'todo' => [],
      'in_progress' => [],
      'review' => [],
      'done' => [],
    ];

    foreach ($tasks as $task) {
      $status = $task->get('status')->value ?? 'todo';
      if (isset($columns[$status])) {
        $columns[$status][] = $this->serializeTask($task);
      }
    }

    // Build cache metadata.
    $cache_metadata = new CacheableMetadata();
    $cache_metadata->addCacheableDependency($project);
    foreach ($tasks as $task) {
      $cache_metadata->addCacheableDependency($task);
    }
    $cache_metadata->addCacheTags(['group_ai_pm_task_list']);
    $cache_metadata->addCacheContexts(['user.permissions']);

    $response = new CacheableJsonResponse([
      'projectId' => $project->id(),
      'columns' => $columns,
    ]);
    $response->addCacheableDependency($cache_metadata);
    return $response;
  }

  /**
   * Updates task status.
   *
   * @param \Drupal\group_ai_pm\Entity\TaskInterface $task
   *   The task entity.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Updated task JSON or error.
   */
  public function updateStatus(TaskInterface $task, Request $request) {
    // Check update access.
    if (!$task->access('update')) {
      return new JsonResponse(
        ['error' => 'Access denied'],
        Response::HTTP_FORBIDDEN
      );
    }

    $data = json_decode($request->getContent(), TRUE);
    $new_status = $data['status'] ?? NULL;

    if (!$new_status) {
      return new JsonResponse(
        ['error' => 'Status is required'],
        Response::HTTP_UNPROCESSABLE_ENTITY
      );
    }

    $allowed_statuses = ['todo', 'in_progress', 'review', 'done'];
    if (!in_array($new_status, $allowed_statuses)) {
      return new JsonResponse(
        ['error' => 'Invalid status value'],
        Response::HTTP_UNPROCESSABLE_ENTITY
      );
    }

    try {
      $task->set('status', $new_status);
      $task->save();
      return new JsonResponse($this->serializeTask($task));
    }
    catch (EntityStorageException $e) {
      return new JsonResponse(
        ['error' => 'Failed to update task'],
        Response::HTTP_INTERNAL_SERVER_ERROR
      );
    }
  }

  /**
   * Updates task fields (title, priority, assignee).
   *
   * @param \Drupal\group_ai_pm\Entity\TaskInterface $task
   *   The task entity.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Updated task JSON or error.
   */
  public function updateTask(TaskInterface $task, Request $request) {
    // Check update access.
    if (!$task->access('update')) {
      return new JsonResponse(
        ['error' => 'Access denied'],
        Response::HTTP_FORBIDDEN
      );
    }

    $data = json_decode($request->getContent(), TRUE);

    if (isset($data['title'])) {
      $title = trim($data['title']);
      if (empty($title)) {
        return new JsonResponse(
          ['error' => 'Title cannot be empty'],
          Response::HTTP_UNPROCESSABLE_ENTITY
        );
      }
      $task->set('title', $title);
    }

    if (isset($data['priority'])) {
      $priority = $data['priority'];
      $allowed_priorities = ['low', 'medium', 'high'];
      if (!in_array($priority, $allowed_priorities)) {
        return new JsonResponse(
          ['error' => 'Invalid priority value'],
          Response::HTTP_UNPROCESSABLE_ENTITY
        );
      }
      $task->set('priority', $priority);
    }

    if (isset($data['assignee'])) {
      $assignee_id = $data['assignee'];
      if ($assignee_id !== NULL && !is_int($assignee_id)) {
        return new JsonResponse(
          ['error' => 'Invalid assignee'],
          Response::HTTP_UNPROCESSABLE_ENTITY
        );
      }
      $task->set('assignee', $assignee_id);
    }

    try {
      $task->save();
      return new JsonResponse($this->serializeTask($task));
    }
    catch (EntityStorageException $e) {
      return new JsonResponse(
        ['error' => 'Failed to update task'],
        Response::HTTP_INTERNAL_SERVER_ERROR
      );
    }
  }

  /**
   * Creates a new task.
   *
   * @param \Drupal\group_ai_pm\Entity\ProjectInterface $project
   *   The project entity.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   New task JSON or error.
   */
  public function quickCreate(ProjectInterface $project, Request $request) {
    // Check project view access.
    if (!$project->access('view')) {
      return new JsonResponse(
        ['error' => 'Access denied'],
        Response::HTTP_FORBIDDEN
      );
    }

    // Check task create access.
    $storage = $this->entityTypeManager()->getStorage('task');
    if (!$this->entityTypeManager()->getAccessControlHandler('task')->createAccess()) {
      return new JsonResponse(
        ['error' => 'Access denied'],
        Response::HTTP_FORBIDDEN
      );
    }

    $data = json_decode($request->getContent(), TRUE);
    $title = $data['title'] ?? NULL;

    if (!$title || empty(trim($title))) {
      return new JsonResponse(
        ['error' => 'Title is required'],
        Response::HTTP_UNPROCESSABLE_ENTITY
      );
    }

    try {
      $task = $storage->create([
        'title' => trim($title),
        'project' => $project->id(),
        'status' => 'todo',
        'priority' => 'medium',
      ]);
      $task->save();
      return new JsonResponse(
        $this->serializeTask($task),
        Response::HTTP_CREATED
      );
    }
    catch (EntityStorageException $e) {
      return new JsonResponse(
        ['error' => 'Failed to create task'],
        Response::HTTP_INTERNAL_SERVER_ERROR
      );
    }
  }

  /**
   * Deletes a task entity.
   *
   * @param \Drupal\group_ai_pm\Entity\TaskInterface $task
   *   The task entity.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   204 No Content on success, 403 JSON error if access denied.
   */
  public function deleteTask(TaskInterface $task) {
    // Check delete access.
    if (!$task->access('delete')) {
      return new JsonResponse(
        ['error' => 'Access denied'],
        Response::HTTP_FORBIDDEN
      );
    }

    try {
      $task->delete();
      return new Response('', Response::HTTP_NO_CONTENT);
    }
    catch (EntityStorageException $e) {
      return new JsonResponse(
        ['error' => 'Failed to delete task'],
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
  protected function serializeTask(TaskInterface $task) {
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

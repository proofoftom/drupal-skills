<?php

namespace Drupal\group_ai_pm\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\group_ai_pm\Entity\ProjectInterface;

/**
 * REST API controller for Kanban board projects.
 */
class ProjectApiController extends ControllerBase {

  /**
   * Returns task count summary for a project.
   *
   * @param \Drupal\group_ai_pm\Entity\ProjectInterface $project
   *   The project entity.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   JSON response with task counts per status.
   */
  public function summary(ProjectInterface $project) {
    // Get all tasks for the project.
    $storage = $this->entityTypeManager()->getStorage('task');
    $tasks = $storage->loadByProperties(['project' => $project->id()]);

    // Count tasks by status.
    $summary = [
      'todo' => 0,
      'in_progress' => 0,
      'review' => 0,
      'done' => 0,
    ];

    foreach ($tasks as $task) {
      $status = $task->get('status')->value ?? 'todo';
      if (isset($summary[$status])) {
        $summary[$status]++;
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
      'summary' => $summary,
    ]);
    $response->addCacheableDependency($cache_metadata);
    return $response;
  }

}

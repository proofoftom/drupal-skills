<?php

namespace Drupal\group_ai_pm\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group_ai_pm\Entity\ProjectInterface;

/**
 * Controller for the Kanban board page.
 */
class KanbanController extends ControllerBase {

  /**
   * Returns the Kanban board render array.
   *
   * @param \Drupal\group_ai_pm\Entity\ProjectInterface $project
   *   The project entity.
   *
   * @return array
   *   Render array for the board page.
   */
  public function board(ProjectInterface $project) {
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

    // Get current user permissions.
    $current_user = $this->currentUser();
    $can_create_task = $this->entityTypeManager()->getAccessControlHandler('task')->createAccess('create');
    $can_edit_any_task = $current_user->hasPermission('edit any task');

    // Get project members.
    $members = $this->getProjectMembers($project);

    // Pass data to JavaScript via drupalSettings.
    $build = [
      '#theme' => 'group_ai_pm_kanban',
      '#project' => $project,
      '#attached' => [
        'library' => ['group_ai_pm/kanban'],
        'drupalSettings' => [
          'groupAiPm' => [
            'kanban' => [
              'projectId' => $project->id(),
              'projectTitle' => $project->getTitle(),
              'apiBaseUrl' => '/api/kanban',
              'csrfTokenUrl' => '/session/token',
              'columns' => [
                'todo' => ['label' => $this->t('To Do')],
                'in_progress' => ['label' => $this->t('In Progress')],
                'review' => ['label' => $this->t('Review')],
                'done' => ['label' => $this->t('Done')],
              ],
              'statusLabels' => [
                'todo' => $this->t('To Do'),
                'in_progress' => $this->t('In Progress'),
                'review' => $this->t('Review'),
                'done' => $this->t('Done'),
              ],
              'priorityLabels' => [
                'low' => $this->t('Low'),
                'medium' => $this->t('Medium'),
                'high' => $this->t('High'),
              ],
              'tasks' => $columns,
              'members' => $members,
              'permissions' => [
                'createTask' => $can_create_task,
                'editAnyTask' => $can_edit_any_task,
              ],
            ],
          ],
        ],
      ],
      '#cache' => [
        'tags' => array_merge($project->getCacheTags(), ['group_ai_pm_task_list']),
        'contexts' => ['user.permissions', 'user'],
      ],
    ];

    // Add task cache tags.
    foreach ($tasks as $task) {
      $build['#cache']['tags'] = array_merge(
        $build['#cache']['tags'],
        $task->getCacheTags()
      );
    }

    // Add member cache tags.
    $user_storage = $this->entityTypeManager()->getStorage('user');
    foreach ($members as $member) {
      if ($user = $user_storage->load($member['id'])) {
        $build['#cache']['tags'] = array_merge(
          $build['#cache']['tags'],
          $user->getCacheTags()
        );
      }
    }

    return $build;
  }

  /**
   * Returns unique assignee users for a project.
   *
   * @param \Drupal\group_ai_pm\Entity\ProjectInterface $project
   *   The project entity.
   *
   * @return array
   *   Array of {id, name, pictureUrl}.
   */
  protected function getProjectMembers(ProjectInterface $project) {
    $storage = $this->entityTypeManager()->getStorage('task');
    $tasks = $storage->loadByProperties(['project' => $project->id()]);

    $user_ids = [];
    foreach ($tasks as $task) {
      $assignee_id = $task->get('assignee')->value;
      if ($assignee_id) {
        $user_ids[$assignee_id] = TRUE;
      }
    }

    if (empty($user_ids)) {
      return [];
    }

    $users = $this->entityTypeManager()->getStorage('user')->loadMultiple(array_keys($user_ids));
    $members = [];

    foreach ($users as $user) {
      $picture_url = NULL;
      if ($user->hasField('user_picture') && !$user->get('user_picture')->isEmpty()) {
        $file = $user->user_picture->entity;
        if ($file) {
          $picture_url = $file->createFileUrl(FALSE);
        }
      }

      $members[] = [
        'id' => (int) $user->id(),
        'name' => $user->getDisplayName(),
        'pictureUrl' => $picture_url,
      ];
    }

    return $members;
  }

  /**
   * Serializes a task for JSON.
   *
   * @param \Drupal\group_ai_pm\Entity\TaskInterface $task
   *   The task entity.
   *
   * @return array
   *   Serialized task data.
   */
  protected function serializeTask($task) {
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

<?php

namespace Drupal\group_ai_pm\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the Project Dashboard.
 */
class DashboardController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a DashboardController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, DateFormatterInterface $date_formatter) {
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('date.formatter')
    );
  }

  /**
   * Returns the project dashboard page.
   *
   * @return array
   *   A render array.
   */
  public function content() {
    $build = [];

    // Load all projects with access checking, sorted by creation date descending.
    $project_ids = $this->entityTypeManager
      ->getStorage('project')
      ->getQuery()
      ->accessCheck(TRUE)
      ->sort('created', 'DESC')
      ->execute();

    if (!$project_ids) {
      $build['empty'] = [
        '#theme' => 'group_ai_pm_dashboard',
        '#projects' => [],
        '#empty' => TRUE,
        '#quick_action_new_project_url' => Url::fromRoute('entity.project.add_form')->toString(),
        '#attached' => [
          'library' => ['group_ai_pm/dashboard'],
        ],
        '#cache' => [
          'tags' => ['project_list'],
          'contexts' => ['user.permissions'],
          'max_age' => 600,
        ],
      ];
      return $build;
    }

    $projects = $this->entityTypeManager
      ->getStorage('project')
      ->loadMultiple($project_ids);

    $project_summaries = [];
    foreach ($projects as $project) {
      // Count tasks per status.
      $task_ids = $this->entityTypeManager
        ->getStorage('task')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('project', $project->id())
        ->execute();

      $task_counts = [
        'todo' => 0,
        'in_progress' => 0,
        'review' => 0,
        'done' => 0,
      ];

      if ($task_ids) {
        $tasks = $this->entityTypeManager
          ->getStorage('task')
          ->loadMultiple($task_ids);

        foreach ($tasks as $task) {
          $status = $task->getStatus() ?? 'todo';
          if (isset($task_counts[$status])) {
            $task_counts[$status]++;
          }
        }
      }

      $total_tasks = array_sum($task_counts);
      $progress_percentage = $total_tasks > 0
        ? round(($task_counts['done'] / $total_tasks) * 100)
        : 0;

      $project_summaries[] = [
        'title' => $project->getTitle(),
        'status' => $project->getStatus(),
        'url' => $project->toUrl()->toString(),
        'board_url' => Url::fromRoute('group_ai_pm.kanban_board', ['project' => $project->id()])->toString(),
        'task_counts' => $task_counts,
        'total_tasks' => $total_tasks,
        'progress_percentage' => $progress_percentage,
      ];
    }

    // Get 5 most recent projects.
    $recent_project_ids = array_slice($project_ids, 0, 5);
    $recent_projects = [];
    foreach ($recent_project_ids as $pid) {
      if (isset($projects[$pid])) {
        $p = $projects[$pid];
        $recent_projects[] = [
          'title' => $p->getTitle(),
          'url' => $p->toUrl()->toString(),
        ];
      }
    }

    $build['dashboard'] = [
      '#theme' => 'group_ai_pm_dashboard',
      '#projects' => $project_summaries,
      '#empty' => FALSE,
      '#recent_projects' => $recent_projects,
      '#quick_action_new_project_url' => Url::fromRoute('entity.project.add_form')->toString(),
      '#attached' => [
        'library' => ['group_ai_pm/dashboard'],
      ],
      '#cache' => [
        'tags' => ['project_list', 'task_list'],
        'contexts' => ['user.permissions'],
        'max_age' => 600,
      ],
    ];

    return $build;
  }

}

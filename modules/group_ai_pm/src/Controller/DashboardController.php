<?php

namespace Drupal\group_ai_pm\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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

    // Get project count.
    $project_count = $this->entityTypeManager
      ->getStorage('project')
      ->getQuery()
      ->accessCheck(TRUE)
      ->count()
      ->execute();

    $build['project_count'] = [
      '#markup' => $this->t('<div class="dashboard-stat"><h3>@count Projects</h3></div>', [
        '@count' => $project_count,
      ]),
    ];

    // Get recent projects.
    $recent_projects = $this->entityTypeManager
      ->getStorage('project')
      ->getQuery()
      ->accessCheck(TRUE)
      ->sort('created', 'DESC')
      ->range(0, 10)
      ->execute();

    if ($recent_projects) {
      $projects = $this->entityTypeManager
        ->getStorage('project')
        ->loadMultiple($recent_projects);

      $rows = [];
      foreach ($projects as $project) {
        $rows[] = [
          'title' => $project->getTitle(),
          'status' => $project->getStatus(),
          'owner' => $project->getOwner()->getDisplayName(),
          'created' => $this->dateFormatter->format($project->getCreatedTime(), 'short'),
        ];
      }

      $build['recent_projects'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Title'),
          $this->t('Status'),
          $this->t('Owner'),
          $this->t('Created'),
        ],
        '#rows' => $rows,
        '#caption' => $this->t('Recent Projects'),
        '#prefix' => '<div class="dashboard-section">',
        '#suffix' => '</div>',
      ];
    }
    else {
      $build['no_projects'] = [
        '#markup' => $this->t('<p>No projects yet.</p>'),
      ];
    }

    return $build;
  }

}

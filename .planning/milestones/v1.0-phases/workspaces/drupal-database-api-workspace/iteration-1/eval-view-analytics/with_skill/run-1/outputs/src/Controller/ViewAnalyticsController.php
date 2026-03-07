<?php

namespace Drupal\view_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\view_analytics\ViewAnalyticsTracker;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders the View Analytics admin report.
 */
class ViewAnalyticsController extends ControllerBase {

  public function __construct(
    protected readonly ViewAnalyticsTracker $tracker,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('view_analytics.tracker'),
    );
  }

  /**
   * Renders the top 10 most viewed nodes report.
   */
  public function report(): array {
    $rows = $this->tracker->getTopViewed(10);

    // Collect nids and load nodes to get their titles.
    $nids = array_column($rows, 'nid');
    $nodes = $nids
      ? $this->entityTypeManager()->getStorage('node')->loadMultiple($nids)
      : [];

    $table_rows = [];
    foreach ($rows as $row) {
      $node = $nodes[$row->nid] ?? NULL;
      $table_rows[] = [
        $row->nid,
        $node ? $node->label() : $this->t('(deleted)'),
        (int) $row->view_count,
      ];
    }

    return [
      '#type' => 'table',
      '#caption' => $this->t('Top 10 most viewed nodes'),
      '#header' => [
        $this->t('Node ID'),
        $this->t('Title'),
        $this->t('View Count'),
      ],
      '#rows' => $table_rows,
      '#empty' => $this->t('No view data recorded yet.'),
    ];
  }

}

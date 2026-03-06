<?php

namespace Drupal\view_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for View Analytics routes.
 */
class ViewAnalyticsController extends ControllerBase {

  public function __construct(
    protected readonly Connection $database,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('database'),
    );
  }

  /**
   * Renders the top 10 most viewed nodes report.
   */
  public function report(): array {
    $query = $this->database->select('view_analytics', 'va');
    $query->addField('va', 'nid');
    $query->addExpression('COUNT(*)', 'view_count');
    // Join node_field_data to get the title without using Entity API.
    $query->leftJoin('node_field_data', 'n', '[va].[nid] = [n].[nid] AND [n].[default_langcode] = 1');
    $query->addField('n', 'title');
    $query->groupBy('va.nid');
    $query->groupBy('n.title');
    $query->orderBy('view_count', 'DESC');
    $query->range(0, 10);

    $results = $query->execute()->fetchAll();

    $rows = [];
    foreach ($results as $row) {
      $rows[] = [
        $row->nid,
        $row->title ?? $this->t('(deleted)'),
        $row->view_count,
      ];
    }

    return [
      '#type' => 'table',
      '#header' => [
        $this->t('Node ID'),
        $this->t('Title'),
        $this->t('Views'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('No views recorded yet.'),
    ];
  }

}

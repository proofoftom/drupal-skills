<?php

namespace Drupal\content_recommendations_baseline;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Fetches recommended content items.
 */
class RecommendationsService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Returns recommended node IDs ordered by created date.
   *
   * @param int $limit
   *   Maximum number of items to return.
   *
   * @return array
   *   Array of node titles keyed by nid.
   */
  public function getRecommendations(int $limit = 5): array {
    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery()
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->range(0, $limit)
      ->accessCheck(TRUE);

    $nids = $query->execute();
    if (empty($nids)) {
      return [];
    }

    $nodes = $storage->loadMultiple($nids);
    $items = [];
    foreach ($nodes as $node) {
      $items[$node->id()] = $node->label();
    }

    return $items;
  }

}

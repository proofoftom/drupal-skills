<?php

declare(strict_types=1);

namespace Drupal\content_recommendations;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Fetches recommended content items for the current user.
 */
class RecommendationService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountInterface $currentUser,
  ) {}

  /**
   * Returns recently published nodes accessible to the current user.
   *
   * @param int $limit
   *   Maximum number of items to return.
   *
   * @return \Drupal\node\NodeInterface[]
   *   Array of node entities.
   */
  public function getRecommendations(int $limit = 5): array {
    $storage = $this->entityTypeManager->getStorage('node');

    $query = $storage->getQuery()
      ->condition('status', NodeInterface::PUBLISHED)
      ->accessCheck(TRUE)
      ->sort('changed', 'DESC')
      ->range(0, $limit);

    $nids = $query->execute();
    if (empty($nids)) {
      return [];
    }

    return $storage->loadMultiple($nids);
  }

}

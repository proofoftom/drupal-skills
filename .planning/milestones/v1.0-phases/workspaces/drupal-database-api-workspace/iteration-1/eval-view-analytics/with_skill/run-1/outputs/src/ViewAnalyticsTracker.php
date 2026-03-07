<?php

namespace Drupal\view_analytics;

use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Records and retrieves node view analytics from a custom table.
 */
class ViewAnalyticsTracker {

  public function __construct(
    protected readonly Connection $database,
    protected readonly RequestStack $requestStack,
  ) {}

  /**
   * Records a single node view event.
   */
  public function recordView(int $nid, int $uid): void {
    $request = $this->requestStack->getCurrentRequest();
    $referrer = $request ? substr($request->headers->get('referer', ''), 0, 255) : '';

    $this->database->insert('view_analytics')
      ->fields([
        'nid' => $nid,
        'uid' => $uid,
        'viewed_at' => \Drupal::time()->getRequestTime(),
        'referrer' => $referrer ?: NULL,
      ])
      ->execute();
  }

  /**
   * Returns the top viewed nodes with their view counts.
   *
   * @return array
   *   Array of stdClass objects with 'nid' and 'view_count' properties,
   *   ordered by view_count descending.
   */
  public function getTopViewed(int $limit = 10): array {
    $query = $this->database->select('view_analytics', 'va');
    $query->addField('va', 'nid');
    $query->addExpression('COUNT(*)', 'view_count');
    $query->groupBy('va.nid');
    $query->orderBy('view_count', 'DESC');
    $query->range(0, $limit);

    return $query->execute()->fetchAll();
  }

}

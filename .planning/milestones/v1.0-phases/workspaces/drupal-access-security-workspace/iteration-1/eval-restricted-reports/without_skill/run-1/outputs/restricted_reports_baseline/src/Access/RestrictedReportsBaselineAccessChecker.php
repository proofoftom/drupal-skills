<?php

namespace Drupal\restricted_reports_baseline\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Access checker for the restricted reports baseline page.
 */
class RestrictedReportsBaselineAccessChecker {

  /**
   * Checks access for the restricted reports baseline page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged-in account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function access(AccountInterface $account): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'view restricted reports baseline');
  }

}

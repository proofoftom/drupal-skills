<?php

namespace Drupal\restricted_reports\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Checks access for the restricted reports page.
 */
class RestrictedReportsAccessChecker {

  /**
   * Checks access based on the 'view restricted reports' permission.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged-in account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result. Allowed if the user has the required permission,
   *   forbidden otherwise. Cache context is added automatically by
   *   allowedIfHasPermission().
   */
  public function access(AccountInterface $account): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'view restricted reports');
  }

}

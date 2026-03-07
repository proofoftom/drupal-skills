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
   * Uses allowedIfHasPermission() which automatically adds the
   * user.permissions cache context, ensuring access decisions are
   * cached per user permissions rather than globally.
   */
  public function access(AccountInterface $account): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'view restricted reports');
  }

}

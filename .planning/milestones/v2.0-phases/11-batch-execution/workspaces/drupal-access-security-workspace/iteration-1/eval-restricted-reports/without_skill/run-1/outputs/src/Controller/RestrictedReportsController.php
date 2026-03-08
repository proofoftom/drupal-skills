<?php

namespace Drupal\restricted_reports\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Controller for the Restricted Reports module.
 */
class RestrictedReportsController extends ControllerBase {

  /**
   * Returns the restricted reports page.
   *
   * @return array
   *   A render array.
   */
  public function page(): array {
    return [
      '#markup' => $this->t('Welcome to the Restricted Reports page. This content is only visible to authorized users.'),
    ];
  }

  /**
   * Checks access for the restricted reports page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function access(AccountInterface $account): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'view restricted reports');
  }

}

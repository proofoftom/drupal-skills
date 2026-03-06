<?php

namespace Drupal\restricted_reports\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns the restricted reports page.
 */
class RestrictedReportsController extends ControllerBase {

  /**
   * Renders the restricted reports page.
   */
  public function page(): array {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Welcome to Restricted Reports. This page is only visible to users with the appropriate permission.'),
    ];
  }

}

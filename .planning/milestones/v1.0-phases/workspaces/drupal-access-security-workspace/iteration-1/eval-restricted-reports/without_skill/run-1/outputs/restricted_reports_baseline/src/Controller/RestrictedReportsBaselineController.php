<?php

namespace Drupal\restricted_reports_baseline\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for the restricted reports baseline page.
 */
class RestrictedReportsBaselineController extends ControllerBase {

  /**
   * Returns the restricted reports baseline page.
   *
   * @return array
   *   A render array.
   */
  public function page(): array {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Welcome to the Restricted Reports Baseline page. You have the required permission to view this content.'),
    ];
  }

}

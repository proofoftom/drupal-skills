<?php

namespace Drupal\restricted_reports\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for the Restricted Reports page.
 */
class RestrictedReportsController extends ControllerBase {

  /**
   * Returns the restricted reports page.
   *
   * @return array
   *   A render array for the page.
   */
  public function view(): array {
    return [
      '#markup' => $this->t('This is the restricted reports page. You have the required permission to view this content.'),
    ];
  }

}

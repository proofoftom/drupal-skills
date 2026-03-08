<?php

namespace Drupal\restricted_reports\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for the restricted reports page.
 */
class RestrictedReportsController extends ControllerBase {

  /**
   * Returns the restricted reports page content.
   *
   * @return array
   *   A render array for the page.
   */
  public function page(): array {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Welcome to the restricted reports page. You have access because you hold the <em>view restricted reports</em> permission.'),
    ];
  }

}

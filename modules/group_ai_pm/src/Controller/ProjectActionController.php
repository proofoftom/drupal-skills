<?php

namespace Drupal\group_ai_pm\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\group_ai_pm\Entity\Project;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for project action routes.
 */
class ProjectActionController extends ControllerBase {

  /**
   * Mark a project as completed.
   *
   * @param \Drupal\group_ai_pm\Entity\Project $project
   *   The project entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to the project view.
   */
  public function completeProject(Project $project) {
    $project->setStatus('completed');
    $project->save();

    $this->messenger()->addStatus($this->t('Project %title marked as completed.', [
      '%title' => $project->getTitle(),
    ]));

    return new RedirectResponse($project->toUrl('canonical')->toString());
  }

  /**
   * Check access for completing a project.
   *
   * @param \Drupal\group_ai_pm\Entity\Project $project
   *   The project entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function completeProjectAccess(Project $project, AccountInterface $account) {
    // Allow users who can edit the project.
    return $project->access('update', $account, TRUE);
  }

}

<?php

namespace Drupal\group_ai_pm;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for Project entities.
 */
class ProjectAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view project');

      case 'update':
        if ($account->hasPermission('edit any project')) {
          return AccessResult::allowed();
        }
        if ($account->hasPermission('edit own project') && $entity->get('uid')->target_id == $account->id()) {
          return AccessResult::allowed();
        }
        return AccessResult::neutral();

      case 'delete':
        if ($account->hasPermission('delete any project')) {
          return AccessResult::allowed();
        }
        if ($account->hasPermission('delete own project') && $entity->get('uid')->target_id == $account->id()) {
          return AccessResult::allowed();
        }
        return AccessResult::neutral();

      default:
        return AccessResult::neutral();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'create project');
  }

}

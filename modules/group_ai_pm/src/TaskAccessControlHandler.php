<?php

namespace Drupal\group_ai_pm;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for Task entities.
 */
class TaskAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view task')
          ->addCacheContexts(['user.permissions']);

      case 'update':
        if ($account->hasPermission('edit any task')) {
          return AccessResult::allowed();
        }
        if ($account->hasPermission('edit own task') && $entity->get('uid')->target_id == $account->id()) {
          return AccessResult::allowed()
            ->addCacheableDependency($entity)
            ->addCacheContexts(['user']);
        }
        return AccessResult::neutral()
          ->addCacheContexts(['user.permissions']);

      case 'delete':
        if ($account->hasPermission('delete any task')) {
          return AccessResult::allowed();
        }
        if ($account->hasPermission('delete own task') && $entity->get('uid')->target_id == $account->id()) {
          return AccessResult::allowed()
            ->addCacheableDependency($entity)
            ->addCacheContexts(['user']);
        }
        return AccessResult::neutral()
          ->addCacheContexts(['user.permissions']);

      default:
        return AccessResult::neutral();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'create task')
      ->addCacheContexts(['user.permissions']);
  }

}

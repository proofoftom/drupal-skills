<?php

namespace Drupal\knowledge_resource;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Knowledge Resource entity.
 */
class KnowledgeResourceAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view knowledge resource entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit knowledge resource entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete knowledge resource entities');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add knowledge resource entities');
  }

}

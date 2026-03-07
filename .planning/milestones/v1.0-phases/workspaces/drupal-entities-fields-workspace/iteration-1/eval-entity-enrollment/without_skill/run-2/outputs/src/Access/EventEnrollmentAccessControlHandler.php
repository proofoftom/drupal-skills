<?php

namespace Drupal\event_enrollment\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the event enrollment entity type.
 */
class EventEnrollmentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission('administer event enrollment entities')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view event enrollment entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit event enrollment entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete event enrollment entities');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer event enrollment entities',
      'add event enrollment entities',
    ], 'OR');
  }

}

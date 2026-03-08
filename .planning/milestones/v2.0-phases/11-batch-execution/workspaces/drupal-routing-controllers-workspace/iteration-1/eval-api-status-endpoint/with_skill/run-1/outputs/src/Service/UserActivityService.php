<?php

namespace Drupal\user_activity\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserInterface;

/**
 * Provides activity data for a given user.
 */
class UserActivityService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a UserActivityService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
  }

  /**
   * Returns activity data for the given user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   *
   * @return array
   *   An associative array with keys:
   *   - node_count: Number of nodes the user has authored.
   *   - last_access: Unix timestamp of the user's last access, or NULL.
   */
  public function getActivityData(UserInterface $user): array {
    $uid = $user->id();

    $node_count = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->condition('uid', $uid)
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    $last_access = $user->getLastAccessedTime();

    return [
      'node_count' => (int) $node_count,
      'last_access' => $last_access > 0 ? (int) $last_access : NULL,
    ];
  }

}

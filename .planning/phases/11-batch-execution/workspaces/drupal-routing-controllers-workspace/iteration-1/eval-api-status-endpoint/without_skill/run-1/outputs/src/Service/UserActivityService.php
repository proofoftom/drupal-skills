<?php

namespace Drupal\user_activity\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserInterface;

/**
 * Service for retrieving user activity data.
 */
class UserActivityService {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a UserActivityService object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entity_type_manager) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Retrieves activity data for the given user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   *
   * @return array
   *   An associative array with keys:
   *   - node_count: Number of nodes authored by the user.
   *   - last_access: Timestamp of the user's last access, or NULL if never.
   */
  public function getActivityData(UserInterface $user): array {
    $node_count = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->condition('uid', $user->id())
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

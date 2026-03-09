<?php

namespace Drupal\group_ai_pm;

/**
 * Generate granular CRUD permissions for Project and Task entities.
 */
class PermissionGenerator {

  /**
   * Get permissions for Project and Task entities.
   *
   * @return array
   *   Array of permission definitions.
   */
  public static function getPermissions() {
    $permissions = [];

    foreach (['project', 'task'] as $entity_type) {
      $label_plural = $entity_type === 'task' ? 'Tasks' : 'Projects';

      $permissions["create {$entity_type}"] = [
        'title' => "Create $label_plural",
        'description' => "Create new $label_plural.",
      ];

      $permissions["edit own {$entity_type}"] = [
        'title' => "Edit own $label_plural",
        'description' => "Edit $label_plural that the user owns.",
      ];

      $permissions["edit any {$entity_type}"] = [
        'title' => "Edit any $label_plural",
        'description' => "Edit any $label_plural.",
        'restrict access' => TRUE,
      ];

      $permissions["delete own {$entity_type}"] = [
        'title' => "Delete own $label_plural",
        'description' => "Delete $label_plural that the user owns.",
      ];

      $permissions["delete any {$entity_type}"] = [
        'title' => "Delete any $label_plural",
        'description' => "Delete any $label_plural.",
        'restrict access' => TRUE,
      ];

      $permissions["view {$entity_type}"] = [
        'title' => "View $label_plural",
        'description' => "View $label_plural.",
      ];
    }

    return $permissions;
  }

}

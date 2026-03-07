<?php

namespace Drupal\event_enrollment\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Event Enrollment entity type.
 *
 * @ContentEntityType(
 *   id = "event_enrollment",
 *   label = @Translation("Event Enrollment"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *   },
 *   base_table = "event_enrollment",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class EventEnrollment extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    // Baseline: missing parent::baseFieldDefinitions() call
    $fields = [];

    $fields['event_reference'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Event'))
      ->setSetting('target_type', 'node')
      ->setRequired(TRUE);

    $fields['user_reference'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setSetting('allowed_values', [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'cancelled' => 'Cancelled',
      ])
      ->setRequired(TRUE);

    $fields['enrollment_date'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Enrollment Date'));

    return $fields;
  }

}

<?php

namespace Drupal\event_enrollment\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Event Enrollment entity.
 *
 * @ContentEntityType(
 *   id = "event_enrollment",
 *   label = @Translation("Event Enrollment"),
 *   label_collection = @Translation("Event Enrollments"),
 *   label_singular = @Translation("event enrollment"),
 *   label_plural = @Translation("event enrollments"),
 *   label_count = @PluralTranslation(
 *     singular = "@count event enrollment",
 *     plural = "@count event enrollments",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\event_enrollment\EventEnrollmentListBuilder",
 *     "form" = {
 *       "default" = "Drupal\event_enrollment\Form\EventEnrollmentForm",
 *       "add" = "Drupal\event_enrollment\Form\EventEnrollmentForm",
 *       "edit" = "Drupal\event_enrollment\Form\EventEnrollmentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\event_enrollment\Access\EventEnrollmentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "event_enrollment",
 *   admin_permission = "administer event enrollment entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/event-enrollment/{event_enrollment}",
 *     "add-form" = "/admin/content/event-enrollment/add",
 *     "edit-form" = "/admin/content/event-enrollment/{event_enrollment}/edit",
 *     "delete-form" = "/admin/content/event-enrollment/{event_enrollment}/delete",
 *     "collection" = "/admin/content/event-enrollment",
 *   },
 * )
 */
class EventEnrollment extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Event reference field.
    $fields['event'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Event'))
      ->setDescription(t('The event node this enrollment is for.'))
      ->setSetting('target_type', 'node')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // User reference field.
    $fields['user'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The user who is enrolled.'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Enrollment status field.
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Enrollment Status'))
      ->setDescription(t('The enrollment status.'))
      ->setRequired(TRUE)
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'cancelled' => 'Cancelled',
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Enrollment date (created timestamp).
    $fields['enrollment_date'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Enrollment Date'))
      ->setDescription(t('The time the enrollment was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // Changed timestamp.
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time the enrollment was last edited.'));

    return $fields;
  }

}

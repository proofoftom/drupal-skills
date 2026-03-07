<?php

namespace Drupal\event_enrollment\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the EventEnrollment entity.
 *
 * @ContentEntityType(
 *   id = "event_enrollment",
 *   label = @Translation("Event Enrollment"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\event_enrollment\EventEnrollmentListBuilder",
 *     "form" = {
 *       "default" = "Drupal\event_enrollment\Form\EventEnrollmentForm",
 *       "add" = "Drupal\event_enrollment\Form\EventEnrollmentForm",
 *       "edit" = "Drupal\event_enrollment\Form\EventEnrollmentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\event_enrollment\EventEnrollmentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     }
 *   },
 *   base_table = "event_enrollment",
 *   admin_permission = "administer event enrollments",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/event-enrollment/{event_enrollment}",
 *     "add-form" = "/admin/structure/event-enrollment/add",
 *     "edit-form" = "/admin/structure/event-enrollment/{event_enrollment}/edit",
 *     "delete-form" = "/admin/structure/event-enrollment/{event_enrollment}/delete",
 *     "collection" = "/admin/structure/event-enrollment",
 *   }
 * )
 */
class EventEnrollment extends ContentEntityBase implements EventEnrollmentInterface {

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('enrollment_date')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('enrollment_date', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['event'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Event'))
      ->setDescription(t('The event node this enrollment is for.'))
      ->setSetting('target_type', 'node')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The user who enrolled.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Enrollment Status'))
      ->setDescription(t('The enrollment status.'))
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

    $fields['enrollment_date'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Enrollment Date'))
      ->setDescription(t('The time the enrollment was created.'));

    return $fields;
  }

}

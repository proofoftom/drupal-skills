<?php

namespace Drupal\event_enrollment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for the event enrollment entity type.
 */
class EventEnrollmentListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['event'] = $this->t('Event');
    $header['user'] = $this->t('User');
    $header['status'] = $this->t('Status');
    $header['enrollment_date'] = $this->t('Enrollment Date');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\event_enrollment\Entity\EventEnrollment $entity */
    $row['id'] = $entity->id();

    // Event reference.
    $event = $entity->get('event')->entity;
    $row['event'] = $event ? $event->label() : $this->t('N/A');

    // User reference.
    $user = $entity->get('user')->entity;
    $row['user'] = $user ? $user->label() : $this->t('N/A');

    // Status.
    $row['status'] = $entity->get('status')->value;

    // Enrollment date.
    $enrollment_date = $entity->get('enrollment_date')->value;
    $row['enrollment_date'] = $enrollment_date ? \Drupal::service('date.formatter')->format($enrollment_date) : '';

    return $row + parent::buildRow($entity);
  }

}

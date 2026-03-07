<?php

namespace Drupal\event_enrollment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list builder for the EventEnrollment entity.
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
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\event_enrollment\Entity\EventEnrollmentInterface $entity */
    $row['id'] = $entity->id();
    $row['event'] = $entity->get('event')->entity ? $entity->get('event')->entity->label() : '';
    $row['user'] = $entity->get('user')->entity ? $entity->get('user')->entity->getDisplayName() : '';
    $row['status'] = $entity->getStatus();
    return $row + parent::buildRow($entity);
  }

}

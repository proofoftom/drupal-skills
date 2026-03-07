<?php

namespace Drupal\knowledge_resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of Knowledge Resource entities.
 */
class KnowledgeResourceListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['resource_type'] = $this->t('Resource Type');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['resource_type'] = $entity->get('resource_type')->value;
    $row['created'] = \Drupal::service('date.formatter')->format($entity->get('created')->value);
    return $row + parent::buildRow($entity);
  }

}

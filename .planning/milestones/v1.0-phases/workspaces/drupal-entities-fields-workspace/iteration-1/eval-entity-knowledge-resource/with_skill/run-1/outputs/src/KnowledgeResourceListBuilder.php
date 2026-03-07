<?php

namespace Drupal\knowledge_resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

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
    $header['author'] = $this->t('Author');
    $header['related_topic'] = $this->t('Related Topic');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\knowledge_resource\Entity\KnowledgeResourceInterface $entity */
    $row['id'] = Link::createFromRoute($entity->id(), 'entity.knowledge_resource.canonical', ['knowledge_resource' => $entity->id()]);

    $resource_type = $entity->get('resource_type')->value;
    $row['resource_type'] = $resource_type ? $entity->get('resource_type')->getFieldDefinition()->getFieldStorageDefinition()->getSetting('allowed_values')[$resource_type] ?? $resource_type : '';

    $author = $entity->get('author')->entity;
    $row['author'] = $author ? $author->getDisplayName() : '';

    $topic = $entity->get('related_topic')->entity;
    $row['related_topic'] = $topic ? $topic->label() : '';

    $row['created'] = \Drupal::service('date.formatter')->format($entity->get('created')->value, 'short');

    return $row + parent::buildRow($entity);
  }

}

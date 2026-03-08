<?php

namespace Drupal\group_ai_pm;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of Project entities.
 */
class ProjectListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = [
      'data' => $this->t('Title'),
      'field' => 'title',
      'specifier' => 'title',
      'sort' => 'asc',
    ];
    $header['status'] = [
      'data' => $this->t('Status'),
      'field' => 'status',
      'specifier' => 'status',
    ];
    $header['owner'] = [
      'data' => $this->t('Owner'),
      'field' => 'uid',
      'specifier' => 'uid',
    ];
    $header['created'] = [
      'data' => $this->t('Created'),
      'field' => 'created',
      'specifier' => 'created',
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['title'] = $entity->getTitle();
    $row['status'] = $entity->getStatus();
    $row['owner'] = $entity->getOwner()->getDisplayName();
    $row['created'] = \Drupal::service('date.formatter')->format($entity->getCreatedTime(), 'short');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->accessCheck(TRUE)
      ->pager(50);
    $header = $this->buildHeader();
    $query->tableSort($header);
    return $query->execute();
  }

}

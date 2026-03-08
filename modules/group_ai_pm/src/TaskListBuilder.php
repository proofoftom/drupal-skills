<?php

namespace Drupal\group_ai_pm;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of Task entities.
 */
class TaskListBuilder extends EntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a TaskListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_type, $storage);
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter')
    );
  }

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
    $header['project'] = [
      'data' => $this->t('Project'),
      'field' => 'project',
      'specifier' => 'project',
    ];
    $header['status'] = [
      'data' => $this->t('Status'),
      'field' => 'status',
      'specifier' => 'status',
    ];
    $header['priority'] = [
      'data' => $this->t('Priority'),
      'field' => 'priority',
      'specifier' => 'priority',
    ];
    $header['due_date'] = [
      'data' => $this->t('Due Date'),
      'field' => 'due_date',
      'specifier' => 'due_date',
    ];
    $header['assignee'] = [
      'data' => $this->t('Assignee'),
      'field' => 'assignee',
      'specifier' => 'assignee',
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['title'] = $entity->getTitle();
    if ($entity->get('project')->target_id) {
      $project = $entity->get('project')->entity;
      $row['project'] = $project ? $project->getTitle() : '';
    }
    else {
      $row['project'] = '';
    }
    $row['status'] = $entity->getStatus();
    $row['priority'] = $entity->getPriority();
    $due_date = $entity->getDueDate();
    $row['due_date'] = $due_date ? $this->dateFormatter->format(strtotime($due_date), 'short') : '';
    if ($entity->get('assignee')->target_id) {
      $assignee = $entity->get('assignee')->entity;
      $row['assignee'] = $assignee ? $assignee->getDisplayName() : '';
    }
    else {
      $row['assignee'] = '';
    }
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

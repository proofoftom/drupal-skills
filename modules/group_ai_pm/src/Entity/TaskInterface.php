<?php

namespace Drupal\group_ai_pm\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface for Task entities.
 */
interface TaskInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the task title.
   *
   * @return string
   *   The task title.
   */
  public function getTitle();

  /**
   * Sets the task title.
   *
   * @param string $title
   *   The task title.
   *
   * @return \Drupal\group_ai_pm\Entity\TaskInterface
   *   The called entity.
   */
  public function setTitle($title);

  /**
   * Gets the task description.
   *
   * @return string
   *   The task description.
   */
  public function getDescription();

  /**
   * Sets the task description.
   *
   * @param string $description
   *   The task description.
   *
   * @return \Drupal\group_ai_pm\Entity\TaskInterface
   *   The called entity.
   */
  public function setDescription($description);

  /**
   * Gets the task status.
   *
   * @return string
   *   The task status.
   */
  public function getStatus();

  /**
   * Sets the task status.
   *
   * @param string $status
   *   The task status.
   *
   * @return \Drupal\group_ai_pm\Entity\TaskInterface
   *   The called entity.
   */
  public function setStatus($status);

  /**
   * Gets the task priority.
   *
   * @return string
   *   The task priority.
   */
  public function getPriority();

  /**
   * Sets the task priority.
   *
   * @param string $priority
   *   The task priority.
   *
   * @return \Drupal\group_ai_pm\Entity\TaskInterface
   *   The called entity.
   */
  public function setPriority($priority);

  /**
   * Gets the task due date.
   *
   * @return string
   *   The task due date.
   */
  public function getDueDate();

  /**
   * Sets the task due date.
   *
   * @param string $due_date
   *   The task due date.
   *
   * @return \Drupal\group_ai_pm\Entity\TaskInterface
   *   The called entity.
   */
  public function setDueDate($due_date);

  /**
   * Gets the task creation timestamp.
   *
   * @return int
   *   The task creation timestamp.
   */
  public function getCreatedTime();

  /**
   * Sets the task creation timestamp.
   *
   * @param int $timestamp
   *   The task creation timestamp.
   *
   * @return \Drupal\group_ai_pm\Entity\TaskInterface
   *   The called entity.
   */
  public function setCreatedTime($timestamp);

}

<?php

namespace Drupal\group_ai_pm\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Interface for Project entities.
 */
interface ProjectInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the project title.
   *
   * @return string
   *   The project title.
   */
  public function getTitle();

  /**
   * Sets the project title.
   *
   * @param string $title
   *   The project title.
   *
   * @return \Drupal\group_ai_pm\Entity\ProjectInterface
   *   The called entity.
   */
  public function setTitle($title);

  /**
   * Gets the project description.
   *
   * @return string
   *   The project description.
   */
  public function getDescription();

  /**
   * Sets the project description.
   *
   * @param string $description
   *   The project description.
   *
   * @return \Drupal\group_ai_pm\Entity\ProjectInterface
   *   The called entity.
   */
  public function setDescription($description);

  /**
   * Gets the project status.
   *
   * @return string
   *   The project status.
   */
  public function getStatus();

  /**
   * Sets the project status.
   *
   * @param string $status
   *   The project status.
   *
   * @return \Drupal\group_ai_pm\Entity\ProjectInterface
   *   The called entity.
   */
  public function setStatus($status);

  /**
   * Gets the project creation timestamp.
   *
   * @return int
   *   The project creation timestamp.
   */
  public function getCreatedTime();

  /**
   * Sets the project creation timestamp.
   *
   * @param int $timestamp
   *   The project creation timestamp.
   *
   * @return \Drupal\group_ai_pm\Entity\ProjectInterface
   *   The called entity.
   */
  public function setCreatedTime($timestamp);

}

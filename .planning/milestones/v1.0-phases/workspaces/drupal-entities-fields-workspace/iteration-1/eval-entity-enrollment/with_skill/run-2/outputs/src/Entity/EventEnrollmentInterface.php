<?php

namespace Drupal\event_enrollment\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for the EventEnrollment entity.
 */
interface EventEnrollmentInterface extends ContentEntityInterface {

  /**
   * Gets the enrollment status.
   *
   * @return string
   *   The enrollment status (pending, confirmed, cancelled).
   */
  public function getStatus();

  /**
   * Sets the enrollment status.
   *
   * @param string $status
   *   The enrollment status.
   *
   * @return $this
   */
  public function setStatus($status);

  /**
   * Gets the enrollment creation date.
   *
   * @return int
   *   The creation timestamp.
   */
  public function getCreatedTime();

  /**
   * Sets the enrollment creation date.
   *
   * @param int $timestamp
   *   The creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

}

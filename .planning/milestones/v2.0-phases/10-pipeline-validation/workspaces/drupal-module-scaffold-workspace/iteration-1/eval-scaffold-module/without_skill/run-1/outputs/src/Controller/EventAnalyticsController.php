<?php

namespace Drupal\event_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\event_analytics\AttendanceTracker;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for Event Analytics routes.
 */
class EventAnalyticsController extends ControllerBase {

  /**
   * The attendance tracker service.
   *
   * @var \Drupal\event_analytics\AttendanceTracker
   */
  protected AttendanceTracker $attendanceTracker;

  /**
   * Constructs an EventAnalyticsController object.
   *
   * @param \Drupal\event_analytics\AttendanceTracker $attendance_tracker
   *   The attendance tracker service.
   */
  public function __construct(AttendanceTracker $attendance_tracker) {
    $this->attendanceTracker = $attendance_tracker;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('event_analytics.attendance_tracker')
    );
  }

}

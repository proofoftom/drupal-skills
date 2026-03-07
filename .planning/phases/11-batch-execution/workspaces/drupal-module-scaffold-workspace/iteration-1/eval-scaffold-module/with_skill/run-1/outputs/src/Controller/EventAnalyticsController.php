<?php

namespace Drupal\event_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\event_analytics\Service\AttendanceManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the Event Analytics dashboard.
 */
class EventAnalyticsController extends ControllerBase {

  /**
   * The attendance manager service.
   *
   * @var \Drupal\event_analytics\Service\AttendanceManager
   */
  protected $attendanceManager;

  /**
   * Constructs an EventAnalyticsController object.
   *
   * @param \Drupal\event_analytics\Service\AttendanceManager $attendance_manager
   *   The attendance manager service.
   */
  public function __construct(AttendanceManager $attendance_manager) {
    $this->attendanceManager = $attendance_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_analytics.attendance_manager')
    );
  }

  /**
   * Returns the Event Analytics dashboard page.
   *
   * @return array
   *   A render array.
   */
  public function dashboard() {
    return [
      '#markup' => $this->t('Event Analytics Dashboard'),
    ];
  }

}

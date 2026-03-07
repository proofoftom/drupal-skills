<?php

namespace Drupal\user_activity\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;
use Drupal\user_activity\Service\UserActivityService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for user activity routes.
 */
class UserActivityController extends ControllerBase {

  /**
   * The user activity service.
   *
   * @var \Drupal\user_activity\Service\UserActivityService
   */
  protected $userActivityService;

  /**
   * Constructs a UserActivityController object.
   *
   * @param \Drupal\user_activity\Service\UserActivityService $user_activity_service
   *   The user activity service.
   */
  public function __construct(UserActivityService $user_activity_service) {
    $this->userActivityService = $user_activity_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user_activity.activity_service')
    );
  }

  /**
   * Returns the activity page render array.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   *
   * @return array
   *   A render array.
   */
  public function activityPage(UserInterface $user): array {
    $data = $this->userActivityService->getActivityData($user);

    $last_access_display = $data['last_access']
      ? $this->t('@date', ['@date' => date('Y-m-d H:i:s', $data['last_access'])])
      : $this->t('Never');

    return [
      '#theme' => 'item_list',
      '#title' => $this->t('Activity for @name', ['@name' => $user->getDisplayName()]),
      '#items' => [
        $this->t('Nodes authored: @count', ['@count' => $data['node_count']]),
        $this->t('Last access: @time', ['@time' => $last_access_display]),
      ],
    ];
  }

  /**
   * Returns the activity data as a JSON response.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the activity data.
   */
  public function activityJson(UserInterface $user): JsonResponse {
    $data = $this->userActivityService->getActivityData($user);
    $data['uid'] = (int) $user->id();
    $data['display_name'] = $user->getDisplayName();

    return new JsonResponse($data);
  }

  /**
   * Returns the dynamic page title for the activity page.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The translated page title.
   */
  public function title(UserInterface $user) {
    return $this->t('@name Activity', ['@name' => $user->getDisplayName()]);
  }

}

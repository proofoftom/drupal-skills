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
  protected UserActivityService $userActivityService;

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
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('user_activity.activity_service')
    );
  }

  /**
   * Renders the activity summary page for a user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity from the route parameter.
   *
   * @return array
   *   A render array.
   */
  public function activityPage(UserInterface $user): array {
    $data = $this->userActivityService->getActivityData($user);

    $last_access_formatted = $data['last_access']
      ? $this->dateFormatter()->format($data['last_access'], 'medium')
      : $this->t('Never');

    return [
      '#theme' => 'item_list',
      '#title' => $this->t('Activity for @name', ['@name' => $user->getDisplayName()]),
      '#items' => [
        $this->t('Nodes authored: @count', ['@count' => $data['node_count']]),
        $this->t('Last access: @time', ['@time' => $last_access_formatted]),
      ],
      '#cache' => [
        'tags' => $user->getCacheTags(),
        'contexts' => ['user.permissions'],
      ],
    ];
  }

  /**
   * Returns the dynamic page title for the activity page.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity from the route parameter.
   *
   * @return string
   *   The page title.
   */
  public function activityPageTitle(UserInterface $user): string {
    return $this->t('@name\'s Activity', ['@name' => $user->getDisplayName()]);
  }

  /**
   * Returns user activity data as a JSON response.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity from the route parameter.
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
   * Returns the date formatter service.
   *
   * @return \Drupal\Core\Datetime\DateFormatterInterface
   *   The date formatter.
   */
  protected function dateFormatter() {
    return $this->container()->get('date.formatter');
  }

  /**
   * Returns the service container.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface
   *   The service container.
   */
  protected function container(): ContainerInterface {
    return \Drupal::getContainer();
  }

}

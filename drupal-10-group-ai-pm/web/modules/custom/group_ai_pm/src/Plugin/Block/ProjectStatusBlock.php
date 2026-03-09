<?php

namespace Drupal\group_ai_pm\Plugin\Block;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays a count of projects grouped by status.
 *
 * @Block(
 *   id = "project_status_block",
 *   admin_label = @Translation("Project Status Block"),
 *   category = @Translation("Group AI Project Management")
 * )
 */
class ProjectStatusBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $storage = $this->entityTypeManager->getStorage('project');
    $statuses = ['planning', 'active', 'review', 'completed'];
    $build = [
      '#type' => 'table',
      '#header' => ['Status', 'Count'],
      '#rows' => [],
      '#cache' => [
        'tags' => ['project_list'],
        'contexts' => ['user.permissions'],
        'max-age' => Cache::PERMANENT,
      ],
    ];

    foreach ($statuses as $status) {
      $query = $storage->getQuery();
      $query->accessCheck(FALSE);
      $count = $query
        ->condition('status', $status)
        ->count()
        ->execute();
      $build['#rows'][] = [
        'data' => [
          ucfirst($status),
          (int) $count,
        ],
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access group_ai_pm dashboard');
  }

}

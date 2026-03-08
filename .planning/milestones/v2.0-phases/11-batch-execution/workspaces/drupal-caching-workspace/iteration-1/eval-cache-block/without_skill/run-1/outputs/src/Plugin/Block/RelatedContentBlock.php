<?php

declare(strict_types=1);

namespace Drupal\related_content_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Related Content block.
 *
 * @Block(
 *   id = "related_content_block",
 *   admin_label = @Translation("Related Content"),
 *   category = @Translation("Custom"),
 * )
 */
class RelatedContentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * Constructs a RelatedContentBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user account.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    RouteMatchInterface $route_match,
    AccountInterface $current_user,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $node_ids = $this->getRelatedNodeIds();

    if (empty($node_ids)) {
      return [
        '#cache' => [
          'contexts' => $this->getCacheContexts(),
          'tags' => $this->getCacheTags(),
        ],
      ];
    }

    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');
    $nodes = $node_storage->loadMultiple($node_ids);

    $items = [];
    $cache_tags = $this->getCacheTags();

    foreach ($nodes as $node) {
      $items[] = [
        '#type' => 'link',
        '#title' => $node->label(),
        '#url' => $node->toUrl(),
      ];
      // Merge in the cache tags for each loaded node so the block
      // invalidates whenever any of the displayed nodes change.
      $cache_tags = Cache::mergeTags($cache_tags, $node->getCacheTags());
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#title' => $this->t('Related Content'),
      '#cache' => [
        'contexts' => $this->getCacheContexts(),
        'tags' => $cache_tags,
      ],
    ];
  }

  /**
   * Returns node IDs considered related to the current page and user.
   *
   * The selection logic intentionally depends on both the current route and
   * the current user so that the cache contexts declared in getCacheContexts()
   * match the actual dependencies of this method.
   *
   * @return int[]
   *   An array of node IDs.
   */
  protected function getRelatedNodeIds(): array {
    $node_storage = $this->entityTypeManager->getStorage('node');

    // Determine a base set of published nodes.  In a real implementation this
    // would use field values or taxonomy terms from the current node; here we
    // keep the query simple so the block works on any Drupal install.
    $query = $node_storage->getQuery()
      ->condition('status', 1)
      ->accessCheck(TRUE)
      ->range(0, 5)
      ->sort('created', 'DESC');

    // Vary by current route: if we are on a node page, exclude that node.
    $current_node = $this->routeMatch->getParameter('node');
    if ($current_node) {
      $current_nid = is_object($current_node) ? $current_node->id() : $current_node;
      $query->condition('nid', $current_nid, '<>');
    }

    // Vary by current user: restrict to content the user has authored so that
    // two different users see different lists (demonstrating user cache context).
    if (!$this->currentUser->isAnonymous()) {
      $query->condition('uid', $this->currentUser->id());
    }

    return array_values($query->execute());
  }

  /**
   * {@inheritdoc}
   *
   * Declare cache contexts so Drupal stores a separate cache entry for each
   * combination of URL path and authenticated user.
   */
  public function getCacheContexts(): array {
    return Cache::mergeContexts(parent::getCacheContexts(), [
      // One cache entry per URL so content stays relevant to the current page.
      'url',
      // One cache entry per user so each person sees their own related content.
      'user',
    ]);
  }

  /**
   * {@inheritdoc}
   *
   * The base tag 'node_list' ensures the block is invalidated whenever any
   * node is created, updated, or deleted — covering cases where newly
   * published content should appear in the list.
   */
  public function getCacheTags(): array {
    return Cache::mergeTags(parent::getCacheTags(), ['node_list']);
  }

}

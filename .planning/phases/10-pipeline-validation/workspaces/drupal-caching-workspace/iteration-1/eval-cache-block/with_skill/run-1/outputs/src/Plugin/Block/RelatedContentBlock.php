<?php

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
 *   category = @Translation("Custom")
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
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
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
  public function build() {
    $node_ids = $this->getRelatedNodeIds();

    if (empty($node_ids)) {
      return [
        '#markup' => $this->t('No related content found.'),
        '#cache' => [
          'tags' => ['node_list'],
          'contexts' => ['route', 'user'],
          'max-age' => Cache::PERMANENT,
        ],
      ];
    }

    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($node_ids);

    // Collect cache tags from every loaded node so the block invalidates
    // whenever any of the displayed nodes is updated or deleted. Also include
    // node_list so a new node being created can also appear in the listing.
    $tags = Cache::mergeTags(['node_list'], parent::getCacheTags());
    foreach ($nodes as $node) {
      // $node->getCacheTags() returns ['node:ID'] -- the node:ID pattern.
      $tags = Cache::mergeTags($tags, $node->getCacheTags());
    }

    $items = [];
    foreach ($nodes as $node) {
      $items[] = [
        '#type' => 'link',
        '#title' => $node->label(),
        '#url' => $node->toUrl(),
      ];
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#title' => $this->t('Related Content'),
      '#cache' => [
        // Tags: per-node tags (node:ID) so the block invalidates when any
        // displayed node changes, plus node_list for when nodes are added/deleted.
        'tags' => $tags,
        // Contexts: route so the block varies by which page it appears on;
        // user so the block varies per user (access-checked query).
        'contexts' => ['route', 'user'],
        // Permanent: tag-based invalidation handles freshness -- never max-age 0.
        'max-age' => Cache::PERMANENT,
      ],
    ];
  }

  /**
   * Returns node IDs for related content based on the current page and user.
   *
   * Resolves related nodes by inspecting the current route for a node context
   * and querying published nodes of the same type that the current user has
   * access to view.
   *
   * @return int[]
   *   An array of node IDs.
   */
  protected function getRelatedNodeIds(): array {
    // Attempt to read the node from the current route (e.g. node.view).
    $current_node = $this->routeMatch->getParameter('node');

    if (!$current_node) {
      return [];
    }

    $node_type = $current_node->getType();
    $current_nid = (int) $current_node->id();

    // Query for published nodes of the same type, excluding the current node,
    // limited to those the current user can view.
    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery()
      ->condition('type', $node_type)
      ->condition('status', 1)
      ->condition('nid', $current_nid, '!=')
      ->accessCheck(TRUE)
      ->range(0, 5)
      ->sort('created', 'DESC');

    return array_values(array_map('intval', $query->execute()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // Vary by route (different related content per page) and by individual
    // user (access-checked query may return different results per user).
    // Always merge with parent to preserve block-configuration contexts.
    return Cache::mergeContexts(parent::getCacheContexts(), ['route', 'user']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // node_list ensures the block is invalidated when any node is created,
    // updated, or deleted -- covering both the displayed nodes and the
    // underlying query result set. Per-node tags are added in build() once
    // the nodes are loaded.
    return Cache::mergeTags(parent::getCacheTags(), ['node_list']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Cache permanently; tag-based invalidation handles freshness.
    // Never use max-age 0, which would bubble up and disable page-level caching.
    return Cache::PERMANENT;
  }

}

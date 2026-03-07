<?php

namespace Drupal\related_content_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Related Content' block.
 *
 * Displays a list of related content nodes based on the current page and
 * current user. Cache metadata is declared so the block invalidates when
 * the displayed nodes change, varies by URL path, and varies per user.
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
   *   The plugin ID for the plugin instance.
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
  public function build(): array {
    // Determine the current node from the route, if any.
    $current_node = $this->routeMatch->getParameter('node');
    $current_nid = $current_node?->id();

    // Load related nodes. In a real implementation this would query for nodes
    // related to the current page and personalized for the current user.
    // Here we load a small set of published nodes, excluding the current page.
    $node_storage = $this->entityTypeManager->getStorage('node');

    $query = $node_storage->getQuery()
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->range(0, 5)
      ->accessCheck(TRUE);

    // Exclude the currently viewed node so related content differs per page.
    if ($current_nid) {
      $query->condition('nid', $current_nid, '<>');
    }

    // Vary results by user: authenticated users see their own authored content
    // promoted first (simulating personalization).
    if ($this->currentUser->isAuthenticated()) {
      $query->condition('uid', $this->currentUser->id());
    }

    $nids = $query->execute();

    if (empty($nids)) {
      return [
        '#markup' => $this->t('No related content found.'),
        '#cache' => [
          // Still declare cache metadata even for the empty state so Drupal
          // knows when to invalidate this cached empty response.
          'tags' => ['node_list'],
          'contexts' => ['url.path', 'user'],
          'max-age' => Cache::PERMANENT,
        ],
      ];
    }

    /** @var \Drupal\node\NodeInterface[] $nodes */
    $nodes = $node_storage->loadMultiple($nids);

    // Build the list items and accumulate cache tags from each loaded node.
    $items = [];
    $node_tags = [];
    foreach ($nodes as $node) {
      $items[] = [
        '#type' => 'link',
        '#title' => $node->label(),
        '#url' => $node->toUrl(),
      ];
      // Collect per-entity tags so the block invalidates when any displayed
      // node is updated or deleted.
      $node_tags = Cache::mergeTags($node_tags, $node->getCacheTags());
    }

    // Also add node_list so the block invalidates when any node is created,
    // which could change which related content appears.
    $node_tags = Cache::mergeTags($node_tags, ['node_list']);

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#title' => $this->t('Related Content'),
      '#cache' => [
        'tags' => $node_tags,
        'contexts' => ['url.path', 'user'],
        'max-age' => Cache::PERMANENT,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Vary by URL path (different page = different related content) and by user
   * (personalized results per user). Always merge with parent so block
   * configuration contexts are preserved.
   */
  public function getCacheContexts(): array {
    return Cache::mergeContexts(parent::getCacheContexts(), ['url.path', 'user']);
  }

  /**
   * {@inheritdoc}
   *
   * Include node_list so the block is invalidated when any node is added,
   * updated, or deleted. Always merge with parent so block configuration tags
   * are preserved.
   */
  public function getCacheTags(): array {
    return Cache::mergeTags(parent::getCacheTags(), ['node_list']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    return Cache::PERMANENT;
  }

}

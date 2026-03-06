<?php

namespace Drupal\related_content_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block that displays nodes related to the current page,
 * filtered by the current user's group membership.
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
  protected $entityTypeManager;

  /**
   * The node IDs of displayed related nodes.
   *
   * @var int[]
   */
  protected $relatedNodeIds = [];

  /**
   * Constructs a RelatedContentBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
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
    $node_storage = $this->entityTypeManager->getStorage('node');

    // Load related nodes (in a real implementation, this would query by group
    // membership and current route context; here we load a sample set).
    $this->relatedNodeIds = $node_storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', 1)
      ->range(0, 5)
      ->execute();

    $nodes = $node_storage->loadMultiple($this->relatedNodeIds);

    $items = [];
    $tags = [];

    foreach ($nodes as $node) {
      $items[] = [
        '#type' => 'link',
        '#title' => $node->label(),
        '#url' => $node->toUrl(),
      ];
      // Collect cache tags from each displayed node so invalidation is
      // automatic when any of these nodes are updated.
      $tags = Cache::mergeTags($tags, $node->getCacheTags());
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#title' => $this->t('Related Content'),
      '#cache' => [
        'tags' => $tags,
        'contexts' => $this->getCacheContexts(),
        'max-age' => Cache::PERMANENT,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // Vary by route so different pages display their own related content,
    // and by user so group-membership filtering works per individual user.
    return Cache::mergeContexts(parent::getCacheContexts(), ['route', 'user']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Merge parent block config tags with node_list so the block is
    // invalidated whenever any node is created, updated, or deleted.
    return Cache::mergeTags(parent::getCacheTags(), ['node_list']);
  }

}

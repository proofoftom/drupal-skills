<?php

declare(strict_types=1);

namespace Drupal\related_content_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Related Content block.
 */
#[Block(
  id: 'related_content_block',
  admin_label: new TranslatableMarkup('Related Content'),
  category: new TranslatableMarkup('Custom'),
)]
class RelatedContentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a RelatedContentBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user account.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly RouteMatchInterface $routeMatch,
    protected readonly AccountInterface $currentUser,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
   * Loads related node IDs based on the current page and user.
   *
   * In a real implementation this would contain logic such as taxonomy matching,
   * user-preference lookups, or view-based queries. Here we return a small set
   * of published nodes so the caching behaviour can be verified end-to-end.
   *
   * @return int[]
   *   An array of node IDs to display.
   */
  protected function loadRelatedNids(): array {
    $currentNode = $this->routeMatch->getParameter('node');
    $currentNid  = ($currentNode instanceof NodeInterface) ? $currentNode->id() : 0;

    $storage = $this->entityTypeManager->getStorage('node');

    $query = $storage->getQuery()
      ->condition('status', NodeInterface::PUBLISHED)
      ->accessCheck(TRUE)
      ->range(0, 5)
      ->sort('created', 'DESC');

    // Exclude the current node so we never link back to the same page.
    if ($currentNid) {
      $query->condition('nid', $currentNid, '<>');
    }

    return array_values($query->execute());
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $nids = $this->loadRelatedNids();

    if (empty($nids)) {
      return [
        '#cache' => [
          'contexts' => $this->getCacheContexts(),
          'tags'     => $this->getCacheTags(),
          'max-age'  => $this->getCacheMaxAge(),
        ],
      ];
    }

    /** @var \Drupal\node\NodeInterface[] $nodes */
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

    $items = [];
    foreach ($nodes as $node) {
      $items[] = [
        '#type'  => 'link',
        '#title' => $node->label(),
        '#url'   => $node->toUrl(),
      ];
    }

    return [
      '#theme'     => 'item_list',
      '#items'     => $items,
      '#title'     => $this->t('Related Content'),
      '#list_type' => 'ul',
      '#cache'     => [
        'contexts' => $this->getCacheContexts(),
        'tags'     => $this->getCacheTags(),
        'max-age'  => $this->getCacheMaxAge(),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Vary by:
   *   - url.path — different pages show different related content.
   *   - user     — personalised results per user account.
   */
  public function getCacheContexts(): array {
    return Cache::mergeContexts(parent::getCacheContexts(), [
      'url.path',
      'user',
    ]);
  }

  /**
   * {@inheritdoc}
   *
   * Invalidate when any node changes so stale related-content links are never
   * served. The 'node_list' tag covers node creation/deletion; individual
   * node:<id> tags cover updates to currently-displayed nodes.
   */
  public function getCacheTags(): array {
    $nids = $this->loadRelatedNids();

    $nodeTags = [];
    foreach ($nids as $nid) {
      $nodeTags[] = 'node:' . $nid;
    }

    return Cache::mergeTags(
      parent::getCacheTags(),
      ['node_list'],
      $nodeTags,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    // Tags and contexts control freshness; no arbitrary TTL needed.
    return Cache::PERMANENT;
  }

}

<?php

declare(strict_types=1);

namespace Drupal\featured_resources\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a Featured Resources block.
 *
 * @Block(
 *   id = "featured_resources_block",
 *   admin_label = @Translation("Featured Resources"),
 *   category = @Translation("Custom"),
 * )
 */
class FeaturedResourcesBlock extends BlockBase {

  /**
   * Hardcoded sample learning resources.
   */
  private const RESOURCES = [
    [
      'title' => 'Introduction to Community Management',
      'url' => 'https://example.com/community-management',
      'description' => 'Learn the fundamentals of building and managing thriving online communities.',
      'type' => 'Article',
    ],
    [
      'title' => 'Knowledge Graph Fundamentals',
      'url' => 'https://example.com/knowledge-graphs',
      'description' => 'Explore how knowledge graphs connect information across your organization.',
      'type' => 'Video',
    ],
    [
      'title' => 'Open Social Platform Guide',
      'url' => 'https://example.com/open-social-guide',
      'description' => 'A comprehensive guide to using the Open Social platform for collaborative learning.',
      'type' => 'Guide',
    ],
    [
      'title' => 'AI-Assisted Learning Strategies',
      'url' => 'https://example.com/ai-learning',
      'description' => 'Discover how AI can enhance your learning experience and knowledge discovery.',
      'type' => 'Article',
    ],
    [
      'title' => 'Collaborative Learning Best Practices',
      'url' => 'https://example.com/collaborative-learning',
      'description' => 'Tips and strategies for effective collaborative learning in digital spaces.',
      'type' => 'Guide',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#theme' => 'featured_resources_list',
      '#heading' => $this->t('Featured Resources'),
      '#resources' => self::RESOURCES,
      '#attached' => [
        'library' => ['featured_resources/featured_resources_styles'],
      ],
    ];
  }

}

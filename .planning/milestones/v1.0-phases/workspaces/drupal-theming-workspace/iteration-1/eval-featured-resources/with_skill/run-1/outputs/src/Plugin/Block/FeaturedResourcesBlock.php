<?php

namespace Drupal\featured_resources\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a Featured Resources block.
 */
#[Block(
  id: 'featured_resources_block',
  admin_label: new TranslatableMarkup('Featured Resources'),
  category: new TranslatableMarkup('Custom'),
)]
class FeaturedResourcesBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $items = [
      [
        'title' => $this->t('Introduction to Open Social'),
        'url' => 'https://www.drupal.org/project/social',
      ],
      [
        'title' => $this->t('Drupal Theming Guide'),
        'url' => 'https://www.drupal.org/docs/theming-drupal',
      ],
      [
        'title' => $this->t('Twig Templates in Drupal'),
        'url' => 'https://www.drupal.org/docs/theming-drupal/twig-in-drupal',
      ],
      [
        'title' => $this->t('Drupal 11 Release Notes'),
        'url' => 'https://www.drupal.org/project/drupal/releases/11.0.0',
      ],
    ];

    return [
      '#theme' => 'featured_resources_list',
      '#items' => $items,
      '#heading' => $this->t('Featured Learning Resources'),
      '#attached' => [
        'library' => ['featured_resources/featured_resources'],
      ],
    ];
  }

}

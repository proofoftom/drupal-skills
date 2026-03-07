<?php

declare(strict_types=1);

namespace Drupal\content_recommendations\Plugin\Block;

use Drupal\content_recommendations\RecommendationService;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block displaying recommended content items.
 */
#[Block(
  id: "content_recommendations_block",
  admin_label: new TranslatableMarkup("Content Recommendations"),
  category: new TranslatableMarkup("Custom"),
)]
class ContentRecommendationsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected RecommendationService $recommendationService,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('content_recommendations.recommendation_service'),
    );
  }

  public function defaultConfiguration(): array {
    return [
      'items_to_show' => 5,
    ];
  }

  public function blockForm($form, FormStateInterface $form_state): array {
    $form['items_to_show'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of items to show'),
      '#default_value' => $this->configuration['items_to_show'],
      '#min' => 1,
      '#max' => 20,
      '#required' => TRUE,
    ];

    return $form;
  }

  public function blockValidate($form, FormStateInterface $form_state): void {
    $count = (int) $form_state->getValue('items_to_show');
    if ($count < 1 || $count > 20) {
      $form_state->setErrorByName('items_to_show', $this->t('Number of items must be between 1 and 20.'));
    }
  }

  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['items_to_show'] = (int) $form_state->getValue('items_to_show');
  }

  public function build(): array {
    $limit = (int) $this->configuration['items_to_show'];
    $nodes = $this->recommendationService->getRecommendations($limit);

    if (empty($nodes)) {
      return [
        '#markup' => $this->t('No recommendations available.'),
        '#cache' => [
          'tags' => ['node_list'],
          'contexts' => ['user'],
        ],
      ];
    }

    $items = [];
    foreach ($nodes as $node) {
      $items[] = $node->toLink()->toRenderable();
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#title' => $this->t('Recommended Content'),
      '#cache' => [
        'tags' => ['node_list'],
        'contexts' => ['user'],
        'max-age' => 3600,
      ],
    ];
  }

}

<?php

namespace Drupal\content_recommendations_baseline\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\content_recommendations_baseline\RecommendationsService;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[Block(
  id: "content_recommendations_baseline_block",
  admin_label: new TranslatableMarkup("Content Recommendations"),
  category: new TranslatableMarkup("Custom"),
)]
class ContentRecommendationsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected RecommendationsService $recommendationsService,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('content_recommendations_baseline.recommendations_service'),
    );
  }

  public function defaultConfiguration() {
    return [
      'items_to_show' => 5,
    ];
  }

  public function blockForm($form, FormStateInterface $form_state) {
    $form['items_to_show'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of items to show'),
      '#default_value' => $this->configuration['items_to_show'],
      '#min' => 1,
      '#max' => 50,
      '#required' => TRUE,
    ];

    return $form;
  }

  public function blockValidate($form, FormStateInterface $form_state) {
    $value = $form_state->getValue('items_to_show');
    if ($value < 1 || $value > 50) {
      $form_state->setErrorByName('items_to_show', $this->t('Items to show must be between 1 and 50.'));
    }
  }

  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['items_to_show'] = (int) $form_state->getValue('items_to_show');
  }

  public function build() {
    $limit = (int) $this->configuration['items_to_show'];
    $items = $this->recommendationsService->getRecommendations($limit);

    if (empty($items)) {
      return [
        '#markup' => $this->t('No recommendations available.'),
      ];
    }

    return [
      '#theme' => 'item_list',
      '#title' => $this->t('Recommended Content'),
      '#items' => array_values($items),
      '#cache' => [
        'tags' => ['node_list'],
        'contexts' => ['user'],
      ],
    ];
  }

}

<?php

namespace Drupal\resource_directory\Plugin\views\filter;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Views filter plugin that provides a dropdown to filter resources by category.
 *
 * @ViewsFilter("resource_category")
 */
class ResourceCategoryFilter extends FilterPluginBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary(): string {
    return !empty($this->value) ? (string) $this->value : $this->t('any');
  }

  /**
   * {@inheritdoc}
   */
  public function canExpose(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(array &$form, FormStateInterface $form_state): void {
    $categories = $this->database->select('resource_links', 'rl')
      ->fields('rl', ['category'])
      ->distinct()
      ->orderBy('category')
      ->execute()
      ->fetchCol();

    $options = ['' => $this->t('- Any -')];
    foreach ($categories as $category) {
      $options[$category] = $category;
    }

    $form['value'] = [
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#options' => $options,
      '#default_value' => $this->value ?? '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    if (empty($this->value)) {
      return;
    }
    $this->ensureMyTable();
    $this->query->addWhere(
      $this->options['group'],
      "$this->tableAlias.$this->realField",
      $this->value,
    );
  }

}

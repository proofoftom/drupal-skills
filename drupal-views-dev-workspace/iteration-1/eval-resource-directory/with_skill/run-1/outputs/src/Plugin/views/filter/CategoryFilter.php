<?php

namespace Drupal\resource_directory\Plugin\views\filter;

use Drupal\Core\Database\Connection;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter by resource category using a dropdown of existing values.
 */
#[ViewsFilter("resource_directory_category")]
class CategoryFilter extends InOperator {

  protected Connection $database;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }

  public function init(ViewExecutable $view, DisplayPluginBase $display, ?array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = $this->t('Categories');
    $this->definition['options callback'] = [$this, 'getCategories'];
  }

  /**
   * Returns distinct category values from the resource_links table.
   */
  public function getCategories(): array {
    $categories = $this->database
      ->query("SELECT DISTINCT [category] FROM {resource_links} WHERE [category] != '' ORDER BY [category]")
      ->fetchCol();
    return array_combine($categories, $categories);
  }

}

<?php

namespace Drupal\group_ai_pm_ai\Plugin\AiFunctionCall;

use Drupal\ai_agents\Plugin\AiFunctionCall\AiFunctionCallBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * AI function to query projects with optional status filter.
 *
 * @AiFunctionCall(
 *   id = "query_projects_tool",
 *   label = @Translation("Query Projects"),
 *   description = @Translation("Query projects with optional status filter")
 * )
 */
class QueryProjectsTool extends AiFunctionCallBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
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
  public function execute() {
    $status = $this->functionCall->getArgumentsObject()->status ?? NULL;

    $storage = $this->entityTypeManager->getStorage('project');
    $query = $storage->getQuery();

    if ($status) {
      $query->condition('status', $status);
    }

    $ids = $query->execute();
    $projects = $storage->loadMultiple($ids);

    $results = [];
    foreach ($projects as $project) {
      $results[] = [
        'id' => $project->id(),
        'title' => $project->getTitle(),
        'status' => $project->getStatus(),
        'description' => $project->getDescription(),
      ];
    }

    return json_encode($results);
  }

  /**
   * {@inheritdoc}
   */
  public function getArguments() {
    return [
      'status' => [
        'type' => 'string',
        'description' => 'Filter by project status (optional)',
        'enum' => ['planning', 'active', 'review', 'completed'],
        'required' => FALSE,
      ],
    ];
  }

}

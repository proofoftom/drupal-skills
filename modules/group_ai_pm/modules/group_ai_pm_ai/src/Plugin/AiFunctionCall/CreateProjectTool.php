<?php

namespace Drupal\group_ai_pm_ai\Plugin\AiFunctionCall;

use Drupal\ai_agents\Plugin\AiFunctionCall\AiFunctionCallBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * AI function to create a project entity.
 *
 * @AiFunctionCall(
 *   id = "create_project_tool",
 *   label = @Translation("Create Project"),
 *   description = @Translation("Create a new project with title, description, and status")
 * )
 */
class CreateProjectTool extends AiFunctionCallBase implements ContainerFactoryPluginInterface {

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
    $title = $this->functionCall->getArgumentsObject()->title ?? '';
    $description = $this->functionCall->getArgumentsObject()->description ?? '';
    $status = $this->functionCall->getArgumentsObject()->status ?? 'planning';

    $storage = $this->entityTypeManager->getStorage('project');
    $project = $storage->create([
      'title' => $title,
      'description' => $description,
      'status' => $status,
    ]);
    $project->save();

    return "Project created successfully with ID: {$project->id()}";
  }

  /**
   * {@inheritdoc}
   */
  public function getArguments() {
    return [
      'title' => [
        'type' => 'string',
        'description' => 'The project title',
        'required' => TRUE,
      ],
      'description' => [
        'type' => 'string',
        'description' => 'The project description',
        'required' => FALSE,
      ],
      'status' => [
        'type' => 'string',
        'description' => 'The project status (planning, active, review, completed)',
        'enum' => ['planning', 'active', 'review', 'completed'],
        'required' => FALSE,
      ],
    ];
  }

}

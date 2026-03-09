<?php

namespace Drupal\group_ai_pm_ai\Plugin\AiFunctionCall;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ai\Attribute\FunctionCall;
use Drupal\ai\Base\FunctionCallBase;
use Drupal\ai\Service\FunctionCalling\ExecutableFunctionCallInterface;
use Drupal\group_ai_pm\Service\AiTaskService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * AI function to create a task from natural language.
 */
#[FunctionCall(
  id: 'group_ai_pm_create_task',
  function_name: 'create_task_from_natural_language',
  name: 'Create Task from Natural Language',
  description: 'Create a new project management task from a natural language description',
  group: 'task_tools',
  context_definitions: [
    'text' => new ContextDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Natural Language Description'),
      description: new TranslatableMarkup('A natural language description of the task to create'),
      required: TRUE,
    ),
    'project_id' => new ContextDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Project ID'),
      description: new TranslatableMarkup('The ID of the project to create the task in'),
      required: TRUE,
    ),
  ],
)]
class CreateTaskTool extends FunctionCallBase implements ExecutableFunctionCallInterface {

  /**
   * The AI task service.
   *
   * @var \Drupal\group_ai_pm\Service\AiTaskService
   */
  protected AiTaskService $aiTaskService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->aiTaskService = $container->get('group_ai_pm.ai_task');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $text = $this->getContextValue('text');
    $project_id = (int) $this->getContextValue('project_id');

    if (empty($text)) {
      $this->setOutput('Error: text parameter is required.');
      return;
    }

    if (empty($project_id)) {
      $this->setOutput('Error: project_id parameter is required.');
      return;
    }

    if (!$this->aiTaskService->isAvailable()) {
      $this->setOutput('Error: AI service is not available or not configured.');
      return;
    }

    try {
      $parsed = $this->aiTaskService->parseNaturalLanguage($text);

      if (empty($parsed)) {
        $this->setOutput('Error: Failed to parse natural language input.');
        return;
      }

      $task = $this->aiTaskService->createTaskFromParsed($parsed, $project_id);

      if ($task === NULL) {
        $this->setOutput('Error: Failed to create task from parsed input.');
        return;
      }

      $this->setOutput("Task created successfully with ID: {$task->id()}, Title: {$task->getTitle()}");
    }
    catch (\Exception $e) {
      $this->setOutput('Error creating task: ' . $e->getMessage());
    }
  }

}

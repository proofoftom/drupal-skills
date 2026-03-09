<?php

namespace Drupal\group_ai_pm_ai\Plugin\AiFunctionCall;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ai\Attribute\FunctionCall;
use Drupal\ai\Base\FunctionCallBase;
use Drupal\ai\Service\FunctionCalling\ExecutableFunctionCallInterface;
use Drupal\ai\Service\FunctionCalling\FunctionCallInterface;
use Drupal\group_ai_pm\Service\AiTaskService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * AI function to create a task from natural language.
 */
#[FunctionCall(
  id: 'group_ai_pm_ai:create_task_tool',
  function_name: 'create_task',
  name: new TranslatableMarkup('Create Task'),
  description: new TranslatableMarkup('Create a new task from natural language description within a project'),
  group: 'group_ai_pm_ai',
  context_definitions: [
    'text' => new ContextDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Task description'),
      description: new TranslatableMarkup('Natural language description of the task to create'),
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
   * @var \Drupal\group_ai_pm\Service\AiTaskService|null
   */
  protected ?AiTaskService $aiTaskService = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): FunctionCallInterface|static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->aiTaskService = $container->get('group_ai_pm.ai_task_service');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    if ($this->aiTaskService === NULL) {
      $this->setOutput('Error: AI task service is not available.');
      return;
    }

    $text = $this->getContextValue('text');
    $project_id = (int) $this->getContextValue('project_id');

    if (empty($text) || $project_id <= 0) {
      $this->setOutput('Error: Text and project_id are required.');
      return;
    }

    try {
      // Check if AI is available.
      if (!$this->aiTaskService->isAvailable()) {
        $this->setOutput('Error: AI service is not configured.');
        return;
      }

      // Parse natural language into task fields.
      $parsed = $this->aiTaskService->parseNaturalLanguage($text);

      // Create the task.
      $task = $this->aiTaskService->createTaskFromParsed($parsed, $project_id);

      $this->setOutput("Task created successfully with ID: {$task->id()} - Title: {$task->getTitle()}");
    }
    catch (\Exception $e) {
      $this->setOutput('Error: ' . $e->getMessage());
    }
  }

}

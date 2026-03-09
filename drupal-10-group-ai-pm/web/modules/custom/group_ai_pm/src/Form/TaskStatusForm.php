<?php

namespace Drupal\group_ai_pm\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for managing task statuses with AJAX updates.
 */
class TaskStatusForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a TaskStatusForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, DateFormatterInterface $date_formatter) {
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'group_ai_pm_task_status_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#title'] = $this->t('Task Overview');

    // Query tasks with pagination.
    $task_ids = $this->entityTypeManager
      ->getStorage('task')
      ->getQuery()
      ->accessCheck(TRUE)
      ->sort('created', 'DESC')
      ->pager(20)
      ->execute();

    $form['tasks_table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Title'),
        $this->t('Project'),
        $this->t('Status'),
        $this->t('Priority'),
        $this->t('Due Date'),
        $this->t('Assignee'),
      ],
      '#rows' => [],
      '#empty' => $this->t('No tasks found.'),
      '#attributes' => [
        'class' => ['gapm-task-status-table'],
      ],
    ];

    if (!empty($task_ids)) {
      $tasks = $this->entityTypeManager
        ->getStorage('task')
        ->loadMultiple($task_ids);

      foreach ($tasks as $task) {
        $project_id = $task->get('project_id')->value;
        $project = $this->entityTypeManager
          ->getStorage('project')
          ->load($project_id);
        $project_title = $project ? $project->getTitle() : $this->t('Unknown');

        $assignee = $task->getOwner();
        $assignee_name = $assignee ? $assignee->getDisplayName() : $this->t('Unassigned');

        $due_date = $task->getDueDate();
        $due_date_display = $due_date
          ? $this->dateFormatter->format(strtotime($due_date), 'short')
          : $this->t('No date');

        $row_id = 'task-row-' . $task->id();

        $form['tasks_table'][$row_id] = [
          '#attributes' => ['id' => $row_id],
          'title' => [
            '#plain_text' => $task->getTitle(),
          ],
          'project' => [
            '#plain_text' => $project_title,
          ],
          'status' => [
            '#type' => 'select',
            '#options' => [
              'todo' => $this->t('To Do'),
              'in_progress' => $this->t('In Progress'),
              'review' => $this->t('Review'),
              'done' => $this->t('Done'),
            ],
            '#default_value' => $task->getStatus() ?? 'todo',
            '#ajax' => [
              'callback' => [$this, 'updateTaskStatus'],
              'wrapper' => $row_id,
              'event' => 'change',
              'progress' => [
                'type' => 'throbber',
                'message' => $this->t('Updating...'),
              ],
            ],
            '#attributes' => [
              'data-task-id' => $task->id(),
              'class' => ['gapm-task-status-select'],
            ],
          ],
          'priority' => [
            '#plain_text' => ucfirst($task->getPriority() ?? 'normal'),
          ],
          'due_date' => [
            '#plain_text' => $due_date_display,
          ],
          'assignee' => [
            '#plain_text' => $assignee_name,
          ],
        ];
      }
    }

    // Add pager.
    $form['pager'] = [
      '#type' => 'pager',
    ];

    return $form;
  }

  /**
   * AJAX callback to update task status.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The updated row.
   */
  public function updateTaskStatus(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    if (empty($trigger['#attributes']['data-task-id'])) {
      return [];
    }

    $task_id = $trigger['#attributes']['data-task-id'];
    $new_status = $form_state->getValue('tasks_table')[$task_id]['status'] ?? NULL;

    if (!$new_status) {
      return [];
    }

    // Load task and update status.
    $task = $this->entityTypeManager
      ->getStorage('task')
      ->load($task_id);

    if (!$task) {
      return [];
    }

    $task->setStatus($new_status);
    $task->save();

    // Get the table row element to return.
    $row_key = 'task-row-' . $task_id;
    if (isset($form['tasks_table'][$row_key])) {
      return $form['tasks_table'][$row_key];
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No-op: status changes happen via AJAX callbacks.
  }

}

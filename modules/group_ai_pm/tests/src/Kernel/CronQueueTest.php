<?php

namespace Drupal\Tests\group_ai_pm\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests cron and queue processing for overdue task notifications.
 *
 * @group group_ai_pm
 */
class CronQueueTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'group_ai_pm',
    'system',
    'user',
    'field',
    'datetime',
    'text',
    'options',
    'views',
    'group',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('project');
    $this->installEntitySchema('task');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
  }

  /**
   * Tests that cron creates queue items for overdue tasks.
   */
  public function testCronQueuesOverdueTasks() {
    // Create a project.
    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Test Project',
      'uid' => 1,
    ]);
    $project->save();

    // Create a task with a due date in the past.
    $past_date = date('Y-m-d', strtotime('-1 day'));
    $task = \Drupal::entityTypeManager()->getStorage('task')->create([
      'title' => 'Overdue Task',
      'project' => $project->id(),
      'uid' => 1,
      'status' => 'todo',
      'due_date' => $past_date,
    ]);
    $task->save();

    // Verify queue is empty before cron.
    $queue = \Drupal::queue('group_ai_pm_overdue_notifications');
    $this->assertEquals(0, $queue->numberOfItems());

    // Run cron.
    \Drupal::service('cron')->run();

    // Verify queue now has the overdue task item.
    $this->assertGreaterThan(0, $queue->numberOfItems());
    $item = $queue->claimItem();
    $this->assertNotNull($item);
    $this->assertEquals($task->id(), $item->data->task_id);
    $this->assertEquals('Overdue Task', $item->data->title);
    $this->assertEquals($past_date, $item->data->due_date);
  }

  /**
   * Tests that cron does not queue tasks with done status.
   */
  public function testCronIgnoresDoneTasks() {
    // Create a project.
    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Test Project',
      'uid' => 1,
    ]);
    $project->save();

    // Create a done task with a past due date.
    $past_date = date('Y-m-d', strtotime('-1 day'));
    $task = \Drupal::entityTypeManager()->getStorage('task')->create([
      'title' => 'Done Task',
      'project' => $project->id(),
      'uid' => 1,
      'status' => 'done',
      'due_date' => $past_date,
    ]);
    $task->save();

    // Run cron.
    \Drupal::service('cron')->run();

    // Verify queue is still empty (done tasks are not queued).
    $queue = \Drupal::queue('group_ai_pm_overdue_notifications');
    $this->assertEquals(0, $queue->numberOfItems());
  }

  /**
   * Tests that cron does not queue tasks with future due dates.
   */
  public function testCronIgnoresFutureTasks() {
    // Create a project.
    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Test Project',
      'uid' => 1,
    ]);
    $project->save();

    // Create a task with a future due date.
    $future_date = date('Y-m-d', strtotime('+1 day'));
    $task = \Drupal::entityTypeManager()->getStorage('task')->create([
      'title' => 'Future Task',
      'project' => $project->id(),
      'uid' => 1,
      'status' => 'todo',
      'due_date' => $future_date,
    ]);
    $task->save();

    // Run cron.
    \Drupal::service('cron')->run();

    // Verify queue is empty (future tasks are not queued).
    $queue = \Drupal::queue('group_ai_pm_overdue_notifications');
    $this->assertEquals(0, $queue->numberOfItems());
  }

  /**
   * Tests that cron uses lock to prevent concurrent execution.
   */
  public function testCronUsesLock() {
    // Create a project and overdue task.
    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Test Project',
      'uid' => 1,
    ]);
    $project->save();

    $past_date = date('Y-m-d', strtotime('-1 day'));
    $task = \Drupal::entityTypeManager()->getStorage('task')->create([
      'title' => 'Overdue Task',
      'project' => $project->id(),
      'uid' => 1,
      'status' => 'todo',
      'due_date' => $past_date,
    ]);
    $task->save();

    // Acquire the lock manually to simulate concurrent execution.
    $lock = \Drupal::service('lock');
    $lock->acquire('group_ai_pm_overdue_check', 300);

    // Run cron - should not queue anything because lock is held.
    \Drupal::service('cron')->run();

    // Verify queue is empty (cron was skipped due to lock).
    $queue = \Drupal::queue('group_ai_pm_overdue_notifications');
    $this->assertEquals(0, $queue->numberOfItems());

    // Release the lock.
    $lock->release('group_ai_pm_overdue_check');

    // Run cron again - should queue the overdue task.
    \Drupal::service('cron')->run();
    $this->assertGreaterThan(0, $queue->numberOfItems());
  }

  /**
   * Tests the OverdueNotificationWorker processes items.
   */
  public function testOverdueNotificationWorkerProcessItem() {
    $plugin_manager = \Drupal::service('plugin.manager.queue_worker');
    $worker = $plugin_manager->createInstance('group_ai_pm_overdue_notifications');

    // Create test data.
    $data = new \stdClass();
    $data->task_id = 1;
    $data->title = 'Test Task';
    $data->due_date = '2024-01-01';

    // Process the item - should not throw exception.
    try {
      $worker->processItem($data);
    }
    catch (\Exception $e) {
      $this->fail('Worker threw unexpected exception: ' . $e->getMessage());
    }
  }

  /**
   * Tests OverdueNotificationWorker handles missing task_id.
   */
  public function testOverdueNotificationWorkerHandlesMissingTaskId() {
    $plugin_manager = \Drupal::service('plugin.manager.queue_worker');
    $worker = $plugin_manager->createInstance('group_ai_pm_overdue_notifications');

    // Create test data without task_id.
    $data = new \stdClass();
    $data->title = 'Test Task';
    $data->due_date = '2024-01-01';

    // Process the item - should log error and not throw.
    try {
      $worker->processItem($data);
    }
    catch (\Exception $e) {
      $this->fail('Worker threw unexpected exception: ' . $e->getMessage());
    }
  }

  /**
   * Tests multiple overdue tasks are queued.
   */
  public function testCronQueuesMultipleOverdueTasks() {
    // Create a project.
    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Test Project',
      'uid' => 1,
    ]);
    $project->save();

    // Create multiple overdue tasks.
    $past_date = date('Y-m-d', strtotime('-1 day'));
    $task_ids = [];
    for ($i = 0; $i < 3; $i++) {
      $task = \Drupal::entityTypeManager()->getStorage('task')->create([
        'title' => 'Overdue Task ' . $i,
        'project' => $project->id(),
        'uid' => 1,
        'status' => 'todo',
        'due_date' => $past_date,
      ]);
      $task->save();
      $task_ids[] = $task->id();
    }

    // Run cron.
    \Drupal::service('cron')->run();

    // Verify all overdue tasks were queued.
    $queue = \Drupal::queue('group_ai_pm_overdue_notifications');
    $this->assertEquals(3, $queue->numberOfItems());

    // Verify queue items have correct task IDs.
    $queued_task_ids = [];
    while ($item = $queue->claimItem()) {
      $queued_task_ids[] = $item->data->task_id;
      $queue->deleteItem($item);
    }

    sort($queued_task_ids);
    sort($task_ids);
    $this->assertEquals($task_ids, $queued_task_ids);
  }

}

<?php

namespace Drupal\Tests\group_ai_pm\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Project and Task entity CRUD operations and field defaults.
 *
 * @group group_ai_pm
 */
class EntityCrudTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['group_ai_pm', 'system', 'user', 'field', 'datetime', 'views', 'group'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('project');
    $this->installEntitySchema('task');
    $this->installEntitySchema('user');
  }

  /**
   * Tests Project entity creation and field defaults.
   */
  public function testProjectEntityCrud() {
    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Test Project',
      'description' => 'A test project',
      'uid' => 1,
    ]);
    $project->save();

    $this->assertNotNull($project->id());
    $this->assertEquals('Test Project', $project->getTitle());
    $this->assertEquals('A test project', $project->getDescription());
    $this->assertEquals('planning', $project->getStatus(), 'Project status should default to planning');
    $this->assertEquals(1, $project->get('uid')->target_id);

    // Load the project.
    $loaded_project = \Drupal::entityTypeManager()->getStorage('project')->load($project->id());
    $this->assertNotNull($loaded_project);
    $this->assertEquals('Test Project', $loaded_project->getTitle());
    $this->assertEquals('planning', $loaded_project->getStatus());

    // Delete the project.
    $project_id = $project->id();
    $project->delete();

    $deleted_project = \Drupal::entityTypeManager()->getStorage('project')->load($project_id);
    $this->assertNull($deleted_project);
  }

  /**
   * Tests Task entity creation and field defaults.
   */
  public function testTaskEntityCrud() {
    // Create a parent project first.
    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Parent Project',
      'uid' => 1,
    ]);
    $project->save();

    // Create a task linked to the project.
    $task = \Drupal::entityTypeManager()->getStorage('task')->create([
      'title' => 'Test Task',
      'description' => 'A test task',
      'project' => $project->id(),
      'uid' => 1,
    ]);
    $task->save();

    $this->assertNotNull($task->id());
    $this->assertEquals('Test Task', $task->getTitle());
    $this->assertEquals('A test task', $task->getDescription());
    $this->assertEquals('todo', $task->getStatus(), 'Task status should default to todo');
    $this->assertEquals('medium', $task->getPriority(), 'Task priority should default to medium');
    $this->assertEquals($project->id(), $task->get('project')->target_id, 'Task should reference parent project');

    // Load the task.
    $loaded_task = \Drupal::entityTypeManager()->getStorage('task')->load($task->id());
    $this->assertNotNull($loaded_task);
    $this->assertEquals('Test Task', $loaded_task->getTitle());
    $this->assertEquals('todo', $loaded_task->getStatus());
    $this->assertEquals('medium', $loaded_task->getPriority());
    $this->assertEquals($project->id(), $loaded_task->get('project')->target_id);

    // Delete the task.
    $task_id = $task->id();
    $task->delete();

    $deleted_task = \Drupal::entityTypeManager()->getStorage('task')->load($task_id);
    $this->assertNull($deleted_task);
  }

  /**
   * Tests Task entity reference to Project entity.
   */
  public function testTaskProjectReference() {
    // Create multiple projects.
    $project1 = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Project One',
      'uid' => 1,
    ]);
    $project1->save();

    $project2 = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Project Two',
      'uid' => 1,
    ]);
    $project2->save();

    // Create a task for project1.
    $task1 = \Drupal::entityTypeManager()->getStorage('task')->create([
      'title' => 'Task 1',
      'project' => $project1->id(),
      'uid' => 1,
    ]);
    $task1->save();

    // Create a task for project2.
    $task2 = \Drupal::entityTypeManager()->getStorage('task')->create([
      'title' => 'Task 2',
      'project' => $project2->id(),
      'uid' => 1,
    ]);
    $task2->save();

    // Verify task1 references project1.
    $loaded_task1 = \Drupal::entityTypeManager()->getStorage('task')->load($task1->id());
    $this->assertEquals($project1->id(), $loaded_task1->get('project')->target_id);

    // Verify task2 references project2.
    $loaded_task2 = \Drupal::entityTypeManager()->getStorage('task')->load($task2->id());
    $this->assertEquals($project2->id(), $loaded_task2->get('project')->target_id);

    // Load referenced entities.
    $referenced_project1 = $loaded_task1->get('project')->entity;
    $this->assertNotNull($referenced_project1);
    $this->assertEquals('Project One', $referenced_project1->getTitle());

    $referenced_project2 = $loaded_task2->get('project')->entity;
    $this->assertNotNull($referenced_project2);
    $this->assertEquals('Project Two', $referenced_project2->getTitle());
  }

  /**
   * Tests entity field updates.
   */
  public function testEntityFieldUpdates() {
    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Original Title',
      'status' => 'planning',
      'uid' => 1,
    ]);
    $project->save();

    // Update fields.
    $project->setTitle('Updated Title');
    $project->setStatus('active');
    $project->save();

    // Verify updates persisted.
    $loaded_project = \Drupal::entityTypeManager()->getStorage('project')->load($project->id());
    $this->assertEquals('Updated Title', $loaded_project->getTitle());
    $this->assertEquals('active', $loaded_project->getStatus());
  }

}

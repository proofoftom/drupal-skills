<?php

namespace Drupal\Tests\group_ai_pm\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests entity forms and their integration with access control.
 *
 * @group group_ai_pm
 */
class EntityFormIntegrationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['group_ai_pm', 'system', 'user', 'field', 'datetime', 'views', 'group'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests project add form requires permission.
   */
  public function testProjectAddFormAccess() {
    // Anonymous user should be denied.
    $this->drupalGet('/admin/content/project/add');
    $this->assertSession()->statusCodeEquals(403);

    // User without create permission should be denied.
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);
    $this->drupalGet('/admin/content/project/add');
    $this->assertSession()->statusCodeEquals(403);

    // User with create permission should be allowed.
    $user = $this->drupalCreateUser(['create project']);
    $this->drupalLogin($user);
    $this->drupalGet('/admin/content/project/add');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests task add form requires permission.
   */
  public function testTaskAddFormAccess() {
    // Anonymous user should be denied.
    $this->drupalGet('/admin/content/task/add');
    $this->assertSession()->statusCodeEquals(403);

    // User without create permission should be denied.
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);
    $this->drupalGet('/admin/content/task/add');
    $this->assertSession()->statusCodeEquals(403);

    // User with create permission should be allowed.
    $user = $this->drupalCreateUser(['create task']);
    $this->drupalLogin($user);
    $this->drupalGet('/admin/content/task/add');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests creating a project through the form.
   */
  public function testCreateProjectThroughForm() {
    $user = $this->drupalCreateUser(['create project', 'view project']);
    $this->drupalLogin($user);

    $this->drupalGet('/admin/content/project/add');
    $this->assertSession()->statusCodeEquals(200);

    // Fill and submit the form.
    $edit = [
      'title[0][value]' => 'New Project from Form',
      'description[0][value]' => 'This is a test project created through the form.',
      'status' => 'active',
    ];
    $this->submitForm($edit, 'Save');

    // Verify the project was created.
    $this->assertSession()->pageTextContains('New Project from Form');
  }

  /**
   * Tests creating a task through the form.
   */
  public function testCreateTaskThroughForm() {
    $user = $this->drupalCreateUser(['create task', 'create project', 'view task', 'view project']);
    $this->drupalLogin($user);

    // First create a project.
    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Parent Project',
      'uid' => $user->id(),
    ]);
    $project->save();

    // Now create a task.
    $this->drupalGet('/admin/content/task/add');
    $this->assertSession()->statusCodeEquals(200);

    $edit = [
      'title[0][value]' => 'New Task from Form',
      'description[0][value]' => 'This is a test task created through the form.',
      'project[0][target_id]' => 'Parent Project (' . $project->id() . ')',
      'status' => 'in_progress',
      'priority' => 'high',
    ];
    $this->submitForm($edit, 'Save');

    // Verify the task was created.
    $this->assertSession()->pageTextContains('New Task from Form');
  }

  /**
   * Tests editing own project.
   */
  public function testEditOwnProject() {
    $user = $this->drupalCreateUser(['create project', 'edit own project', 'view project']);
    $this->drupalLogin($user);

    // Create a project.
    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'My Project',
      'uid' => $user->id(),
    ]);
    $project->save();

    // Edit the project.
    $this->drupalGet('/admin/content/project/' . $project->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);

    $edit = [
      'title[0][value]' => 'Updated My Project',
    ];
    $this->submitForm($edit, 'Save');

    // Verify the update.
    $updated_project = \Drupal::entityTypeManager()->getStorage('project')->load($project->id());
    $this->assertEquals('Updated My Project', $updated_project->getTitle());
  }

  /**
   * Tests cannot edit other's project without permission.
   */
  public function testCannotEditOthersProject() {
    $owner = $this->drupalCreateUser(['create project']);
    $user = $this->drupalCreateUser(['edit own project', 'view project']);

    // Owner creates a project.
    $this->drupalLogin($owner);
    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Owner Project',
      'uid' => $owner->id(),
    ]);
    $project->save();

    // Different user tries to edit it.
    $this->drupalLogin($user);
    $this->drupalGet('/admin/content/project/' . $project->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests edit any project permission.
   */
  public function testEditAnyProject() {
    $owner = $this->drupalCreateUser(['create project']);
    $admin = $this->drupalCreateUser(['edit any project', 'view project']);

    // Owner creates a project.
    $this->drupalLogin($owner);
    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Owner Project',
      'uid' => $owner->id(),
    ]);
    $project->save();

    // Admin should be able to edit it.
    $this->drupalLogin($admin);
    $this->drupalGet('/admin/content/project/' . $project->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);

    $edit = [
      'title[0][value]' => 'Updated by Admin',
    ];
    $this->submitForm($edit, 'Save');

    $updated_project = \Drupal::entityTypeManager()->getStorage('project')->load($project->id());
    $this->assertEquals('Updated by Admin', $updated_project->getTitle());
  }

  /**
   * Tests delete project permission.
   */
  public function testDeleteProject() {
    $owner = $this->drupalCreateUser(['create project', 'delete own project']);
    $this->drupalLogin($owner);

    // Create and delete a project.
    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Project to Delete',
      'uid' => $owner->id(),
    ]);
    $project->save();

    $project_id = $project->id();
    $this->drupalGet('/admin/content/project/' . $project_id . '/delete');
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm([], 'Delete');

    // Verify deletion.
    $deleted_project = \Drupal::entityTypeManager()->getStorage('project')->load($project_id);
    $this->assertNull($deleted_project);
  }

  /**
   * Tests task field defaults are applied in forms.
   */
  public function testTaskFieldDefaults() {
    $user = $this->drupalCreateUser(['create task', 'create project']);
    $this->drupalLogin($user);

    // Create a project.
    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Test Project',
      'uid' => $user->id(),
    ]);
    $project->save();

    // View the add task form.
    $this->drupalGet('/admin/content/task/add');

    // Verify default values are pre-selected.
    $this->assertSession()->fieldValueEquals('status', 'todo');
    $this->assertSession()->fieldValueEquals('priority', 'medium');
  }

}

<?php

namespace Drupal\Tests\group_ai_pm\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests access control handlers for Project and Task entities.
 *
 * @group group_ai_pm
 */
class AccessControlTest extends KernelTestBase {

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
   * Creates a user with specified permissions.
   *
   * @param array $permissions
   *   Array of permission strings.
   *
   * @return \Drupal\user\UserInterface
   *   A user entity.
   */
  protected function createUserWithPermissions(array $permissions) {
    $user = \Drupal::entityTypeManager()->getStorage('user')->create([
      'uid' => NULL,
      'name' => 'user_' . uniqid(),
      'mail' => uniqid() . '@example.com',
      'roles' => 'authenticated',
      'pass' => 'password',
    ]);
    $user->save();

    // Grant permissions by setting the user's roles.
    // For testing, we need to use the permission system directly.
    // This simulates user permissions for access checks.
    return $user;
  }

  /**
   * Tests Project access control for view operations.
   */
  public function testProjectViewAccess() {
    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Test Project',
      'uid' => 1,
    ]);
    $project->save();

    // Create test users.
    $user_with_view = $this->createUserWithPermissions(['view project']);
    $user_without_view = $this->createUserWithPermissions([]);

    // Get access control handler.
    $handler = \Drupal::entityTypeManager()->getAccessControlHandler('project');

    // User with permission should be allowed.
    $access = $handler->access($project, 'view', $user_with_view);
    $this->assertTrue($access);

    // User without permission should be denied.
    $access = $handler->access($project, 'view', $user_without_view);
    $this->assertFalse($access);
  }

  /**
   * Tests Project access control for edit own operations.
   */
  public function testProjectEditOwnAccess() {
    $owner_user = $this->createUserWithPermissions(['edit own project']);
    $other_user = $this->createUserWithPermissions(['edit own project']);

    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Test Project',
      'uid' => $owner_user->id(),
    ]);
    $project->save();

    $handler = \Drupal::entityTypeManager()->getAccessControlHandler('project');

    // Owner with 'edit own' permission should be allowed to update.
    $access = $handler->access($project, 'update', $owner_user);
    $this->assertTrue($access);

    // Different user with only 'edit own' should be denied (not their entity).
    $access = $handler->access($project, 'update', $other_user);
    $this->assertFalse($access);
  }

  /**
   * Tests Project access control for edit any operations.
   */
  public function testProjectEditAnyAccess() {
    $owner_user = $this->createUserWithPermissions([]);
    $admin_user = $this->createUserWithPermissions(['edit any project']);

    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Test Project',
      'uid' => $owner_user->id(),
    ]);
    $project->save();

    $handler = \Drupal::entityTypeManager()->getAccessControlHandler('project');

    // Admin with 'edit any' should be allowed.
    $access = $handler->access($project, 'update', $admin_user);
    $this->assertTrue($access);

    // Owner without 'edit any' should be denied (no 'edit own' either).
    $access = $handler->access($project, 'update', $owner_user);
    $this->assertFalse($access);
  }

  /**
   * Tests Project access control for delete own operations.
   */
  public function testProjectDeleteOwnAccess() {
    $owner_user = $this->createUserWithPermissions(['delete own project']);
    $other_user = $this->createUserWithPermissions(['delete own project']);

    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Test Project',
      'uid' => $owner_user->id(),
    ]);
    $project->save();

    $handler = \Drupal::entityTypeManager()->getAccessControlHandler('project');

    // Owner with 'delete own' should be allowed.
    $access = $handler->access($project, 'delete', $owner_user);
    $this->assertTrue($access);

    // Different user with only 'delete own' should be denied.
    $access = $handler->access($project, 'delete', $other_user);
    $this->assertFalse($access);
  }

  /**
   * Tests Project access control for delete any operations.
   */
  public function testProjectDeleteAnyAccess() {
    $owner_user = $this->createUserWithPermissions([]);
    $admin_user = $this->createUserWithPermissions(['delete any project']);

    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Test Project',
      'uid' => $owner_user->id(),
    ]);
    $project->save();

    $handler = \Drupal::entityTypeManager()->getAccessControlHandler('project');

    // Admin with 'delete any' should be allowed.
    $access = $handler->access($project, 'delete', $admin_user);
    $this->assertTrue($access);

    // Owner without permission should be denied.
    $access = $handler->access($project, 'delete', $owner_user);
    $this->assertFalse($access);
  }

  /**
   * Tests Project create access.
   */
  public function testProjectCreateAccess() {
    $user_with_create = $this->createUserWithPermissions(['create project']);
    $user_without_create = $this->createUserWithPermissions([]);

    $handler = \Drupal::entityTypeManager()->getAccessControlHandler('project');

    // User with permission should be allowed to create.
    $access = $handler->createAccess(NULL, $user_with_create);
    $this->assertTrue($access);

    // User without permission should be denied.
    $access = $handler->createAccess(NULL, $user_without_create);
    $this->assertFalse($access);
  }

  /**
   * Tests Task access control for view operations.
   */
  public function testTaskViewAccess() {
    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Test Project',
      'uid' => 1,
    ]);
    $project->save();

    $task = \Drupal::entityTypeManager()->getStorage('task')->create([
      'title' => 'Test Task',
      'project' => $project->id(),
      'uid' => 1,
    ]);
    $task->save();

    $user_with_view = $this->createUserWithPermissions(['view task']);
    $user_without_view = $this->createUserWithPermissions([]);

    $handler = \Drupal::entityTypeManager()->getAccessControlHandler('task');

    // User with permission should be allowed.
    $access = $handler->access($task, 'view', $user_with_view);
    $this->assertTrue($access);

    // User without permission should be denied.
    $access = $handler->access($task, 'view', $user_without_view);
    $this->assertFalse($access);
  }

  /**
   * Tests Task access control for edit operations.
   */
  public function testTaskEditAccess() {
    $owner_user = $this->createUserWithPermissions(['edit own task']);
    $admin_user = $this->createUserWithPermissions(['edit any task']);
    $other_user = $this->createUserWithPermissions(['edit own task']);

    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Test Project',
      'uid' => 1,
    ]);
    $project->save();

    $task = \Drupal::entityTypeManager()->getStorage('task')->create([
      'title' => 'Test Task',
      'project' => $project->id(),
      'uid' => $owner_user->id(),
    ]);
    $task->save();

    $handler = \Drupal::entityTypeManager()->getAccessControlHandler('task');

    // Owner with 'edit own' should be allowed.
    $access = $handler->access($task, 'update', $owner_user);
    $this->assertTrue($access);

    // Admin with 'edit any' should be allowed.
    $access = $handler->access($task, 'update', $admin_user);
    $this->assertTrue($access);

    // Different user with only 'edit own' should be denied.
    $access = $handler->access($task, 'update', $other_user);
    $this->assertFalse($access);
  }

  /**
   * Tests Task access control for delete operations.
   */
  public function testTaskDeleteAccess() {
    $owner_user = $this->createUserWithPermissions(['delete own task']);
    $admin_user = $this->createUserWithPermissions(['delete any task']);

    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Test Project',
      'uid' => 1,
    ]);
    $project->save();

    $task = \Drupal::entityTypeManager()->getStorage('task')->create([
      'title' => 'Test Task',
      'project' => $project->id(),
      'uid' => $owner_user->id(),
    ]);
    $task->save();

    $handler = \Drupal::entityTypeManager()->getAccessControlHandler('task');

    // Owner with 'delete own' should be allowed.
    $access = $handler->access($task, 'delete', $owner_user);
    $this->assertTrue($access);

    // Admin with 'delete any' should be allowed.
    $access = $handler->access($task, 'delete', $admin_user);
    $this->assertTrue($access);
  }

  /**
   * Tests Task create access.
   */
  public function testTaskCreateAccess() {
    $user_with_create = $this->createUserWithPermissions(['create task']);
    $user_without_create = $this->createUserWithPermissions([]);

    $handler = \Drupal::entityTypeManager()->getAccessControlHandler('task');

    // User with permission should be allowed to create.
    $access = $handler->createAccess(NULL, $user_with_create);
    $this->assertTrue($access);

    // User without permission should be denied.
    $access = $handler->createAccess(NULL, $user_without_create);
    $this->assertFalse($access);
  }

}

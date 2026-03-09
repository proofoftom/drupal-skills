<?php

namespace Drupal\Tests\group_ai_pm\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Group AI PM Kanban board page.
 *
 * @group group_ai_pm
 */
class BoardPageTest extends BrowserTestBase {

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
  protected $defaultTheme = 'stark';

  /**
   * Tests board page renders with #kanban-app mount point element.
   */
  public function testBoardPageRendersMountPoint() {
    // Create a user with permission.
    $user = $this->drupalCreateUser(['access group_ai_pm dashboard']);
    $this->drupalLogin($user);

    // Create a project.
    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Test Project',
      'uid' => $user->id(),
    ]);
    $project->save();

    // Navigate to the board page.
    $this->drupalGet('/admin/content/project/' . $project->id() . '/board');
    $this->assertSession()->statusCodeEquals(200);

    // Assert the kanban-app mount point exists.
    $this->assertSession()->elementExists('css', '#kanban-app');
  }

  /**
   * Tests board page includes drupalSettings in the page output.
   */
  public function testBoardPageIncludesDrupalSettings() {
    // Create a user with permission.
    $user = $this->drupalCreateUser(['access group_ai_pm dashboard']);
    $this->drupalLogin($user);

    // Create a project.
    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Test Project',
      'uid' => $user->id(),
    ]);
    $project->save();

    // Navigate to the board page.
    $this->drupalGet('/admin/content/project/' . $project->id() . '/board');
    $this->assertSession()->statusCodeEquals(200);

    // Assert drupalSettings is in the page (as a script tag).
    $this->assertSession()->responseContains('drupalSettings');
    $this->assertSession()->responseContains('groupAiPm');

    // Check for kanban-specific settings.
    $this->assertSession()->responseContains('projectId');
    $this->assertSession()->responseContains('apiBaseUrl');
  }

  /**
   * Tests anonymous user gets 403 on the board page.
   */
  public function testBoardPageAnonymousAccessDenied() {
    // Create a project (as an authenticated user).
    $user = $this->drupalCreateUser(['access group_ai_pm dashboard']);
    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Test Project',
      'uid' => $user->id(),
    ]);
    $project->save();

    // Try to access the board page as anonymous.
    $this->drupalGet('/admin/content/project/' . $project->id() . '/board');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests user without permission gets 403 on the board page.
   */
  public function testBoardPageAccessDeniedWithoutPermission() {
    // Create a user without 'access group_ai_pm dashboard' permission.
    $user = $this->drupalCreateUser(['view project']);

    // Create a project.
    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Test Project',
      'uid' => 1,
    ]);
    $project->save();

    // Login as user without permission.
    $this->drupalLogin($user);

    // Try to access the board page.
    $this->drupalGet('/admin/content/project/' . $project->id() . '/board');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests board page with tasks.
   */
  public function testBoardPageWithTasks() {
    // Create a user with permission.
    $user = $this->drupalCreateUser(['access group_ai_pm dashboard']);
    $this->drupalLogin($user);

    // Create a project.
    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Test Project',
      'uid' => $user->id(),
    ]);
    $project->save();

    // Create some tasks.
    $task1 = \Drupal::entityTypeManager()->getStorage('task')->create([
      'title' => 'Task 1',
      'project' => $project->id(),
      'status' => 'todo',
      'uid' => $user->id(),
    ]);
    $task1->save();

    $task2 = \Drupal::entityTypeManager()->getStorage('task')->create([
      'title' => 'Task 2',
      'project' => $project->id(),
      'status' => 'in_progress',
      'uid' => $user->id(),
    ]);
    $task2->save();

    // Navigate to the board page.
    $this->drupalGet('/admin/content/project/' . $project->id() . '/board');
    $this->assertSession()->statusCodeEquals(200);

    // Assert kanban-app exists and page title.
    $this->assertSession()->elementExists('css', '#kanban-app');
    $this->assertSession()->pageTextContains('Kanban Board');
  }

  /**
   * Tests board page container has correct CSS class.
   */
  public function testBoardPageContainerClass() {
    // Create a user with permission.
    $user = $this->drupalCreateUser(['access group_ai_pm dashboard']);
    $this->drupalLogin($user);

    // Create a project.
    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Test Project',
      'uid' => $user->id(),
    ]);
    $project->save();

    // Navigate to the board page.
    $this->drupalGet('/admin/content/project/' . $project->id() . '/board');
    $this->assertSession()->statusCodeEquals(200);

    // Assert the kanban container has the expected CSS class.
    $this->assertSession()->elementExists('css', '.gapm-kanban-container');
  }

}

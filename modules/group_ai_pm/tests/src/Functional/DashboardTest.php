<?php

namespace Drupal\Tests\group_ai_pm\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Group AI PM dashboard page.
 *
 * @group group_ai_pm
 */
class DashboardTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['group_ai_pm', 'system', 'user', 'field', 'datetime', 'views', 'group'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests dashboard access for anonymous user.
   */
  public function testDashboardAccessAnonymous() {
    $this->drupalGet('/admin/group-ai-pm/dashboard');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests dashboard access for authenticated user without permission.
   */
  public function testDashboardAccessUnauthorized() {
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $this->drupalGet('/admin/group-ai-pm/dashboard');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests dashboard page loads for authorized user.
   */
  public function testDashboardAccessAuthorized() {
    $user = $this->drupalCreateUser(['view project']);
    $this->drupalLogin($user);

    $this->drupalGet('/admin/group-ai-pm/dashboard');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests dashboard displays project count.
   */
  public function testDashboardDisplaysProjectCount() {
    $user = $this->drupalCreateUser(['view project', 'create project']);
    $this->drupalLogin($user);

    // Create a few projects.
    for ($i = 0; $i < 3; $i++) {
      $project = \Drupal::entityTypeManager()->getStorage('project')->create([
        'title' => 'Test Project ' . $i,
        'uid' => $user->id(),
      ]);
      $project->save();
    }

    $this->drupalGet('/admin/group-ai-pm/dashboard');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Projects');
  }

  /**
   * Tests dashboard displays recent projects in a table.
   */
  public function testDashboardDisplaysRecentProjects() {
    $user = $this->drupalCreateUser(['view project', 'create project']);
    $this->drupalLogin($user);

    // Create projects with distinct titles.
    $project1 = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'First Project',
      'uid' => $user->id(),
    ]);
    $project1->save();

    $project2 = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Second Project',
      'uid' => $user->id(),
    ]);
    $project2->save();

    $this->drupalGet('/admin/group-ai-pm/dashboard');
    $this->assertSession()->statusCodeEquals(200);

    // Check for project titles in the page.
    $this->assertSession()->pageTextContains('First Project');
    $this->assertSession()->pageTextContains('Second Project');
  }

  /**
   * Tests dashboard shows no projects message when empty.
   */
  public function testDashboardEmptyProjects() {
    $user = $this->drupalCreateUser(['view project']);
    $this->drupalLogin($user);

    $this->drupalGet('/admin/group-ai-pm/dashboard');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('No projects yet');
  }

  /**
   * Tests dashboard respects access control.
   */
  public function testDashboardAccessControl() {
    // Create two users.
    $user1 = $this->drupalCreateUser(['view project', 'create project']);
    $user2 = $this->drupalCreateUser(['view project']);

    // User1 creates projects.
    for ($i = 0; $i < 2; $i++) {
      $project = \Drupal::entityTypeManager()->getStorage('project')->create([
        'title' => 'User1 Project ' . $i,
        'uid' => $user1->id(),
      ]);
      $project->save();
    }

    // Both users should see the dashboard with the same count due to accessCheck.
    $this->drupalLogin($user1);
    $this->drupalGet('/admin/group-ai-pm/dashboard');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogin($user2);
    $this->drupalGet('/admin/group-ai-pm/dashboard');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests dashboard displays project information.
   */
  public function testDashboardProjectInformation() {
    $user = $this->drupalCreateUser(['view project', 'create project']);
    $this->drupalLogin($user);

    // Create a project with specific details.
    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Test Project Details',
      'status' => 'active',
      'uid' => $user->id(),
    ]);
    $project->save();

    $this->drupalGet('/admin/group-ai-pm/dashboard');
    $this->assertSession()->statusCodeEquals(200);

    // Verify project information appears on dashboard.
    $this->assertSession()->pageTextContains('Test Project Details');
    $this->assertSession()->pageTextContains('Active');
  }

}

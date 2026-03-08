<?php

namespace Drupal\Tests\group_ai_pm\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Group AI PM settings form.
 *
 * @group group_ai_pm
 */
class SettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['group_ai_pm', 'system', 'user', 'field', 'datetime', 'views', 'group'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that anonymous user cannot access settings form.
   */
  public function testSettingsFormAccessDenied() {
    $this->drupalGet('/admin/config/group_ai_pm/settings');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests that admin user can access and submit settings form.
   */
  public function testSettingsFormAccessAllowed() {
    $admin_user = $this->drupalCreateUser(['administer group_ai_pm']);
    $this->drupalLogin($admin_user);

    $this->drupalGet('/admin/config/group_ai_pm/settings');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Default Project Status');
    $this->assertSession()->pageTextContains('Default Task Status');
  }

  /**
   * Tests form submission and configuration save.
   */
  public function testSettingsFormSubmit() {
    $admin_user = $this->drupalCreateUser(['administer group_ai_pm']);
    $this->drupalLogin($admin_user);

    $this->drupalGet('/admin/config/group_ai_pm/settings');

    // Submit form with custom values.
    $edit = [
      'default_project_status' => 'active',
      'default_task_status' => 'in_progress',
      'ai_provider' => 'test_provider',
    ];
    $this->submitForm($edit, 'Save configuration');

    // Verify success message.
    $this->assertSession()->pageTextContains('The configuration options have been saved');

    // Verify configuration was saved.
    $config = \Drupal::config('group_ai_pm.settings');
    $this->assertEquals('active', $config->get('default_project_status'));
    $this->assertEquals('in_progress', $config->get('default_task_status'));
    $this->assertEquals('test_provider', $config->get('ai_provider'));
  }

  /**
   * Tests form loads with saved values.
   */
  public function testSettingsFormLoadsSavedValues() {
    $admin_user = $this->drupalCreateUser(['administer group_ai_pm']);
    $this->drupalLogin($admin_user);

    // Set some configuration.
    \Drupal::configFactory()->getEditable('group_ai_pm.settings')
      ->set('default_project_status', 'review')
      ->set('default_task_status', 'done')
      ->set('ai_provider', 'custom_ai')
      ->save();

    // Load form and verify values are pre-filled.
    $this->drupalGet('/admin/config/group_ai_pm/settings');
    $this->assertSession()->fieldValueEquals('default_project_status', 'review');
    $this->assertSession()->fieldValueEquals('default_task_status', 'done');
    $this->assertSession()->fieldValueEquals('ai_provider', 'custom_ai');
  }

  /**
   * Tests form with default values when no config exists.
   */
  public function testSettingsFormDefaults() {
    $admin_user = $this->drupalCreateUser(['administer group_ai_pm']);
    $this->drupalLogin($admin_user);

    // Ensure config doesn't exist.
    \Drupal::configFactory()->getEditable('group_ai_pm.settings')->delete();

    $this->drupalGet('/admin/config/group_ai_pm/settings');
    $this->assertSession()->fieldValueEquals('default_project_status', 'planning');
    $this->assertSession()->fieldValueEquals('default_task_status', 'todo');
  }

  /**
   * Tests that unauthenticated user cannot access form.
   */
  public function testSettingsFormUnauthenticated() {
    $this->drupalLogout();
    $this->drupalGet('/admin/config/group_ai_pm/settings');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests that authenticated user without permission cannot access form.
   */
  public function testSettingsFormUnauthorized() {
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $this->drupalGet('/admin/config/group_ai_pm/settings');
    $this->assertSession()->statusCodeEquals(403);
  }

}

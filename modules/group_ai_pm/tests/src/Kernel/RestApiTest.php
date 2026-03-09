<?php

namespace Drupal\Tests\group_ai_pm\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the custom REST API endpoints for the Kanban board.
 *
 * @group group_ai_pm
 */
class RestApiTest extends KernelTestBase {

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
    return $user;
  }

  /**
   * Makes an HTTP request to a path using the kernel.
   *
   * @param string $method
   *   The HTTP method (GET, POST, PATCH, DELETE, etc.).
   * @param string $path
   *   The request path.
   * @param \Drupal\user\UserInterface $user
   *   Optional user to set as the current user.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The HTTP response.
   */
  protected function makeRequest($method, $path, $user = NULL) {
    // Set the current user if provided.
    if ($user) {
      \Drupal::service('account_switcher')->switchTo($user);
    }

    // Create and execute the request.
    $request = Request::create($path, $method);
    $response = $this->container->get('http_kernel')->handle($request);

    // Switch back to anonymous.
    if ($user) {
      \Drupal::service('account_switcher')->switchBack();
    }

    return $response;
  }

  /**
   * Tests GET /api/kanban/project/{project_id}?_format=json returns 200 with JSON.
   */
  public function testGetKanbanBoardEndpoint() {
    // Create a user with permission.
    $user = $this->createUserWithPermissions(['access group_ai_pm dashboard']);

    // Create a project.
    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Test Project',
      'uid' => 1,
    ]);
    $project->save();

    // Create some tasks for the project.
    $task1 = \Drupal::entityTypeManager()->getStorage('task')->create([
      'title' => 'Task 1',
      'project' => $project->id(),
      'status' => 'todo',
      'uid' => 1,
    ]);
    $task1->save();

    $task2 = \Drupal::entityTypeManager()->getStorage('task')->create([
      'title' => 'Task 2',
      'project' => $project->id(),
      'status' => 'in_progress',
      'uid' => 1,
    ]);
    $task2->save();

    // Make request to the kanban endpoint.
    $path = '/api/kanban/project/' . $project->id() . '?_format=json';
    $response = $this->makeRequest('GET', $path, $user);

    // Assert 200 response.
    $this->assertEquals(200, $response->getStatusCode());

    // Parse the JSON response.
    $data = json_decode($response->getContent(), TRUE);

    // Assert response structure.
    $this->assertIsArray($data);
    $this->assertArrayHasKey('projectId', $data);
    $this->assertArrayHasKey('columns', $data);
    $this->assertEquals($project->id(), $data['projectId']);

    // Assert columns structure.
    $this->assertIsArray($data['columns']);
    $this->assertArrayHasKey('todo', $data['columns']);
    $this->assertArrayHasKey('in_progress', $data['columns']);
    $this->assertArrayHasKey('review', $data['columns']);
    $this->assertArrayHasKey('done', $data['columns']);

    // Assert tasks are grouped by status.
    $this->assertCount(1, $data['columns']['todo']);
    $this->assertCount(1, $data['columns']['in_progress']);
    $this->assertCount(0, $data['columns']['review']);
    $this->assertCount(0, $data['columns']['done']);

    // Assert task serialization.
    $todo_task = $data['columns']['todo'][0];
    $this->assertArrayHasKey('id', $todo_task);
    $this->assertArrayHasKey('title', $todo_task);
    $this->assertArrayHasKey('status', $todo_task);
    $this->assertEquals('Task 1', $todo_task['title']);
    $this->assertEquals('todo', $todo_task['status']);
  }

  /**
   * Tests GET /api/kanban/project/{project_id}/summary?_format=json returns 200.
   */
  public function testGetProjectSummaryEndpoint() {
    // Create a user with permission.
    $user = $this->createUserWithPermissions(['access group_ai_pm dashboard']);

    // Create a project.
    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Test Project',
      'uid' => 1,
    ]);
    $project->save();

    // Create tasks with different statuses.
    $statuses = ['todo', 'in_progress', 'review', 'done'];
    foreach ($statuses as $status) {
      $task = \Drupal::entityTypeManager()->getStorage('task')->create([
        'title' => 'Task ' . $status,
        'project' => $project->id(),
        'status' => $status,
        'uid' => 1,
      ]);
      $task->save();
    }

    // Make request to the summary endpoint.
    $path = '/api/kanban/project/' . $project->id() . '/summary?_format=json';
    $response = $this->makeRequest('GET', $path, $user);

    // Assert 200 response.
    $this->assertEquals(200, $response->getStatusCode());

    // Parse the JSON response.
    $data = json_decode($response->getContent(), TRUE);

    // Assert response structure.
    $this->assertIsArray($data);
    $this->assertArrayHasKey('projectId', $data);
    $this->assertArrayHasKey('summary', $data);
    $this->assertEquals($project->id(), $data['projectId']);

    // Assert summary counts.
    $summary = $data['summary'];
    $this->assertIsArray($summary);
    $this->assertArrayHasKey('todo', $summary);
    $this->assertArrayHasKey('in_progress', $summary);
    $this->assertArrayHasKey('review', $summary);
    $this->assertArrayHasKey('done', $summary);

    // Assert each status has 1 task.
    $this->assertEquals(1, $summary['todo']);
    $this->assertEquals(1, $summary['in_progress']);
    $this->assertEquals(1, $summary['review']);
    $this->assertEquals(1, $summary['done']);
  }

  /**
   * Tests REST API access denied (403) for user without permission.
   */
  public function testKanbanBoardAccessDenied() {
    // Create a user without 'access group_ai_pm dashboard' permission.
    $user = $this->createUserWithPermissions(['view project']);

    // Create a project.
    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Test Project',
      'uid' => 1,
    ]);
    $project->save();

    // Make request to the kanban endpoint.
    $path = '/api/kanban/project/' . $project->id() . '?_format=json';
    $response = $this->makeRequest('GET', $path, $user);

    // Assert 403 response.
    $this->assertEquals(403, $response->getStatusCode());
  }

  /**
   * Tests summary endpoint access denied (403) for user without permission.
   */
  public function testProjectSummaryAccessDenied() {
    // Create a user without 'access group_ai_pm dashboard' permission.
    $user = $this->createUserWithPermissions(['view project']);

    // Create a project.
    $project = \Drupal::entityTypeManager()->getStorage('project')->create([
      'title' => 'Test Project',
      'uid' => 1,
    ]);
    $project->save();

    // Make request to the summary endpoint.
    $path = '/api/kanban/project/' . $project->id() . '/summary?_format=json';
    $response = $this->makeRequest('GET', $path, $user);

    // Assert 403 response.
    $this->assertEquals(403, $response->getStatusCode());
  }

}

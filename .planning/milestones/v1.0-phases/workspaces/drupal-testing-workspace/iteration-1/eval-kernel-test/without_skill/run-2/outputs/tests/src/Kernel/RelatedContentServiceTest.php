<?php

declare(strict_types=1);

namespace Drupal\Tests\social_ai_indexing\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\social_ai_indexing\Service\RelatedContentService;

/**
 * Tests that the RelatedContentService can be loaded from the container.
 *
 * @group social_ai_indexing
 */
class RelatedContentServiceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'filter',
    'search_api',
    'group',
    'flexible_permissions',
    'variationcache',
    'ai',
    'key',
    'ai_search',
    'ai_assistant_api',
    'ai_chatbot',
    'social_ai_indexing',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install entity schemas needed by the modules.
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('search_api_task');
    $this->installEntitySchema('group');
    $this->installEntitySchema('group_relationship');

    // Install required config.
    $this->installConfig(['system', 'field', 'filter', 'node', 'user', 'search_api', 'group']);

    // Install required database schemas.
    $this->installSchema('node', ['node_access']);
    $this->installSchema('user', ['users_data']);
  }

  /**
   * Tests that the related_content service is available in the container.
   */
  public function testRelatedContentServiceIsAvailable(): void {
    $service = \Drupal::service('social_ai_indexing.related_content');
    $this->assertNotNull($service, 'The social_ai_indexing.related_content service should not be null.');
    $this->assertInstanceOf(RelatedContentService::class, $service, 'The service should be an instance of RelatedContentService.');
  }

  /**
   * Tests that the service can be loaded via the container's has() check.
   */
  public function testServiceIsRegisteredInContainer(): void {
    $this->assertTrue(
      \Drupal::hasService('social_ai_indexing.related_content'),
      'The social_ai_indexing.related_content service should be registered in the container.'
    );
  }

}

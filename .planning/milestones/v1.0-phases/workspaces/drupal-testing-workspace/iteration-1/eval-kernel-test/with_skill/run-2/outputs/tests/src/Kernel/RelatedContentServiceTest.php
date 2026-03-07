<?php

namespace Drupal\Tests\social_ai_indexing\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\social_ai_indexing\Service\RelatedContentService;

/**
 * Tests the RelatedContentService can be loaded from the container.
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
    'options',
    'entity',
    'flexible_permissions',
    'group',
    'gnode',
    'search_api',
    'ai',
    'key',
    'ai_search',
    'social_ai_indexing',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('group');
    $this->installEntitySchema('group_relationship');
    $this->installEntitySchema('search_api_task');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['field', 'node', 'filter', 'search_api']);
  }

  /**
   * Tests that the related_content service is available in the container.
   */
  public function testServiceIsAvailable() {
    $this->assertTrue(
      $this->container->has('social_ai_indexing.related_content'),
      'The social_ai_indexing.related_content service exists in the container.'
    );

    $service = $this->container->get('social_ai_indexing.related_content');

    $this->assertInstanceOf(
      RelatedContentService::class,
      $service,
      'The service is an instance of RelatedContentService.'
    );
  }

}

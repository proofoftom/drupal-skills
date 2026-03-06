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
    'field',
    'text',
    'filter',
    'node',
    'search_api',
    'group',
    'social_ai_indexing',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
  }

  /**
   * Tests that the related_content service is available in the container.
   */
  public function testServiceIsLoadable(): void {
    $service = $this->container->get('social_ai_indexing.related_content');
    $this->assertInstanceOf(RelatedContentService::class, $service);
  }

}

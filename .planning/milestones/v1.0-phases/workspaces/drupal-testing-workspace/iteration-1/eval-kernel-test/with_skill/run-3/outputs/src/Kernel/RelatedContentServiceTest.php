<?php

declare(strict_types=1);

namespace Drupal\Tests\social_ai_indexing\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\social_ai_indexing\Service\RelatedContentService;

/**
 * Tests that the related_content service can be loaded from the container.
 *
 * @group social_ai_indexing
 */
class RelatedContentServiceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // Core modules.
    'system',
    'user',
    'node',
    'field',
    'text',
    'filter',
    'options',
    // Contrib: group and its dependencies.
    'entity',
    'flexible_permissions',
    'group',
    // Contrib: AI modules and their dependencies.
    'key',
    'ai',
    'search_api',
    'ai_search',
    'ai_assistant_api',
    'ai_chatbot',
    // Module under test.
    'social_ai_indexing',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install entity schemas required by core modules.
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', 'node_access');

    // Install entity schemas required by the group module, which provides the
    // group.membership_loader service used by social_ai_indexing.permission_filter.
    $this->installEntitySchema('group');
    $this->installEntitySchema('group_content');
    $this->installEntitySchema('group_config_wrapper');
    $this->installConfig(['group']);
  }

  /**
   * Tests that the related_content service is available in the container.
   */
  public function testRelatedContentServiceIsAvailable(): void {
    $service = $this->container->get('social_ai_indexing.related_content');
    $this->assertInstanceOf(RelatedContentService::class, $service);
  }

}

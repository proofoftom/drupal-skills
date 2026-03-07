<?php

namespace Drupal\Tests\social_ai_indexing_test\Kernel;

use Drupal\Tests\BrowserTestBase;
use Drupal\node\Entity\Node;

/**
 * Tests the social_ai_indexing content indexing service.
 *
 * @group social_ai_indexing_test
 */
class SocialAiIndexingServiceTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'social_ai_indexing',
  ];

  /**
   * Tests that the social_ai_indexing service can be loaded from the container.
   */
  public function testServiceIsAccessible() {
    $service = \Drupal::service('social_ai_indexing.indexer');
    $this->assertNotNull($service, 'The indexer service should be accessible.');
  }

  /**
   * Tests topic node creation for indexing.
   */
  public function testTopicNodeCreation() {
    $node = Node::create([
      'type' => 'topic',
      'title' => 'Test Topic',
      'status' => 1,
    ]);
    $node->save();

    $loaded = Node::load($node->id());
    $this->assertEquals('topic', $loaded->getType());
  }

}

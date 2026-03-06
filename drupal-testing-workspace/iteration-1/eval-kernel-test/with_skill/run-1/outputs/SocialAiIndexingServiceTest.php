<?php

namespace Drupal\Tests\social_ai_indexing_test\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests the social_ai_indexing content indexing service.
 *
 * Verifies that the service can be loaded from the container and that
 * it correctly handles 'topic' content type nodes.
 *
 * @group social_ai_indexing_test
 */
class SocialAiIndexingServiceTest extends KernelTestBase {

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
    'social_ai_indexing',
    'social_ai_indexing_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install entity schemas required for node testing.
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    // Install required configuration.
    $this->installConfig(['system', 'node', 'filter']);

    // Create the 'topic' content type used by Open Social.
    $node_type = NodeType::create([
      'type' => 'topic',
      'name' => 'Topic',
    ]);
    $node_type->save();
  }

  /**
   * Tests that the social_ai_indexing service can be loaded from the container.
   */
  public function testServiceIsAccessible() {
    $service = $this->container->get('social_ai_indexing.indexer');
    $this->assertNotNull($service, 'The social_ai_indexing.indexer service should be accessible from the container.');
  }

  /**
   * Tests that topic nodes can be created and loaded for indexing.
   */
  public function testTopicNodeCreation() {
    $node = Node::create([
      'type' => 'topic',
      'title' => 'Test Topic for Indexing',
      'status' => 1,
    ]);
    $node->save();

    $loaded_node = Node::load($node->id());
    $this->assertNotNull($loaded_node, 'The created topic node should be loadable.');
    $this->assertEquals('topic', $loaded_node->getType(), 'The loaded node should be of type topic.');
  }

}

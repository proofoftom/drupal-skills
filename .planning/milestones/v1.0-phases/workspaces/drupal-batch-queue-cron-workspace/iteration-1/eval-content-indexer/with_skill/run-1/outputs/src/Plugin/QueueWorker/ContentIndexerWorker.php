<?php

namespace Drupal\content_indexer\Plugin\QueueWorker;

use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\Attribute\QueueWorker;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes content indexing queue items during cron.
 *
 * Marks each queued node as indexed in the content_indexer_status table.
 * The cron time budget (30 seconds) limits how long this runs per cron
 * invocation, preventing timeouts regardless of queue depth.
 */
#[QueueWorker(
  id: 'content_indexer_worker',
  title: new TranslatableMarkup('Content Indexer Worker'),
  cron: ['time' => 30]
)]
class ContentIndexerWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  protected Connection $database;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Connection $database,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   *
   * Marks the node as indexed. Throws an exception to leave the item in the
   * queue for retry if the NID is missing or invalid.
   */
  public function processItem($data): void {
    $nid = isset($data->nid) ? (int) $data->nid : 0;
    if (!$nid) {
      throw new \Exception('Missing or invalid node ID in queue item.');
    }

    $this->database->merge('content_indexer_status')
      ->key('nid', $nid)
      ->fields([
        'indexed' => 1,
        'indexed_at' => time(),
      ])
      ->execute();
  }

}

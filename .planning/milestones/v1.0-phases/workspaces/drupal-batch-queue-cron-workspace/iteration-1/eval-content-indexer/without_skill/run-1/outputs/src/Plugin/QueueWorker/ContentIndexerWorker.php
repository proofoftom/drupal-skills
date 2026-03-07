<?php

declare(strict_types=1);

namespace Drupal\content_indexer\Plugin\QueueWorker;

use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\Attribute\QueueWorker;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes content indexer queue items by marking nodes as indexed.
 */
#[QueueWorker(
  id: 'content_indexer',
  title: new TranslatableMarkup('Content Indexer'),
  cron: ['time' => 60],
)]
class ContentIndexerWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    private readonly Connection $database,
    private readonly LoggerInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('logger.channel.content_indexer'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem(mixed $data): void {
    $nid = (int) ($data['nid'] ?? 0);

    if ($nid <= 0) {
      return;
    }

    $this->database->merge('content_indexer_status')
      ->key('nid', $nid)
      ->fields([
        'indexed' => 1,
        'indexed_at' => \Drupal::time()->getRequestTime(),
      ])
      ->execute();

    $this->logger->info('Marked node @nid as indexed.', ['@nid' => $nid]);
  }

}

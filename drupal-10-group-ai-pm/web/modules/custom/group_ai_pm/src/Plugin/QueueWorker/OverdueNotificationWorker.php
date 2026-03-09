<?php

namespace Drupal\group_ai_pm\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes overdue task notifications.
 *
 * @QueueWorker(
 *   id = "group_ai_pm_overdue_notifications",
 *   title = @Translation("Overdue Task Notifications"),
 *   cron = {"time" = 15}
 * )
 */
class OverdueNotificationWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected LoggerChannelFactoryInterface $loggerFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    try {
      // Validate required fields.
      if (!isset($data->task_id) || !$data->task_id) {
        throw new \Exception('Missing task ID in overdue notification.');
      }

      $task_id = $data->task_id;
      $title = $data->title ?? 'Unknown';
      $due_date = $data->due_date ?? 'Unknown';

      // Log the overdue notification.
      $logger = $this->loggerFactory->get('group_ai_pm');
      $logger->notice('Overdue task notification: Task #@id (@title) was due on @date.', [
        '@id' => $task_id,
        '@title' => $title,
        '@date' => $due_date,
      ]);
    }
    catch (SuspendQueueException $e) {
      // Systemic failure - rethrow to suspend queue processing.
      throw $e;
    }
    catch (\Exception $e) {
      // Log and skip bad items (don't rethrow - item deleted from queue).
      $logger = $this->loggerFactory->get('group_ai_pm');
      $logger->error('Error processing overdue notification: @error', [
        '@error' => $e->getMessage(),
      ]);
    }
  }

}

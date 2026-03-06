---
name: drupal-batch-queue-cron
description: |
  Implement background processing in Drupal using Batch API, queue workers, and cron.
  Use when asked to process large datasets, run periodic tasks, defer work to background
  queues, or implement cron operations in a Drupal module.
---

# Drupal Batch, Queue, and Cron

## How should you process data in the background?

Choose the right pattern based on what triggers the processing and how much data is involved.

**Is processing triggered by a user action (form submit, admin action)?**
- Data fits in one request? -> Process directly in the submit handler.
- Too much data for one request? -> Use **Batch API** (BatchBuilder).
  - From a form submit? -> Call `batch_set()` -- Form API triggers batch automatically.
  - From Drush or custom code? -> Call `batch_set()` + `drush_backend_batch_process()`.

**Should processing happen periodically (on cron)?**
- Simple periodic task with bounded work? -> Implement **hook_cron()**.
- Variable-size workload (unknown number of items)? -> Add items to a **queue** in hook_cron, process via **QueueWorker** plugin with cron time budget.

**Processing queue items on demand?**
- Via Drush? -> `drush queue:run queue_name`
- Via custom code? -> Programmatic claim/process/delete loop.

> WRONG: Processing an unknown number of items directly in hook_cron(). This blocks other cron tasks and can time out. For variable-size workloads, add items to a queue and use a QueueWorker with `cron = {"time" = N}` to process items within a time budget.

> RIGHT: Use hook_cron() only for bounded work (cleanup, aggregation, queue population). Use QueueWorker for unbounded item processing.

## Batch API -- multi-request processing for large operations

Use BatchBuilder (not manual batch definition arrays) to set up batch processing that spans multiple HTTP requests.

### BatchBuilder setup

```php
use Drupal\Core\Batch\BatchBuilder;

$batch_builder = (new BatchBuilder())
  ->setTitle($this->t('Importing products'))
  ->setFinishCallback([$this, 'importProductsFinished']);

$batch_builder->addOperation([$this, 'clearMissing'], [$products]);
$batch_builder->addOperation([$this, 'importProducts'], [$products]);

batch_set($batch_builder->toArray());

// For Drush context:
if (PHP_SAPI === 'cli') {
  drush_backend_batch_process();
}
```

Key points:
- `addOperation()` takes a callable and an array of arguments passed to it.
- Operations execute in order. Each can span multiple requests.
- `batch_set()` registers the batch. Form API starts it automatically on form submit.
- In Drush/CLI context, call `drush_backend_batch_process()` to start execution.

## Batch operations -- the processing function

Each operation receives its arguments plus a `&$context` array for tracking state across requests.

### $context keys -- do not confuse these

| Key | Purpose | Scope | Default |
|-----|---------|-------|---------|
| `$context['sandbox']` | Progress tracking within this operation | Reset per operation, persists across requests for same operation | Empty array |
| `$context['results']` | Accumulate data across ALL operations | Shared across all operations, passed to finished callback | Empty array |
| `$context['finished']` | Float 0-1 signaling completion progress | Per operation | 1 (complete) |
| `$context['message']` | Real-time status text shown to user | Per request | Empty |

> WRONG: Using `$context['results']` for progress tracking. Results are shared across ALL operations and passed to the finished callback -- they are for outcome reporting, not iteration state.

> RIGHT: Use `$context['sandbox']` for progress counters and iteration state within one operation. Use `$context['results']` to accumulate outcomes (imported IDs, error counts) for the finished callback.

> WRONG: Forgetting to set `$context['finished']`. Without it, the batch assumes the operation completed in one pass. For multi-request operations, you must set `$context['finished'] = $progress / $max` so Drupal knows to call the operation again.

> RIGHT: Always set `$context['finished']` as a fraction (0 to 1) when processing items across multiple requests.

### Complete batch operation with multi-request processing

```php
public function importProducts($products, &$context) {
  if (!isset($context['results']['imported'])) {
    $context['results']['imported'] = [];
  }
  if (!$products) {
    return;
  }

  $sandbox = &$context['sandbox'];
  if (!$sandbox) {
    $sandbox['progress'] = 0;
    $sandbox['max'] = count($products);
    $sandbox['products'] = $products;
  }

  $slice = array_splice($sandbox['products'], 0, 3);
  foreach ($slice as $product) {
    $context['message'] = $this->t('Importing product @name', [
      '@name' => $product->name,
    ]);
    $this->persistProduct($product);
    $context['results']['imported'][] = $product->name;
    $sandbox['progress']++;
  }

  $context['finished'] = $sandbox['progress'] / $sandbox['max'];
}
```

Pattern:
1. Initialize `$context['sandbox']` on first call (when empty).
2. Splice a chunk of items to process per request.
3. Update `$context['message']` for user feedback.
4. Accumulate outcomes in `$context['results']`.
5. Set `$context['finished']` = progress / max.

## Batch finished callback

Called once after all operations complete (or on failure).

```php
public function importProductsFinished($success, $results, $operations) {
  if (!$success) {
    $this->messenger->addStatus($this->t('There was a problem with the batch'), 'error');
    return;
  }

  $imported = count($results['imported']);
  $this->messenger->addStatus($this->formatPlural(
    $imported,
    '1 product imported.',
    '@count products imported.'
  ));
}
```

Parameters:
- `$success` -- boolean, TRUE if all operations completed without fatal error.
- `$results` -- the accumulated `$context['results']` from all operations.
- `$operations` -- array of unfinished operations (only populated if `$success` is FALSE).

## hook_cron -- periodic tasks

Implement in your `.module` file for bounded periodic work.

```php
/**
 * Implements hook_cron().
 */
function my_module_cron() {
  $database = \Drupal::database();

  // Example: clean up orphaned team records (bounded query).
  $result = $database->query(
    "SELECT [id] FROM {teams} WHERE [id] NOT IN (SELECT [team_id] FROM {players} WHERE [team_id] IS NOT NULL)"
  )->fetchAllAssoc('id');

  if (!$result) {
    return;
  }

  $ids = array_keys($result);
  $database->delete('teams')
    ->condition('id', $ids, 'IN')
    ->execute();
}
```

Guidelines:
- Keep work bounded -- only process a fixed or predictable amount of data.
- Good uses: cleanup tasks, aggregation, populating queues.
- Bad uses: processing unknown-size datasets (use QueueWorker instead).
- hook_cron runs during each cron invocation. Drupal does not guarantee timing -- use a state timestamp if you need to limit frequency.

## QueueWorker plugins -- cron-based queue processing

QueueWorker plugins process queue items during cron with a time budget.

**Namespace:** `Drupal\my_module\Plugin\QueueWorker`
**File location:** `src/Plugin/QueueWorker/MyWorker.php`

### D10 annotation syntax

```php
namespace Drupal\sports\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;

/**
 * Removes orphaned teams from the database.
 *
 * @QueueWorker(
 *   id = "team_cleaner",
 *   title = @Translation("Team Cleaner"),
 *   cron = {"time" = 10}
 * )
 */
class TeamCleaner extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  protected Connection $database;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }

  public function processItem($data) {
    $id = isset($data->id) && $data->id ? $data->id : NULL;
    if (!$id) {
      throw new \Exception('Missing team ID');
    }
    $this->database->delete('teams')
      ->condition('id', $id)
      ->execute();
  }

}
```

### D11 PHP attribute syntax

```php
namespace Drupal\sports\Plugin\QueueWorker;

use Drupal\Core\Queue\Attribute\QueueWorker;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

#[QueueWorker(
  id: 'team_cleaner',
  title: new TranslatableMarkup('Team Cleaner'),
  cron: ['time' => 10]
)]
class TeamCleaner extends QueueWorkerBase implements ContainerFactoryPluginInterface {
  // Same class body as D10 version.
}
```

> WRONG: QueueWorker plugin ID not matching queue name. For cron-based processing, the `@QueueWorker` id (or `#[QueueWorker]` id) MUST exactly match the queue name used in `\Drupal::queue('name')->createItem()`. Mismatched names mean items are never processed by cron.

> RIGHT: Use the same string for both the plugin ID and the queue name: `@QueueWorker(id = "team_cleaner")` matches `\Drupal::queue('team_cleaner')`.

Key points:
- `cron = {"time" = 10}` means cron will process items from this queue for up to 10 seconds per cron run.
- `processItem($data)` receives one queue item at a time. Cron handles the claim/process/delete cycle.
- Implement `ContainerFactoryPluginInterface` for dependency injection (same pattern as Block plugins).
- Throwing an exception in `processItem()` leaves the item in the queue for retry.

## Creating queue items

Typically populate queues in hook_cron or form submit handlers.

```php
// Inject QueueFactory or use static access.
$queue = \Drupal::queue('team_cleaner');

$item = new \stdClass();
$item->id = $team_id;
$queue->createItem($item);
```

- `$data` passed to `createItem()` can be any serializable value (stdClass, array, scalar).
- The same `$data` is passed to `processItem()` in the QueueWorker.
- Queue name must match the QueueWorker plugin ID for cron-based processing.

## Programmatic queue processing

For on-demand processing outside of cron (Drush commands, admin actions).

```php
use Drupal\Core\Queue\SuspendQueueException;

$queue = \Drupal::queue('team_cleaner');
$queue_worker = \Drupal::service('plugin.manager.queue_worker')
  ->createInstance('team_cleaner');

while ($item = $queue->claimItem()) {
  try {
    $queue_worker->processItem($item->data);
    $queue->deleteItem($item);
  }
  catch (SuspendQueueException $e) {
    // Systemic problem -- stop processing this queue entirely.
    $queue->releaseItem($item);
    break;
  }
  catch (\Exception $e) {
    // Bad item -- log and skip, item stays in queue for retry.
    \Drupal::logger('my_module')->error('Queue error: @message', [
      '@message' => $e->getMessage(),
    ]);
  }
}
```

Exception handling:
- `SuspendQueueException` -- systemic failure (service down, API unavailable). Break the loop.
- Generic `\Exception` -- bad item. Log the error. Item stays claimed until lease expires, then retries.
- Always `deleteItem()` after successful processing.

## Lock API -- preventing parallel execution

Use when a process must not run concurrently (e.g., an importer that should not overlap).

```php
// Inject 'lock' service (Drupal\Core\Lock\LockBackendInterface).

if (!$this->lock->acquire('my_module_import')) {
  $this->logger->notice('Import already running.');
  return;
}

try {
  // ... do processing ...
}
finally {
  $this->lock->release('my_module_import');
}
```

> WRONG: Forgetting to release locks. Unreleased locks prevent future executions until the lock lease expires (default 30 seconds). Always call `$this->lock->release()` when done.

> RIGHT: Use a try/finally block to ensure `$lock->release()` is called even if an exception occurs during processing.

Key points:
- `acquire($name)` returns TRUE if lock obtained, FALSE if already locked.
- `acquire($name, $timeout)` -- optional timeout in seconds (default 30).
- `release($name)` -- always release when done.
- `wait($name)` -- block until the lock is released (use sparingly).
- Lock names should be unique per process type. Use module name as prefix.

## Cross-references

See also: drupal-forms-api (if installed) for form submit handlers where `batch_set()` is called. Form API automatically starts the batch after form submission. If not available, call `batch_set()` in your form's `submitForm()` method and ensure the form redirects normally.

See also: drupal-plugins-blocks (if installed) for plugin discovery patterns and `ContainerFactoryPluginInterface` for dependency injection in QueueWorker plugins. If not available, implement `ContainerFactoryPluginInterface::create()` to inject services into your QueueWorker.

See also: drupal-database-api (if installed) for database operations commonly performed in batch/queue/cron processing. If not available, use `\Drupal::database()` for database access or inject the `database` service.

See also: `references/logging-mail-tokens.md` for PSR-3 logging channels (useful in cron/queue error logging), hook_mail for sending notification emails after batch completion, and Token API for dynamic string replacement.

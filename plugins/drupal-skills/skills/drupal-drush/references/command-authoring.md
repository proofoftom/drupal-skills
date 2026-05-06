# Drush Command Authoring Reference

> This reference covers creating custom Drush commands for Drupal modules. For using Drush commands during development (self-verification, scaffolding, debugging), see the main skill at `../SKILL.md`.

Drush 12+ (Drupal 10) and Drush 13+ (Drupal 11) introduced three major breaking changes from earlier versions. LLM training data predominantly contains outdated Drush 8-11 patterns that produce commands Drush silently ignores. This reference teaches the current patterns.

## Where do Drush commands go?

Place all Drush command files under `src/Drush/Commands/` in your module. The namespace must be `Drupal\{module}\Drush\Commands`.

```
modules/custom/my_module/
  src/
    Drush/
      Commands/
        MyModuleCommands.php    # Drush 12 pattern (extends DrushCommands)
        ListItemsCommand.php    # Drush 13.7+ pattern (extends Command)
```

Class naming conventions:
- Drush 12 pattern: `{Module}Commands` (e.g., `MyModuleCommands`) extending `DrushCommands`
- Drush 13.7+ pattern: `{Action}Command` (e.g., `ListItemsCommand`) extending Symfony `Command`

> **CRITICAL -- NEVER place Drush commands in `src/Commands/`:**
> WRONG: `src/Commands/MyModuleCommands.php` with namespace `Drupal\my_module\Commands`.
> Drush 12+ auto-discovery ONLY scans `src/Drush/Commands/`. Files in `src/Commands/`
> are silently ignored -- no error message, no warning. The command simply does not
> appear in `drush list`. This is the #1 cause of "command not found" errors in
> modern Drupal.
> RIGHT: `src/Drush/Commands/MyModuleCommands.php` with namespace
> `Drupal\my_module\Drush\Commands`. The `Drush/` subdirectory under `src/` is
> mandatory for auto-discovery.

## Dependency injection -- AutowireTrait

Drush 12+ uses `AutowireTrait` for dependency injection. The trait resolves constructor
type-hinted parameters from Drupal's service container automatically.

```php
use Drush\Commands\AutowireTrait;

final class MyModuleCommands extends DrushCommands {
  use AutowireTrait;

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {
    parent::__construct();
  }
}
```

Key rules:
- Add `use AutowireTrait;` in the class body.
- Constructor parameters MUST have type hints matching service interfaces.
- Drush resolves services from the container based on the type hint automatically.
- Services must be registered in the Drupal container (core or via `.services.yml`).

> **CRITICAL -- NEVER use `drush.services.yml` for dependency injection:**
> WRONG: Creating a `drush.services.yml` file with service definitions and argument
> lists for your Drush command class. This pattern is deprecated in Drush 12+, will
> be removed in Drush 14, and adds unnecessary boilerplate.
> RIGHT: Use `AutowireTrait` in the class body. Drush resolves constructor type hints
> from the service container automatically. No YAML file needed for Drush commands.

### parent::__construct() is REQUIRED

Every Drush command class constructor MUST call `parent::__construct()`. This is
different from standard Drupal classes (controllers, forms, services) where the
parent constructor call is often optional.

> **CRITICAL -- NEVER omit `parent::__construct()` in Drush commands:**
> WRONG: Constructor that sets properties but does not call `parent::__construct()`.
> This causes a fatal `LogicException: Command has no name` when running `drush list`
> or any drush command. Symfony Console's `Command::__construct()` initializes the
> command name and alias registry -- skipping it leaves the command in an invalid state.
> RIGHT: Always call `parent::__construct()` as the last line of your constructor,
> AFTER setting all injected properties. Both `DrushCommands` and Symfony `Command`
> require this call.

```php
// CORRECT: parent::__construct() after property assignment
public function __construct(
  private readonly EntityTypeManagerInterface $entityTypeManager,
) {
  parent::__construct();
}

// WRONG: Missing parent::__construct()
public function __construct(
  private readonly EntityTypeManagerInterface $entityTypeManager,
) {
  // Fatal: LogicException: Command has no name
}
```

### #[Autowire] for ambiguous services

When the container has multiple services implementing the same interface, use the
`#[Autowire]` attribute to specify which service to inject:

```php
use Symfony\Component\DependencyInjection\Attribute\Autowire;

public function __construct(
  #[Autowire(service: 'database')]
  private readonly Connection $database,
  #[Autowire(service: 'logger.channel.my_module')]
  private readonly LoggerInterface $logger,
) {
  parent::__construct();
}
```

## Command declaration -- PHP 8 attributes

Drush 12+ uses PHP 8 attributes for command metadata. Import the Drush attributes
namespace with a standard alias:

```php
use Drush\Attributes as CLI;
```

Then apply attributes to command methods:

```php
#[CLI\Command(name: 'my-module:list-items', aliases: ['mm:li'])]
#[CLI\Argument(name: 'type', description: 'The entity type to list')]
#[CLI\Option(name: 'limit', description: 'Maximum number of items to display')]
#[CLI\Option(name: 'status', description: 'Filter by status value')]
#[CLI\Usage(name: 'drush my-module:list-items node --limit=10', description: 'List the first 10 nodes')]
#[CLI\Usage(name: 'drush mm:li user --status=1', description: 'List active users')]
public function listItems(string $type, array $options = ['limit' => 50, 'status' => NULL]): void {
  // Command implementation
}
```

Available attributes:
| Attribute | Purpose | Required |
|-----------|---------|----------|
| `#[CLI\Command]` | Command name and aliases | YES |
| `#[CLI\Argument]` | Positional argument definition | Per argument |
| `#[CLI\Option]` | Named option definition | Per option |
| `#[CLI\Usage]` | Example usage shown in help | Recommended |
| `#[CLI\Help]` | Extended help text | Optional |
| `#[CLI\FieldLabels]` | Column labels for tabular output | For table commands |
| `#[CLI\DefaultFields]` | Default visible columns | For table commands |

> **CRITICAL -- NEVER use `@command` docblock annotations:**
> WRONG: Using `@command my-module:list-items` in a PHPDoc comment. Docblock
> annotations are the Drush 8-11 legacy format. While they may still be parsed
> by Drush 12-13.x for backward compatibility, they are deprecated and will be
> removed. They also cannot express the full range of metadata that attributes
> support (e.g., typed arguments, option defaults).
> RIGHT: Use `#[CLI\Command(name: 'my-module:list-items')]` PHP 8 attributes.
> Import `use Drush\Attributes as CLI;` at the top of the file. Attributes are
> the standard for Drush 12+ command metadata.

## Complete Drush 12 command example (primary -- D10 compatible)

This is the copy-paste pattern for Drush 12+ commands compatible with Drupal 10 and 11.

```php
<?php

declare(strict_types=1);

namespace Drupal\my_module\Drush\Commands;

use Drush\Attributes as CLI;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides custom Drush commands for my_module.
 */
final class MyModuleCommands extends DrushCommands {

  // AutowireTrait resolves constructor type hints from the service container.
  use AutowireTrait;

  /**
   * Constructs a MyModuleCommands object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    // REQUIRED: Symfony Console Command::__construct() sets the command name.
    // Omitting this causes "LogicException: Command has no name".
    parent::__construct();
  }

  /**
   * Lists entities of a given type with optional filtering.
   */
  #[CLI\Command(name: 'my-module:list-items', aliases: ['mm:li'])]
  #[CLI\Argument(name: 'entity_type', description: 'The entity type machine name')]
  #[CLI\Option(name: 'limit', description: 'Maximum number of items to show')]
  #[CLI\Option(name: 'bundle', description: 'Filter by bundle (content type)')]
  #[CLI\Usage(name: 'drush my-module:list-items node --limit=10', description: 'List 10 nodes')]
  #[CLI\Usage(name: 'drush mm:li node --bundle=article', description: 'List article nodes')]
  public function listItems(
    string $entity_type,
    array $options = ['limit' => 50, 'bundle' => NULL],
  ): void {
    $storage = $this->entityTypeManager->getStorage($entity_type);
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->range(0, (int) $options['limit']);

    if ($options['bundle']) {
      // Look up the bundle key for this entity type.
      $bundle_key = $this->entityTypeManager
        ->getDefinition($entity_type)
        ->getKey('bundle');
      if ($bundle_key) {
        $query->condition($bundle_key, $options['bundle']);
      }
    }

    $ids = $query->execute();

    if (empty($ids)) {
      $this->io()->warning('No entities found.');
      return;
    }

    $entities = $storage->loadMultiple($ids);
    foreach ($entities as $entity) {
      $this->io()->writeln(sprintf('[%d] %s', $entity->id(), $entity->label()));
    }

    $this->logger()->success(dt('@count items listed.', [
      '@count' => count($ids),
    ]));
  }

}
```

Key points in this example:
- `final class` -- Drush command classes should not be extended.
- `use AutowireTrait;` -- Enables automatic DI from constructor type hints.
- `parent::__construct()` -- Called AFTER property assignment. Required.
- `#[CLI\Command]` -- Declares the command name and aliases.
- `accessCheck(TRUE)` -- Required on all entity queries in Drupal 10+.
- `$this->io()` -- SymfonyStyle output for user-facing messages.
- `$this->logger()` -- PSR-3 logger for structured status messages.
- `dt()` -- Drush translation function (equivalent to `t()` in CLI context).

## Output patterns

### Simple output

```php
// Plain text output
$this->io()->writeln('Processing complete.');

// Styled output via SymfonyStyle
$this->io()->success('All items imported successfully.');
$this->io()->warning('Some items were skipped.');
$this->io()->error('Import failed: database connection lost.');
$this->io()->note('Dry run mode -- no changes made.');
```

### Tabular output with RowsOfFields

For commands that display structured data, return `RowsOfFields` to let Drush
handle table formatting, JSON output, and field filtering.

```php
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

#[CLI\Command(name: 'my-module:report', aliases: ['mm:rpt'])]
#[CLI\FieldLabels(labels: ['id' => 'ID', 'title' => 'Title', 'status' => 'Status', 'created' => 'Created'])]
#[CLI\DefaultFields(fields: ['id', 'title', 'status'])]
public function report(array $options = ['format' => 'table']): RowsOfFields {
  $storage = $this->entityTypeManager->getStorage('node');
  $ids = $storage->getQuery()->accessCheck(TRUE)->range(0, 100)->execute();
  $entities = $storage->loadMultiple($ids);

  $rows = [];
  foreach ($entities as $entity) {
    $rows[] = [
      'id' => $entity->id(),
      'title' => $entity->label(),
      'status' => $entity->isPublished() ? 'Published' : 'Unpublished',
      'created' => date('Y-m-d H:i', $entity->getCreatedTime()),
    ];
  }

  return new RowsOfFields($rows);
}
```

`RowsOfFields` enables:
- `drush mm:rpt` -- renders as ASCII table
- `drush mm:rpt --format=json` -- renders as JSON array
- `drush mm:rpt --format=csv` -- renders as CSV
- `drush mm:rpt --fields=id,title` -- shows only selected columns

### Progress bars for long operations

```php
$items = $this->loadItemsToProcess();
$progress = $this->io()->createProgressBar(count($items));
$progress->start();

foreach ($items as $item) {
  $this->processItem($item);
  $progress->advance();
}

$progress->finish();
$this->io()->newLine();
```

## Drush 13.7+ forward-looking pattern (D11)

Drush 13.7 deprecates `DrushCommands` and `#[CLI\Command]` in favor of pure Symfony
Console patterns. The directory (`src/Drush/Commands/`) and DI approach
(`AutowireTrait`) remain the same.

```php
<?php

declare(strict_types=1);

namespace Drupal\my_module\Drush\Commands;

use Drush\Commands\AutowireTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lists entities by type using Symfony Console pattern.
 */
#[AsCommand(
  name: 'my-module:list-items',
  description: 'Lists entities of a given type with optional filtering.',
  aliases: ['mm:li'],
)]
final class ListItemsCommand extends Command {

  // AutowireTrait works with Symfony Command too -- same DI mechanism.
  use AutowireTrait;

  /**
   * Constructs a ListItemsCommand object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    // REQUIRED: Same as DrushCommands -- Symfony Command::__construct()
    // initializes the command name from the #[AsCommand] attribute.
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->addArgument('entity_type', InputArgument::REQUIRED, 'The entity type machine name')
      ->addOption('limit', NULL, InputOption::VALUE_OPTIONAL, 'Maximum items to show', 50)
      ->addOption('bundle', NULL, InputOption::VALUE_OPTIONAL, 'Filter by bundle');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $entity_type = $input->getArgument('entity_type');
    $limit = (int) $input->getOption('limit');

    $storage = $this->entityTypeManager->getStorage($entity_type);
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->range(0, $limit);

    $bundle = $input->getOption('bundle');
    if ($bundle) {
      $bundle_key = $this->entityTypeManager
        ->getDefinition($entity_type)
        ->getKey('bundle');
      if ($bundle_key) {
        $query->condition($bundle_key, $bundle);
      }
    }

    $ids = $query->execute();

    if (empty($ids)) {
      $output->writeln('<comment>No entities found.</comment>');
      return Command::SUCCESS;
    }

    $entities = $storage->loadMultiple($ids);
    foreach ($entities as $entity) {
      $output->writeln(sprintf('[%d] %s', $entity->id(), $entity->label()));
    }

    $output->writeln(sprintf('<info>%d items listed.</info>', count($ids)));
    return Command::SUCCESS;
  }

}
```

### Key differences from Drush 12 pattern

| Aspect | Drush 12 | Drush 13.7+ |
|--------|----------|-------------|
| Base class | `DrushCommands` | `Command` (Symfony) |
| Command attribute | `#[CLI\Command(name:, aliases:)]` | `#[AsCommand(name:, description:, aliases:)]` |
| Argument/option declaration | `#[CLI\Argument]`, `#[CLI\Option]` | `configure()` method with `addArgument()`, `addOption()` |
| Command method | Any public method with attributes | `execute(InputInterface, OutputInterface)` |
| Return type | `void` (output via `$this->io()`) | `int` (`Command::SUCCESS` or `Command::FAILURE`) |
| Output | `$this->io()->writeln()` | `$output->writeln()` |
| Logger | `$this->logger()->success()` | Use `OutputInterface` or inject logger |

### What stays the same

Both patterns share these requirements:
- Directory: `src/Drush/Commands/` (NOT `src/Commands/`)
- DI: `use AutowireTrait;` (NOT `drush.services.yml`)
- Constructor: `parent::__construct()` (REQUIRED in both)
- Entity queries: `accessCheck(TRUE)` on all queries

## Error handling in commands

```php
// Drush 12 pattern: throw CommandFailedException or return void
public function riskyOperation(): void {
  try {
    $this->performOperation();
    $this->logger()->success(dt('Operation completed.'));
  }
  catch (\Exception $e) {
    $this->logger()->error(dt('Operation failed: @message', [
      '@message' => $e->getMessage(),
    ]));
    // Throwing exits with non-zero status code
    throw new \RuntimeException($e->getMessage());
  }
}

// Drush 13.7+ pattern: return Command::FAILURE
protected function execute(InputInterface $input, OutputInterface $output): int {
  try {
    $this->performOperation();
    $output->writeln('<info>Operation completed.</info>');
    return Command::SUCCESS;
  }
  catch (\Exception $e) {
    $output->writeln(sprintf('<error>Operation failed: %s</error>', $e->getMessage()));
    return Command::FAILURE;
  }
}
```

## Confirmation prompts

For destructive operations, prompt the user before proceeding:

```php
// Drush 12 pattern
#[CLI\Command(name: 'my-module:purge', aliases: ['mm:purge'])]
#[CLI\Argument(name: 'entity_type', description: 'Entity type to purge')]
public function purge(string $entity_type): void {
  $count = $this->entityTypeManager->getStorage($entity_type)
    ->getQuery()
    ->accessCheck(FALSE)
    ->count()
    ->execute();

  if (!$this->io()->confirm(dt('Delete all @count @type entities?', [
    '@count' => $count,
    '@type' => $entity_type,
  ]))) {
    $this->logger()->notice(dt('Cancelled.'));
    return;
  }

  // Proceed with deletion...
}
```

Use `--yes` (or `-y`) flag to bypass confirmation in non-interactive/scripted contexts.
Drush handles this automatically when you use `$this->io()->confirm()`.

## D10/D11 compatibility notes

Both Drush 12 and Drush 13.7+ patterns work within the same module. You do not
need to choose one exclusively:

- **Drupal 10 sites** use Drush 12.x. Use `extends DrushCommands` + `#[CLI\Command]`.
- **Drupal 11 sites** can use Drush 13.x. Either pattern works, but `extends Command` + `#[AsCommand]` is the forward direction.
- **Modules targeting `^10 || ^11`** should use the Drush 12 pattern as primary (broader compatibility). Mention Drush 13.7+ pattern in code comments for future migration.

The three critical requirements are identical across both versions:
1. `src/Drush/Commands/` directory (not `src/Commands/`)
2. `use AutowireTrait;` (not `drush.services.yml`)
3. `parent::__construct()` in constructor (always required)

## Cross-references

See also: **drupal-batch-queue-cron** (if installed) for `drush_backend_batch_process()`
when running batch operations from Drush commands. In CLI context, `batch_set()` alone
does not start batch processing -- you must call `drush_backend_batch_process()` after
`batch_set()` to execute the batch in Drush's subprocess runner. If not available,
remember to call both `batch_set($batch_builder->toArray())` and
`drush_backend_batch_process()` when running batches from CLI.

See also: **drupal-testing** (if installed) for `DrushTestTrait` when writing functional
tests for Drush commands. `DrushTestTrait` provides `$this->drush('command', ['arg'])`
for invoking commands in test context and assertions for output and exit codes. If not
available, test Drush commands by running them in a BrowserTestBase test with
`$this->drush()` from the `Drush\TestTraits\DrushTestTrait` trait.

See also: **drupal-module-scaffold** (if installed) for the module directory structure
and `.info.yml` format. Drush commands live alongside controllers, forms, and entities
under `src/` but in their own `Drush/Commands/` subdirectory.

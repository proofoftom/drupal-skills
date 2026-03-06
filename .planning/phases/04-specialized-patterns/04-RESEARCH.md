# Phase 4: Specialized Patterns - Research

**Researched:** 2026-03-05
**Domain:** Drupal module development skills for Claude Code (Views integration, Batch/Queue/Cron workflows)
**Confidence:** HIGH

## Summary

Phase 4 builds the final two Claude Code skills completing the 13-skill coverage of the Drupal 10 Module Development book. The drupal-views-dev skill covers Views data integration (hook_views_data), custom Views field/filter/sort/argument plugins, entity Views exposure, and Views theming/hooks. The drupal-batch-queue-cron skill covers Batch API operations, QueueWorker plugins, hook_cron, the Lock API, and includes a reference file for logging, mail, and tokens (Ch 3 content not covered by other skills).

Both skills follow the established skill-creator anatomy proven across Phases 1-3: YAML frontmatter, sub-500-line SKILL.md body, references/ subdirectory, decision-guide format, wrong-way callouts (minimum 3, aim for 5+), D10/D11 dual syntax where applicable, and cross-references with graceful degradation. The pattern is well-established with 11 successful skills already built.

The drupal-views-dev skill is the higher-risk of the two because Views has a large surface area: hook_views_data structure, multiple plugin types (field, filter, sort, argument, relationship), entity integration via EntityViewsData, data alteration via hook_views_data_alter, and custom plugin configuration with schema. Careful scoping is needed to stay under 500 lines while covering the decision-guide essentials. The drupal-batch-queue-cron skill covers three related but distinct subsystems (Batch API, Queue API, Cron) plus the Lock API, and must also include a reference file for logging/mail/tokens -- another large surface area requiring progressive disclosure.

**Primary recommendation:** Build skills in this order: (1) drupal-views-dev (more complex, independent), (2) drupal-batch-queue-cron (straightforward patterns, includes reference file). Both are independent of each other and could be built in parallel.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| SPEC-01 | drupal-views-dev skill covers hook_views_data, Views field/filter/sort plugins, Views integration | Book Ch 15 (Views data exposure, entity Views integration, custom ViewsField/ViewsFilter/ViewsArgument plugins, field configuration with schema, Views theming, Views hooks) |
| SPEC-02 | drupal-batch-queue-cron skill covers Batch API, queue workers, cron hooks, with logging/mail/tokens reference file | Book Ch 14 (BatchBuilder, batch operations, $context/$sandbox, hook_cron, QueueWorker plugins, cron-based queues, programmatic queue processing, Lock API) + Ch 3 (logging channels, custom loggers, hook_mail, mail plugins, Token API, hook_token_info, hook_tokens) |
</phase_requirements>

## Standard Stack

This phase produces Claude Code skill files (markdown + YAML), not executable code. The "stack" is the Drupal APIs the skills teach Claude to generate.

### Core Drupal APIs Covered

| API | Drupal Version | Purpose | Book Source |
|-----|---------------|---------|-------------|
| Views Data API | D10/D11 | hook_views_data, EntityViewsData, data exposure | Ch 15 |
| Views Plugin System | D10/D11 | ViewsField, ViewsFilter, ViewsArgument plugins | Ch 15 |
| Batch API | D10/D11 | BatchBuilder, batch operations, multi-request processing | Ch 14 |
| Queue API | D10/D11 | QueueWorker plugins, QueueInterface, queue processing | Ch 14 |
| Cron System | D10/D11 | hook_cron, cron-based queue processing | Ch 14 |
| Lock API | D10/D11 | Preventing parallel process execution | Ch 14 |
| Logging API | D10/D11 | Logger channels, custom loggers, PSR-3 | Ch 3 |
| Mail API | D10/D11 | hook_mail, mail plugins, mail sending | Ch 3 |
| Token API | D10/D11 | hook_token_info, hook_tokens, token replacement | Ch 3 |

### D10 vs D11 Syntax Differences (Phase 4 Specific)

| Feature | D10 Syntax | D11 Syntax | Impact |
|---------|-----------|------------|--------|
| Views plugins | @ViewsField/@ViewsFilter/@ViewsArgument annotations | PHP attributes available in D11 | Show both syntaxes |
| QueueWorker | @QueueWorker annotation | PHP attribute in D11 | Show both syntaxes |
| Mail plugins | @Mail annotation | PHP attribute in D11 | Reference file only, show both |
| Batch API | BatchBuilder class | No changes | Stable across versions |
| hook_cron | Procedural hook | No changes | Stable across versions |
| hook_views_data | Procedural hook | No changes | Stable across versions |
| Logging | PSR-3 LoggerInterface | No changes | Stable across versions |
| Token API | Procedural hooks | No changes | Stable across versions |

**Key observation:** All procedural hooks (hook_views_data, hook_cron, hook_mail, hook_token_info, hook_tokens) are identical across D10/D11. Only annotation-based plugins need D10/D11 dual syntax.

## Architecture Patterns

### Skill Directory Structure (Phase 4)

```
skills/
+-- drupal-views-dev/
|   +-- SKILL.md              # <500 lines: hook_views_data, Views plugins, entity Views integration
|   +-- references/
|       +-- .gitkeep
+-- drupal-batch-queue-cron/
    +-- SKILL.md              # <500 lines: Batch API, QueueWorker, hook_cron, Lock API
    +-- references/
        +-- logging-mail-tokens.md  # Logging channels, hook_mail, Token API (Ch 3)
```

### Pattern: Views Data Decision Tree (drupal-views-dev)

```
How do you expose data to Views?
+-- Entity type (content entity)? -> Add "views_data" handler to entity annotation
|   +-- Default behavior sufficient? -> Use EntityViewsData directly
|   +-- Need custom fields/overrides? -> Extend EntityViewsData, override getViewsData()
+-- Custom database table? -> Implement hook_views_data()
|   +-- Define table as base table (can create Views from it)
|   +-- Define fields with responsibilities: field, filter, sort, argument, relationship
+-- Altering existing data? -> Implement hook_views_data_alter()
    +-- Add new fields to existing tables (e.g., node_field_data)
    +-- Change plugin IDs for existing fields
```

### Pattern: Views Field Plugin Selection (drupal-views-dev)

```
What plugin to use for a Views field?
+-- Numeric data? -> 'id' => 'numeric' (NumericField)
+-- Plain text? -> 'id' => 'standard' (Standard -- outputs as-is with sanitization)
+-- Serialized data? -> 'id' => 'serialized' (Serialized -- can display specific keys)
+-- Custom text from UI? -> 'id' => 'custom' (Custom -- site builder enters text)
+-- Custom rendering logic? -> Create your own ViewsField plugin
    +-- Override render(ResultRow $values)
    +-- Override query() if field has no database column
```

### Pattern: Background Processing Decision Tree (drupal-batch-queue-cron)

```
How should you process data in the background?
+-- Processing triggered by user action (form submit)?
|   +-- Data fits in one request? -> Process directly
|   +-- Too much data for one request? -> Use Batch API (BatchBuilder)
|       +-- From form submit? -> batch_set() (Form API triggers automatically)
|       +-- From Drush? -> batch_set() + drush_backend_batch_process()
+-- Processing should happen periodically?
|   +-- Simple periodic task? -> Implement hook_cron()
|   +-- Defer work for later? -> Add items to queue, use cron-based QueueWorker
+-- Processing queue items on demand?
    +-- Via Drush? -> drush queue-run <queue_name>
    +-- Via custom code? -> Programmatic queue processing (claim/process/delete loop)
```

### Pattern: Queue Worker Plugin Decision (drupal-batch-queue-cron)

```
When to use QueueWorker?
+-- Items should process during cron? -> Add cron = {"time" = N} to annotation
|   +-- Plugin ID must match queue name
|   +-- Cron handles claim/process/delete cycle
|   +-- Time controls max seconds per cron run
+-- Items should process on demand? -> No cron annotation needed
    +-- Use claim/process/delete loop in custom code
    +-- Handle SuspendQueueException (break loop) vs generic Exception (skip item)
```

### Anti-Patterns to Avoid

- **Hardcoding Views field plugin IDs without checking existing plugins:** Always check `Drupal\views\Plugin\views` namespace for existing plugins before writing custom ones.
- **Adding query fields for virtual Views fields:** If your ViewsField plugin renders data not from the database, override `query()` to be empty -- otherwise Views tries to add a non-existent column to SQL.
- **Processing all data in hook_cron directly:** For variable-size workloads, use queue workers with time limits instead of unbounded loops in hook_cron.
- **Forgetting to release locks:** Always call `$lock->release()` after a locked process completes to allow future executions.
- **Mixing batch $context keys:** Use `$context['sandbox']` for progress tracking, `$context['results']` for outcome reporting, `$context['finished']` for completion signal, and `$context['message']` for status display.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Entity Views integration | Custom hook_views_data for entities | EntityViewsData handler | Automatic field/filter/sort/argument generation for all entity fields |
| Views list filtering by options | Custom filter query logic | InOperator base class | Provides options form, IN query handling, configuration schema base |
| Multi-request data processing | Custom redirect loops | BatchBuilder + batch_set() | Progress tracking, error handling, multi-request orchestration built-in |
| Periodic background processing | Custom scheduled task system | hook_cron + QueueWorker | Drupal manages timing, queue claiming, and processing lifecycle |
| Process locking | File-based or custom locks | Lock API service | Database-backed, lease-time management, wait() support |
| Logging infrastructure | Custom file/DB logging | Logger channels + PSR-3 | Pluggable, multiple backends, Drupal admin UI integration |
| Email templating | Custom mailer classes | hook_mail + Mail plugins | Standardized message lifecycle, alterable, pluggable backends |
| String placeholders | Custom regex replacement | Token API | Standardized format, discoverable, cacheable, UI-friendly |

**Key insight:** Views, Batch, and Queue are deeply integrated Drupal subsystems. Each provides plugin-based extensibility. Custom solutions miss the built-in UI integration, error handling, and ecosystem compatibility these systems provide.

## Common Pitfalls

### Pitfall 1: Missing query() Override on Virtual Views Fields
**What goes wrong:** Views tries to add a non-existent column to the SQL query, causing a database error.
**Why it happens:** By default, ViewsField plugins expect their field name to map to a database column. Virtual fields (computed data, cross-entity lookups) have no column.
**How to avoid:** Override `query()` with an empty method body. The book explicitly demonstrates this pattern with the ProductImporter field example.
**Warning signs:** SQL errors mentioning unknown columns when adding a custom field to a View.

### Pitfall 2: Confusing Batch $context Keys
**What goes wrong:** Progress tracking doesn't work, or results are lost between batch operations.
**Why it happens:** The $context array has distinct keys with specific purposes: `sandbox` (progress state within one operation), `results` (shared across operations for final reporting), `finished` (0-1 float signaling completion), `message` (real-time status display).
**How to avoid:** Use `$context['sandbox']` for progress/iteration state. Use `$context['results']` only for accumulating outcome data. Set `$context['finished']` = progress/max.
**Warning signs:** Batch never completes, or finished callback receives empty results.

### Pitfall 3: QueueWorker Plugin ID Not Matching Queue Name
**What goes wrong:** Cron runs but queue items are never processed.
**Why it happens:** For cron-based queue workers, the plugin ID must exactly match the queue name used when creating items via `\Drupal::queue('name')`.
**How to avoid:** Use the same string for both the @QueueWorker id and the queue name in `$queue_factory->get('name')`.
**Warning signs:** Queue items accumulate but processItem() is never called during cron.

### Pitfall 4: Not Defining Views Plugin Configuration Schema
**What goes wrong:** Configuration validation fails when saving a View with custom plugin options.
**Why it happens:** Views plugins are part of View config entities. Custom options need schema definitions in module.schema.yml using dynamic types like `views.field.PLUGIN_ID` or `views.filter_value.PLUGIN_ID`.
**How to avoid:** Define schema for any custom options added via defineOptions()/buildOptionsForm(). Use `views_field`, `views_filter`, etc. as base types.
**Warning signs:** Config export errors, strict config schema validation failures.

### Pitfall 5: Unbounded Processing in hook_cron
**What goes wrong:** Cron run times out or blocks other cron tasks from running.
**Why it happens:** Processing an unknown number of items directly in hook_cron without time limits.
**How to avoid:** Use QueueWorker with `cron = {"time" = N}` to process queue items with a time budget. Add items to queue elsewhere, process during cron with automatic time management.
**Warning signs:** Cron taking excessively long, other modules' cron tasks delayed.

### Pitfall 6: Forgetting hook_views_data Table Group
**What goes wrong:** Views fields appear ungrouped in the UI, making them hard to find among hundreds of available fields.
**Why it happens:** Omitting the `$data['table_name']['table']['group']` key in hook_views_data.
**How to avoid:** Always set a group label (e.g., `t('Sports')`) for each table definition. This groups all fields from that table in the Views UI.
**Warning signs:** Custom fields scattered in Views field picker without logical grouping.

## Code Examples

Verified patterns from book source (Ch 14, 15, 3):

### Exposing Entity Type to Views (One Line)

```php
// Source: Book Ch 15 - Entities in Views
// In entity type annotation handlers array:
"views_data" = "Drupal\views\EntityViewsData"

// Or with custom overrides:
"views_data" = "Drupal\products\Entity\ProductViewsData"
```

### hook_views_data -- Basic Table and Field Definition

```php
// Source: Book Ch 15 - Views data
/**
 * Implements hook_views_data().
 */
function sports_views_data() {
  $data = [];

  // Define table.
  $data['players']['table']['group'] = t('Sports');
  $data['players']['table']['base'] = [
    'field' => 'id',
    'title' => t('Players'),
    'help' => t('Contains player data.'),
  ];

  // Numeric field.
  $data['players']['id'] = [
    'title' => t('ID'),
    'help' => t('The unique player ID.'),
    'field' => [
      'id' => 'numeric',
    ],
  ];

  // Text field.
  $data['players']['name'] = [
    'title' => t('Name'),
    'help' => t('The name of the player.'),
    'field' => [
      'id' => 'standard',
    ],
  ];

  return $data;
}
```

### Views Relationship (Join)

```php
// Source: Book Ch 15 - Views relationships
$data['players']['team_id'] = [
  'title' => t('Team ID'),
  'help' => t('The unique team ID of the player.'),
  'field' => [
    'id' => 'numeric',
  ],
  'relationship' => [
    'base' => 'teams',
    'base field' => 'id',
    'id' => 'standard',
    'label' => t('Player team'),
  ],
];
```

### Views Sort and Filter

```php
// Source: Book Ch 15 - Views sorts and filters
$data['teams']['name'] = [
  'title' => t('Name'),
  'help' => t('The name of the team.'),
  'field' => [
    'id' => 'standard',
  ],
  'sort' => [
    'id' => 'standard',
  ],
  'filter' => [
    'id' => 'string',
  ],
];
```

### Custom ViewsField Plugin

```php
// Source: Book Ch 15 - Custom Views field
namespace Drupal\products\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field plugin that renders data about the Importer.
 *
 * @ViewsField("product_importer")
 */
class ProductImporter extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $product = $this->getEntity($values);
    // ... custom rendering logic
    return $this->sanitizeValue($importer->label());
  }
}
```

### Custom ViewsFilter Plugin (InOperator)

```php
// Source: Book Ch 15 - Custom Views filter
namespace Drupal\sports\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter which filters by the available teams.
 *
 * @ViewsFilter("team_filter")
 */
class TeamFilter extends InOperator {

  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = $this->t('Teams');
    $this->definition['options callback'] = [$this, 'getTeams'];
  }

  public function getTeams() {
    $result = $this->database->query("SELECT [name] FROM {teams}")->fetchAllAssoc('name');
    $teams = array_keys($result);
    return array_combine($teams, $teams);
  }
}
```

### BatchBuilder Setup

```php
// Source: Book Ch 14 - Batch operations
use Drupal\Core\Batch\BatchBuilder;

$batch_builder = (new BatchBuilder())
  ->setTitle($this->t('Importing products'))
  ->setFinishCallback([$this, 'importProductsFinished']);
$batch_builder->addOperation([$this, 'clearMissing'], [$products]);
$batch_builder->addOperation([$this, 'importProducts'], [$products]);
batch_set($batch_builder->toArray());

// For Drush context:
if (PHP_SAPI == 'cli') {
  drush_backend_batch_process();
}
```

### Batch Operation with Multi-Request Processing

```php
// Source: Book Ch 14 - Batch operations
public function importProducts($products, &$context) {
  if (!isset($context['results']['imported'])) {
    $context['results']['imported'] = [];
  }
  if (!$products) { return; }

  $sandbox = &$context['sandbox'];
  if (!$sandbox) {
    $sandbox['progress'] = 0;
    $sandbox['max'] = count($products);
    $sandbox['products'] = $products;
  }

  $slice = array_splice($sandbox['products'], 0, 3);
  foreach ($slice as $product) {
    $context['message'] = $this->t('Importing product @name', ['@name' => $product->name]);
    $this->persistProduct($product);
    $context['results']['imported'][] = $product->name;
    $sandbox['progress']++;
  }

  $context['finished'] = $sandbox['progress'] / $sandbox['max'];
}
```

### Batch Finished Callback

```php
// Source: Book Ch 14 - Batch operations
public function importProductsFinished($success, $results, $operations) {
  if (!$success) {
    $this->messenger->addStatus($this->t('There was a problem with the batch'), 'error');
    return;
  }

  $imported = count($results['imported']);
  $this->messenger->addStatus($this->formatPlural(
    $imported, '1 product imported.', '@count products imported.'
  ));
}
```

### hook_cron Implementation

```php
// Source: Book Ch 14 - Cron
/**
 * Implements hook_cron().
 */
function sports_cron() {
  $database = \Drupal::database();
  $result = $database->query(
    "SELECT [id] FROM {teams} WHERE [id] NOT IN (SELECT [team_id] FROM {players} WHERE [team_id] IS NOT NULL)"
  )->fetchAllAssoc('id');
  if (!$result) { return; }
  $ids = array_keys($result);
  $database->delete('teams')
    ->condition('id', $ids, 'IN')
    ->execute();
}
```

### QueueWorker Plugin (Cron-Based)

```php
// Source: Book Ch 14 - Cron-based queues
namespace Drupal\sports\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * A worker plugin that removes a team from the database.
 *
 * @QueueWorker(
 *   id = "team_cleaner",
 *   title = @Translation("Team Cleaner"),
 *   cron = {"time" = 10}
 * )
 */
class TeamCleaner extends QueueWorkerBase implements ContainerFactoryPluginInterface {

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

### Adding Items to a Queue

```php
// Source: Book Ch 14 - Cron-based queues
$queue = \Drupal::queue('team_cleaner');
$item = new \stdClass();
$item->id = $team_id;
$queue->createItem($item);
```

### Programmatic Queue Processing

```php
// Source: Book Ch 14 - Processing a queue programmatically
$queue = \Drupal::queue('team_cleaner');
$queue_worker = \Drupal::service('plugin.manager.queue_worker')
  ->createInstance('team_cleaner');

while ($item = $queue->claimItem()) {
  try {
    $queue_worker->processItem($item->data);
    $queue->deleteItem($item);
  } catch (SuspendQueueException $e) {
    $queue->releaseItem($item);
    break;
  } catch (\Exception $e) {
    // Log the exception.
  }
}
```

### Lock API Usage

```php
// Source: Book Ch 14 - The Lock API
if (!$this->lock->acquire($plugin->getPluginId())) {
  $this->logger()->log('notice', $this->t(
    'The plugin @plugin is already running.',
    ['@plugin' => $plugin->getPluginDefinition()['label']]
  ));
  return;
}

// ... do processing ...

$this->lock->release($plugin->getPluginId());
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Manual batch arrays | BatchBuilder class | D8.6+ | Cleaner API, fluent interface |
| hook_cron for all background work | QueueWorker plugins with cron time budget | D8+ | Time-bounded, plugin-based |
| Views handlers (D7 terminology) | Views plugins (D8+ terminology) | D8 | Plugin system, annotations/attributes |
| views_data in .views.inc files | hook_views_data() or EntityViewsData handler | D8 | Hook system or entity handler |
| drupal_mail() function | Mail plugin manager service | D8+ | Plugin-based, injectable |
| watchdog() function | Logger channels (PSR-3) | D8+ | Standardized, pluggable |

**Deprecated/outdated:**
- `watchdog()`: Use logger channels (`\Drupal::logger('channel')->error()`)
- `drupal_mail()`: Use `\Drupal::service('plugin.manager.mail')->mail()`
- Views "handlers" terminology: Now "plugins" in D8+
- Manual batch definition arrays: Use BatchBuilder for cleaner code

## Open Questions

1. **D11 PHP attributes for Views plugins**
   - What we know: D10 uses @ViewsField, @ViewsFilter, @ViewsArgument annotations. D11 supports PHP attributes for plugin discovery.
   - What's unclear: Exact D11 attribute syntax for Views plugins (no D11-specific Views attribute examples in book).
   - Recommendation: Show D10 annotation syntax as primary with a note about D11 attribute availability. The pattern follows the same structure as other plugin types already documented in Phase 2 skills.

2. **Reference file scope for logging/mail/tokens**
   - What we know: Ch 3 covers logging (channels, custom loggers), mail (hook_mail, mail plugins, altering), and tokens (hook_token_info, hook_tokens, Token service). Large surface area.
   - What's unclear: How much fits in the reference file while staying useful.
   - Recommendation: Focus reference file on: (1) logger channel setup and usage patterns, (2) hook_mail + sending via mail manager, (3) hook_token_info + hook_tokens for custom tokens. Skip custom mail plugin creation (niche use case) and custom logger implementation (rarely needed).

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Manual validation via automated shell checks |
| Config file | None -- skills are markdown files, not executable code |
| Quick run command | `wc -l < skills/drupal-*/SKILL.md` (line count check) |
| Full suite command | `for f in skills/drupal-views-dev/SKILL.md skills/drupal-batch-queue-cron/SKILL.md; do echo "$f:"; wc -l < "$f"; grep -c "WRONG:" "$f"; grep -c "if installed\|if available" "$f"; done` |

### Phase Requirements to Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| SPEC-01 | drupal-views-dev SKILL.md valid | automated check | `test -f skills/drupal-views-dev/SKILL.md && wc -l < skills/drupal-views-dev/SKILL.md` | Wave 0 |
| SPEC-02 | drupal-batch-queue-cron SKILL.md valid | automated check | `test -f skills/drupal-batch-queue-cron/SKILL.md && wc -l < skills/drupal-batch-queue-cron/SKILL.md` | Wave 0 |
| SPEC-02 | logging-mail-tokens.md reference file | automated check | `test -f skills/drupal-batch-queue-cron/references/logging-mail-tokens.md` | Wave 0 |

### Sampling Rate
- **Per task commit:** Line count + wrong-way callout count + cross-ref count for modified skill
- **Per wave merge:** Full suite check across both new skills
- **Phase gate:** All skills exist, under 500 lines, minimum callouts/cross-refs, frontmatter valid

### Wave 0 Gaps
- [ ] `skills/drupal-views-dev/` directory and SKILL.md
- [ ] `skills/drupal-batch-queue-cron/` directory and SKILL.md
- [ ] `skills/drupal-batch-queue-cron/references/logging-mail-tokens.md`

## Sources

### Primary (HIGH confidence)
- Book source: "Sipos D. Drupal 10 Module Development, 4th ed, 2023" -- Ch 15 (Views), Ch 14 (Batches, Queues, and Cron), Ch 3 (Logging, Mailing, Tokens)
- Existing skills in `skills/` directory -- 11 established skills proving the SKILL.md anatomy, frontmatter, wrong-way callouts, cross-references, reference files patterns
- Phase 3 research and plans -- established execution patterns for skill creation

### Secondary (MEDIUM confidence)
- Drupal.org Views API docs for D10/D11 plugin attribute verification
- Drupal.org Queue API docs for current best practices

### Tertiary (LOW confidence)
- D11 Views plugin attribute syntax -- inferred from other plugin type patterns, not verified against D11 source

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - all APIs documented in book source with complete code examples
- Architecture: HIGH - established skill patterns from Phases 1-3, directory structure proven across 11 skills
- Pitfalls: HIGH - book explicitly demonstrates common mistakes (missing query() override, batch $context confusion, queue name mismatch)

**Research date:** 2026-03-05
**Valid until:** 2026-04-05 (stable APIs, book content is static)

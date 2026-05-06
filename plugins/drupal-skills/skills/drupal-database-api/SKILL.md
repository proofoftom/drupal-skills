---
name: drupal-database-api
description: |
  Work with Drupal's database abstraction layer for custom tables and direct SQL queries.
  Use when asked to create custom database tables via hook_schema(), write dynamic or
  static select/insert/update/delete queries, define database schema, or build
  hook_update_N() for schema changes. Covers tagged queries and query alter hooks.
  Do NOT use for entity data -- use drupal-entities-fields and Entity Query instead.
  Do NOT use for Views query handlers (use drupal-views-dev instead).
---

# Drupal Database API

The Database API is stable across Drupal 10 and 11. No syntax differences between versions.

## How should you access data?

Before writing any database query, determine the correct approach:

**Working with entities** (nodes, users, taxonomy terms, custom entities)?
-> Use Entity Query or entity storage `loadMultiple()`. NOT direct database queries.

**Need complex SQL that Entity Query cannot handle?**
-> Use Database API to find entity IDs, then load entities via storage handler.

**Custom tables** (non-entity data like logs, statistics, integration data)?
-> Define with `hook_schema()`, query with Database API.

**Simple one-off query against a custom table?**
-> Static query with placeholders.

**Complex, alterable query against custom tables?**
-> Dynamic query builder.

> WRONG: Using direct SQL for entity data. `$database->query("SELECT * FROM {node_field_data} WHERE title = :t", [':t' => $title])` bypasses access control, field storage abstraction, and cache invalidation. Use `\Drupal::entityQuery('node')` or `\Drupal::entityTypeManager()->getStorage('node')->loadByProperties()` instead.

## Getting the database connection

**Preferred -- inject via dependency injection:**

```php
// In a service class or controller:
use Drupal\Core\Database\Connection;

class MyService {
  public function __construct(
    protected readonly Connection $database,
  ) {}

  public function getRecords(): array {
    return $this->database->select('my_table', 'm')
      ->fields('m')
      ->execute()
      ->fetchAll();
  }
}
```

```yaml
# my_module.services.yml
services:
  my_module.my_service:
    class: Drupal\my_module\MyService
    arguments: ['@database']
```

**Static fallback** (only in `.module` files or procedural code):

```php
$database = \Drupal::database();
```

## hook_schema() -- defining custom tables

Implement in the `.install` file. Tables are created on module install, dropped on uninstall.

```php
<?php
// my_module.install

/**
 * Implements hook_schema().
 */
function my_module_schema(): array {
  $schema = [];

  $schema['my_module_teams'] = [
    'description' => 'Stores team data.',
    'fields' => [
      'id' => [
        'description' => 'Primary identifier.',
        'type' => 'serial',
        'unsigned' => TRUE,
        'not null' => TRUE,
      ],
      'name' => [
        'description' => 'The team name.',
        'type' => 'varchar',
        'length' => 255,
        'not null' => TRUE,
      ],
      'description' => [
        'description' => 'The team description.',
        'type' => 'text',
        'size' => 'normal',
      ],
      'score' => [
        'description' => 'Team score.',
        'type' => 'int',
        'unsigned' => TRUE,
        'default' => 0,
      ],
    ],
    'primary key' => ['id'],
    'indexes' => [
      'name' => ['name'],
    ],
  ];

  $schema['my_module_players'] = [
    'description' => 'Stores player data.',
    'fields' => [
      'id' => [
        'type' => 'serial',
        'unsigned' => TRUE,
        'not null' => TRUE,
      ],
      'team_id' => [
        'type' => 'int',
        'unsigned' => TRUE,
      ],
      'name' => [
        'type' => 'varchar',
        'length' => 255,
        'not null' => TRUE,
      ],
      'data' => [
        'type' => 'blob',
        'size' => 'big',
      ],
    ],
    'primary key' => ['id'],
  ];

  return $schema;
}
```

### Schema field types

| Type | Use for | Key properties |
|------|---------|---------------|
| `serial` | Auto-increment integer (primary keys) | `unsigned` |
| `varchar` | Short strings (up to 255 chars) | `length` (required) |
| `text` | Long strings | `size`: tiny, small, medium, normal, big |
| `int` | Integers | `size`: tiny, small, medium, normal, big; `unsigned` |
| `float` | Floating point numbers | `size`: tiny, small, medium, normal, big |
| `numeric` | Exact decimals (money, coordinates) | `precision`, `scale` |
| `blob` | Binary/serialized data | `size`: normal, big |

### Common field properties

- `type` -- required, the column type
- `length` -- required for varchar
- `size` -- tiny, small, medium, normal (default), big
- `not null` -- TRUE to prevent NULL values
- `default` -- default value for the column
- `unsigned` -- TRUE for non-negative numbers
- `description` -- documents the column purpose

## Static queries -- simple, direct SQL

Use for straightforward queries against custom tables:

```php
$result = $database->query(
  "SELECT [name], [score] FROM {my_module_teams} WHERE [score] > :min_score",
  [':min_score' => 100]
);
```

**Syntax rules:**
- `{table_name}` -- curly braces around table names (adds prefix automatically)
- `[column_name]` -- square brackets around column names (protects against reserved words)
- `:placeholder` -- named placeholders with colon prefix (replaced safely by the API)

> WRONG: Concatenating user input into SQL. `$database->query("SELECT * FROM {users} WHERE name = '$name'")` is a SQL injection vulnerability. ALWAYS use placeholders: `$database->query("SELECT * FROM {my_table} WHERE [name] = :name", [':name' => $name])`.

### Handling results

```php
// Iterate directly:
foreach ($result as $record) {
  $name = $record->name;
  $score = $record->score;
}

// Or use fetch methods:
$all = $result->fetchAll();           // Array of stdClass objects
$keyed = $result->fetchAllAssoc('id'); // Array keyed by field value
$single = $result->fetchField();       // Single value from first column
$row = $result->fetchAssoc();          // Single row as associative array
$column = $result->fetchCol();         // Array of values from first column
```

## Dynamic select queries -- complex, alterable

Use the query builder for complex queries that other modules might need to alter:

```php
$query = $database->select('my_module_teams', 't');
$query->fields('t', ['id', 'name', 'score']);
$query->condition('score', 50, '>');
$query->orderBy('score', 'DESC');
$query->range(0, 10);
$result = $query->execute();

foreach ($result as $record) {
  // $record->id, $record->name, $record->score
}
```

### Key methods

- `->fields('alias', ['col1', 'col2'])` -- select specific columns (omit second arg for all)
- `->addField('alias', 'column', 'column_alias')` -- add individual field with alias
- `->condition('column', $value, '=')` -- operator defaults to `=`
- `->condition('column', [$v1, $v2], 'IN')` -- IN clause
- `->condition('column', NULL, 'IS NOT NULL')` -- NULL checks
- `->isNull('column')` / `->isNotNull('column')` -- alternative NULL checks
- `->orderBy('column', 'ASC')` -- sort results (ASC or DESC)
- `->range($offset, $limit)` -- LIMIT/OFFSET equivalent
- `->countQuery()` -- returns a count query from the current query
- `->execute()` -- runs the query, returns result set

## Joins

```php
$query = $database->select('my_module_players', 'p');
$query->join('my_module_teams', 't', 't.id = p.team_id');
$query->addField('p', 'name', 'player_name');
$query->addField('t', 'name', 'team_name');
$query->addField('t', 'description', 'team_description');
$query->fields('p', ['id', 'data']);
$query->condition('p.id', 1);
$result = $query->execute()->fetchAll();
```

Join methods (not chainable -- call on `$query` directly):
- `$query->join('table', 'alias', 'condition')` -- INNER JOIN
- `$query->innerJoin('table', 'alias', 'condition')` -- same as join()
- `$query->leftJoin('table', 'alias', 'condition')` -- LEFT JOIN
- `$query->rightJoin('table', 'alias', 'condition')` -- RIGHT JOIN

When joining tables with same column names, use `addField()` with aliases to avoid conflicts.

## Pager queries

Use `PagerSelectExtender` for automatic pagination:

```php
$query = $database->select('my_module_players', 'p')
  ->fields('p')
  ->extend(\Drupal\Core\Database\Query\PagerSelectExtender::class)
  ->limit(10);

$result = $query->execute()->fetchAll();

// Build render array with pager:
$build['table'] = [
  '#theme' => 'table',
  '#header' => $header,
  '#rows' => $rows,
];
$build['pager'] = [
  '#type' => 'pager',
];
```

The extender handles page detection, range calculation, and pager initialization automatically. Just call `->limit()` and add a `pager` render element to output.

## INSERT, UPDATE, DELETE, MERGE

### INSERT -- returns the insert ID

```php
$id = $database->insert('my_module_players')
  ->fields([
    'name' => 'New Player',
    'team_id' => 1,
    'data' => serialize(['position' => 'forward']),
  ])
  ->execute();
```

### UPDATE -- returns affected row count

```php
$affected = $database->update('my_module_players')
  ->fields(['name' => 'Updated Name'])
  ->condition('id', $id)
  ->execute();
```

### DELETE -- returns affected row count

```php
$affected = $database->delete('my_module_players')
  ->condition('id', $id)
  ->execute();
```

### MERGE (upsert) -- insert or update

```php
$database->merge('my_module_teams')
  ->keys(['id' => $team_id])
  ->fields([
    'name' => 'Team Name',
    'score' => 100,
  ])
  ->execute();
```

`->keys()` defines which fields identify an existing record. If found, it updates; if not, it inserts.

> WRONG: Running INSERT, UPDATE, or DELETE queries against entity tables (node_field_data, users_field_data, etc.). This bypasses entity hooks, access control, and cache invalidation. Always use entity storage: `$entity->save()` or `$entity->delete()`.

## Transactions

Wrap related operations in a transaction for all-or-nothing behavior:

```php
$transaction = $database->startTransaction();
try {
  $database->insert('my_module_teams')
    ->fields(['name' => 'New Team'])
    ->execute();
  $database->insert('my_module_players')
    ->fields(['name' => 'Player 1', 'team_id' => $team_id])
    ->execute();
}
catch (\Exception $e) {
  $transaction->rollBack();
  \Drupal::logger('my_module')->error($e->getMessage());
}
// Transaction commits automatically when $transaction goes out of scope.
```

## Query altering (hook_query_alter)

Queries opt in to alteration by adding tags:

```php
// In the module that builds the query:
$result = $database->select('my_module_players', 'p')
  ->fields('p')
  ->addTag('my_module_player_listing')
  ->execute();
```

```php
// In another module that wants to alter it:
// my_other_module.module

/**
 * Implements hook_query_TAG_alter().
 */
function my_other_module_query_my_module_player_listing_alter(
  Drupal\Core\Database\Query\AlterableInterface $query
): void {
  $query->condition('p.team_id', 5);
}
```

> WRONG: Altering queries without checking the tag. If you use `hook_query_alter()` instead of the tag-specific `hook_query_TAG_alter()`, always check `$query->hasTag('expected_tag')` before modifying. Altering unrelated queries causes subtle, hard-to-debug data issues.

Entity queries are automatically tagged (e.g., `entity_query`, `node_access`), which is how contributed modules like node access modules filter results.

## Update hooks for schema changes

When you need to modify an existing table after module installation, write an update hook in the `.install` file. Also update `hook_schema()` so fresh installs get the new schema.

```php
/**
 * Add the "location" field to the teams table.
 */
function my_module_update_10001(&$sandbox): void {
  $field = [
    'description' => 'The team location.',
    'type' => 'varchar',
    'length' => 255,
  ];
  $schema = \Drupal::database()->schema();
  $schema->addField('my_module_teams', 'location', $field);
}
```

> WRONG: Modifying `hook_schema()` without writing an update hook. `hook_schema()` only runs on fresh install. Existing sites will never see your schema change. Always pair schema changes with an update hook.

### Schema change methods

Use `\Drupal::database()->schema()` for DDL operations inside update hooks:

- `->addField('table', 'field', $spec)` -- add a column
- `->changeField('table', 'old_name', 'new_name', $spec)` -- modify a column
- `->dropField('table', 'field')` -- remove a column
- `->addIndex('table', 'name', ['fields'], $spec)` -- add an index
- `->dropIndex('table', 'name')` -- remove an index
- `->addUniqueKey('table', 'name', ['fields'])` -- add unique constraint
- `->dropTable('table')` -- remove entire table
- `->tableExists('table')` -- check if table exists
- `->fieldExists('table', 'field')` -- check if column exists

### Batch updates with $sandbox

For large data migrations in update hooks, use `$sandbox` for progress:

```php
/**
 * Migrate legacy data in batches.
 */
function my_module_update_10002(&$sandbox): void {
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['max'] = \Drupal::database()
      ->select('my_module_legacy', 'l')
      ->countQuery()
      ->execute()
      ->fetchField();
  }

  $records = \Drupal::database()
    ->select('my_module_legacy', 'l')
    ->fields('l')
    ->range($sandbox['progress'], 50)
    ->execute();

  foreach ($records as $record) {
    // Process each record...
    $sandbox['progress']++;
  }

  $sandbox['#finished'] = $sandbox['max'] > 0
    ? $sandbox['progress'] / $sandbox['max']
    : 1;
}
```

Set `$sandbox['#finished']` to a float between 0 and 1. The update system re-calls the function until it reaches 1.

### Update hook numbering

- Drupal 10: start at `10001` (schema version base is 10000)
- Drupal 11: start at `11001` (schema version base is 11000)
- Increment sequentially: 10001, 10002, 10003...
- The DocBlock comment describes the update (shown in Drush output)

> WRONG: Using the same update hook number twice or skipping numbers. Update hooks run in order and each number runs exactly once. Reusing a number means the second implementation never runs.

## Cross-references

See also: **drupal-entities-fields** (if installed) for Entity Query and entity storage patterns. Use Entity Query for ALL entity data -- Database API is only for custom tables. If not available, use `\Drupal::entityQuery('entity_type')` for entity queries and `\Drupal::entityTypeManager()->getStorage('type')->loadMultiple($ids)` for loading.

See also: **drupal-testing** (if installed) for Kernel tests that verify database operations with `$this->installSchema('module', ['table'])`. If not available, Kernel tests require `protected static $modules` array listing your module and `$this->installSchema()` in `setUp()` to create test tables.

See also: **drupal-module-scaffold** (if installed) for `.install` file patterns where `hook_schema()` and update hooks live. If not available, create `module_name.install` alongside `module_name.info.yml`.

See also: **drupal-caching** (if installed) for cache tag invalidation when custom table data changes. Use `Cache::invalidateTags(['my_module_data_list'])` after writes to ensure cached render arrays update. If not available, use `\Drupal\Core\Cache\Cache::invalidateTags()` directly.

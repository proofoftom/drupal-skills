---
name: drupal-views-dev
description: |
  Integrate custom data with Drupal Views and build custom Views plugins.
  Use when asked to expose a database table or entity type to Views, create custom
  Views field/filter/sort handlers, or alter existing Views data definitions.
---

# Drupal Views Development

## How do you expose data to Views?

**Is it a content entity type?**
YES -> Add `"views_data"` handler to entity annotation/attribute. See "Entity Views integration" below.
  - Default fields sufficient? -> Use `Drupal\views\EntityViewsData` directly
  - Need custom fields or overrides? -> Extend `EntityViewsData`, override `getViewsData()`

**Is it a custom database table (non-entity)?**
YES -> Implement `hook_views_data()`. See "hook_views_data()" below.
  - Define table group, base table, and fields with plugin responsibilities
  - Each field can have: field, filter, sort, argument, relationship

**Modifying existing Views data?**
YES -> Implement `hook_views_data_alter()`. See "Altering Views data" below.
  - Add virtual fields to existing tables (e.g., `node_field_data`)
  - Change plugin IDs for existing fields

> WRONG: Writing `hook_views_data()` for content entities. This manually duplicates what `EntityViewsData` does automatically -- generating Views integration for all base fields, filters, sorts, arguments, and relationships out of the box.
> RIGHT: Use the `"views_data"` handler in the entity annotation/attribute. Only use `hook_views_data()` for custom (non-entity) database tables created via `hook_schema()`.

## Entity Views integration

Add a `views_data` handler to the entity type annotation or attribute. This single line gives Views full access to all entity base fields.

**Default handler (no customization needed):**

```php
// In entity annotation handlers array:
"views_data" = "Drupal\views\EntityViewsData",
```

**Custom handler (add extra fields or override definitions):**

```php
// In entity annotation handlers array:
"views_data" = "Drupal\my_module\Entity\MyEntityViewsData",
```

Custom handler class:

```php
namespace Drupal\my_module\Entity;

use Drupal\views\EntityViewsData;

class MyEntityViewsData extends EntityViewsData {

  public function getViewsData() {
    $data = parent::getViewsData();
    // Add custom fields or modify existing definitions.
    $data['my_entity']['custom_field'] = [
      'title' => $this->t('Custom Field'),
      'help' => $this->t('A computed field with custom rendering.'),
      'field' => [
        'id' => 'my_custom_field_plugin',
      ],
    ];
    return $data;
  }

}
```

See also: **drupal-entities-fields** (if installed) for entity type definitions, annotation/attribute syntax, and the handlers array where `views_data` is declared. If not available, add `"views_data" = "Drupal\views\EntityViewsData"` to the entity annotation `handlers` array alongside `storage`, `form`, `list_builder`, etc.

## hook_views_data() -- exposing a custom table

Use this for custom database tables that are NOT entity tables. Returns an array describing tables, fields, and their Views plugin responsibilities.

```php
/**
 * Implements hook_views_data().
 */
function my_module_views_data() {
  $data = [];

  // Table group -- ALWAYS set for UI organization.
  $data['players']['table']['group'] = t('Sports');

  // Base table -- makes this table available as a Views base.
  $data['players']['table']['base'] = [
    'field' => 'id',
    'title' => t('Players'),
    'help' => t('Contains player data.'),
  ];

  // Numeric field with filter and sort.
  $data['players']['id'] = [
    'title' => t('ID'),
    'help' => t('The unique player ID.'),
    'field' => [
      'id' => 'numeric',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  // Text field with filter and sort.
  $data['players']['name'] = [
    'title' => t('Name'),
    'help' => t('The player name.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  return $data;
}
```

> WRONG: Omitting the table group in `hook_views_data()`. Without `$data['table']['table']['group']`, fields scatter across the Views UI among hundreds of other fields with no logical grouping, making them impossible for site builders to find.
> RIGHT: Always set `$data['table_name']['table']['group'] = t('Label')` to group all fields from your table together in the Views field picker.

See also: **drupal-database-api** (if installed) for `hook_schema()` to define the custom tables that `hook_views_data()` exposes. If not available, define tables in your `.install` file via `hook_schema()` returning table definitions with fields, primary keys, and indexes.

## Field responsibilities in hook_views_data()

Each column entry can have multiple plugin responsibilities:

| Key | Purpose | Common Plugin IDs |
|-----|---------|-------------------|
| `field` | How to display the value | `numeric`, `standard`, `date`, `boolean`, `serialized` |
| `filter` | How to filter by this column | `string`, `numeric`, `boolean`, `date`, `in_operator`, `bundle` |
| `sort` | How to sort by this column | `standard`, `date` |
| `argument` | How to use as contextual filter | `numeric`, `string`, `standard` |
| `relationship` | JOIN to another table | `standard` (see "Relationships" below) |

**Choosing a field plugin:**
- Numeric data -> `'id' => 'numeric'`
- Plain text -> `'id' => 'standard'` (outputs with sanitization)
- Serialized data -> `'id' => 'serialized'`
- Date/timestamp -> `'id' => 'date'`
- Boolean -> `'id' => 'boolean'`
- Custom rendering logic -> Create your own ViewsField plugin (see below)

## Relationships (JOINs between tables)

Define relationships in `hook_views_data()` via the `relationship` key to JOIN tables.

```php
$data['players']['team_id'] = [
  'title' => t('Team ID'),
  'help' => t('The team this player belongs to.'),
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

- `base`: the target table to JOIN
- `base field`: the column in the target table to match against
- `id`: the relationship plugin (`standard` for simple JOINs)
- `label`: the UI label shown in the Views relationship configuration

## Custom ViewsField plugin

**When:** Computed data, cross-entity lookups, custom rendering that no built-in plugin handles.

**Namespace:** `Drupal\my_module\Plugin\views\field`
**File:** `src/Plugin/views/field/MyField.php`
**Extends:** `Drupal\views\Plugin\views\field\FieldPluginBase`

### D10 annotation syntax

```php
namespace Drupal\my_module\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field plugin that renders computed data.
 *
 * @ViewsField("my_module_computed")
 */
class ComputedField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Leave empty -- this field has no database column.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $this->getEntity($values);
    // Custom rendering logic using entity data.
    return $this->sanitizeValue($entity->label());
  }

}
```

### D11 attribute syntax

```php
namespace Drupal\my_module\Plugin\views\field;

use Drupal\views\Attribute\ViewsField;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

#[ViewsField("my_module_computed")]
class ComputedField extends FieldPluginBase {

  public function query() {
    // Leave empty -- this field has no database column.
  }

  public function render(ResultRow $values) {
    $entity = $this->getEntity($values);
    return $this->sanitizeValue($entity->label());
  }

}
```

> WRONG: Forgetting to override `query()` for virtual fields. If your ViewsField has no database column (computed/virtual data), you MUST override `query()` with an empty method body. Otherwise Views adds a non-existent column to the SQL query, causing a database error like "Unknown column 'table.field_name' in 'field list'".
> RIGHT: Override `query()` with an empty body for any ViewsField plugin that renders data not stored in the table. Use `$this->getEntity($values)` in `render()` to access the entity and compute your output.

### Field configuration (defineOptions + buildOptionsForm)

When your custom field needs user-configurable options:

```php
use Drupal\Core\Form\FormStateInterface;

protected function defineOptions() {
  $options = parent::defineOptions();
  $options['display_mode'] = ['default' => 'label'];
  return $options;
}

public function buildOptionsForm(&$form, FormStateInterface $form_state) {
  $form['display_mode'] = [
    '#type' => 'select',
    '#title' => $this->t('Display mode'),
    '#options' => [
      'label' => $this->t('Label'),
      'id' => $this->t('ID'),
    ],
    '#default_value' => $this->options['display_mode'],
  ];
  parent::buildOptionsForm($form, $form_state);
}
```

Access in `render()`: `$this->options['display_mode']`

> WRONG: Adding custom plugin options via `defineOptions()`/`buildOptionsForm()` without defining configuration schema. Views plugins are stored as part of View config entities. Missing schema causes config export/import failures and strict validation errors.
> RIGHT: Define schema in `my_module.schema.yml` using dynamic types:

```yaml
# my_module.schema.yml
views.field.my_module_computed:
  type: views_field
  label: 'My Module Computed Field'
  mapping:
    display_mode:
      type: string
      label: 'Display mode'
```

The `views_field` base type inherits all standard field options. You only need to define your custom options in `mapping`.

## Custom ViewsFilter plugin

**Common pattern:** Extend `InOperator` for select-list filters (e.g., filter by team, category, status).

**Namespace:** `Drupal\my_module\Plugin\views\filter`
**File:** `src/Plugin/views/filter/MyFilter.php`

### D10 annotation syntax

```php
namespace Drupal\my_module\Plugin\views\filter;

use Drupal\Core\Database\Connection;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter which filters by available teams.
 *
 * @ViewsFilter("team_filter")
 */
class TeamFilter extends InOperator {

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

  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = $this->t('Teams');
    $this->definition['options callback'] = [$this, 'getTeams'];
  }

  public function getTeams() {
    $result = $this->database->query("SELECT [name] FROM {teams}")
      ->fetchAllAssoc('name');
    $teams = array_keys($result);
    return array_combine($teams, $teams);
  }

}
```

### D11 attribute syntax

```php
namespace Drupal\my_module\Plugin\views\filter;

use Drupal\views\Attribute\ViewsFilter;
// ... same use statements as D10 ...

#[ViewsFilter("team_filter")]
class TeamFilter extends InOperator {
  // Constructor, create(), init(), getTeams() are IDENTICAL to D10 version.
  // Only the annotation/attribute syntax at the top of the class changes.
}
```

**Filter configuration schema** -- InOperator filters need a `views.filter_value` schema entry:

```yaml
# my_module.schema.yml
views.filter.team_filter:
  type: views_filter
  mapping:
    value:
      type: sequence
      label: 'Teams'

views.filter_value.team_filter:
  type: sequence
  label: 'Teams'
  sequence:
    type: string
    label: 'Team'
```

The `views.filter_value.[plugin_id]` type is referenced dynamically from the `views_filter` base type's `value` key definition.

See also: **drupal-plugins-blocks** (if installed) for plugin discovery patterns, D10 annotations vs D11 attributes, `ContainerFactoryPluginInterface` for DI in plugins, and the 4-parameter `create()` signature. If not available, inject services via `ContainerFactoryPluginInterface::create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)`.

## Custom ViewsArgument plugin

**When:** Contextual filters need custom query logic (e.g., filtering by name OR ID).

**Namespace:** `Drupal\my_module\Plugin\views\argument`
**File:** `src/Plugin/views/argument/MyArgument.php`

### D10 annotation syntax

```php
namespace Drupal\my_module\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\ArgumentPluginBase;

/**
 * Argument for filtering by a team.
 *
 * @ViewsArgument("team")
 */
class Team extends ArgumentPluginBase {

  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    $field = is_numeric($this->argument) ? 'id' : 'name';
    $this->query->addWhere(0, "$this->tableAlias.$field", $this->argument);
  }

}
```

### D11 attribute syntax

```php
use Drupal\views\Attribute\ViewsArgument;

#[ViewsArgument("team")]
class Team extends ArgumentPluginBase {
  // query() method is identical to D10 version.
}
```

## Altering Views data -- hook_views_data_alter()

Add virtual fields to existing entity tables or change plugin IDs for existing fields.

```php
/**
 * Implements hook_views_data_alter().
 */
function my_module_views_data_alter(&$data) {
  // Add a virtual field to the node table.
  $data['node_field_data']['my_disclaimer'] = [
    'title' => t('Disclaimer'),
    'help' => t('Shows a disclaimer message.'),
    'field' => [
      'id' => 'my_module_disclaimer',
    ],
  ];
}
```

Use cases:
- Add computed/virtual fields to core entity tables (node, user, taxonomy)
- Change the plugin ID for an existing field (e.g., swap `standard` for a custom filter)
- Add relationships between tables not connected by default

> WRONG: Using `hook_views_data()` to add fields to tables you do not own (like `node_field_data`). `hook_views_data()` defines NEW tables. To add fields to EXISTING tables defined by other modules, use `hook_views_data_alter()`.
> RIGHT: Use `hook_views_data_alter(&$data)` to modify or extend Views definitions from other modules. Use `hook_views_data()` only for tables your module owns.

## Summary of Views plugin namespaces

| Plugin Type | Namespace | Base Class | Annotation (D10) | Attribute (D11) |
|-------------|-----------|------------|-------------------|-----------------|
| Field | `Plugin\views\field` | `FieldPluginBase` | `@ViewsField("id")` | `#[ViewsField("id")]` |
| Filter | `Plugin\views\filter` | `FilterPluginBase` / `InOperator` | `@ViewsFilter("id")` | `#[ViewsFilter("id")]` |
| Sort | `Plugin\views\sort` | `SortPluginBase` | `@ViewsSort("id")` | `#[ViewsSort("id")]` |
| Argument | `Plugin\views\argument` | `ArgumentPluginBase` | `@ViewsArgument("id")` | `#[ViewsArgument("id")]` |

All Views plugins support DI via `ContainerFactoryPluginInterface` with the 4-parameter `create()` signature.

## Cross-references

See also: **drupal-entities-fields** (if installed) for entity type definitions and the handlers array where `views_data` handler is declared. If not available, add `"views_data" = "Drupal\views\EntityViewsData"` to the entity annotation `handlers` array.

See also: **drupal-plugins-blocks** (if installed) for plugin discovery patterns, D10 annotations vs D11 attributes, and `ContainerFactoryPluginInterface` for DI in plugins. If not available, inject services via `ContainerFactoryPluginInterface::create()` with the 4-parameter signature.

See also: **drupal-database-api** (if installed) for `hook_schema()` to define the custom tables that `hook_views_data()` exposes. If not available, define tables in your module's `.install` file via `hook_schema()`.

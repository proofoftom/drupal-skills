---
name: drupal-config-storage
description: |
  Store and manage Drupal configuration, state, and temporary data using the correct
  storage API. Use when asked to save module settings, create config/install YAML files,
  write config schemas, store runtime state values, manage temporary per-user data, or
  choose between Config API vs State API vs TempStore. Covers config/install vs
  config/optional, ConfigFactoryInterface, and config overrides.
  Do NOT use for form building (use drupal-forms-api for the form itself).
---

# Drupal Config, State, and TempStore

## Where should you store this data?

Drupal has three distinct storage APIs. Choosing the wrong one is one of the most common mistakes -- it causes broken config exports, lost settings, or unnecessary database bloat. Use this decision tree.

**Is it admin-configurable settings (site name, API keys, feature toggles, display options)?**
YES -> Use **Config API**. Stored in YAML, exportable between environments, requires a schema file.

**Is it system state (last cron run, a flag, an environment marker, an API token)?**
YES -> Use **State API**. Key/value store. NOT exportable. Environment-specific. No schema needed.

**Is it temporary per-user data (form drafts, wizard progress, multi-step data, content locks)?**
YES -> Use **TempStore**. Auto-expires. PrivateTempStore for per-user, SharedTempStore for multi-user coordination.

**Is it structured data with admin UI (products, workflows, content types)?**
YES -> Not a storage API -- use a config entity or content entity. See drupal-entities-fields (if installed) for entity type creation. If not available, config entities are PHP classes with ConfigEntityType annotation/attribute stored as exportable YAML.

> WRONG: Storing admin settings in State API. State is NOT exportable. If an admin configures an API endpoint URL or a feature toggle, that setting disappears when you export config to another environment. Admin settings belong in Config API.
> RIGHT: Use Config API for anything humans configure through the UI. Use State API only for runtime flags the application sets programmatically (like "last cron timestamp" or "system install time").

> WRONG: Using `variable_get()` / `variable_set()` to store values. These are Drupal 7 functions that do not exist in Drupal 10+.
> RIGHT: Use Config API (`$this->config('module.settings')->get('key')`) for exportable settings, or State API (`\Drupal::state()->get('key')`) for environment-specific runtime values. The D7 variable system was split into these two APIs in Drupal 8.

## Config API

The Config API stores settings as YAML files that can be exported and imported between environments (dev, staging, production). Every config object needs three things: a default values file, a schema file, and code that reads/writes the values.

### Reading config

**Immutable (read-only, includes overrides):**

```php
// Static (only in .module files or procedural code):
$config = \Drupal::config('my_module.settings');
$value = $config->get('api_endpoint');

// In classes (inject config.factory service):
$config = $this->configFactory->get('my_module.settings');
$value = $config->get('api_endpoint');
```

**Mutable (for saving changes, bypasses overrides):**

```php
$config = $this->configFactory->getEditable('my_module.settings');
$config->set('api_endpoint', 'https://example.com/api')->save();
```

### Injecting config.factory

Register it as a service argument:

```yaml
# my_module.services.yml
services:
  my_module.my_service:
    class: Drupal\my_module\MyService
    arguments: ['@config.factory']
```

```php
namespace Drupal\my_module;

use Drupal\Core\Config\ConfigFactoryInterface;

class MyService {

  protected ConfigFactoryInterface $configFactory;

  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  public function getEndpoint(): string {
    return $this->configFactory->get('my_module.settings')->get('api_endpoint') ?? '';
  }

}
```

### Default config values

Place default values in `config/install/my_module.settings.yml`. These are imported when the module is installed.

```yaml
# config/install/my_module.settings.yml
api_endpoint: 'https://api.example.com/v1'
items_per_page: 10
enable_caching: true
```

Optional config (imported only when dependencies are met) goes in `config/optional/`.

> WRONG: Creating a config form that saves values but providing no `config/install/` file. The config object does not exist until someone saves the form, so any code reading the config before that gets NULL for every key.
> RIGHT: Always provide a `config/install/my_module.settings.yml` with sensible defaults. This ensures the config object exists from the moment the module is installed.

## Config schema

Every config object MUST have a schema file. Config "works" without a schema in development, but without it:
- Config export/import validation fails silently
- Config translation cannot find translatable strings
- Type casting is unreliable (strings vs integers vs booleans)

> WRONG: Skipping the config schema file because "it works without one." Missing schema breaks config export/import validation, translation, and type casting. It is technical debt that bites when you move config between environments.
> RIGHT: Always create `config/schema/my_module.schema.yml` alongside any config object. Every config object needs a schema.

### Schema file structure

```yaml
# config/schema/my_module.schema.yml
my_module.settings:
  type: config_object
  label: 'My Module settings'
  mapping:
    api_endpoint:
      type: string
      label: 'API endpoint URL'
    items_per_page:
      type: integer
      label: 'Items per page'
    enable_caching:
      type: boolean
      label: 'Enable caching'
```

### Common schema types

| Type | Use for | Translatable? |
|------|---------|---------------|
| `string` | Short text, machine names, URLs | No |
| `text` | Longer text content | Yes (by default) |
| `label` | Human-readable labels, titles | Yes (by default) |
| `integer` | Whole numbers | No |
| `boolean` | True/false toggles | No |
| `float` | Decimal numbers | No |
| `email` | Email addresses | No |
| `uri` | URIs | No |
| `path` | File system paths | No |

> WRONG: Using `type: string` for a user-facing label that should be translatable. Plain `string` type is never translated. If admins see this text in the UI and it needs translation, it must be `type: label`.
> RIGHT: Use `type: label` for any human-readable text that should be translatable (setting labels, button text, display titles). Use `type: string` for machine-readable values (API keys, machine names, URLs).

### Nested mappings and sequences

```yaml
my_module.settings:
  type: config_object
  label: 'My Module settings'
  mapping:
    notifications:
      type: mapping
      label: 'Notification settings'
      mapping:
        enabled:
          type: boolean
          label: 'Enable notifications'
        recipients:
          type: sequence
          label: 'Notification recipients'
          sequence:
            type: email
            label: 'Email address'
```

Use `mapping` when keys matter (named properties). Use `sequence` when keys do not matter (ordered list of items).

### config_object vs config_entity

- `type: config_object` -- Simple config: a single set of settings (like `my_module.settings`). One instance per config name.
- `type: config_entity` -- Config entity: multiple instances with CRUD operations (like content types, views, image styles). See drupal-entities-fields (if installed) for config entity patterns. If not available, config entities require a PHP class with ConfigEntityType annotation/attribute and a schema using `type: config_entity`.

### Schema properties

- `translatable: true` -- marks a field as translatable (already set by default on `label` and `text` types)
- `nullable: true` -- allows the value to be empty/null (fields are required by default)

For configuration translation patterns, see references/i18n.md in this skill directory.

## Complete Config API example

Here is a full file ecosystem for a module with config settings.

### File tree

```
modules/custom/weather_widget/
  weather_widget.info.yml
  weather_widget.routing.yml
  weather_widget.services.yml
  config/
    install/
      weather_widget.settings.yml
    schema/
      weather_widget.schema.yml
  src/
    Form/
      SettingsForm.php
    WeatherService.php
```

### config/install/weather_widget.settings.yml

```yaml
api_key: ''
location: 'London'
units: 'metric'
cache_lifetime: 3600
```

### config/schema/weather_widget.schema.yml

```yaml
weather_widget.settings:
  type: config_object
  label: 'Weather Widget settings'
  mapping:
    api_key:
      type: string
      label: 'API key'
    location:
      type: label
      label: 'Default location'
    units:
      type: string
      label: 'Temperature units'
    cache_lifetime:
      type: integer
      label: 'Cache lifetime in seconds'
```

### src/WeatherService.php (reads config)

```php
namespace Drupal\weather_widget;

use Drupal\Core\Config\ConfigFactoryInterface;

class WeatherService {

  protected ConfigFactoryInterface $configFactory;

  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  public function getWeather(): array {
    $config = $this->configFactory->get('weather_widget.settings');
    $api_key = $config->get('api_key');
    $location = $config->get('location');
    $units = $config->get('units');
    // Use these values to call weather API...
    return [];
  }

}
```

### weather_widget.services.yml

```yaml
services:
  weather_widget.weather:
    class: Drupal\weather_widget\WeatherService
    arguments: ['@config.factory']
```

Notice how every config object has paired files: `config/install/*.yml` (default values) + `config/schema/*.schema.yml` (type definitions). The schema file name matches the module name, and the config name inside matches the install file name.

## State API

The State API is a key/value store for environment-specific data that is NOT exported between environments. Use it for runtime flags, timestamps, and system markers that the application sets programmatically.

### Reading and writing

```php
// Static (procedural code only):
\Drupal::state()->set('my_module.last_cron_run', \Drupal::time()->getRequestTime());
$timestamp = \Drupal::state()->get('my_module.last_cron_run', 0);

// Multiple at once:
\Drupal::state()->setMultiple([
  'my_module.last_run' => time(),
  'my_module.items_processed' => 42,
]);
$values = \Drupal::state()->getMultiple(['my_module.last_run', 'my_module.items_processed']);

// Delete:
\Drupal::state()->delete('my_module.last_cron_run');
\Drupal::state()->deleteMultiple(['my_module.last_run', 'my_module.items_processed']);
```

### Injecting the State service

```yaml
# my_module.services.yml
services:
  my_module.tracker:
    class: Drupal\my_module\ImportTracker
    arguments: ['@state']
```

```php
namespace Drupal\my_module;

use Drupal\Core\State\StateInterface;

class ImportTracker {

  protected StateInterface $state;

  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  public function getLastImport(): int {
    return $this->state->get('my_module.last_import', 0);
  }

  public function recordImport(int $timestamp, int $count): void {
    $this->state->setMultiple([
      'my_module.last_import' => $timestamp,
      'my_module.import_count' => $count,
    ]);
  }

}
```

### Key characteristics

- Stored in the `key_value` database table under the `state` collection
- No schema file needed
- NOT exportable -- values stay in the current environment
- Prefix keys with your module name: `my_module.my_key`
- Can store scalar values and serializable objects

## TempStore

TempStore provides temporary, auto-expiring storage for data that must persist across multiple requests but is not permanent. Entries expire after 7 days by default (604800 seconds, configurable globally in services).

### PrivateTempStore -- per-user temporary data

Use for: multi-step form data, wizard progress, draft content, user-specific temporary state.

Each entry is namespaced by user ID (or session ID for anonymous users), so two users can have different values for the same key without conflict.

```php
// Inject tempstore.private factory:
$factory = \Drupal::service('tempstore.private');
$store = $factory->get('my_module');

// Store data:
$store->set('wizard_step', 2);
$store->set('wizard_data', ['name' => 'Example', 'email' => 'a@b.com']);

// Read data:
$step = $store->get('wizard_step');
$data = $store->get('wizard_data');

// Delete when done:
$store->delete('wizard_step');
$store->delete('wizard_data');

// Read metadata (owner, timestamp):
$metadata = $store->getMetadata('wizard_data');
```

### SharedTempStore -- multi-user coordination

Use for: content locking, shared editing state, preventing simultaneous edits.

Entries are NOT namespaced by user. Any user can read any entry in the collection.

```php
// Inject tempstore.shared factory:
$factory = \Drupal::service('tempstore.shared');
$store = $factory->get('my_module');

// Store data (owned by current user):
$store->set('node_123_lock', ['editing' => TRUE]);

// Read data (any user can read):
$lock = $store->get('node_123_lock');

// Set only if no one else owns this entry:
$store->setIfNotExists('node_123_lock', ['editing' => TRUE]);

// Set only if current user owns it (overwrite own previous data):
$store->setIfOwner('node_123_lock', ['editing' => TRUE]);

// Read/delete only if owned by current user:
$data = $store->getIfOwner('node_123_lock');
$store->deleteIfOwner('node_123_lock');

// Check who owns it:
$metadata = $store->getMetadata('node_123_lock');
```

### Injecting TempStore in a service

```yaml
# my_module.services.yml
services:
  my_module.wizard:
    class: Drupal\my_module\WizardManager
    arguments: ['@tempstore.private']
```

```php
use Drupal\Core\TempStore\PrivateTempStoreFactory;

class WizardManager {

  public function __construct(protected PrivateTempStoreFactory $tempStoreFactory) {}

  public function saveProgress(string $step, array $data): void {
    $store = $this->tempStoreFactory->get('my_module_wizard');
    $store->set('current_step', $step);
    $store->set('form_data', $data);
  }

  public function clearProgress(): void {
    $store = $this->tempStoreFactory->get('my_module_wizard');
    $store->delete('current_step');
    $store->delete('form_data');
  }

}
```

## Config overrides

Config values can be overridden at three levels without changing the stored configuration.

### Environment-specific overrides (settings.php)

```php
// In settings.php -- NOT exported, environment-specific:
$config['my_module.settings']['api_key'] = 'production-secret-key';
$config['system.performance']['css']['preprocess'] = TRUE;
```

Global overrides take highest priority. Use for: API keys, environment-specific URLs, performance settings that differ per environment.

### Module overrides (ConfigFactoryOverrideInterface)

Create a service implementing `ConfigFactoryOverrideInterface` and tag it with `config.factory.override`. Implement `loadOverrides($names)` to return override values, `getCacheSuffix()` for cache keying, `createConfigObject()` (return NULL), and `getCacheableMetadata()`. Register with priority to control override order (higher = takes precedence).

```yaml
# my_module.services.yml
services:
  my_module.config_overrides:
    class: Drupal\my_module\MyConfigOverrides
    tags:
      - { name: config.factory.override, priority: 5 }
```

### Language overrides

Handled by the config translation system. See references/i18n.md in this skill directory.

### Override priority (highest to lowest)

1. Global overrides (`$config` in settings.php)
2. Module overrides (ConfigFactoryOverrideInterface services)
3. Language overrides (config translation system)

Important: `$this->config()` and `$factory->get()` return values WITH overrides applied. `$factory->getEditable()` returns the stored value WITHOUT overrides, so admins always see and edit the real stored values.

## D10/D11 compatibility notes

Config API, State API, and TempStore patterns are identical in Drupal 10 and Drupal 11. There are no syntax changes between versions for any of the storage APIs covered in this skill.

## Cross-references

See also: **drupal-forms-api** (if installed) for `ConfigFormBase`, which provides a form UI for Config API settings with built-in `getEditableConfigNames()`, parent submit handling, and immutable/editable config distinction. If not available, extend `ConfigFormBase` from `Drupal\Core\Form\ConfigFormBase`, implement `getEditableConfigNames()` returning the config names your form edits, and call `parent::submitForm()` in your submit handler.

See also: **drupal-entities-fields** (if installed) for config entities when you need structured, CRUD-able configuration with admin UI (like content types, image styles, views). If not available, config entities are PHP classes with `ConfigEntityType` annotation/attribute, require `config_export` keys in their definition, and use `type: config_entity` in their schema.

See also: **drupal-module-scaffold** (if installed) for `.info.yml` setup, `config/install/` directory placement, and `config/schema/` directory setup. If not available, place default config in `config/install/my_module.settings.yml` and schema in `config/schema/my_module.schema.yml` inside your module directory.

For configuration translation patterns, see references/i18n.md in this skill directory.

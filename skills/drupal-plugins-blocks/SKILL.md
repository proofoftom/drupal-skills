---
name: drupal-plugins-blocks
description: |
  Create Drupal block plugins and custom plugin types with correct plugin-style
  dependency injection (ContainerFactoryPluginInterface, 4-param create()). Use when
  asked to create a custom block, add a block configuration form, build a custom plugin
  type with a plugin manager, or work with Drupal's plugin discovery system. Covers
  D10 annotations and D11 PHP attributes for plugin discovery.
  Do NOT use for controller/service DI (use drupal-routing-controllers instead).
---

# Drupal Plugins and Blocks

## What kind of plugin do you need?

Drupal's plugin system provides extensible functionality. Choose the right approach:

**Is it a custom block (content placed via Block Layout)?**
YES -> Extend `BlockBase`, implement `build()`. Add DI via `ContainerFactoryPluginInterface`. See "Block plugin" below.

**Is it a block with a configuration form?**
YES -> Same as above, plus implement `defaultConfiguration()`, `blockForm()`, `blockSubmit()`, optionally `blockValidate()`. See "Block configuration forms" below.

**Is it a custom plugin type (reusable extension point for your module)?**
YES -> Create a plugin manager + annotation/attribute class + plugin interface. See "Custom plugin types" below.

**Is it an existing plugin type (field formatter, field widget, entity handler)?**
YES -> Extend the appropriate base class with correct annotation/attribute. See drupal-entities-fields (if installed) for field plugin patterns. If not available, follow the same DI and annotation/attribute patterns shown below for blocks.

## Block plugin -- complete pattern

Block plugins live in `src/Plugin/Block/` and extend `BlockBase`. The class needs a plugin annotation (D10) or attribute (D11) to be discovered.

**Namespace:** `Drupal\module_name\Plugin\Block`
**File location:** `src/Plugin/Block/MyBlock.php`

### D10 annotation syntax

```php
namespace Drupal\my_module\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a custom block.
 *
 * @Block(
 *   id = "my_module_example",
 *   admin_label = @Translation("Example Block"),
 *   category = @Translation("Custom"),
 * )
 */
class ExampleBlock extends BlockBase {

  public function build() {
    return [
      '#markup' => $this->t('Hello from the example block.'),
    ];
  }

}
```

### D11 attribute syntax

```php
namespace Drupal\my_module\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

#[Block(
  id: "my_module_example",
  admin_label: new TranslatableMarkup("Example Block"),
  category: new TranslatableMarkup("Custom"),
)]
class ExampleBlock extends BlockBase {

  public function build() {
    return [
      '#markup' => $this->t('Hello from the example block.'),
    ];
  }

}
```

Key differences: D10 uses `@Block(...)` docblock annotation with `@Translation("...")`. D11 uses `#[Block(...)]` PHP attribute with `new TranslatableMarkup("...")`. The class body is identical.

> WRONG: Using `@Translation("...")` in D11 attribute syntax. `@Translation` is annotation-only -- it does not work inside PHP attributes.
> RIGHT: Use `new TranslatableMarkup("...")` in D11 attributes. Import `Drupal\Core\StringTranslation\TranslatableMarkup`.

## Plugin DI -- THE critical pattern

Plugin DI is different from controller/form DI. This is the single most common mistake Claude makes with Drupal plugins. Plugin classes that need services MUST implement `ContainerFactoryPluginInterface` and follow the 4-parameter `create()` signature.

### Controller/form DI (for comparison -- do NOT use in plugins)

```php
// Controller create() -- 1 parameter:
public static function create(ContainerInterface $container) {
  return new static($container->get('my_service'));
}
```

### Plugin DI -- correct 4-parameter signature

```php
// Plugin create() -- 4 parameters:
public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
  return new static(
    $configuration,
    $plugin_id,
    $plugin_definition,
    $container->get('my_module.my_service')
  );
}
```

> WRONG: Using controller DI signature in plugin classes -- `create(ContainerInterface $container)` with only 1 parameter. Plugin `create()` MUST have 4 parameters: `$container, $configuration, $plugin_id, $plugin_definition`. Using 1 parameter causes a fatal error because the plugin manager passes all 4 arguments.
> RIGHT: Plugin `create()` signature is `create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)`. Always pass `$configuration, $plugin_id, $plugin_definition` as the first 3 constructor arguments, followed by injected services.

> WRONG: Forgetting `parent::__construct($configuration, $plugin_id, $plugin_definition)` in the plugin constructor. Without this call, the plugin loses its configuration array, plugin ID, and definition metadata. Block placement settings, default configuration, and plugin inspection methods all break silently.
> RIGHT: Plugin constructors MUST call `parent::__construct($configuration, $plugin_id, $plugin_definition)` before storing injected services. This is unlike controllers, which have no required parent constructor call.

> WRONG: Using `\Drupal::service('my_service')` inside plugin classes to get services. Static service calls bypass dependency injection, making the plugin untestable and hiding its dependencies.
> RIGHT: Implement `ContainerFactoryPluginInterface`, inject services via `create()` + constructor. The `.module` file is the ONE place where `\Drupal::service()` calls are acceptable because procedural code cannot use constructor injection.

### Complete block with DI -- D10 annotation

```php
namespace Drupal\my_module\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\my_module\DataService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a data display block.
 *
 * @Block(
 *   id = "my_module_data_block",
 *   admin_label = @Translation("Data Display Block"),
 *   category = @Translation("Custom"),
 * )
 */
class DataBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected DataService $dataService;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, DataService $data_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->dataService = $data_service;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('my_module.data_service')
    );
  }

  public function build() {
    $items = $this->dataService->getItems();
    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#cache' => [
        'max-age' => 3600,
      ],
    ];
  }

}
```

### Complete block with DI -- D11 attribute

```php
namespace Drupal\my_module\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\my_module\DataService;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[Block(
  id: "my_module_data_block",
  admin_label: new TranslatableMarkup("Data Display Block"),
  category: new TranslatableMarkup("Custom"),
)]
class DataBlock extends BlockBase implements ContainerFactoryPluginInterface {

  // Constructor, create(), and build() are IDENTICAL to D10 version.
  // Only the annotation/attribute syntax at the top of the class changes.

}
```

## Block configuration forms

Blocks can have their own configuration form, displayed when an admin places or edits the block in Block Layout. Block config is stored automatically by the block placement system.

### Methods for block config

- `defaultConfiguration()`: Provide default values for config keys.
- `blockForm($form, FormStateInterface $form_state)`: Add form elements to block config.
- `blockValidate($form, FormStateInterface $form_state)`: Optional validation.
- `blockSubmit($form, FormStateInterface $form_state)`: Save config values.
- Access stored config: `$this->configuration['key']`

### Complete block config example

```php
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

class ConfigurableBlock extends BlockBase {

  public function defaultConfiguration() {
    return [
      'message' => 'Default message',
      'items_count' => 5,
    ];
  }

  public function blockForm($form, FormStateInterface $form_state) {
    $form['message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message'),
      '#default_value' => $this->configuration['message'],
      '#required' => TRUE,
    ];

    $form['items_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of items'),
      '#default_value' => $this->configuration['items_count'],
      '#min' => 1,
      '#max' => 50,
    ];

    return $form;
  }

  public function blockValidate($form, FormStateInterface $form_state) {
    $count = $form_state->getValue('items_count');
    if ($count < 1 || $count > 50) {
      $form_state->setErrorByName('items_count', $this->t('Items count must be between 1 and 50.'));
    }
  }

  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['message'] = $form_state->getValue('message');
    $this->configuration['items_count'] = $form_state->getValue('items_count');
  }

  public function build() {
    return [
      '#markup' => $this->configuration['message'],
    ];
  }

}
```

> WRONG: Manually saving block configuration to Config API using `$this->configFactory->getEditable()->set()->save()`. Block config is NOT stored in the Config API. It is managed by the block placement system and saved automatically when `blockSubmit()` sets values on `$this->configuration`.
> RIGHT: In `blockSubmit()`, set values via `$this->configuration['key'] = $value`. In `build()` and other methods, read them via `$this->configuration['key']`. Provide defaults in `defaultConfiguration()`. The block system handles persistence.

## Block access control

Override `blockAccess()` to control who sees the block. Return an `AccessResult` with appropriate cache metadata.

```php
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

public function blockAccess(AccountInterface $account) {
  return AccessResult::allowedIfHasPermission($account, 'access my_module content')
    ->addCacheContexts(['user.permissions']);
}
```

`AccessResult::allowedIfHasPermission()` handles cache contexts automatically. If you need custom logic, use `AccessResult::allowedIf($condition)` and add cache contexts manually.

See also: **drupal-access-security** (if installed) for the full access control system including `AccessResult` cache metadata patterns, permission definitions, and route-level access. If not available, use `AccessResult::allowedIfHasPermission()` which handles caching automatically, or `AccessResult::allowedIf()` with `->addCacheContexts(['user.permissions'])` for custom conditions.

## Custom plugin types

When your module needs a reusable extension point (e.g., "sandwich types", "notification channels", "import formatters"), create a custom plugin type. This requires three components plus a services.yml entry.

### Component 1: Plugin Interface

```php
// src/Plugin/SandwichPluginInterface.php
namespace Drupal\my_module\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

interface SandwichPluginInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Returns the sandwich description.
   */
  public function description(): string;

  /**
   * Returns the number of calories.
   */
  public function calories(): int;

}
```

### Component 2a: Annotation class (D10)

```php
// src/Annotation/Sandwich.php
namespace Drupal\my_module\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Sandwich plugin annotation.
 *
 * @Annotation
 */
class Sandwich extends Plugin {

  /**
   * The human-readable plugin label.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A brief description.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

}
```

### Component 2b: Attribute class (D11)

```php
// src/Attribute/Sandwich.php
namespace Drupal\my_module\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Sandwich extends Plugin {

  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly TranslatableMarkup $description = new TranslatableMarkup(""),
  ) {
    parent::__construct($id);
  }

}
```

### Component 3: Plugin Manager

```php
// src/Plugin/SandwichPluginManager.php
namespace Drupal\my_module\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\my_module\Annotation\Sandwich;

class SandwichPluginManager extends DefaultPluginManager {

  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
  ) {
    parent::__construct(
      'Plugin/Sandwich',
      $namespaces,
      $module_handler,
      SandwichPluginInterface::class,
      Sandwich::class,
    );
    $this->alterInfo('sandwich_info');
    $this->setCacheBackend($cache_backend, 'sandwich_plugins');
  }

}
```

For D11 attribute-based discovery, replace the annotation class reference:

```php
// D11 manager constructor -- use Attribute class instead of Annotation class:
parent::__construct(
  'Plugin/Sandwich',
  $namespaces,
  $module_handler,
  SandwichPluginInterface::class,
  \Drupal\my_module\Attribute\Sandwich::class,
);
```

For backward compatibility supporting both D10 annotations and D11 attributes, use `AttributeBridgeDecorator` in the manager to accept either syntax.

### Services registration

```yaml
# my_module.services.yml
services:
  plugin.manager.sandwich:
    class: Drupal\my_module\Plugin\SandwichPluginManager
    parent: default_plugin_manager
```

The `parent: default_plugin_manager` shorthand injects `$namespaces`, `$cache_backend`, and `$module_handler` automatically.

### Example plugin implementation

```php
// src/Plugin/Sandwich/HamSandwich.php (D10 annotation)
namespace Drupal\my_module\Plugin\Sandwich;

use Drupal\Core\Plugin\PluginBase;
use Drupal\my_module\Plugin\SandwichPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Sandwich(
 *   id = "ham",
 *   label = @Translation("Ham Sandwich"),
 *   description = @Translation("A classic ham sandwich."),
 * )
 */
class HamSandwich extends PluginBase implements SandwichPluginInterface {

  public function description(): string {
    return (string) $this->pluginDefinition['description'];
  }

  public function calories(): int {
    return 350;
  }

}
```

### Using the plugin manager

```php
// In a controller or service with plugin.manager.sandwich injected:
$definitions = $this->sandwichManager->getDefinitions();
$instance = $this->sandwichManager->createInstance('ham');
$description = $instance->description();
```

## Plugin discovery

Drupal discovers plugins by scanning specific namespaces:

- **Annotation-based (D10):** Doctrine annotation reader scans `src/Plugin/{PluginType}/` across all modules.
- **Attribute-based (D11):** PHP native attribute reader scans the same `src/Plugin/{PluginType}/` namespace.
- **YAML-based:** Some plugin types use YAML discovery (menu links in `module_name.links.menu.yml`, local tasks in `module_name.links.task.yml`). These are a different system from annotation/attribute plugins.

Plugin directory convention: `src/Plugin/{PluginType}/` where `{PluginType}` matches the first argument to `DefaultPluginManager::__construct()`. For blocks, Drupal core defines `Plugin/Block`. For a custom Sandwich type, use `Plugin/Sandwich`.

## Cross-references

See also: **drupal-forms-api** (if installed) for Form API lifecycle used in block config forms (`blockForm`/`blockSubmit` use the same render array form elements as `buildForm()`). If not available, `blockForm()` returns a form render array using the same `#type` elements as regular forms -- `textfield`, `select`, `number`, etc. Use `$form_state->setErrorByName()` in `blockValidate()`.

See also: **drupal-config-storage** (if installed) for Config API patterns. Block config is NOT stored via Config API -- it uses the block placement system. Config API is for module-level settings. If not available, do not use `\Drupal::config()` for block settings -- use `$this->configuration` and `blockSubmit()`.

See also: **drupal-routing-controllers** (if installed) for service registration patterns (`.services.yml`). Plugin managers are registered as services. If not available, register the manager in `my_module.services.yml` using `parent: default_plugin_manager`.

See also: **drupal-entities-fields** (if installed) for entity-level plugins (field formatters, field widgets, entity handlers) which follow the same plugin DI pattern. If not available, field plugins extend base classes like `FormatterBase` or `WidgetBase` and use the same 4-parameter `create()` signature shown in this skill.

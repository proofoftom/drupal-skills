---
name: drupal-module-scaffold
description: |
  Scaffold Drupal modules with correct .info.yml, PSR-4 namespace structure,
  and .module files. Use when asked to create a new Drupal module, start a
  custom module, or set up module boilerplate.
---

# Drupal Module Scaffold

## What files do you need?

When creating a new Drupal module, decide which files to create based on what the module will do.

### ALWAYS create these files

Every Drupal module requires at minimum:

- `module_name.info.yml` -- Makes Drupal recognize the module. Without this, nothing works.
- `src/` directory -- PSR-4 autoloading root. Even if empty initially, create it for any class-based code.

### CREATE WHEN the module needs them

Ask yourself these questions to decide what else to create:

**Does the module implement hooks?**
YES -> Create `module_name.module` (procedural hook implementations go here)
NO -> Do not create a .module file. Many modern modules have no .module file at all.

**Does the module define pages or API endpoints?**
YES -> Create `module_name.routing.yml` + a Controller class in `src/Controller/`
NO -> Skip routing. See also: drupal-routing-controllers (if installed) for full routing patterns.

**Does the module provide services (shared logic, API clients, managers)?**
YES -> Create `module_name.services.yml` + service class in `src/`
NO -> Skip services file.

**Does the module define permissions?**
YES -> Create `module_name.permissions.yml`
NO -> Skip permissions file.

**Does the module add CSS or JS?**
YES -> Create `module_name.libraries.yml` + asset files
NO -> Skip libraries file.

**Does the module define entity types?**
YES -> See drupal-entities-fields (if installed) for content and config entity type creation. If not available, you need entity classes in `src/Entity/`, schema files, and handler definitions.
NO -> Skip entity infrastructure.

## .info.yml -- getting it right

The `.info.yml` file is the only file strictly required for a module to be recognized by Drupal. Place it at the root of your module directory.

### Required keys

```yaml
name: Hello World
description: 'A short description of what this module does.'
type: module
core_version_requirement: ^10 || ^11
package: Custom
```

| Key | Purpose | Notes |
|-----|---------|-------|
| `name` | Human-readable module name | Shown on admin/modules page |
| `description` | What the module does | Keep it concise, shown in UI |
| `type` | Declares this is a module (not a theme or profile) | Always `module` for modules |
| `core_version_requirement` | Drupal version compatibility | Semantic versioning constraint |
| `package` | Groups the module in the admin UI | Use `Custom` for site-specific modules |

> WRONG: Using `core: 8.x` in the info file. This is the legacy Drupal 8 format and does not work in Drupal 10+.
> RIGHT: Use `core_version_requirement: ^10 || ^11` to declare compatibility. The `core:` key was replaced by `core_version_requirement` starting in Drupal 9. Using `^10 || ^11` supports both major versions.

### Optional keys

```yaml
dependencies:
  - drupal:node
  - drupal:views
  - other_project:other_module
configure: module_name.settings
lifecycle: experimental
hidden: true
```

| Key | Purpose | When to use |
|-----|---------|-------------|
| `dependencies` | Modules required before this one can be enabled | When your module calls another module's API |
| `configure` | Route name linking to config form | When module has a settings page |
| `lifecycle` | Stability indicator (`experimental`, `deprecated`, `obsolete`) | For contrib/core lifecycle management |
| `hidden` | Hides module from the admin UI | For helper/internal sub-modules |

> WRONG: Listing dependencies as just `node` or `views` without the project prefix.
> RIGHT: Use `project:module_name` format. Core modules use `drupal:module_name` (e.g., `drupal:node`). Contributed modules use `project_name:module_name` (e.g., `ctools:ctools`). This format maps to Drupal.org project URLs and is required for dependency resolution.

## PSR-4 namespace structure

Drupal uses PSR-4 autoloading. The `src/` directory in your module maps to the `Drupal\module_name\` namespace. Any PHP class placed under `src/` with the correct namespace will be autoloaded.

### Standard directory layout

```
modules/custom/hello_world/
  hello_world.info.yml
  hello_world.module            # Only if implementing hooks
  hello_world.routing.yml       # Only if defining routes
  hello_world.services.yml      # Only if registering services
  hello_world.permissions.yml   # Only if defining permissions
  hello_world.libraries.yml     # Only if attaching CSS/JS
  src/
    Controller/                 # Route controllers
    Form/                       # Form classes
    Entity/                     # Entity type classes
    Plugin/                     # Plugin classes (blocks, etc.)
      Block/                    # Block plugin classes
    EventSubscriber/            # Event subscriber classes
    Service/                    # Service classes (or directly in src/)
```

### Namespace rules

- `src/Controller/HelloWorldController.php` -> `Drupal\hello_world\Controller\HelloWorldController`
- `src/Form/SettingsForm.php` -> `Drupal\hello_world\Form\SettingsForm`
- `src/Plugin/Block/HelloBlock.php` -> `Drupal\hello_world\Plugin\Block\HelloBlock`

> WRONG: Placing PHP class files outside the `src/` directory (e.g., in the module root or in a `lib/` directory). Classes outside `src/` will not be autoloaded by Drupal's PSR-4 class loader and will cause fatal errors.
> RIGHT: All PHP classes go inside `src/`. The ONLY PHP file that lives outside `src/` is the `.module` file, which contains procedural hook implementations (functions, not classes).

## .module file patterns

The `.module` file is for procedural code only -- specifically, hook implementations. Do not create a `.module` file unless the module needs to implement hooks.

### When to create a .module file

- Implementing `hook_theme()` to define theme hooks
- Implementing `hook_form_alter()` or `hook_form_FORM_ID_alter()` to modify forms
- Implementing `hook_help()` to provide help text
- Implementing `hook_entity_type_build()`, `hook_entity_base_field_info()`, etc.
- Implementing any procedural hook that Drupal invokes by naming convention

### .module file structure

```php
<?php

/**
 * @file
 * Contains hook implementations for the Hello World module.
 */

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_help().
 */
function hello_world_help($route_name, RouteMatchInterface $route_match) {
  switch ($route_name) {
    case 'help.page.hello_world':
      return '<p>' . t('Provides Hello World functionality.') . '</p>';
  }
}

/**
 * Implements hook_theme().
 */
function hello_world_theme() {
  return [
    'hello_world_greeting' => [
      'variables' => [
        'name' => NULL,
        'message' => NULL,
      ],
    ],
  ];
}

/**
 * Implements hook_form_alter().
 */
function hello_world_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  if ($form_id === 'node_article_form') {
    // Modify the article node form.
    $form['title']['widget'][0]['value']['#description'] = t('Custom description.');
  }
}
```

### Key conventions

- The `@file` docblock at the top describes the file's purpose.
- Hook function names follow the pattern: `modulename_hookname()` (e.g., `hello_world_help`).
- Each hook implementation has a DocBlock: `/** * Implements hook_name(). */`
- Use statements go at the top, after the `@file` docblock.

> WRONG: Putting class definitions in the `.module` file. Classes must go in `src/` under PSR-4 namespaces.
> RIGHT: The `.module` file is exclusively for procedural hook implementations (functions). If you need a class, create it in `src/` and reference it from hooks if needed.

> WRONG: Using `\Drupal::service('my_service')` inside class methods (controllers, forms, services). Static service calls bypass dependency injection, making code untestable and tightly coupled.
> RIGHT: Inject services via the `create()` factory method and constructor. The `.module` file is the ONE place where `\Drupal::service()` and `\Drupal::` static calls are acceptable, because procedural code cannot use constructor injection. Inside any class that extends `ControllerBase`, `FormBase`, or implements `ContainerInjectionInterface`, always use dependency injection.

## Complete module scaffold example

Here is a realistic module called `hello_world` showing all key files together as a complete ecosystem.

### Directory tree

```
modules/custom/hello_world/
  hello_world.info.yml
  hello_world.module
  hello_world.routing.yml
  hello_world.services.yml
  src/
    Controller/
      HelloWorldController.php
    HelloWorldSalutation.php
```

### hello_world.info.yml

```yaml
name: Hello World
description: 'Provides a configurable greeting page.'
type: module
core_version_requirement: ^10 || ^11
package: Custom
```

### hello_world.routing.yml

```yaml
hello_world.hello:
  path: '/hello'
  defaults:
    _controller: '\Drupal\hello_world\Controller\HelloWorldController::helloWorld'
    _title: 'Hello'
  requirements:
    _permission: 'access content'
```

### hello_world.services.yml

```yaml
services:
  hello_world.salutation:
    class: Drupal\hello_world\HelloWorldSalutation
    arguments: ['@config.factory']
```

### src/Controller/HelloWorldController.php

```php
<?php

namespace Drupal\hello_world\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\hello_world\HelloWorldSalutation;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the Hello World greeting page.
 */
class HelloWorldController extends ControllerBase {

  /**
   * The salutation service.
   *
   * @var \Drupal\hello_world\HelloWorldSalutation
   */
  protected $salutation;

  /**
   * Constructs a HelloWorldController object.
   *
   * @param \Drupal\hello_world\HelloWorldSalutation $salutation
   *   The salutation service.
   */
  public function __construct(HelloWorldSalutation $salutation) {
    $this->salutation = $salutation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('hello_world.salutation')
    );
  }

  /**
   * Returns the Hello World greeting page.
   *
   * @return array
   *   A render array.
   */
  public function helloWorld() {
    return [
      '#markup' => $this->salutation->getSalutation(),
    ];
  }

}
```

### src/HelloWorldSalutation.php

```php
<?php

namespace Drupal\hello_world;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides greeting salutations based on the time of day.
 */
class HelloWorldSalutation {

  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a HelloWorldSalutation object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Returns a greeting based on the time of day.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The greeting.
   */
  public function getSalutation() {
    $time = new \DateTime();
    $hour = (int) $time->format('G');

    if ($hour >= 6 && $hour < 12) {
      return $this->t('Good morning!');
    }

    if ($hour >= 12 && $hour < 18) {
      return $this->t('Good afternoon!');
    }

    return $this->t('Good evening!');
  }

}
```

### hello_world.module

```php
<?php

/**
 * @file
 * Contains hook implementations for the Hello World module.
 */

/**
 * Implements hook_theme().
 */
function hello_world_theme() {
  return [
    'hello_world_greeting' => [
      'variables' => [
        'salutation' => NULL,
      ],
    ],
  ];
}
```

Notice how every PHP class has its paired YAML configuration:
- `HelloWorldController.php` is paired with `hello_world.routing.yml` (defines the route)
- `HelloWorldSalutation.php` is paired with `hello_world.services.yml` (registers the service)
- The `.module` file stands alone since it uses the hook naming convention (no YAML needed)

## D10/D11 compatibility notes

Module scaffolding has minimal differences between Drupal 10 and 11. The key points:

- **core_version_requirement**: Use `^10 || ^11` to support both versions.
- **PHP attributes for plugins**: Drupal 11.1+ uses PHP attributes instead of annotations for plugin discovery (blocks, entity types, etc.). This does not affect the basic module scaffold files (.info.yml, .module, .routing.yml, .services.yml) but matters when your module defines plugins. See drupal-plugins-blocks (if installed) for attribute syntax.
- **TranslatableMarkup**: In D11 attribute syntax, `@Translation("text")` becomes `new TranslatableMarkup("text")`. This only applies to plugin annotations/attributes, not to `$this->t()` calls in code.

## Cross-references

See also: **drupal-routing-controllers** (if installed) for route definitions, controller patterns, service injection, and menu link configuration. If not available, ensure you create a `.routing.yml` file alongside any controller class, and inject services via `create()` + constructor rather than static `\Drupal::service()` calls.

See also: **drupal-entities-fields** (if installed) for content and config entity type creation, base field definitions, and entity handler configuration. If not available, remember that entity types require both a PHP class with the correct annotation/attribute and corresponding YAML configuration for schema, links, and permissions.

See also: **drupal-forms-api** (if installed) for Form API lifecycle (`buildForm`, `validateForm`, `submitForm`) and form altering patterns. If not available, extend `FormBase` or `ConfigFormBase` for new forms, and use `hook_form_alter()` in the `.module` file to modify existing forms.

---
name: drupal-routing-controllers
description: |
  Define Drupal routes with controllers, services, and dependency injection.
  Use when asked to add a page, endpoint, or route to a Drupal module, create
  a controller, define a custom service, or set up dependency injection.
---

# Drupal Routing and Controllers

## What kind of route do you need?

When adding a page, endpoint, or route to a Drupal module, start by determining the route type.

### Simple page (controller returns render array)

Create a `.routing.yml` entry pointing to a controller method. The controller returns a render array that Drupal's theme system turns into a full page response.

### Form page

Use `_form` instead of `_controller` in the route defaults. The form class handles the entire page.

```yaml
my_module.settings:
  path: '/admin/config/my-module/settings'
  defaults:
    _form: '\Drupal\my_module\Form\SettingsForm'
    _title: 'My Module Settings'
  requirements:
    _permission: 'administer site configuration'
```

See also: **drupal-forms-api** (if installed) for Form API lifecycle (`buildForm`, `validateForm`, `submitForm`) and form altering patterns.

### Entity page

Entity route providers auto-generate CRUD routes from link templates defined in the entity type annotation/attribute. Do NOT manually define routes for standard entity operations (view, add, edit, delete, collection).

See also: **drupal-entities-fields** (if installed) for entity route providers that auto-generate CRUD routes. If not available, use `AdminHtmlRouteProvider` as the route provider handler in your entity type definition.

### Access-controlled page

Add a `requirements` key to the route. See the "Route access patterns" section below.

### Admin page

Routes under `/admin/*` automatically use the admin theme. You can also force the admin theme on any route:

```yaml
options:
  _admin_route: TRUE
```

### Route with parameters

Define parameters in the path with curly braces. Drupal can automatically upcast entity parameters.

```yaml
my_module.view_item:
  path: '/my-module/item/{node}'
  defaults:
    _controller: '\Drupal\my_module\Controller\ItemController::view'
    _title: 'View Item'
  requirements:
    _permission: 'access content'
```

When the parameter name matches an entity type machine name (e.g., `{node}`, `{user}`, `{taxonomy_term}`), Drupal automatically loads the entity. No extra configuration needed.

For non-entity parameters or custom upcasting, add explicit parameter options:

```yaml
my_module.view_item:
  path: '/my-module/item/{item_id}'
  defaults:
    _controller: '\Drupal\my_module\Controller\ItemController::view'
    _title: 'View Item'
  requirements:
    _permission: 'access content'
    item_id: '\d+'
  options:
    parameters:
      item_id:
        type: entity:node
```

## .routing.yml reference

Every route definition lives in `module_name.routing.yml`. Each route has a machine name, a path, defaults, requirements, and optionally options.

### Route structure

```yaml
module_name.route_name:
  path: '/path/to/page'
  defaults:
    _controller: '\Drupal\module_name\Controller\MyController::methodName'
    _title: 'Page Title'
  requirements:
    _permission: 'access content'
  options:
    _admin_route: TRUE
```

| Key | Purpose | Notes |
|-----|---------|-------|
| `path` | URL path for this route | Must start with `/`. Use `{param}` for variables. |
| `defaults._controller` | Fully qualified class and method | Format: `'\Drupal\module\Controller\Class::method'` |
| `defaults._form` | Form class for form pages | Use INSTEAD of `_controller`, not alongside it |
| `defaults._title` | Static page title | Shown in browser tab and page heading |
| `defaults._title_callback` | Dynamic title method | Format: `'\Drupal\module\Controller\Class::titleMethod'` |
| `requirements._permission` | Permission check | Comma-separated for AND, `+` for OR |
| `requirements._role` | Role check | Machine name of role |
| `requirements._custom_access` | Custom access check method | Format: `'\Drupal\module\Access\MyAccess::access'` |
| `requirements._access` | Bypass access checking | `'TRUE'` for unrestricted access |
| `options._admin_route` | Use admin theme | `TRUE` to force admin theme |
| `options.parameters` | Parameter type mapping | For entity upcasting and type hints |

> WRONG: Using `hook_menu()` to define routes and pages. This is the Drupal 7 approach and does not exist in Drupal 10/11. The routing system replaced `hook_menu()` entirely in Drupal 8.
> RIGHT: Define routes in `module_name.routing.yml`. Every page, form, or endpoint gets a route definition in this YAML file. Controllers handle the response logic.

> WRONG: Using `_access: 'TRUE'` on routes that should have permission checks. This opens the route to everyone, including anonymous users, with no access control whatsoever.
> RIGHT: Use `_permission: 'access content'` for pages most users should see, or define a custom permission in `module_name.permissions.yml` and reference it. Reserve `_access: 'TRUE'` only for truly public endpoints like health checks or OAuth callbacks.

## Controller patterns

Controllers are PHP classes that handle the logic for a route. Place them in `src/Controller/` inside your module.

### Extending ControllerBase

Always extend `ControllerBase` unless you have a specific reason not to. It provides helper methods and implements `ContainerInjectionInterface` for dependency injection.

```php
<?php

namespace Drupal\my_module\Controller;

use Drupal\Core\Controller\ControllerBase;

class MyController extends ControllerBase {

  public function content() {
    return [
      '#markup' => $this->t('Hello from MyController.'),
    ];
  }

}
```

**Helper methods from ControllerBase:**
- `$this->t()` -- translation
- `$this->redirect()` -- redirect to a route
- `$this->entityTypeManager()` -- access entity type manager
- `$this->currentUser()` -- get current user account
- `$this->config()` -- load configuration

### Return types

Controllers can return several types:

| Return Type | When to Use |
|-------------|-------------|
| Render array | Standard pages -- Drupal's theme system processes them |
| `JsonResponse` | JSON API endpoints |
| `Response` | Raw responses bypassing the theme system |
| `RedirectResponse` | Redirects (use `LocalRedirectResponse` or `TrustedRedirectResponse` for safety) |

> WRONG: Returning a plain string from a controller method. Plain strings bypass Drupal's render pipeline, lose caching metadata, skip theme processing, and break block layout.
> RIGHT: Always return a render array for standard pages. Use `['#markup' => $this->t('My text')]` at minimum. For API responses, use `new JsonResponse($data)`. For redirects, use a redirect response object.

### Title callbacks

For dynamic page titles, use `_title_callback` instead of `_title`:

```yaml
my_module.user_page:
  path: '/my-module/user/{user}'
  defaults:
    _controller: '\Drupal\my_module\Controller\UserController::view'
    _title_callback: '\Drupal\my_module\Controller\UserController::title'
  requirements:
    _permission: 'access content'
```

```php
public function title(UserInterface $user) {
  return $this->t('Profile for @name', ['@name' => $user->getDisplayName()]);
}
```

## Services and dependency injection

Services are reusable PHP classes registered in the Drupal service container. They are the correct way to share logic across controllers, forms, and other classes.

### .services.yml structure

```yaml
services:
  my_module.my_service:
    class: Drupal\my_module\MyService
    arguments: ['@entity_type.manager', '@config.factory']
```

| Element | Purpose | Example |
|---------|---------|---------|
| Service name | Unique identifier | `my_module.my_service` (always prefix with module name) |
| `class` | Fully qualified class name | `Drupal\my_module\MyService` |
| `arguments` | Injected dependencies | `['@other_service']` (@ prefix = service reference) |
| `tags` | Service tags for discovery | `[{ name: event_subscriber }]` |

### Dependency injection in controllers

`ControllerBase` implements `ContainerInjectionInterface`. Override `create()` and add a constructor to inject services.

```php
<?php

namespace Drupal\my_module\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\my_module\MyService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MyController extends ControllerBase {

  /**
   * The custom service.
   *
   * @var \Drupal\my_module\MyService
   */
  protected $myService;

  /**
   * Constructs a MyController object.
   *
   * @param \Drupal\my_module\MyService $my_service
   *   The custom service.
   */
  public function __construct(MyService $my_service) {
    $this->myService = $my_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('my_module.my_service')
    );
  }

  /**
   * Returns the page content.
   *
   * @return array
   *   A render array.
   */
  public function content() {
    return [
      '#markup' => $this->myService->getMessage(),
    ];
  }

}
```

The DI flow works like this:
1. Drupal finds the route and resolves the controller.
2. It checks if the controller implements `ContainerInjectionInterface` (which `ControllerBase` does).
3. It calls `create()` with the service container.
4. `create()` pulls specific services from the container and passes them to the constructor.
5. The constructor stores them as class properties for use in controller methods.

> WRONG: Using `\Drupal::service('my_module.my_service')` or `\Drupal::entityTypeManager()` inside controller, form, or service classes. Static service calls bypass dependency injection, making code untestable and tightly coupled.
> RIGHT: Inject services via `create()` + constructor. Static `\Drupal::` calls are ONLY acceptable in `.module` files (procedural code), because procedural code cannot use constructor injection. Inside any class that extends `ControllerBase`, `FormBase`, or implements `ContainerInjectionInterface`, always inject.

> WRONG: Injecting the entire service container into a class. This hides the actual dependencies, makes code harder to understand and test, and defeats the purpose of dependency injection.
> RIGHT: Inject only the specific services your class needs. Each injected service should have a typed class property, a constructor parameter, and a `$container->get()` call in `create()`.

### Creating a custom service

A custom service is a PHP class registered in `.services.yml`. It can have its own injected dependencies.

```php
<?php

namespace Drupal\my_module;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class MyService {

  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a MyService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Returns a greeting message.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The greeting.
   */
  public function getMessage() {
    $config = $this->configFactory->get('my_module.settings');
    $name = $config->get('name') ?? 'World';
    return $this->t('Hello, @name!', ['@name' => $name]);
  }

}
```

Services inject their dependencies through the constructor directly -- no `create()` method needed. The container resolves `@service_name` arguments from `.services.yml` automatically.

## Complete route + controller + service example

Here is a realistic example showing all paired files for a module that provides a greeting page with a custom service.

### Directory tree

```
modules/custom/greeting/
  greeting.info.yml
  greeting.routing.yml
  greeting.services.yml
  src/
    Controller/
      GreetingController.php
    GreetingService.php
```

### greeting.info.yml

```yaml
name: Greeting
description: 'Provides a configurable greeting page.'
type: module
core_version_requirement: ^10 || ^11
package: Custom
```

### greeting.routing.yml

```yaml
greeting.page:
  path: '/greeting'
  defaults:
    _controller: '\Drupal\greeting\Controller\GreetingController::content'
    _title: 'Greeting'
  requirements:
    _permission: 'access content'

greeting.admin:
  path: '/admin/config/greeting'
  defaults:
    _form: '\Drupal\greeting\Form\GreetingSettingsForm'
    _title: 'Greeting Settings'
  requirements:
    _permission: 'administer site configuration'
  options:
    _admin_route: TRUE
```

### greeting.services.yml

```yaml
services:
  greeting.greeting_service:
    class: Drupal\greeting\GreetingService
    arguments: ['@config.factory', '@current_user']
```

### src/Controller/GreetingController.php

```php
<?php

namespace Drupal\greeting\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\greeting\GreetingService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GreetingController extends ControllerBase {

  protected $greetingService;

  public function __construct(GreetingService $greeting_service) {
    $this->greetingService = $greeting_service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('greeting.greeting_service')
    );
  }

  public function content() {
    return [
      '#markup' => $this->greetingService->getGreeting(),
    ];
  }

}
```

### src/GreetingService.php

```php
<?php

namespace Drupal\greeting;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class GreetingService {

  use StringTranslationTrait;

  protected $configFactory;
  protected $currentUser;

  public function __construct(ConfigFactoryInterface $config_factory, AccountProxyInterface $current_user) {
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
  }

  public function getGreeting() {
    $name = $this->currentUser->getDisplayName();
    return $this->t('Welcome, @name!', ['@name' => $name]);
  }

}
```

Notice how every PHP class has its paired YAML configuration:
- `GreetingController.php` is paired with `greeting.routing.yml` (defines the routes)
- `GreetingService.php` is paired with `greeting.services.yml` (registers the service and its dependencies)
- The `.info.yml` stands alone as the module declaration

## Route access patterns

Control who can access your routes using these requirement keys:

| Requirement | Purpose | Example |
|-------------|---------|---------|
| `_permission` | Check a single permission | `_permission: 'access content'` |
| `_permission` (multiple OR) | User has ANY of these permissions | `_permission: 'view content+administer nodes'` |
| `_permission` (multiple AND) | User has ALL of these permissions | `_permission: 'access content,administer nodes'` |
| `_role` | Check user role | `_role: 'administrator'` |
| `_access` | Unrestricted access | `_access: 'TRUE'` |
| `_custom_access` | Custom access check method | `_custom_access: '\Drupal\my_module\Access\MyAccess::access'` |

Custom access checkers return `AccessResult` objects:

```php
use Drupal\Core\Access\AccessResult;

public function access(AccountInterface $account) {
  return AccessResult::allowedIf($account->hasPermission('my permission'));
}
```

For detailed access control patterns, see **drupal-access-security** (if installed).

## D10/D11 compatibility notes

Routing, controllers, and services have no syntax differences between Drupal 10 and Drupal 11. The `.routing.yml`, `.services.yml`, and controller class patterns are identical across both versions.

The only D11 change relevant to routing is in entity route providers -- entity type annotations are replaced by PHP attributes in D11.1+. This affects how entity routes are auto-generated but not how you write custom routes. See **drupal-entities-fields** (if installed) for D11 attribute syntax for entity types.

## Cross-references

See also: **drupal-module-scaffold** (if installed) for module creation, `.info.yml` setup, PSR-4 namespace structure, and `.module` file patterns. If not available, every module needs at minimum a `module_name.info.yml` with `name`, `description`, `type: module`, and `core_version_requirement: ^10 || ^11`.

See also: **drupal-entities-fields** (if installed) for entity route providers that auto-generate CRUD routes, content and config entity types, and base field definitions. If not available, use `AdminHtmlRouteProvider` as the `route_provider` handler in your entity type definition to auto-generate standard entity routes.

See also: **drupal-forms-api** (if installed) for Form API lifecycle and form routes using `_form` default. If not available, extend `FormBase` or `ConfigFormBase` for new forms, reference the form class in `.routing.yml` with `_form` instead of `_controller`.

For menu links, local tasks, and local actions, see **references/menus.md** in this skill directory.

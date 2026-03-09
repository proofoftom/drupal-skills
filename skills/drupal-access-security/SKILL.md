---
name: drupal-access-security
description: |
  Implement Drupal access control and security: permissions (.permissions.yml), route
  access requirements, entity access handlers, CSRF protection, and XSS prevention.
  Use when asked to restrict access to routes or content, define custom permissions,
  protect forms against CSRF, sanitize output against XSS, or implement AccessResult-based
  access checks. Covers cacheable access results with addCacheableDependency().
  Do NOT use for building the routes themselves (use drupal-routing-controllers).
---

# Drupal Access Control and Security

Access control patterns (permissions, AccessResult, CSRF, XSS prevention) are identical in D10 and D11. No syntax differences between versions.

## What kind of access control do you need?

Start by identifying the access scenario, then follow the pattern.

### Route requires a specific permission (most common)

Use `_permission` in `.routing.yml` requirements. Define the permission in `module_name.permissions.yml`.

```yaml
# my_module.routing.yml
my_module.dashboard:
  path: '/my-module/dashboard'
  defaults:
    _controller: '\Drupal\my_module\Controller\DashboardController::content'
    _title: 'Dashboard'
  requirements:
    _permission: 'access my_module dashboard'
```

```yaml
# my_module.permissions.yml
access my_module dashboard:
  title: 'Access My Module dashboard'
  description: 'View the My Module dashboard page.'
```

> WRONG: Using a permission string in `_permission` that is not defined in `module_name.permissions.yml`. Every permission string used in routes or access checks MUST be defined in the permissions file. Orphaned permission strings silently deny access to everyone -- no error, no warning, just a 403.

> RIGHT: Define every permission in `module_name.permissions.yml` first, then reference the exact string in routes and access checks.

### Route requires a specific role (less common)

Use `_role` when access is strictly role-based. Prefer permissions over roles for flexibility.

```yaml
# my_module.routing.yml
my_module.admin_only:
  path: '/my-module/admin-only'
  defaults:
    _controller: '\Drupal\my_module\Controller\AdminController::content'
    _title: 'Admin Only'
  requirements:
    _role: 'administrator'
```

### Route needs custom logic (ownership, conditions, status checks)

Use `_custom_access` pointing to a class method.

```yaml
# my_module.routing.yml
my_module.edit_own:
  path: '/my-module/item/{node}/edit'
  defaults:
    _controller: '\Drupal\my_module\Controller\ItemController::edit'
    _title: 'Edit Item'
  requirements:
    _custom_access: '\Drupal\my_module\Access\ItemAccessChecker::access'
  options:
    parameters:
      node:
        type: entity:node
```

### Route needs CSRF protection (state-changing action links)

> **CRITICAL:** Any route with a controller that modifies data (mark complete, approve, toggle, archive) MUST have `_csrf_token: 'TRUE'` in requirements. Omitting this is a security vulnerability.

Use `_csrf_token: 'TRUE'` in requirements. For non-form links that change state (approve, delete, toggle, mark complete).

```yaml
# my_module.routing.yml
my_module.approve:
  path: '/my-module/approve/{node}'
  defaults:
    _controller: '\Drupal\my_module\Controller\ApproveController::approve'
  requirements:
    _permission: 'administer my_module'
    _csrf_token: 'TRUE'
```

### Entity needs access control

Implement `EntityAccessControlHandler`. See "Entity access control" section below.

### Open access (truly public pages only)

Use `_access: 'TRUE'` -- rarely needed. Only for pages that should be accessible to everyone, including anonymous users.

```yaml
my_module.public_page:
  path: '/my-module/public'
  defaults:
    _controller: '\Drupal\my_module\Controller\PublicController::content'
    _title: 'Public Page'
  requirements:
    _access: 'TRUE'
```

## Permissions

Define all module permissions in `module_name.permissions.yml`. This file goes in the module root alongside `.info.yml`.

### Static permissions

```yaml
# my_module.permissions.yml
administer my_module:
  title: 'Administer My Module'
  description: 'Full administrative access to My Module settings and content.'
  restrict access: true

access my_module content:
  title: 'Access My Module content'
  description: 'View My Module content listings.'

edit my_module content:
  title: 'Edit My Module content'
  description: 'Create, edit, and delete My Module content.'
```

- `restrict access: true` -- shows a warning on the permissions admin page. Use for dangerous permissions (administer, delete all, bypass access).
- `title` is required. `description` is optional but recommended.

### Dynamic permissions (generated at runtime)

Use `permission_callbacks` to generate permissions based on content types, vocabularies, or other configuration.

```yaml
# my_module.permissions.yml
permission_callbacks:
  - Drupal\my_module\MyModulePermissions::permissions
```

```php
// src/MyModulePermissions.php
namespace Drupal\my_module;

use Drupal\Core\StringTranslation\StringTranslationTrait;

class MyModulePermissions {

  use StringTranslationTrait;

  public function permissions(): array {
    $permissions = [];
    // Generate a permission per content type, vocabulary, etc.
    foreach ($this->getTypes() as $type_id => $type_label) {
      $permissions["edit my_module $type_id"] = [
        'title' => $this->t('Edit My Module @type content', ['@type' => $type_label]),
      ];
    }
    return $permissions;
  }

}
```

> WRONG: Using `hook_permission()` to define permissions. That is Drupal 7. In Drupal 10/11, define permissions in `module_name.permissions.yml` or via `permission_callbacks`.

> RIGHT: Always use `module_name.permissions.yml` for permission definitions. Use `permission_callbacks` for dynamically generated permissions.

### Route permission patterns

| Pattern | Meaning | Example |
|---------|---------|---------|
| `_permission: 'perm_name'` | Single permission required | `_permission: 'access my_module content'` |
| `_permission: 'perm1+perm2'` | ALL permissions required (AND) | `_permission: 'access content+edit my_module content'` |
| `_permission: 'perm1,perm2'` | ANY permission sufficient (OR) | `_permission: 'administer my_module,edit my_module content'` |
| `_role: 'role_name'` | Role check (prefer permissions) | `_role: 'administrator'` |
| `_access: 'TRUE'` | Open access (public pages) | No permission check at all |

## Custom access checkers

When `_permission` is not enough -- ownership checks, status conditions, multi-factor logic -- use a custom access checker.

### Complete example

```php
// src/Access/ItemAccessChecker.php
namespace Drupal\my_module\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

class ItemAccessChecker {

  /**
   * Checks access for editing own items.
   *
   * Parameters after $account are auto-resolved from route parameters.
   * The $node parameter matches the {node} slug in the route path.
   */
  public function access(AccountInterface $account, NodeInterface $node = NULL): AccessResult {
    if (!$node) {
      return AccessResult::forbidden('No node provided.')
        ->addCacheContexts(['url']);
    }

    // Check ownership + permission, with proper cache metadata.
    $is_owner = (int) $node->getOwnerId() === (int) $account->id();
    return AccessResult::allowedIf($is_owner && $account->hasPermission('edit my_module content'))
      ->addCacheContexts(['user', 'user.permissions'])
      ->addCacheTags(['node:' . $node->id()]);
  }

}
```

### AccessResult methods

| Method | Use when | Cache handling |
|--------|----------|---------------|
| `AccessResult::allowed()` | Granting unconditional access | Must add cache contexts manually |
| `AccessResult::forbidden('reason')` | Denying access (reason logged) | Must add cache contexts manually |
| `AccessResult::neutral()` | No opinion (other checks decide) | Must add cache contexts manually |
| `AccessResult::allowedIfHasPermission($account, 'perm')` | Permission-based access | Adds `user.permissions` context automatically |
| `AccessResult::allowedIf($condition)` | Conditional boolean access | Must add cache contexts manually |
| `AccessResult::forbiddenIf($condition, 'reason')` | Conditional denial | Must add cache contexts manually |

> WRONG: Returning bare `AccessResult::allowed()` or `AccessResult::forbidden()` without cache metadata. Access results are cached by Drupal's render system. Without `->addCacheContexts()`, the first user's access decision gets cached and applied to ALL users. A logged-in admin grants access once, and suddenly anonymous users see the page too.

> RIGHT: Always add cache contexts. Use `->addCacheContexts(['user.permissions'])` for permission-based checks, `->addCacheContexts(['user'])` for per-user checks, or use `AccessResult::allowedIfHasPermission()` which adds the correct cache context automatically.

### Common cache contexts for access results

| Context | When to use |
|---------|-------------|
| `user.permissions` | Access depends on user's permissions |
| `user` | Access depends on specific user identity (ownership) |
| `user.roles` | Access depends on user's roles |
| `url` | Access depends on the current URL/route |
| `url.query_args` | Access depends on query parameters |

### Cache tags for access results

Add cache tags when access depends on entity data that can change:

```php
// Access depends on node data -- invalidate when node changes.
return AccessResult::allowedIf($node->isPublished())
  ->addCacheContexts(['user.permissions'])
  ->addCacheTags(['node:' . $node->id()]);
```

## Entity access control

For custom entity types, define an access control handler that determines who can view, update, delete, and create entities.

### Define the handler

```php
// src/Entity/MessageAccessControlHandler.php
namespace Drupal\my_module\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class MessageAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'access my_module content');

      case 'update':
        // Owner can edit, or admin permission.
        $is_owner = (int) $entity->getOwnerId() === (int) $account->id();
        return AccessResult::allowedIf($is_owner)
          ->addCacheContexts(['user'])
          ->orIf(AccessResult::allowedIfHasPermission($account, 'administer my_module'));

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer my_module');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'edit my_module content');
  }

}
```

### Reference the handler in the entity definition

In D10 annotations:

```php
/**
 * @ContentEntityType(
 *   id = "my_module_message",
 *   label = @Translation("Message"),
 *   handlers = {
 *     "access" = "Drupal\my_module\Entity\MessageAccessControlHandler",
 *   },
 * )
 */
```

In D11 attributes:

```php
#[ContentEntityType(
  id: 'my_module_message',
  label: new TranslatableMarkup('Message'),
  handlers: [
    'access' => MessageAccessControlHandler::class,
  ],
)]
```

The default `EntityAccessControlHandler` checks `administer {entity_type}` permission. Only override when you need custom logic.

> WRONG: Checking permissions directly in the controller instead of using an entity access handler. This bypasses Drupal's access system -- entity listings, views, and REST won't respect your access rules.

> RIGHT: Implement `EntityAccessControlHandler` so access is enforced consistently everywhere the entity is loaded, not just in your controller.

### Programmatic entity access checks

To check access in controllers or services, use the entity's `access()` method or get the access control handler from `entityTypeManager()`:

```php
// Check access on an existing entity.
if ($entity->access('update', $this->currentUser())) {
  // User can update this entity.
}

// Check create access (no entity instance yet).
$can_create = $this->entityTypeManager()
  ->getAccessControlHandler('task')
  ->createAccess('bundle_name', $this->currentUser());
```

> WRONG: Calling `$storage->getAccessControlHandler()` on an entity storage object. The storage class (`EntityStorageInterface`) does NOT have a `getAccessControlHandler()` method -- this throws a fatal error at runtime.
> RIGHT: Get the access control handler from the entity type manager: `$this->entityTypeManager()->getAccessControlHandler('entity_type_id')`. The handler is a separate service, not a method on storage.

## CSRF protection

> **CRITICAL: EVERY route that changes state without a form MUST have `_csrf_token: 'TRUE'` in requirements.** This includes action links like "mark complete", "approve", "publish", "toggle", "archive", or any controller method that modifies an entity or database record. Without `_csrf_token`, an attacker can craft a URL that silently changes data when clicked by an authenticated user. This is a SECURITY VULNERABILITY. If the route has a form, the form token handles CSRF -- but non-form action routes have NO protection unless you add `_csrf_token: 'TRUE'`.

CSRF protection is for **non-form state-changing links** (approve, publish, toggle, delete-via-link, mark complete). Forms already have built-in CSRF protection via form tokens -- you do not need `_csrf_token` for form routes.

### Setup

```yaml
# my_module.routing.yml
my_module.toggle_status:
  path: '/my-module/toggle/{node}'
  defaults:
    _controller: '\Drupal\my_module\Controller\ToggleController::toggle'
  requirements:
    _permission: 'administer my_module'
    _csrf_token: 'TRUE'
  options:
    parameters:
      node:
        type: entity:node
```

### Building links (token is automatic)

```php
use Drupal\Core\Url;

// Drupal automatically appends ?token=XXXXX when you build the URL.
$url = Url::fromRoute('my_module.toggle_status', ['node' => $node->id()]);
$link = [
  '#type' => 'link',
  '#title' => $this->t('Toggle status'),
  '#url' => $url,
];
```

Drupal validates the token on the incoming request. No manual token generation, no manual validation. If the token is missing or invalid, Drupal returns a 403 automatically.

> WRONG: Manually generating and validating CSRF tokens with `\Drupal::csrfToken()` for routes. Use `_csrf_token: 'TRUE'` in the route requirements and build links with `Url::fromRoute()`. Drupal handles everything automatically.

> RIGHT: Add `_csrf_token: 'TRUE'` to route requirements and build links with `Url::fromRoute()`. Drupal generates and validates the token transparently.

## XSS prevention

Drupal provides multiple layers of XSS protection. Know which layer applies in each context.

### Twig templates (automatic protection)

Twig auto-escapes all variables by default. You must explicitly use `|raw` to output unescaped HTML.

```twig
{# Auto-escaped -- safe #}
{{ node.label }}
{{ user_input }}

{# NOT escaped -- only use with sanitized markup #}
{{ pre_sanitized_html|raw }}
```

### Render arrays

| Property | Behavior | Use for |
|----------|----------|---------|
| `#markup` | Allows a safe HTML subset (strips dangerous tags) | Pre-sanitized HTML content |
| `#plain_text` | Escapes everything -- all HTML rendered as text | User-provided text, untrusted input |

> WRONG: Using `#markup` with unsanitized user input. `#markup` strips dangerous tags but allows a safe subset (bold, italic, links). For truly untrusted input, this is not enough -- an attacker may craft payloads using allowed tags. Use `#plain_text` to escape everything, or explicitly sanitize with `Html::escape()` first.

> RIGHT: Use `#plain_text` for any user-provided text. Reserve `#markup` for content you have already sanitized or that comes from trusted sources.

### PHP sanitization functions

```php
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;

// Plain text -- escape all HTML characters.
$safe = Html::escape($user_input);

// Filtered HTML -- allow basic formatting tags.
$safe = Xss::filter($user_input);

// Admin HTML -- allow a broader set of tags (for admin-only content).
$safe = Xss::filterAdmin($user_input);
```

### Translation placeholders

The `t()` function and `TranslatableMarkup` escape placeholders automatically based on prefix:

| Prefix | Behavior | Example |
|--------|----------|---------|
| `@variable` | Escaped (HTML entities) | `$this->t('Hello @name', ['@name' => $input])` |
| `%variable` | Escaped + wrapped in `<em>` | `$this->t('Saved %title', ['%title' => $title])` |
| `:url` | Escaped + URL-validated | `$this->t('<a href=":url">Link</a>', [':url' => $url])` |

Never concatenate user input into translatable strings:

```php
// WRONG: Bypasses escaping.
$this->t('Hello ' . $user_input);

// RIGHT: Use placeholders.
$this->t('Hello @name', ['@name' => $user_input]);
```

## Cross-references

See also: **drupal-routing-controllers** (if installed) for route definitions where access requirements are declared. If not available, access requirements are set in `module_name.routing.yml` under the `requirements` key as shown in examples above.

See also: **drupal-entities-fields** (if installed) for entity type definitions that reference access handlers. If not available, set the access handler in the entity annotation/attribute `handlers` array as shown in the entity access section above.

See also: **drupal-plugins-blocks** (if installed) for block-level access via `blockAccess()`. If not available, override `blockAccess()` on your `BlockBase` subclass, returning an `AccessResult` with proper cache metadata.

See also: **drupal-forms-api** (if installed) for form-level security. Forms have built-in CSRF protection via form tokens -- the `_csrf_token` route requirement is for non-form state-changing links only.

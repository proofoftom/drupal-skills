---
name: drupal-caching
description: |
  Apply correct cache metadata (tags, contexts, max-age) to Drupal render arrays and
  implement cache invalidation patterns. Use WHENEVER producing render arrays that display
  entity or config data, working with blocks that need cache metadata, or troubleshooting
  stale content. Covers #cache on render arrays, getCacheTags()/getCacheContexts() on
  blocks, cache tag invalidation, and cache bubbling behavior.
  Do NOT use for building templates or themed output structure (use drupal-theming).
---

# Drupal Caching

Cache metadata (tags, contexts, max-age) is stable across Drupal 10 and 11. No syntax differences between versions.

## The golden rule: EVERY render array needs #cache

Before returning any render array, ask these three questions:

1. **What does it depend on?** -> cache tags
2. **What does it vary by?** -> cache contexts
3. **How long should it live?** -> max-age

```php
$build = [
  '#theme' => 'my_module_output',
  '#data' => $data,
  '#cache' => [
    'tags' => ['node:5', 'config:my_module.settings'],
    'contexts' => ['user.roles', 'url.path'],
    'max-age' => \Drupal\Core\Cache\Cache::PERMANENT,
  ],
];
```

> WRONG: Omitting `#cache` on render arrays. Render arrays without cache metadata become stale after the underlying data changes. Caching is invisible during development (usually disabled) but critical in production. Every render array that displays entity or config data MUST have `#cache` with appropriate tags, contexts, and max-age.
> RIGHT: Always add `'#cache' => ['tags' => [...], 'contexts' => [...], 'max-age' => ...]` to every render array. Use `$entity->getCacheTags()` and `$config->getCacheTags()` to get tags from dependent objects.

## Cache tags -- "What does this depend on?"

Cache tags are strings that mark what data a render array depends on. When that data changes, all render arrays with matching tags are invalidated automatically.

### Entity tags

```php
// Single entity: tag is "entity_type:id"
$tags = $node->getCacheTags();  // Returns ['node:5']

// Use entity's own tags -- never hardcode
$build['#cache']['tags'] = $node->getCacheTags();
```

### Entity list tags

```php
// List tag: invalidated when ANY entity of that type is created/updated/deleted
$tags = $node_type->getListCacheTags();  // Returns ['node_list']

// Use for listings where you don't know which entities appear
$build['#cache']['tags'] = ['node_list'];
```

### Config tags

```php
// Config objects provide their own tags
$config = \Drupal::config('my_module.settings');
$tags = $config->getCacheTags();  // Returns ['config:my_module.settings']

$build['#cache']['tags'] = $config->getCacheTags();
```

### Custom tags

```php
// Define custom tags for custom data
$build['#cache']['tags'] = ['my_module:custom_data'];

// Invalidate when your custom data changes
\Drupal\Core\Cache\Cache::invalidateTags(['my_module:custom_data']);
```

### Merging tags from multiple sources

Tags are ADDITIVE -- use all that apply. Merge when combining from multiple objects:

```php
use Drupal\Core\Cache\Cache;

$tags = Cache::mergeTags(
  $node->getCacheTags(),      // ['node:5']
  $config->getCacheTags()     // ['config:my_module.settings']
);
// Result: ['node:5', 'config:my_module.settings']

$build['#cache']['tags'] = $tags;
```

## Cache contexts -- "What does it vary by?"

Cache contexts tell Drupal to store separate cached versions based on runtime conditions. Contexts BUBBLE UP from child render arrays to the page level.

| Context | Varies by | Use when |
|---------|-----------|----------|
| `user` | Individual user | Content is unique per user (high cardinality -- see lazy builders) |
| `user.roles` | Role combination | Content differs by role (admin vs editor vs anonymous) |
| `user.permissions` | Permission set | Content differs by specific permissions (more granular than roles) |
| `url.path` | Current URL path | Content depends on which page it appears on |
| `url.query_args` | Query string | Content depends on query parameters (filters, pagination) |
| `languages` | Active language | Content varies by site language |
| `route` | Current route | Content differs by route name |

Contexts are hierarchical: `user` encompasses `user.roles`, which encompasses `user.permissions`. Use the most specific context that applies.

```php
// Render array that shows different content per role
$build = [
  '#markup' => $role_specific_message,
  '#cache' => [
    'tags' => $config->getCacheTags(),
    'contexts' => ['user.roles'],
  ],
];
```

## max-age -- "How long should it live?"

| Value | Meaning | Use when |
|-------|---------|----------|
| `Cache::PERMANENT` | Cached until tags invalidate it | Default. Data has proper cache tags for invalidation |
| `3600` | Cached for 1 hour | External data without invalidation tags (API responses) |
| `0` | NEVER cache | Highly dynamic content -- but prefer lazy builders instead |

> WRONG: Setting `max-age` to `0` without understanding consequences. `max-age` of `0` bubbles up to the PAGE level, preventing the entire page from being cached by Dynamic Page Cache. This degrades performance for the whole page, not just your component.
> RIGHT: Use lazy builders (`#lazy_builder`) to isolate uncacheable content. The lazy-built portion is replaced at render time while the rest of the page remains fully cached. Only use `max-age` of `0` when the entire controller response is truly uncacheable.

> WRONG: Relying on `max-age` of `0` for anonymous users. Internal Page Cache ignores bubbled `max-age`. Anonymous users will see stale content even with `max-age` set to `0`. This is a real Drupal bug trap.
> RIGHT: For truly uncacheable anonymous content, use the page cache kill switch service: `\Drupal::service('page_cache_kill_switch')->trigger()`. This prevents Internal Page Cache from caching the response.

## Lazy builders -- isolate uncacheable content

When a small part of the page is dynamic, use `#lazy_builder` instead of `max-age` of `0`. The rest of the page remains cacheable while the lazy-built content is replaced at render time via placeholders.

### Block plugin using lazy builder

```php
// In your block's build() method:
public function build() {
  return [
    '#lazy_builder' => [
      'my_module.lazy_builder:renderDynamicContent',  // service:method
      [$entity_id],                                     // scalar arguments only
    ],
    '#create_placeholder' => TRUE,
  ];
}
```

### Lazy builder service

```yaml
# my_module.services.yml
services:
  my_module.lazy_builder:
    class: Drupal\my_module\MyModuleLazyBuilder
    arguments: ['@entity_type.manager']
```

```php
namespace Drupal\my_module;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

class MyModuleLazyBuilder implements TrustedCallbackInterface {

  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function trustedCallbacks() {
    return ['renderDynamicContent'];
  }

  public function renderDynamicContent($entity_id) {
    $entity = $this->entityTypeManager->getStorage('node')->load($entity_id);
    return [
      '#markup' => $entity->label(),
      '#cache' => [
        'tags' => $entity->getCacheTags(),
        'max-age' => 0,
      ],
    ];
  }

}
```

> WRONG: Passing non-scalar arguments to lazy builders. Lazy builder arguments must be JSON-serializable scalars (strings, numbers, booleans). Passing objects or arrays causes fatal errors.
> RIGHT: Pass entity IDs (integers), configuration keys (strings), or boolean flags. Load the actual objects inside the lazy builder callback using injected services.

### Auto-placeholdering

Drupal automatically converts render arrays with certain cache properties into placeholders without you needing `#lazy_builder`. This happens when:

- `max-age` is `0`
- Cache context is `session` or `user` (high cardinality)

The auto-placeholder conditions are configurable via the `renderer.config` service parameter. However, explicit `#lazy_builder` with `#create_placeholder` is preferred for clarity.

## CacheableDependencyInterface

Implement on custom value objects, response objects, or any class that carries cache metadata.

```php
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\Cache;

class MyDataValue implements CacheableDependencyInterface {

  public function getCacheTags() {
    return ['my_module:data'];
  }

  public function getCacheContexts() {
    return ['user.roles'];
  }

  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

}
```

### Merging and applying cache metadata

```php
use Drupal\Core\Cache\CacheableMetadata;

// Create from a render array
$metadata = CacheableMetadata::createFromRenderArray($build);

// Create from any CacheableDependencyInterface object
$metadata = CacheableMetadata::createFromObject($entity);

// Merge metadata from multiple sources
$metadata->addCacheableDependency($config);
$metadata->addCacheContexts(['user.roles']);
$metadata->addCacheTags(['node_list']);

// Apply merged metadata back to a render array
$metadata->applyTo($build);
```

AccessResult already implements CacheableDependencyInterface -- access results carry cache tags and contexts that affect the render pipeline.

## Block plugin caching

Block plugins extend `BlockBase`, which implements `CacheableDependencyInterface`. Override the cache methods to declare your block's caching requirements.

```php
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

class MyBlock extends BlockBase {

  public function build() {
    return [
      '#markup' => $this->t('Dynamic content'),
      '#cache' => [
        'tags' => $this->getEntity()->getCacheTags(),
      ],
    ];
  }

  public function getCacheContexts() {
    // Always merge with parent -- parent may have contexts from block config
    return Cache::mergeContexts(parent::getCacheContexts(), ['user.roles']);
  }

  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['node_list']);
  }

  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

}
```

> WRONG: Overriding `getCacheContexts()` or `getCacheTags()` without merging with parent values. The parent block class may have contexts or tags from block configuration that you lose if you replace them entirely.
> RIGHT: Always use `Cache::mergeContexts(parent::getCacheContexts(), ['your.context'])` and `Cache::mergeTags(parent::getCacheTags(), ['your_tag'])` to combine with parent values.

## Cache API for custom storage

Use the Cache API directly when you need to cache expensive computations, external API responses, or aggregated data that doesn't fit into render arrays.

```php
// Get the default cache bin
$cache = \Drupal::cache();  // Or inject 'cache.default' service

// Get a specific bin
$cache = \Drupal::cache('data');

// Read
$cached = $cache->get('my_module:expensive_result');
if ($cached) {
  $data = $cached->data;
}
else {
  $data = $this->computeExpensiveResult();
  $cache->set('my_module:expensive_result', $data, \Drupal\Core\Cache\CacheBackendInterface::CACHE_PERMANENT, ['node_list']);
}

// Invalidate by tags (across ALL bins)
Cache::invalidateTags(['my_module:custom_tag']);

// Delete specific entry
$cache->delete('my_module:expensive_result');
```

Common cache bins: `default`, `render`, `page`, `discovery`, `data`. Tags on cache entries enable automatic invalidation via `Cache::invalidateTags()`.

> WRONG: Clearing entire cache bins to invalidate specific data. Deleting all entries in a bin is destructive and unnecessary. Drupal's tag-based invalidation automatically finds and invalidates only the affected entries across all bins.
> RIGHT: Use `Cache::invalidateTags(['my_tag'])` to invalidate specific entries. Under the hood, this uses the `cache_tags.invalidator` service. Inject that service rather than using the static call when possible.

## Internal Page Cache vs Dynamic Page Cache

Drupal has TWO separate page caching systems with different behaviors. Understanding both is critical for correct caching.

| Feature | Internal Page Cache | Dynamic Page Cache |
|---------|--------------------|--------------------|
| Serves | Anonymous users only | All users (including authenticated) |
| Caches | Full HTTP response | Individual render arrays |
| Respects max-age | NO (ignores bubbled max-age) | YES |
| Respects tags | YES (invalidation works) | YES |
| Respects contexts | NO (same response for all anonymous) | YES (varies by context) |
| Kill switch | `page_cache_kill_switch` service | Set `max-age` to `0` |

> WRONG: Assuming `max-age` of `0` prevents anonymous caching. Internal Page Cache does NOT respect bubbled `max-age`. If an anonymous user visits a page first, ALL subsequent anonymous users see that cached version regardless of `max-age` settings on child render arrays.
> RIGHT: For pages that must not be cached for anonymous users, use `\Drupal::service('page_cache_kill_switch')->trigger()` in your controller or event subscriber. This bypasses Internal Page Cache entirely.

### When to use each strategy

- **Content varies by user/role but anonymous sees the same thing:** Default behavior works. Tags invalidate, Dynamic Page Cache varies by context.
- **Content must NEVER be cached for anyone:** Use lazy builders with `max-age` of `0` inside the lazy-built render array.
- **Content must NEVER be cached for anonymous users specifically:** Use `page_cache_kill_switch` service.

## Cross-references

See also: **drupal-theming** (if installed) for render array structure and `#theme` patterns. Cache metadata goes on EVERY render array the theming skill teaches you to build. If not available, add `'#cache' => ['tags' => [...], 'contexts' => [...], 'max-age' => ...]` to all render arrays.

See also: **drupal-access-security** (if installed) for `AccessResult` cache metadata. Access results carry cache tags and contexts that affect the render pipeline. Use `$access->addCacheableDependency($config)` and `$access->addCacheContexts(['user.roles'])`. If not available, always add cache metadata to `AccessResult` objects via `addCacheableDependency()` and `addCacheContexts()`.

See also: **drupal-plugins-blocks** (if installed) for block plugin caching overrides (`getCacheContexts()`, `getCacheTags()`). Blocks extend `BlockBase` which implements `CacheableDependencyInterface`. If not available, override `getCacheContexts()` and `getCacheTags()` on `BlockBase` subclasses, always merging with parent values.

See also: **drupal-entities-fields** (if installed) for entity cache tags via `getCacheTags()` and list cache tags via `getListCacheTags()`. Entities automatically implement `CacheableDependencyInterface`. If not available, use `$entity->getCacheTags()` for individual entity tags and the `entity_type_list` pattern (e.g., `node_list`) for list invalidation.

---
name: drupal-theming
description: |
  Theme Drupal output by building render arrays, Twig templates, hook_theme(),
  preprocess functions, and CSS/JS library attachments. Use WHENEVER producing visible
  output in a Drupal module: creating themed markup, building a template, defining a
  theme hook, attaching CSS or JavaScript libraries, or rendering structured data with
  #theme or #type render elements. Covers render array types and .libraries.yml.
  Do NOT use for cache invalidation or cache metadata strategy (use drupal-caching).
---

# Drupal Theming

## What are you rendering?

Start here. Every controller, block, and form in Drupal returns a render array. Choose the right render array type based on what you need to output.

**Simple text or markup?**
Use `#markup`. Content is sanitized by `Xss::filterAdmin` (allows basic HTML tags).

```php
return ['#markup' => '<p>Hello <em>world</em></p>'];
```

**Plain text (fully escaped)?**
Use `#plain_text`. All HTML is escaped -- safe for user-provided content.

```php
return ['#plain_text' => $user_input];
```

**Custom themed output (your own template)?**
Define `hook_theme()`, use `#theme`, create a Twig template. This is the standard approach for any structured output.

```php
return [
  '#theme' => 'hello_world_salutation',
  '#salutation' => $salutation,
  '#target' => $target_name,
];
```

**Existing core theme hook (table, item_list, links)?**
Use `#theme` with the core hook name. No need to define your own hook_theme().

```php
return ['#theme' => 'table', '#header' => $header, '#rows' => $rows];
```

**Render element (form elements, containers)?**
Use `#type`. These are standardized components with built-in rendering.

```php
return ['#type' => 'html_tag', '#tag' => 'h2', '#value' => $this->t('Title')];
```

> WRONG: Returning raw HTML strings from controllers (`return '<div>Hello</div>';`). Strings bypass caching, theme overrides, altering hooks, and security filtering.
> RIGHT: Always return render arrays. Even simple output should use `#markup` or `#plain_text`, which integrate with Drupal's render pipeline.

> **CRITICAL**: When you define custom theme hooks via `hook_theme()`, you MUST actually USE them by returning render arrays with `'#theme' => 'your_hook_name'` from controllers, block `build()` methods, or preprocess functions. Declaring a theme hook without using `#theme` in a render array means the template is never rendered. ALSO: Always attach your CSS library in the SAME render array via `'#attached' => ['library' => ['module_name/library_name']]`. A `.libraries.yml` file without `#attached` means your CSS is never loaded.

## Render arrays -- the core concept

Render arrays are NOT HTML. They are declarative descriptions that Drupal renders through its pipeline (applying cache metadata, security filtering, theme overrides, and asset attachment).

**Keys starting with `#` are properties:**
- `#theme` -- which theme hook renders this
- `#markup` -- simple sanitized HTML
- `#plain_text` -- fully escaped text
- `#type` -- render element type
- `#cache` -- cache metadata (tags, contexts, max-age)
- `#attached` -- libraries, drupalSettings
- `#prefix`, `#suffix` -- HTML wrappers
- `#access` -- boolean or AccessResult controlling visibility
- `#weight` -- ordering among siblings

**Keys NOT starting with `#` are children** (nested render arrays):

```php
$build = [
  '#type' => 'container',
  'heading' => ['#markup' => '<h2>Title</h2>'],
  'content' => ['#markup' => '<p>Body text</p>'],
];
```

Children are rendered in `#weight` order and output is concatenated.

## Defining a theme hook (hook_theme)

Implement `hook_theme()` in your `.module` file. This defines the data contract between PHP and your Twig template.

### Complete hook_theme() example

```php
// hello_world.module

/**
 * Implements hook_theme().
 */
function hello_world_theme($existing, $type, $theme, $path) {
  return [
    'hello_world_salutation' => [
      'variables' => [
        'salutation' => NULL,
        'target' => NULL,
        'overridden' => FALSE,
      ],
    ],
  ];
}
```

**Rules:**
- The hook name (`hello_world_salutation`) becomes the `#theme` value in render arrays
- `variables` defines every variable the template can use, with default values
- Default values should be `NULL`, empty arrays, or `FALSE` -- never leave them undefined
- Multiple theme hooks can be returned from a single `hook_theme()` implementation

> WRONG: Omitting variables in `hook_theme()`. Every variable the template uses must be declared with a default value, or it will be undefined in Twig and cause silent failures.
> RIGHT: Declare all variables with defaults: `'variables' => ['title' => NULL, 'items' => []]`. The variables array IS the template's API contract.

## Template naming and location

Drupal maps theme hook names to template filenames using a strict convention:

| Hook name | Template filename | Location |
|-----------|------------------|----------|
| `hello_world_salutation` | `hello-world-salutation.html.twig` | `templates/` |
| `my_module_card` | `my-module-card.html.twig` | `templates/` |
| `license_plate` | `license-plate.html.twig` | `templates/` |

**The rule:** Underscores in hook name become hyphens in template filename. Extension is always `.html.twig`. Templates go in the `templates/` directory inside your module.

> WRONG: Template name not matching hook name. Hook `my_module_block` requires template `my-module-block.html.twig`, not `my_module_block.html.twig` or `myModuleBlock.html.twig`.
> RIGHT: Convert every underscore to a hyphen. Place the file in `templates/`. Drupal will not find the template if the naming convention is wrong.

## Twig template patterns

### Basic template with attributes and conditionals

```twig
{# templates/hello-world-salutation.html.twig #}
<div{{ attributes }}>
  {{ salutation }}
  {% if target %}
    <span class="salutation--target">{{ target }}</span>
  {% endif %}
  {% if overridden %}
    <em>{{ 'Overridden'|t }}</em>
  {% endif %}
</div>
```

### Key Twig syntax

| Syntax | Purpose | Notes |
|--------|---------|-------|
| `{{ variable }}` | Output (auto-escaped) | Safe by default |
| `{% if condition %}` | Logic block | `{% endif %}` to close |
| `{% for item in items %}` | Loop | `{% endfor %}` to close |
| `{{ attributes }}` | Render attributes object | Classes, id, data-* attributes |
| `{{ 'Text'|t }}` | Translation filter | Translatable strings in templates |
| `{{ content|raw }}` | Unescaped output | AVOID -- security risk unless content is pre-sanitized |

**Auto-escaping is on by default.** This is a security feature. Do NOT use `|raw` unless you are certain the content has already been sanitized by Drupal's render pipeline.

### Attributes object

The `attributes` variable is an `Attribute` object passed to templates. It renders HTML attributes and supports:

```twig
{# Add classes #}
<div{{ attributes.addClass('my-class', 'another-class') }}>

{# Check for a class #}
{% if attributes.hasClass('active') %}

{# Set arbitrary attribute #}
<div{{ attributes.setAttribute('role', 'banner') }}>
```

## Preprocess functions

Preprocess functions prepare data for templates. They run before the template renders and can add computed variables, set default attributes, or transform data.

### Naming convention

- `template_preprocess_HOOK(&$variables)` -- default preprocessor (in `.module` file)
- `MODULE_preprocess_HOOK(&$variables)` -- module-specific preprocessor

### Example: adding CSS classes in preprocess

```php
// hello_world.module

/**
 * Prepares variables for hello_world_salutation templates.
 *
 * Default template: hello-world-salutation.html.twig.
 */
function template_preprocess_hello_world_salutation(&$variables) {
  $variables['attributes']['class'][] = 'salutation';
  if ($variables['overridden']) {
    $variables['attributes']['class'][] = 'salutation--overridden';
  }
}
```

The `$variables['attributes']` array is automatically converted to an `Attribute` object before the template renders.

## Libraries API (CSS/JS attachment)

Never include CSS or JS with inline `<script>` or `<style>` tags. Drupal uses the Libraries API for all asset management.

### Step 1: Define a library in module_name.libraries.yml

```yaml
# hello_world.libraries.yml
hello_world_clock:
  version: 1.x
  css:
    component:
      css/hello_world_clock.css: {}
  js:
    js/hello_world_clock.js: {}
  dependencies:
    - core/jquery
    - core/drupal
    - core/once
```

**CSS weight categories** (determines load order):
- `base` -- CSS reset, normalize
- `layout` -- page structure, grid
- `component` -- discrete UI components (most common)
- `state` -- active, hover, disabled states
- `theme` -- visual styling, colors, fonts

### Step 2: Attach the library to a render array

```php
$build = [
  '#theme' => 'hello_world_salutation',
  '#salutation' => $salutation,
  '#attached' => [
    'library' => ['hello_world/hello_world_clock'],
  ],
];
```

The library name format is `module_name/library_name`.

### Library load ordering and `defer`

When a library externalizes a dependency as a global variable (e.g., Vue loaded separately from the consuming bundle), be careful with `defer` and `header` attributes:

> WRONG: Using `{ attributes: { defer: true } }` on a library that provides a global variable (like Vue) consumed by IIFE-format bundles in the footer. Deferred scripts execute AFTER non-deferred footer scripts, so the consuming bundle runs before the global is defined — causing "X is not defined" errors.
> RIGHT: Omit `defer` on libraries that provide globals consumed by other scripts. Use `header: true` and `weight: -20` to ensure the global loads first. Drupal's library dependency system handles load ordering — `defer` breaks it by changing execution timing.

```yaml
# WRONG: defer causes Vue to execute AFTER kanban.js
vue:
  js:
    js/vendor/vue.global.prod.js: { attributes: { defer: true } }
  header: true

# RIGHT: no defer, dependency chain handles ordering
vue:
  js:
    js/vendor/vue.global.prod.js: { minified: true }
  header: true
  weight: -20
```

### Attaching to all pages

For module-wide CSS/JS, use `hook_page_attachments()`:

```php
function hello_world_page_attachments(array &$attachments) {
  $attachments['#attached']['library'][] = 'hello_world/global_styles';
}
```

> WRONG: Using inline `<script>` or `<style>` tags in templates or PHP output. Inline tags bypass aggregation, dependency management, conditional loading, and Content Security Policy.
> RIGHT: Define all CSS/JS in `module_name.libraries.yml` and attach via `#attached`. This integrates with Drupal's asset pipeline for aggregation, minification, and dependency resolution.

## Passing PHP values to JavaScript (drupalSettings)

To pass data from PHP to JavaScript, use `drupalSettings`:

```php
$build['#attached']['drupalSettings']['hello_world']['refresh_interval'] = 5000;
$build['#attached']['drupalSettings']['hello_world']['api_endpoint'] = '/api/data';
```

Access in JavaScript (inside a behavior):

```javascript
var interval = settings.hello_world.refresh_interval;
var endpoint = settings.hello_world.api_endpoint;
```

Values must be JSON-serializable (scalars, arrays, simple objects). Do not pass PHP objects or resources.

## Common core theme hooks

Use these built-in hooks instead of creating custom templates for standard data patterns.

### Table

```php
$build = [
  '#theme' => 'table',
  '#header' => ['Name', 'Email', 'Role'],
  '#rows' => [
    ['Alice', 'alice@example.com', 'Admin'],
    ['Bob', 'bob@example.com', 'Editor'],
  ],
  '#empty' => $this->t('No users found.'),
];
```

### Item list

```php
$build = [
  '#theme' => 'item_list',
  '#items' => ['First item', 'Second item', 'Third item'],
  '#list_type' => 'ol',  // 'ul' (default) or 'ol'
  '#title' => $this->t('Steps'),
];
```

### Links

```php
use Drupal\Core\Url;

$build = [
  '#theme' => 'links',
  '#links' => [
    'home' => [
      'title' => $this->t('Home'),
      'url' => Url::fromRoute('<front>'),
    ],
    'about' => [
      'title' => $this->t('About'),
      'url' => Url::fromRoute('my_module.about'),
    ],
  ],
  '#set_active_class' => TRUE,
];
```

## Complete file ecosystem example

A module with custom themed output requires these paired files:

```
modules/custom/hello_world/
  hello_world.info.yml          # Module definition
  hello_world.module            # hook_theme() + preprocess functions
  hello_world.libraries.yml     # CSS/JS library definitions
  src/
    Controller/
      HelloWorldController.php  # Returns render array with #theme
  templates/
    hello-world-salutation.html.twig  # Twig template
  css/
    hello_world.css             # Styles (attached via library)
  js/
    hello_world.js              # Scripts (attached via library)
```

**The render pipeline flows:**
1. Controller returns render array with `#theme => 'hello_world_salutation'`
2. Drupal finds `hook_theme()` definition, gets variable defaults
3. Preprocess function runs, adds computed variables and attributes
4. Twig template renders with variables and attributes object
5. Attached libraries (CSS/JS) are added to the page

## D10/D11 compatibility

The theming APIs (render arrays, `hook_theme()`, Twig templates, preprocess functions, Libraries API) are stable across Drupal 10 and 11 with no syntax changes. Code written for D10 theming works identically in D11.

The only D11-related change in the theming area is that PHP attributes replace annotations for plugin discovery (e.g., block plugins that return themed render arrays), but the render array and template system itself is unchanged.

## Cross-references

See also: **drupal-caching** (if installed) for cache metadata (`#cache`) on render arrays. EVERY render array displaying entity or config data must include cache tags, contexts, and max-age. If not available, add `'#cache' => ['tags' => $entity->getCacheTags(), 'contexts' => ['user'], 'max-age' => \Drupal\Core\Cache\Cache::PERMANENT]` to render arrays that display dynamic content.

See also: **drupal-forms-api** (if installed) for the Form API, which uses render arrays with `#type` for form elements. If not available, form elements use the same render array syntax: `['#type' => 'textfield', '#title' => $this->t('Name')]`. Forms return render arrays from `buildForm()`.

See also: **drupal-routing-controllers** (if installed) for controllers that return render arrays. If not available, controllers return render arrays from their methods, and Drupal renders them through the theme pipeline.

See also: **drupal-entities-fields** (if installed) for entity view builders and field formatters that produce render arrays. If not available, use `\Drupal::entityTypeManager()->getViewBuilder('node')->view($node)` to get a render array for an entity.

See also: **references/js-ajax.md** for JavaScript behaviors, Ajax API, and States system patterns.

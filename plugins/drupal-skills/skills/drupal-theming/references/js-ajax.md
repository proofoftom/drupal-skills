# JavaScript, Ajax API, and States System

This reference covers client-side Drupal patterns: JavaScript behaviors, the `once()` library, `drupalSettings`, the Ajax API, and the States system. These are the correct patterns for adding dynamic behavior to Drupal modules.

## Drupal.behaviors -- the correct way to attach JavaScript

Drupal does NOT use `$(document).ready()` or `DOMContentLoaded`. Instead, all JavaScript runs through the behaviors system.

### The behavior pattern

```javascript
(function (Drupal, $, once) {
  "use strict";

  Drupal.behaviors.myModuleFeature = {
    attach: function (context, settings) {
      // context: DOM element being processed
      //   - document on initial page load
      //   - Ajax-inserted element on Ajax/BigPipe loads
      // settings: drupalSettings object (PHP values passed to JS)

      $(once('myModuleFeature', '.my-selector', context)).each(function () {
        // DOM manipulation here -- runs once per element
        var element = $(this);
        element.addClass('processed');
      });
    },
    detach: function (context, settings, trigger) {
      // Optional: cleanup when elements are removed from DOM
      // trigger is 'unload', 'move', or 'serialize'
      if (trigger === 'unload') {
        // Clean up event listeners, intervals, etc.
      }
    }
  };
})(Drupal, jQuery, once);
```

### Why behaviors, not document.ready?

Behaviors re-fire every time new content is added to the page:
- Initial full page load
- Ajax responses that update page sections
- BigPipe placeholders being filled
- Content loaded via Views infinite scroll, lazy loading, etc.

This is by design. A behavior's `attach` function may be called many times during a single page session.

> WRONG: Using `$(document).ready()` or `document.addEventListener('DOMContentLoaded', ...)` in Drupal modules. These fire once on initial page load and miss all Ajax-inserted content.
> RIGHT: Use `Drupal.behaviors`. Behaviors fire on every page update (Ajax, BigPipe, pjax), ensuring your JavaScript processes all content regardless of how it was loaded.

### Vanilla JavaScript (no jQuery)

```javascript
(function (Drupal, once) {
  "use strict";

  Drupal.behaviors.myModuleVanilla = {
    attach: function (context, settings) {
      once('myModuleVanilla', '.my-selector', context).forEach(function (element) {
        element.classList.add('processed');
        element.addEventListener('click', function (e) {
          // Handle click
        });
      });
    }
  };
})(Drupal, once);
```

jQuery is optional in modern Drupal. If you do not need jQuery, omit `core/jquery` from your library dependencies.

## once() -- prevent duplicate processing

Since behaviors re-fire on every Ajax load, you MUST use `once()` to prevent duplicate DOM manipulation.

### How once() works

`once('uniqueId', selector, context)` returns only elements that have NOT been processed with that unique ID before. On subsequent calls with the same ID, already-processed elements are skipped.

### jQuery pattern

```javascript
$(once('myUniqueId', '.selector', context)).each(function () {
  // This code runs once per element, even if attach fires multiple times
});
```

### Vanilla JS pattern

```javascript
once('myUniqueId', '.selector', context).forEach(function (element) {
  // Runs once per element
});
```

### Library dependency

Declare `core/once` as a dependency in your library:

```yaml
# module_name.libraries.yml
my_feature:
  js:
    js/my_feature.js: {}
  dependencies:
    - core/drupal
    - core/once
```

> WRONG: Forgetting `once()` in behaviors. Without it, every Ajax load duplicates your DOM changes -- appended elements multiply, event handlers stack, intervals compound.
> RIGHT: Always wrap DOM manipulation in `once()`. The unique ID string should be specific to your behavior (e.g., `'myModuleClock'`, not `'init'`).

### Removing once tracking

If you need to re-process elements (rare), use `once.remove()`:

```javascript
once.remove('myUniqueId', '.selector', context);
```

## drupalSettings -- passing PHP values to JavaScript

PHP code passes values to JavaScript through the `drupalSettings` mechanism.

### PHP side (in a controller, form, or preprocess function)

```php
$build['#attached']['drupalSettings']['my_module'] = [
  'apiEndpoint' => '/api/v1/data',
  'refreshInterval' => 5000,
  'userId' => $current_user->id(),
];
```

### JavaScript side (in a behavior)

```javascript
Drupal.behaviors.myModuleApi = {
  attach: function (context, settings) {
    // settings.my_module contains the PHP values
    var endpoint = settings.my_module.apiEndpoint;
    var interval = settings.my_module.refreshInterval;

    once('myModuleApi', '.api-widget', context).forEach(function (element) {
      // Use the PHP-provided values
      setInterval(function () {
        fetch(endpoint).then(function (response) { /* ... */ });
      }, interval);
    });
  }
};
```

### Rules for drupalSettings

- Values must be JSON-serializable (strings, numbers, booleans, arrays, plain objects)
- Do NOT pass PHP objects, closures, or resources
- Namespace under your module name (`settings.my_module.key`) to avoid collisions
- Check for existence before accessing: `if (settings.my_module && settings.my_module.key)`

## Ajax API -- dynamic form and link behavior

The Ajax API lets you update page content without full page reloads. There are two main patterns: Ajax forms and Ajax links.

### Ajax form callbacks

Add `#ajax` to a form element to trigger an Ajax request when it changes:

```php
$form['color'] = [
  '#type' => 'select',
  '#title' => $this->t('Color'),
  '#options' => ['red' => 'Red', 'blue' => 'Blue', 'green' => 'Green'],
  '#ajax' => [
    'callback' => '::colorCallback',
    'wrapper' => 'color-preview-wrapper',
    'event' => 'change',  // Optional: defaults to appropriate event for element type
  ],
];

$form['preview'] = [
  '#type' => 'container',
  '#attributes' => ['id' => 'color-preview-wrapper'],
  '#markup' => '<p>Select a color to preview.</p>',
];
```

### Simple callback (return render array)

The simplest Ajax callback returns a render array that replaces the wrapper element:

```php
public function colorCallback(array &$form, FormStateInterface $form_state) {
  $color = $form_state->getValue('color');
  return [
    '#type' => 'container',
    '#attributes' => ['id' => 'color-preview-wrapper'],
    '#markup' => '<p style="color: ' . $color . ';">Preview in ' . $color . '</p>',
  ];
}
```

The returned render array replaces the content of the element matching `#ajax['wrapper']`.

### Advanced callback (AjaxResponse with commands)

For multiple DOM updates, return an `AjaxResponse` with commands:

```php
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\RemoveCommand;

public function advancedCallback(array &$form, FormStateInterface $form_state) {
  $response = new AjaxResponse();

  // Replace an element entirely
  $response->addCommand(new ReplaceCommand('#element-id', $new_render_array));

  // Set innerHTML of an element
  $response->addCommand(new HtmlCommand('#status', '<p>Updated!</p>'));

  // Change CSS properties
  $response->addCommand(new CssCommand('#element', ['color' => 'red']));

  // Call any jQuery method on an element
  $response->addCommand(new InvokeCommand('#element', 'addClass', ['highlight']));

  // Remove an element
  $response->addCommand(new RemoveCommand('.old-content'));

  return $response;
}
```

### Common Ajax commands

| Command | Purpose | Arguments |
|---------|---------|-----------|
| `ReplaceCommand` | Replace element (outerHTML) | `(selector, content)` |
| `HtmlCommand` | Set innerHTML | `(selector, html)` |
| `CssCommand` | Set CSS properties | `(selector, css_array)` |
| `InvokeCommand` | Call jQuery method | `(selector, method, args)` |
| `RemoveCommand` | Remove element | `(selector)` |
| `InsertCommand` / `AppendCommand` / `PrependCommand` | Insert content | `(selector, content)` |
| `AlertCommand` | Show alert dialog | `(message)` |
| `RedirectCommand` | Redirect browser | `(url)` |

### Ajax links

Add the `use-ajax` class to links for Ajax-powered navigation:

```php
use Drupal\Core\Url;

$build['link'] = [
  '#type' => 'link',
  '#title' => $this->t('Load more'),
  '#url' => Url::fromRoute('my_module.ajax_endpoint'),
  '#attributes' => ['class' => ['use-ajax']],
];
```

The controller at that route must return an `AjaxResponse`:

```php
public function ajaxEndpoint(Request $request) {
  if (!$request->isXmlHttpRequest()) {
    throw new NotFoundHttpException();
  }

  $response = new AjaxResponse();
  $response->addCommand(new HtmlCommand('#target', $rendered_content));
  return $response;
}
```

## States API -- declarative form element visibility

The States system makes form elements dynamic based on other elements' values -- no JavaScript needed.

### Basic syntax

```php
$form['has_children'] = [
  '#type' => 'checkbox',
  '#title' => $this->t('Do you have children?'),
];

$form['num_children'] = [
  '#type' => 'number',
  '#title' => $this->t('How many children?'),
  '#states' => [
    'visible' => [
      ':input[name="has_children"]' => ['checked' => TRUE],
    ],
  ],
];
```

When the checkbox is checked, the number field appears. When unchecked, it hides. No custom JavaScript required.

### Available states

| State | Effect |
|-------|--------|
| `visible` / `invisible` | Show/hide element |
| `enabled` / `disabled` | Enable/disable element |
| `required` / `optional` | Toggle required status |
| `checked` / `unchecked` | Check/uncheck checkbox |
| `expanded` / `collapsed` | Open/close details element |

### Available conditions

| Condition | Triggers when |
|-----------|--------------|
| `checked` | Checkbox is checked |
| `unchecked` | Checkbox is unchecked |
| `empty` | Field is empty |
| `filled` | Field has a value |
| `value` | Field matches specific value |
| `expanded` | Details element is open |
| `collapsed` | Details element is closed |

### Multiple conditions (AND)

```php
'#states' => [
  'visible' => [
    ':input[name="type"]' => ['value' => 'other'],
    ':input[name="confirm"]' => ['checked' => TRUE],
  ],
],
```

Both conditions must be true (AND logic).

### Multiple conditions (OR)

```php
'#states' => [
  'visible' => [
    [':input[name="type"]' => ['value' => 'a']],
    [':input[name="type"]' => ['value' => 'b']],
  ],
],
```

Wrapping conditions in inner arrays creates OR logic.

### Selector syntax

The selector in `#states` is a jQuery selector. Common patterns:
- `:input[name="field_name"]` -- standard form element
- `:input[name="field_name[value]"]` -- nested form element
- `select[name="field_name"]` -- specifically a select element

> WRONG: Writing custom JavaScript to show/hide form elements based on other fields' values. This duplicates what Drupal handles natively and breaks on form rebuilds.
> RIGHT: Use the `#states` API. It is declarative, works with Ajax form rebuilds, and requires zero JavaScript. Drupal handles all the client-side logic.

## Library setup for JavaScript features

### Minimal library (vanilla JS, behaviors only)

```yaml
my_feature:
  js:
    js/my_feature.js: {}
  dependencies:
    - core/drupal
    - core/once
```

### Library with jQuery

```yaml
my_feature:
  js:
    js/my_feature.js: {}
  dependencies:
    - core/jquery
    - core/drupal
    - core/once
```

### Library with drupalSettings

No extra dependency needed for `drupalSettings` -- it is always available via the `settings` parameter in behaviors.

### Attaching to a render array

```php
$build['#attached']['library'][] = 'my_module/my_feature';
```

## Common mistakes reference

| Mistake | Why it fails | Fix |
|---------|-------------|-----|
| `$(document).ready()` | Fires once, misses Ajax content | Use `Drupal.behaviors` |
| Missing `once()` | DOM changes duplicate on Ajax | Wrap in `once('id', selector, context)` |
| Missing `core/once` dependency | `once is not defined` error | Add to library dependencies |
| `drupalSettings.module.key` in global scope | Settings may not exist yet | Access inside behavior `attach` via `settings` param |
| Inline `<script>` tags | Bypass asset pipeline | Use libraries.yml |
| jQuery without dependency | jQuery not loaded | Add `core/jquery` to dependencies |

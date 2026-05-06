---
name: drupal-forms-api
description: |
  Build Drupal forms with the Form API lifecycle (buildForm, validateForm, submitForm).
  Use when asked to create a form, settings page, admin configuration page, confirmation
  dialog, or alter an existing form in a Drupal module. Covers ConfigFormBase for settings
  forms, _form route key, form_alter hooks, and AJAX form elements.
  Do NOT use for entity forms (use drupal-entities-fields for entity add/edit forms).
---

# Drupal Forms API

## What kind of form do you need?

Choose the right base class based on your form's purpose:

**Is it a configuration/settings form?**
YES -> Extend `ConfigFormBase`. Implement `getEditableConfigNames()`, `getFormId()`, `buildForm()`, `submitForm()`. Pair with config schema YAML.

**Is it a standalone form (contact, search, custom)?**
YES -> Extend `FormBase`. Implement `getFormId()`, `buildForm()`, `submitForm()`, optionally `validateForm()`.

**Is it a confirmation form (delete, irreversible action)?**
YES -> Extend `ConfirmFormBase`. Implement `getQuestion()`, `getCancelUrl()`, `submitForm()`.

**Are you altering an existing form?**
YES -> Use `hook_form_alter()` or `hook_form_FORM_ID_alter()` in the .module file.

## FormBase lifecycle

Every Drupal form class implements these methods in sequence:

### getFormId()

Returns a unique machine name for the form. Used internally by Drupal for form caching, theming, and alter hooks.

```php
public function getFormId() {
  return 'my_module_example_form';
}
```

### buildForm()

Returns a render array defining the form elements. Each element is keyed by machine name.

```php
public function buildForm(array $form, FormStateInterface $form_state) {
  $form['name'] = [
    '#type' => 'textfield',
    '#title' => $this->t('Name'),
    '#required' => TRUE,
    '#default_value' => '',
  ];

  $form['email'] = [
    '#type' => 'email',
    '#title' => $this->t('Email address'),
    '#required' => TRUE,
  ];

  $form['actions']['submit'] = [
    '#type' => 'submit',
    '#value' => $this->t('Submit'),
  ];

  return $form;
}
```

### validateForm()

Called before submission. Use `$form_state->setErrorByName()` to flag specific fields. When any error is set, the form redisplays with the offending fields highlighted -- submission is prevented.

```php
public function validateForm(array &$form, FormStateInterface $form_state) {
  $name = $form_state->getValue('name');
  if (mb_strlen($name) > 100) {
    $form_state->setErrorByName('name', $this->t('Name must be 100 characters or fewer.'));
  }
}
```

> WRONG: Validating with exceptions or `drupal_set_message()` inside `validateForm()`. These do not prevent form submission and do not highlight the field with the error.
> RIGHT: Use `$form_state->setErrorByName('field_name', $this->t('Error message'))`. This flags the specific form element, prevents submission, and redisplays the form with the error highlighted on the correct field.

### submitForm()

Called only after validation passes. Process values, save data, set messages, redirect.

```php
public function submitForm(array &$form, FormStateInterface $form_state) {
  $name = $form_state->getValue('name');
  $this->messenger()->addMessage($this->t('Thank you, @name.', ['@name' => $name]));
  $form_state->setRedirect('<front>');
}
```

> WRONG: Using `drupal_set_message()` to display status messages. This function was deprecated in Drupal 8.5 and removed in Drupal 11.
> RIGHT: Use `$this->messenger()->addMessage()` (in form/controller classes) or `\Drupal::messenger()->addMessage()` (in procedural .module code only).

### Dependency injection in form classes

Forms use the same DI pattern as controllers: implement `ContainerInjectionInterface` (already done by `FormBase`) and override `create()`.

```php
namespace Drupal\my_module\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\my_module\MyService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ExampleForm extends FormBase {

  protected $myService;

  public function __construct(MyService $my_service) {
    $this->myService = $my_service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('my_module.my_service')
    );
  }

  // getFormId(), buildForm(), submitForm() ...

}
```

> WRONG: Using `\Drupal::service('my_service')` inside form class methods. Static service calls bypass dependency injection, making the form untestable.
> RIGHT: Inject services via `create()` + constructor. `FormBase` already implements `ContainerInjectionInterface`. The `.module` file is the ONE place where `\Drupal::service()` calls are acceptable because procedural code cannot use constructor injection.

## ConfigFormBase for settings forms

When your form saves to Drupal's Config API, extend `ConfigFormBase` instead of `FormBase`. It provides config helpers and handles boilerplate.

### Complete ConfigFormBase example (3 files)

**File 1: my_module.routing.yml**

```yaml
my_module.settings:
  path: '/admin/config/my-module/settings'
  defaults:
    _form: '\Drupal\my_module\Form\SettingsForm'
    _title: 'My Module Settings'
  requirements:
    _permission: 'administer site configuration'
  options:
    _admin_route: TRUE
```

Note: Use `_form` (not `_controller`) in the route defaults to point to a form class.

**File 2: src/Form/SettingsForm.php**

```php
namespace Drupal\my_module\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['my_module.settings'];
  }

  public function getFormId() {
    return 'my_module_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('my_module.settings');

    $form['api_endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('API Endpoint'),
      '#default_value' => $config->get('api_endpoint'),
      '#required' => TRUE,
    ];

    $form['max_items'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum items'),
      '#default_value' => $config->get('max_items') ?? 10,
      '#min' => 1,
      '#max' => 100,
    ];

    // parent::buildForm() adds the submit button automatically.
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('my_module.settings')
      ->set('api_endpoint', $form_state->getValue('api_endpoint'))
      ->set('max_items', $form_state->getValue('max_items'))
      ->save();

    // parent::submitForm() adds the "saved" status message automatically.
    parent::submitForm($form, $form_state);
  }

}
```

Key ConfigFormBase behavior:
- `getEditableConfigNames()`: returns config names this form edits (required by `ConfigFormBaseTrait`)
- `$this->config('my_module.settings')`: loads config for reading in `buildForm()`
- `$this->config('my_module.settings')->set()->save()`: saves values in `submitForm()`
- `parent::buildForm()`: adds the submit button -- do not add your own unless you need extra buttons
- `parent::submitForm()`: adds "The configuration options have been saved." message

**File 3: config/schema/my_module.schema.yml**

```yaml
my_module.settings:
  type: config_object
  label: 'My Module settings'
  mapping:
    api_endpoint:
      type: string
      label: 'API Endpoint'
    max_items:
      type: integer
      label: 'Maximum items'
```

> WRONG: Creating a ConfigFormBase form without a config schema file. The form "works" in development, but config export/import fails silently, configuration validation is skipped, and translation of config values is impossible.
> RIGHT: Always create `config/schema/module_name.schema.yml` paired with your ConfigFormBase. Use `type: config_object` for simple configuration (not `config_entity`). Every config key your form saves must have a schema entry.

## ConfirmFormBase for destructive actions

Use `ConfirmFormBase` when an action is irreversible (delete, reset, purge). It presents a confirmation page with "Are you sure?" messaging.

```php
namespace Drupal\my_module\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class DeleteItemForm extends ConfirmFormBase {

  public function getFormId() {
    return 'my_module_delete_item_form';
  }

  public function getQuestion() {
    return $this->t('Are you sure you want to delete this item?');
  }

  public function getCancelUrl() {
    return new Url('my_module.item_list');
  }

  public function getDescription() {
    return $this->t('This action cannot be undone.');
  }

  public function getConfirmText() {
    return $this->t('Delete');
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Perform the deletion.
    $this->messenger()->addMessage($this->t('Item deleted.'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
```

Required methods: `getFormId()`, `getQuestion()`, `getCancelUrl()`, `submitForm()`.
Optional methods: `getDescription()`, `getConfirmText()` (defaults to "Confirm").

## Form altering

Form altering lets you modify forms defined by other modules without changing their source code. Alter hooks go in the `.module` file (procedural code).

### hook_form_FORM_ID_alter (preferred for specific forms)

Targets a single form by its form ID. Use this when you know which form you want to alter.

```php
/**
 * Implements hook_form_FORM_ID_alter() for user_register_form.
 */
function my_module_form_user_register_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  // Add a custom field to the user registration form.
  $form['nickname'] = [
    '#type' => 'textfield',
    '#title' => t('Nickname'),
    '#description' => t('Enter a display nickname.'),
    '#weight' => 5,
  ];

  // Add a custom submit handler.
  $form['actions']['submit']['#submit'][] = 'my_module_user_register_submit';
}

/**
 * Custom submit handler for the user registration form.
 */
function my_module_user_register_submit(&$form, \Drupal\Core\Form\FormStateInterface $form_state) {
  $nickname = $form_state->getValue('nickname');
  // Save the nickname value.
}
```

### hook_form_alter (for multiple forms or conditional logic)

Fires for ALL forms on the site. Always guard with a form ID check.

```php
/**
 * Implements hook_form_alter().
 */
function my_module_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  // Only alter node forms.
  if (strpos($form_id, 'node_') === 0 && strpos($form_id, '_form') !== FALSE) {
    $form['#attached']['library'][] = 'my_module/node_form_enhancements';
  }
}
```

> WRONG: Using `hook_form_alter()` without checking `$form_id`. This accidentally modifies ALL forms on the entire site, causing unexpected behavior on admin forms, login forms, and search forms.
> RIGHT: Use `hook_form_FORM_ID_alter()` when targeting a specific form (most common case). If you must use `hook_form_alter()`, always check `$form_id` or inspect `$form_state->getFormObject()` before making changes.

### Adding custom validate and submit handlers

```php
// Add a validation handler.
$form['#validate'][] = 'my_module_custom_validate';

// Add a submit handler (runs after the form's own submitForm).
$form['actions']['submit']['#submit'][] = 'my_module_custom_submit';

// Replace the default submit handler entirely.
$form['actions']['submit']['#submit'] = ['my_module_replacement_submit'];
```

Note: Submit handlers on `$form['actions']['submit']['#submit']` run for that button. Handlers on `$form['#submit']` run for all submit buttons.

## Form elements quick reference

Common form element types (not exhaustive -- full reference at api.drupal.org/api/drupal/elements):

| #type | Purpose | Key properties |
|-------|---------|----------------|
| `textfield` | Single-line text input | `#maxlength`, `#size`, `#placeholder` |
| `textarea` | Multi-line text input | `#rows`, `#cols` |
| `select` | Dropdown select list | `#options` (associative array), `#empty_option` |
| `checkboxes` | Multiple checkbox options | `#options` (associative array) |
| `radios` | Single-choice radio buttons | `#options` (associative array) |
| `number` | Numeric input | `#min`, `#max`, `#step` |
| `email` | Email input with validation | Built-in email format validation |
| `password` | Password input | Not displayed in form rebuilds |
| `submit` | Submit button | `#value` (button label) |
| `hidden` | Hidden form element | Rendered in HTML, user-visible in source |
| `value` | Internal value | NOT rendered in HTML, truly hidden |

### Common properties (apply to most elements)

- `#type`: Element type (required)
- `#title`: Label displayed to the user
- `#default_value`: Pre-filled value
- `#required`: Boolean, marks field as required
- `#description`: Help text below the element
- `#states`: Conditional visibility/required based on other fields
- `#weight`: Controls display order (lower = earlier)
- `#access`: Boolean, controls whether element is rendered at all

### Conditional visibility with #states

```php
$form['other_reason'] = [
  '#type' => 'textfield',
  '#title' => $this->t('Please specify'),
  '#states' => [
    'visible' => [
      ':input[name="reason"]' => ['value' => 'other'],
    ],
    'required' => [
      ':input[name="reason"]' => ['value' => 'other'],
    ],
  ],
];
```

## Routing forms

Forms are routed using `_form` in the route defaults (not `_controller`).

```yaml
my_module.contact:
  path: '/my-module/contact'
  defaults:
    _form: '\Drupal\my_module\Form\ContactForm'
    _title: 'Contact Us'
  requirements:
    _permission: 'access content'
```

For admin settings forms, add `_admin_route: TRUE` so Drupal uses the admin theme:

```yaml
my_module.settings:
  path: '/admin/config/my-module/settings'
  defaults:
    _form: '\Drupal\my_module\Form\SettingsForm'
    _title: 'My Module Settings'
  requirements:
    _permission: 'administer site configuration'
  options:
    _admin_route: TRUE
```

> WRONG: Using `_controller` to point to a form class in routing.yml. The form will not be processed through Drupal's form builder, losing CSRF protection, validation, and submission handling.
> RIGHT: Use `_form: '\Drupal\module_name\Form\FormClassName'` in route defaults. Drupal's form builder handles the entire request lifecycle for `_form` routes.

## AJAX form elements

The `#ajax` property triggers server-side callbacks without a full page reload. The `callback` method returns content to replace the `wrapper` element.

```php
$form['status'] = [
  '#type' => 'select',
  '#title' => $this->t('Status'),
  '#options' => ['draft' => 'Draft', 'published' => 'Published'],
  '#ajax' => [
    'callback' => '::statusCallback',
    'wrapper' => 'status-result',
  ],
];
$form['status_result'] = [
  '#type' => 'container',
  '#attributes' => ['id' => 'status-result'],
];
// Callback returns the element matching the wrapper ID.
public function statusCallback(array &$form, FormStateInterface $form_state) {
  return $form['status_result'];
}
```

> WRONG: `'wrapper' => 'status-result'` but target has `'id' => 'result-wrapper'`. AJAX fires but replacement silently fails -- no error, no update. This is the #1 AJAX debugging trap.
> RIGHT: `#ajax.wrapper` MUST exactly match `#attributes.id` of the container element. Both are plain strings (no `#` prefix).

**AjaxResponse for multi-command callbacks** -- when you need multiple DOM updates, messages, or side effects:

```php
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\MessageCommand;

public function statusCallback(array &$form, FormStateInterface $form_state) {
  $response = new AjaxResponse();
  $response->addCommand(new ReplaceCommand('#status-result', $form['status_result']));
  $response->addCommand(new MessageCommand($this->t('Status updated.')));
  return $response;
}
```

Return a **render array** for single wrapper replacement (simplest). Return an **AjaxResponse** for multiple DOM updates or side effects.
**AJAX in tables (unique wrapper per row)** -- for per-entity AJAX like status toggles:

```php
foreach ($entities as $id => $entity) {
  $form['tasks'][$id]['status'] = [
    '#type' => 'select',
    '#options' => $options,
    '#default_value' => $entity->get('status')->value,
    '#ajax' => [
      'callback' => '::updateTaskStatus',
      'wrapper' => 'task-row-' . $id,
    ],
  ];
  $form['tasks'][$id]['#attributes']['id'] = 'task-row-' . $id;
}
```

## D10/D11 compatibility

Form API patterns are identical in Drupal 10 and Drupal 11. No attribute syntax changes apply to forms. The `buildForm()`, `validateForm()`, `submitForm()` lifecycle, `ConfigFormBase`, `ConfirmFormBase`, and form alter hooks all work the same across both versions.

The only D11 change that touches forms indirectly is PHP attribute syntax for plugin discovery (blocks, entity types). Form classes themselves are not plugins and are not affected.

## Cross-references

See also: **drupal-routing-controllers** (if installed) for route definitions, controller patterns, and service dependency injection. If not available, define routes in `module_name.routing.yml` with `_form` default for form routes, and inject services via `create()` + constructor.

See also: **drupal-config-storage** (if installed) for Config API details, State API, TempStore, and config schema patterns. If not available, use the ConfigFormBase pattern shown above for config forms, always pairing with `config/schema/module_name.schema.yml`.

See also: **drupal-entities-fields** (if installed) for entity form handlers and field widgets. If not available, entity forms use the same Form API lifecycle -- content entities use `ContentEntityForm` which extends `EntityForm` which extends `FormBase`.

See also: **drupal-module-scaffold** (if installed) for .module file setup where form alter hooks live, and PSR-4 namespace conventions for form classes in `src/Form/`. If not available, place form classes in `src/Form/` and alter hooks in the `.module` file.

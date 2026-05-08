# Test Modules and Test-Only Configuration

Each test runs against a freshly-installed Drupal with nothing but the modules listed in `protected static $modules`. Your fields, content types, vocabularies, and blocks are *not there*. You have to install them.

## Two approaches

1. **Install existing module config in the test.** If your real module (not the tests) ships the config in `config/install/`, a Kernel test can call `$this->installConfig(['my_module'])` and get it.
2. **Create a test-only submodule.** Put the test fixtures — fields, content types, taxonomies — inside a hidden submodule that only the tests enable. Keeps your real module lean; avoids leaking test-only config into production sites.

Use (1) when the config is part of your real module anyway. Use (2) when the config is purely for testing (e.g., `field_tags` on a Post content type the real module doesn't own).

## The hidden submodule layout

```
web/modules/custom/atdc/
├── atdc.info.yml
├── atdc.module
├── src/
├── tests/
│   └── src/
│       ├── Functional/
│       └── Kernel/
└── modules/                       ← nested submodules go here
    └── atdc_test/
        ├── atdc_test.info.yml
        └── config/
            └── install/
                ├── node.type.post.yml
                ├── taxonomy.vocabulary.tags.yml
                ├── field.storage.node.field_tags.yml
                └── field.field.node.post.field_tags.yml
```

`atdc_test.info.yml` — note the `hidden: true`:

```yaml
name: ATDC Test
type: module
core_version_requirement: ^10 || ^11
package: Testing
hidden: true
```

`hidden: true` keeps this module out of the Extend page in the admin UI — site admins can't accidentally enable it in production. It's still available to `$this->enableModules()` and to the test runner.

## Using it in a test

```php
class PostBuilderTest extends EntityKernelTestBase {

  protected static $modules = [
    // Core / contrib.
    'node',
    'taxonomy',
    // Custom.
    'atdc',
    // Test-only.
    'atdc_test',
  ];

  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['atdc_test']);
    $this->installEntitySchema('taxonomy_term');
  }

}
```

Two install steps:

- `installConfig(['atdc_test'])` — reads `atdc_test/config/install/*.yml` and creates the content types, fields, vocabularies.
- `installEntitySchema('taxonomy_term')` — creates the DB tables for the taxonomy_term entity. Without this, you get `Base table or view not found: taxonomy_term_data`.

## Generating the config files

Don't hand-write config YAML. Let Drupal do it:

```bash
# Install Drupal against SQLite (throwaway).
./vendor/bin/drush site:install --db-url sqlite://localhost/throwaway.sqlite

# Create the fields/content types you need in the UI.
# Then export:
./vendor/bin/drush config:export --destination=/tmp/exported-config

# Or use the admin UI: /admin/config/development/configuration/single/export
```

Copy the files you need into `modules/atdc_test/config/install/`. Strip the `uuid` and `_core` keys — those are site-specific and Drupal will regenerate them on install.

## Common errors and fixes

### `Exception … references a target entity type 'taxonomy_term', which does not exist`

You listed the `field_tags` config but forgot to include the `taxonomy` module in `$modules`. Add it:

```php
protected static $modules = ['node', 'taxonomy', 'atdc', 'atdc_test'];
```

### `Exception … Missing bundle entity, entity type node_type, entity id post`

You referenced `post` as a content type but didn't ship `node.type.post.yml`. Add it to `config/install/`:

```yaml
# modules/atdc_test/config/install/node.type.post.yml
langcode: en
status: true
dependencies: {  }
name: Post
type: post
description: ''
help: ''
new_revision: true
preview_mode: 1
display_submitted: true
```

### `Base table or view not found: test…taxonomy_term_data`

The entity schema isn't installed. Add `$this->installEntitySchema('taxonomy_term')` in `setUp()` — one call per entity type the test touches.

### `SQLSTATE … NOT NULL constraint failed: node_field_data.created`

You're creating a node without a `created` timestamp, but the DB column is NOT NULL. Either pass `'created' => …` in the `Node::create([...])` args, or make your builder only call `setCreatedTime()` when the user asked for a specific time (null-guard — see `test-data-builders.md`).

## What about Functional tests?

BrowserTestBase installs a *full* Drupal site before each test, including running `hook_install`. If your real module's `config/install/` has the field config, the functional test will pick it up automatically — no test submodule needed.

The hidden-submodule dance is mostly a Kernel-test concern, because Kernel tests don't run the full install pipeline and don't auto-load `config/install/` for every module in `$modules`.

## Test-only `.module` hooks

Sometimes you want a hook (e.g., `hook_entity_insert`) to fire only inside a test. Put the hook inside `atdc_test.module` — since the submodule is only enabled by tests, the hook only fires in tests.

```php
// modules/atdc_test/atdc_test.module
<?php

/**
 * Implements hook_entity_insert().
 */
function atdc_test_entity_insert($entity) {
  // Test-specific behavior: log, assert, side-effect, etc.
}
```

This is cleaner than environment checks inside your real module's hooks.

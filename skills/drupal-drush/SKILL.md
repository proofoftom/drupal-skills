---
name: drupal-drush
description: |
  Use Drush commands for development self-verification, scaffolding, debugging,
  and entity operations. Use WHENEVER developing a Drupal module to verify your
  work, scaffold boilerplate code, debug errors, or test entity operations.
  Do NOT use for Drupal site administration tasks (content editing, user mgmt).
  For creating custom Drush commands, see references/command-authoring.md.
---

# Drupal Drush for Development

Drush is the command-line shell for Drupal. This skill teaches how to USE Drush commands during development -- self-verification, scaffolding, debugging, and entity operations. In ddev environments, prefix all commands with `ddev drush` (e.g., `ddev drush cr`).

## drush generate -- scaffold boilerplate, save tokens

`drush generate` (alias: `drush gen`) produces phpcs-compliant boilerplate code for common Drupal components. Use it instead of manually writing repetitive scaffolding.

### Why use drush generate?

- Generates files that pass `phpcs --standard=Drupal,DrupalPractice` by default
- Saves hundreds to thousands of tokens per component
- Produces correct namespace, directory placement, and YAML configuration
- Supports non-interactive mode via `--answer` flags for agent automation

### Key generators

| Generator | What It Creates | Estimated Token Savings |
|-----------|----------------|------------------------|
| `drush generate module` | .info.yml, .module, composer.json | ~200 tokens |
| `drush generate controller` | Controller class + routing.yml entry | ~300 tokens |
| `drush generate form:config` | Config form + routing + schema | ~500 tokens |
| `drush generate form:simple` | Simple form + routing | ~400 tokens |
| `drush generate entity:content` | Full content entity (class, schema, handlers, forms) | ~2000+ tokens |
| `drush generate entity:configuration` | Full config entity | ~1500+ tokens |
| `drush generate plugin:block` | Block plugin class | ~200 tokens |
| `drush generate service-provider` | Service provider class | ~150 tokens |
| `drush generate event-subscriber` | Event subscriber + services.yml entry | ~250 tokens |
| `drush generate hook` | Hook implementation in .module file | ~100 tokens |
| `drush generate test:kernel` | Kernel test class | ~200 tokens |

### Non-interactive generation

Use `--answer` to provide answers without interactive prompts. Use `--dry-run` to preview generated files without writing them.

```bash
# Preview what will be generated
drush generate controller --dry-run

# Generate a controller with answers pre-filled
drush generate controller --answer="Module name: my_module" --answer="Controller class: ItemController"
```

> WRONG: Manually writing 200+ lines of content entity boilerplate (entity class, base fields, schema, form handlers, list builder, access handler, routing, permissions, links). This is slow, error-prone, and frequently produces phpcs violations.
> RIGHT: Use `drush generate entity:content` to scaffold the entire content entity infrastructure. Then customize the generated files (add fields, modify forms, adjust permissions). Generate the skeleton, customize the details.

### Combining with manual work

`drush generate` provides the skeleton; you customize the details. For example:
1. `drush generate entity:content` -- creates the entity class, schema, forms, handlers
2. Add your custom base fields to `baseFieldDefinitions()`
3. Customize the form class for your specific UI needs
4. Add routes for custom pages beyond CRUD

See also: **drupal-module-scaffold** (if installed) for understanding the file structure that `drush generate` produces.

## Self-verification recipes -- check your own work

Self-verification is the most important Drush skill for development agents. After every major operation, verify it worked. Do not trust your own output -- check it.

### Recipe: After creating or modifying routes

```bash
# Verify a specific route exists by name
drush route --name=my_module.my_route

# Verify a path maps to the correct route
drush route --path=/admin/my-module/items

# List all routes for your module
drush route --name=my_module.
```

> WRONG -- complex php-eval for route checking:
> ```bash
> drush php-eval "\$provider = \Drupal::service('router.route_provider');
>   \$found = FALSE;
>   foreach (['my_module.api', 'my_module.list'] as \$name) {
>     try { \$provider->getRouteByName(\$name); \$found = TRUE; break; }
>     catch (\Exception \$e) {}
>   } echo \$found ? 'PASS' : 'FAIL';"
> ```
> This is unreadable, fragile (shell escaping), and hard to debug.
>
> RIGHT -- built-in Drush command:
> ```bash
> drush route --name=my_module.api
> drush route --path=/api/my-module/items
> ```
> One line, human-readable, proper error messages on failure.

### Recipe: After any module change (ALWAYS do this)

```bash
# Rebuild caches -- required for route/service/plugin discovery
drush cr

# Check for new warnings and errors
drush watchdog:show --severity-min=Warning --count=5
```

Run `drush cr` after EVERY module file change. Routes, services, plugins, and hooks are only discovered after a cache rebuild. Then check watchdog for any errors your changes introduced.

### Recipe: Service and dependency injection verification

```bash
# Check if a service exists in the container
drush php-eval "echo \Drupal::hasService('my_module.my_service') ? 'EXISTS' : 'MISSING';"

# Verify the service resolves to the correct class
drush php-eval "\$s = \Drupal::service('my_module.my_service'); echo get_class(\$s);"

# Verify a class is autoloadable
drush php-eval "echo class_exists('\Drupal\my_module\MyService') ? 'LOADABLE' : 'NOT_FOUND';"
```

### Recipe: Permission verification

```bash
# List all roles and their permissions
drush role:list --format=json

# Count total registered permissions
drush php-eval "echo count(\Drupal::service('user.permissions')->getPermissions());"

# Check if a specific permission exists
drush php-eval "echo array_key_exists('administer my_module', \Drupal::service('user.permissions')->getPermissions()) ? 'FOUND' : 'MISSING';"

# Grant a permission to a role for testing
drush role:perm:add anonymous 'view my_module content'
```

### Recipe: Config and state inspection

```bash
# View a full config object
drush config:get my_module.settings

# View a specific config key
drush config:get my_module.settings api_key

# View a state value
drush state:get my_module.last_run

# Set a state value for testing
drush state:set my_module.debug_mode 1
```

> WRONG: Using `drush sql:query "SELECT data FROM config WHERE name='my_module.settings'"` to inspect config values. This bypasses the config API, returns serialized blobs instead of human-readable values, and does not account for config overrides.
> RIGHT: Use `drush config:get my_module.settings` to view config values. For state values, use `drush state:get my_module.last_run`. These commands use Drupal's Config and State APIs, returning properly formatted values.

### Recipe: Queue verification

```bash
# See all queues and their item counts
drush queue:list

# Process queue items
drush queue:run my_module_queue

# Check results of queue processing
drush watchdog:show --type=my_module --count=5
```

See also: **drupal-batch-queue-cron** (if installed) for QueueWorker plugin patterns and cron-based queue processing.

### Recipe: Module status verification

```bash
# Verify a module is enabled
drush pm:list --status=enabled --field=name | grep my_module

# Enable a module and check for errors
drush en my_module -y
drush watchdog:show --severity-min=Error --count=5
```

## The Drupal-first principle -- entity API over SQL

When working with entities, ALWAYS use Drupal's entity API commands instead of raw SQL queries. The entity API fires hooks, invalidates caches, enforces access checks, and maintains referential integrity. SQL bypasses all of this.

### Entity operations with Drush

```bash
# Create a test entity (interactive -- fires creation hooks)
drush entity:create node article

# Re-save an entity (fires hook_entity_presave, hook_entity_update)
drush entity:save node 42

# Delete entities (fires hook_entity_delete, cleans references)
drush entity:delete node 22,24
```

### Programmatic entity testing with php-eval

For custom entity types that `entity:create` may not handle interactively, use `php-eval` with the entity API:

```bash
# Create a custom entity via the entity API
drush php-eval "\$e = \Drupal::entityTypeManager()->getStorage('task')->create(['title' => 'Test Task', 'status' => 'todo']); \$e->save(); echo 'Created ID: ' . \$e->id();"

# Load and inspect an entity
drush php-eval "\$e = \Drupal::entityTypeManager()->getStorage('task')->load(1); echo \$e->get('title')->value;"

# Update an entity field
drush php-eval "\$e = \Drupal::entityTypeManager()->getStorage('task')->load(1); \$e->set('status', 'done'); \$e->save(); echo 'Updated';"
```

> WRONG: Using `drush sql:query "UPDATE node_field_data SET status = 1 WHERE nid = 42"` to update entity data. This bypasses Drupal's entity API entirely: `hook_entity_presave` and `hook_entity_update` do not fire, cache tags are not invalidated, access checks are skipped, and computed fields are not recalculated. The entity system's in-memory cache may also hold stale data.
> RIGHT: Use `drush entity:save node 42` or `drush php-eval` with the entity API. These fire all hooks, invalidate caches, and enforce access rules. Use `sql:query` ONLY for raw database inspection with no API equivalent (e.g., checking table structure, verifying schema).

> WRONG: Using `drush sql:query "SELECT title FROM node_field_data WHERE nid = 42"` to read entity field values. This skips field access checks, ignores computed fields, and returns raw database values without formatting or translation.
> RIGHT: Use `drush php-eval "\$n = \Drupal::entityTypeManager()->getStorage('node')->load(42); echo \$n->get('title')->value;"` to load entities through the API. Field values are properly formatted, access-checked, and computed.

### When sql:query IS appropriate

- Checking raw table structure: `drush sql:query "DESCRIBE {my_module_data}"`
- Verifying schema changes applied: `drush sql:query "SHOW COLUMNS FROM {my_module_data}"`
- Debugging query performance: `drush sql:query "EXPLAIN SELECT ..."`
- Counting raw rows without entity overhead: `drush sql:query "SELECT COUNT(*) FROM {my_module_data}"`

## Debugging with Drush

### watchdog:show -- your primary debugging tool

```bash
# Show recent PHP errors
drush watchdog:show --type=php --count=10

# Show all warnings and above
drush watchdog:show --severity-min=Warning --count=5

# Show module-specific log entries
drush watchdog:show --type=my_module --count=10

# Machine-readable output for parsing
drush watchdog:show --format=json --count=5
```

### watchdog:tail -- live monitoring

```bash
# Stream new log entries in real-time during development
drush watchdog:tail

# Filter to your module's entries
drush watchdog:tail --type=my_module
```

Use `watchdog:tail` in a separate terminal while developing. It shows errors the moment they occur -- no need to repeatedly check `watchdog:show`.

### Environment and status checks

```bash
# Full environment status (Drupal version, PHP, DB, directories)
drush core:status

# Quick PHP version check
drush core:status --field=php-version

# Check Drupal bootstrap level
drush core:status --field=bootstrap
```

### Config debugging

```bash
# List all config objects matching a pattern
drush config:list | grep my_module

# Export config to see full YAML
drush config:get my_module.settings --format=yaml

# Compare active config to sync directory
drush config:status
```

### Cache debugging

```bash
# Clear all caches (the universal fix)
drush cr

# Test cache tag invalidation
drush cache:tags node:42,config:my_module.settings
```

## php:eval vs php:script -- when to use each

### php:eval -- for one-liners

Use `php:eval` (alias: `php-eval`, `eval`) for quick, single-line checks:

```bash
# Service resolution check
drush php-eval "echo \Drupal::hasService('my_module.manager') ? 'OK' : 'MISSING';"

# Class existence check
drush php-eval "echo class_exists('\Drupal\my_module\MyClass') ? 'OK' : 'MISSING';"

# Quick entity count
drush php-eval "echo \Drupal::entityTypeManager()->getStorage('node')->getQuery()->accessCheck(FALSE)->count()->execute();"
```

**Shell escaping tips for php:eval:**
- Wrap the PHP code in double quotes, escape `$` with `\$`
- Or use single quotes outside and double quotes inside: `drush php-eval '$x = "hello"; echo $x;'`
- For complex code with both quote types, use php:script instead

### php:script -- for complex multi-step tests

Use `php:script` when the test involves multiple steps, variable assignments, or complex logic. Avoid shell escaping entirely.

```php
<?php
// test-entity-workflow.php
// Place this file in the Drupal root or module directory

$storage = \Drupal::entityTypeManager()->getStorage('task');

// Create a test entity.
$task = $storage->create([
  'title' => 'Workflow Test',
  'status' => 'todo',
  'priority' => 'high',
]);
$task->save();
$id = $task->id();
echo "Created task ID: $id\n";

// Re-load and verify fields.
$loaded = $storage->load($id);
$pass = TRUE;
if ($loaded->get('title')->value !== 'Workflow Test') {
  echo "FAIL: title mismatch\n";
  $pass = FALSE;
}
if ($loaded->get('status')->value !== 'todo') {
  echo "FAIL: status mismatch\n";
  $pass = FALSE;
}

// Update and verify.
$loaded->set('status', 'in_progress');
$loaded->save();
$reloaded = $storage->load($id);
if ($reloaded->get('status')->value !== 'in_progress') {
  echo "FAIL: status update failed\n";
  $pass = FALSE;
}

// Clean up.
$reloaded->delete();
echo $pass ? "ALL TESTS PASSED\n" : "SOME TESTS FAILED\n";
```

```bash
# Run the test script
drush php:script test-entity-workflow.php
```

> WRONG -- long shell-escaped php-eval for multi-step testing:
> ```bash
> drush php-eval "\$s = \Drupal::entityTypeManager()->getStorage('task'); \$t = \$s->create(['title' => 'Test', 'status' => 'todo']); \$t->save(); \$id = \$t->id(); \$l = \$s->load(\$id); echo \$l->get('title')->value === 'Test' ? 'PASS' : 'FAIL'; \$l->delete();"
> ```
> This is unreadable, shell escaping is fragile (one missed `\$` breaks everything), and debugging is nearly impossible.
>
> RIGHT -- php:script with a proper PHP file:
> ```bash
> drush php:script test-entity-workflow.php
> ```
> Clean PHP syntax, no shell escaping, easy to debug and extend. Place the script file in the project root or specify a full path.

## Testing with Drush

### Running PHPUnit tests

```bash
# Run all tests for a module
drush test:run --module my_module

# Run tests by group
drush test:run --group my_module
```

See also: **drupal-testing** (if installed) for PHPUnit test types, base classes, and setUp patterns.

## Cross-references

- **drupal-module-scaffold** (if installed) -- `drush generate` complements manual scaffolding. Generate the skeleton, then customize the details.
- **drupal-batch-queue-cron** (if installed) -- Use `drush queue:list` and `drush queue:run` for testing queue workers.
- **drupal-testing** (if installed) -- Use `drush test:run` for executing PHPUnit tests from the command line.
- For creating custom Drush commands (command classes, AutowireTrait, PHP 8 attributes): see `references/command-authoring.md` in this skill directory.

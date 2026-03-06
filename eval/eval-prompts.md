# Eval Prompts for Drupal Skills

Eval prompts grounded in os-knowledge-garden module patterns. Each prompt is designed to test a specific skill (or combination of skills) against real Drupal development tasks derived from the social_ai_indexing, localnodes_platform, and boulder_demo modules.

---

## Single-Skill Prompts

### Eval Prompt: drupal-module-scaffold

**Grounded in:** os-knowledge-garden `localnodes_platform` module structure
**Expected skills:** drupal-module-scaffold

**Prompt:**
"Create a new Drupal module called event_analytics that will track event attendance. It should depend on the node module and be part of a custom 'Events' package."

**Without skills (baseline):**
- May use `core` instead of `core_version_requirement` in .info.yml
- May omit `type: module` (required since D8)
- May create incorrect PSR-4 namespace structure (e.g., wrong `src/` directory placement)
- May include a `core: 8.x` line (deprecated)

**With skills (expected improvement):**
- Correct .info.yml with `type: module`, `core_version_requirement: ^10 || ^11`, proper `package:` and `dependencies:` format
- Correct PSR-4 namespace (`modules/custom/event_analytics/src/`)
- Proper .module file with `declare(strict_types=1)` and `@file` docblock
- Awareness that hook implementations go in .module, OOP code in src/

---

### Eval Prompt: drupal-routing-controllers

**Grounded in:** os-knowledge-garden `social_ai_indexing` AiOverviewController
**Expected skills:** drupal-routing-controllers

**Prompt:**
"Add a route and controller to the social_ai_indexing module that returns JSON data for AI overview content. The endpoint should be at /api/ai/overview and accept a query parameter. Use dependency injection to inject the AI overview service."

**Without skills (baseline):**
- May use `hook_menu()` (D7 pattern, does not exist in D10+)
- May hardcode service access with `\Drupal::service()` instead of DI
- May return a plain string or array instead of a JsonResponse
- May forget `_permission` requirement in routing.yml
- May use incorrect constructor injection pattern (missing `create()` method)

**With skills (expected improvement):**
- Correct .routing.yml with `_controller`, `methods`, and `_permission` requirement
- Controller extends `ControllerBase` with proper `create()` static factory
- Constructor injection of typed service parameters
- Returns `JsonResponse` for API endpoints
- Uses `Request` object to access query parameters

---

### Eval Prompt: drupal-entities-fields

**Grounded in:** os-knowledge-garden `boulder_demo` event-enrollment patterns
**Expected skills:** drupal-entities-fields

**Prompt:**
"Create a custom content entity type called EventEnrollment for tracking event enrollments. It should have base fields for: event reference (entity_reference to node), user reference, enrollment status (list_string with values: pending, confirmed, cancelled), and enrollment date (created timestamp)."

**Without skills (baseline):**
- May use D10 annotation syntax (`@ContentEntityType`) without D11 attribute alternative
- May omit `config_export` or `config_prefix` for config entities (confusion between content/config)
- May forget `baseFieldDefinitions()` static method signature
- May create fields without proper `setDisplayOptions` for form and view
- May omit entity handlers (form, list_builder, access, route_provider)

**With skills (expected improvement):**
- Uses D11 `#[ContentEntityType]` attribute class with correct parameters (or correct D10 annotation)
- Proper `baseFieldDefinitions()` with parent call and all required fields
- Correct entity_reference field setup with `setSetting('target_type', 'node')`
- Display options configured for both `form` and `view` display modes
- All entity handlers defined (form classes, list builder, access handler, route providers)

---

### Eval Prompt: drupal-forms-api

**Grounded in:** os-knowledge-garden `social_ai_indexing` config patterns
**Expected skills:** drupal-forms-api

**Prompt:**
"Create a settings form for configuring the AI search index parameters in the social_ai_indexing module. The form should have fields for: similarity threshold (number, default 0.7), result limit (number, default 5), and cache TTL in seconds (number, default 300). Save settings to config."

**Without skills (baseline):**
- May use `drupal_set_message()` instead of `$this->messenger()->addStatus()`
- May extend `FormBase` instead of `ConfigFormBase` for settings forms
- May omit `getEditableConfigName()` (required for ConfigFormBase)
- May forget to add a route with `_form` key (using `_controller` instead)
- May not create matching config schema YAML

**With skills (expected improvement):**
- Extends `ConfigFormBase` with `getEditableConfigName()`
- Correct `buildForm()`/`validateForm()`/`submitForm()` lifecycle
- Uses `$this->config()` to load and save settings
- Route uses `_form: '\Drupal\social_ai_indexing\Form\SearchSettingsForm'` (not `_controller`)
- Includes config schema YAML with typed entries

---

### Eval Prompt: drupal-plugins-blocks

**Grounded in:** os-knowledge-garden `social_ai_indexing` RelatedContentBlock
**Expected skills:** drupal-plugins-blocks

**Prompt:**
"Create a block plugin that displays related content using a service for content lookup. The block should accept a configuration option for content type filtering and inject the RelatedContentService via dependency injection."

**Without skills (baseline):**
- May use controller-style DI (constructor + `create(ContainerInterface $container)`) without plugin's three required parent parameters
- May forget `parent::__construct($configuration, $plugin_id, $plugin_definition)` call
- May use `\Drupal::service()` instead of `ContainerFactoryPluginInterface`
- May use D10 `@Block` annotation without showing D11 attribute alternative
- May omit `defaultConfiguration()` for block config

**With skills (expected improvement):**
- Implements `ContainerFactoryPluginInterface` with correct `create()` signature (4 params: container, configuration, plugin_id, plugin_definition)
- Constructor passes three plugin params to `parent::__construct()`
- Uses `blockForm()`/`blockSubmit()` for configuration (not generic form methods)
- Returns render array with `#theme` and `#cache` metadata
- `defaultConfiguration()` merges with `parent::defaultConfiguration()`

---

### Eval Prompt: drupal-config-storage

**Grounded in:** os-knowledge-garden `localnodes_platform` config/install YAML
**Expected skills:** drupal-config-storage

**Prompt:**
"Create config/install YAML files for block placement and search index configuration for the localnodes_platform module. The block should be placed in the sidebar_first region of the socialblue theme. Also create the matching config schema."

**Without skills (baseline):**
- May use `variable_get()`/`variable_set()` (D7 pattern)
- May confuse Config API with State API (settings vs runtime data)
- May omit config schema entirely (required for all config)
- May use wrong YAML structure for block placement config
- May forget `langcode` and `status` keys in config install YAML

**With skills (expected improvement):**
- Correct config/install YAML structure matching Drupal's config entity format
- Proper block.block.*.yml with correct keys: `id`, `theme`, `region`, `plugin`, `settings`, `visibility`
- Matching config schema in `config/schema/module_name.schema.yml`
- Uses Config API (`$config->get()`, `$config->set()`, `$config->save()`) not State API
- Understands config/install (loaded on module install) vs config/optional (loaded if dependencies met)

---

### Eval Prompt: drupal-access-security

**Grounded in:** os-knowledge-garden `social_ai_indexing` routing permissions
**Expected skills:** drupal-access-security

**Prompt:**
"Add permission-based access control to the AI overview controller route. Define a custom permission 'access ai overview' and apply it to the /api/ai/overview route. Also add entity access checking for nodes loaded by the controller."

**Without skills (baseline):**
- May use `hook_permission()` (D7 pattern) instead of `module.permissions.yml`
- May return bare boolean from access checks instead of `AccessResult` objects
- May forget to make access results cacheable (`AccessResult::allowed()` vs `new AccessResult(TRUE)`)
- May not chain access results with `->orIf()` or `->andIf()`
- May hardcode CSRF token validation instead of using `_csrf_token: 'TRUE'` in routing

**With skills (expected improvement):**
- Defines permission in `social_ai_indexing.permissions.yml` with `title` and `description`
- Route uses `_permission: 'access ai overview'` requirement
- Entity access uses `$node->access('view', $account)` returning AccessResult
- Uses `AccessResult::allowedIfHasPermission()` for cacheable access checks
- Understands CSRF protection via routing requirement, not manual token validation

---

### Eval Prompt: drupal-theming

**Grounded in:** os-knowledge-garden `social_ai_indexing` templates and libraries
**Expected skills:** drupal-theming

**Prompt:**
"Create a Twig template and hook_theme() for displaying AI-related content in the social_ai_indexing module. The template should render a list of related items with titles and links. Also attach a CSS library for styling."

**Without skills (baseline):**
- May build raw HTML strings instead of render arrays with `#theme`
- May forget to declare template variables in `hook_theme()` return array
- May use wrong template naming convention (underscores vs hyphens in filename)
- May inline CSS/JS instead of using `#attached` libraries
- May use `{{ variable }}` without passing variables through preprocess

**With skills (expected improvement):**
- Implements `hook_theme()` returning array with `variables` key listing all template vars
- Template file named with hyphens: `social-ai-related-content.html.twig`
- Uses `#theme` in render array with `#items`, `#title`, etc. as variables
- Attaches library via `#attached.library` array (`social_ai_indexing/related-content`)
- Library defined in `social_ai_indexing.libraries.yml` with CSS and dependencies

---

### Eval Prompt: drupal-caching

**Grounded in:** os-knowledge-garden `social_ai_indexing` RelatedContentBlock
**Expected skills:** drupal-caching

**Prompt:**
"Add proper cache tags and contexts to a block that displays entity-based related content. The block shows nodes related to the current page node, filtered by the current user's group membership. Ensure the cache invalidates when related nodes are updated."

**Without skills (baseline):**
- May omit `#cache` metadata entirely from render arrays
- May set `max-age: 0` to "fix" caching issues (disables caching for entire page via bubbling)
- May confuse cache tags (data dependencies) with cache contexts (request variations)
- May not understand that cache metadata bubbles up from nested render arrays
- May forget that blocks need `getCacheContexts()` and `getCacheTags()` methods

**With skills (expected improvement):**
- Uses `#cache.tags` with entity-specific tags (`node:ID` for each related node)
- Uses `#cache.contexts` with `route` (varies by current page) and `user` (varies by group membership)
- Implements `getCacheContexts()` and `getCacheTags()` on the block class
- Understands tag invalidation: `Cache::invalidateTags(['node:5'])` when node 5 updates
- Does NOT set `max-age: 0` -- uses proper tag-based invalidation instead

---

### Eval Prompt: drupal-testing

**Grounded in:** os-knowledge-garden `social_ai_indexing` services
**Expected skills:** drupal-testing

**Prompt:**
"Write a kernel test for the RelatedContentService that verifies it returns related nodes filtered by bundle type. The test should install the node module, create test nodes, and verify the service filters results correctly."

**Without skills (baseline):**
- May use wrong base class (e.g., `BrowserTestBase` for a service test, wasting resources)
- May forget `$modules` static property for module installation
- May not call `$this->installEntitySchema('node')` in setUp (kernel tests need explicit schema)
- May forget `@group` annotation (required by Drupal's test runner)
- May call `parent::setUp()` at the wrong point or forget it entirely

**With skills (expected improvement):**
- Extends `KernelTestBase` (correct level: testing services without browser)
- Declares `protected static $modules = ['node', 'social_ai_indexing', ...]`
- Calls `$this->installEntitySchema('node')` and `$this->installConfig(...)` in setUp
- Uses `@group social_ai_indexing` annotation
- Creates test entities with `Node::create([...])->save()`
- Uses proper assertions: `$this->assertCount()`, `$this->assertEquals()`

---

### Eval Prompt: drupal-database-api

**Grounded in:** Analytics use case (custom table, not entity-based)
**Expected skills:** drupal-database-api

**Prompt:**
"Create a custom database table for tracking content view analytics in an event_analytics module. The table should store: nid, uid, timestamp, and referrer. Write the schema hook, an insert function, and a query that returns the top 10 most viewed nodes."

**Without skills (baseline):**
- May try to use Entity API for what should be a simple tracking table
- May use raw SQL strings instead of Drupal's database abstraction
- May forget `hook_schema()` for table definition
- May write queries vulnerable to SQL injection (string interpolation)
- May not use tagged queries for `hook_query_alter()` support

**With skills (expected improvement):**
- Defines table in `hook_schema()` with correct column types, keys, and indexes
- Uses `\Drupal::database()->insert()` with `->fields()` for safe inserts
- Uses `\Drupal::database()->select()` with `->addExpression('COUNT(*)')` and `->groupBy()` for aggregation
- Query is tagged for alter support: `->addTag('event_analytics')`
- Does NOT use Entity API for this tracking data (explicit skill guidance)

---

### Eval Prompt: drupal-views-dev

**Grounded in:** os-knowledge-garden `boulder_demo` event-enrollment patterns
**Expected skills:** drupal-views-dev

**Prompt:**
"Expose the event enrollment entity data to Views with a custom filter for enrollment status. Users should be able to filter enrollments by status (pending, confirmed, cancelled) in Views UI."

**Without skills (baseline):**
- May use `hook_views_data()` when entity integration should come from entity annotation/attribute
- May forget the `group` key in views data (causes "Broken handler" in UI)
- May create a virtual field but forget to implement the query handler
- May not know the difference between `hook_views_data()` (new tables) vs `hook_views_data_alter()` (modify existing)
- May use D10 annotation style for Views plugins without D11 attribute classes

**With skills (expected improvement):**
- For entity-based data, uses `views_data` handler class in entity annotation/attribute (not `hook_views_data()`)
- Custom filter extends `InOperator` with `getValueOptions()` returning status options
- Uses D11 `#[ViewsFilter]` attribute class from `Drupal\views\Attribute` namespace
- Places filter plugin in correct PSR-4 path: `src/Plugin/views/filter/`
- Includes `group` key in hook_views_data definitions to prevent broken handlers

---

### Eval Prompt: drupal-batch-queue-cron

**Grounded in:** os-knowledge-garden `social_ai_indexing` indexing pattern
**Expected skills:** drupal-batch-queue-cron

**Prompt:**
"Create a cron job that queues content for AI indexing using a queue worker. The cron hook should find unindexed nodes and add them to a queue. The queue worker should process each node by calling an indexing service."

**Without skills (baseline):**
- May implement processing directly in `hook_cron()` instead of using queue
- May forget `@QueueWorker` annotation (or D11 `#[QueueWorker]` attribute)
- May not set `cron.time` in queue worker definition (items won't process during cron)
- May use `\Drupal::queue()` instead of injecting the queue factory
- May not handle exceptions in queue worker (failed items disappear from queue)

**With skills (expected improvement):**
- `hook_cron()` adds items to queue via `\Drupal::queue('ai_content_indexer')->createItem($data)`
- `QueueWorker` plugin with `cron: {time: 30}` to process during cron runs
- Queue worker `processItem()` calls the indexing service with proper error handling
- Uses try/catch in processItem -- throws `RequeueException` for retryable failures
- Understands queue lifecycle: createItem -> claimItem -> processItem -> deleteItem

---

## Multi-Skill Prompts

### Eval Prompt: Multi-1 (Module + Entity + Form + Theming)

**Grounded in:** Full-stack module creation pattern from os-knowledge-garden architecture
**Expected skills:** drupal-module-scaffold, drupal-entities-fields, drupal-forms-api, drupal-theming

**Prompt:**
"Create a new Drupal module called event_registrations that defines a custom content entity type for event registrations with fields for event reference, attendee name, and status. Include an admin form for managing registration settings, a list builder for viewing registrations, and a themed display template."

**Expected activation map:**
- drupal-module-scaffold: .info.yml, PSR-4 structure, .module file
- drupal-entities-fields: Entity type definition, base fields, handlers
- drupal-forms-api: Settings form extending ConfigFormBase
- drupal-theming: hook_theme(), Twig template, library attachment

**Coherence check:** Entity form classes should use Form API patterns. Template should render entity fields via render arrays. Module scaffold should include all entity-related files.

---

### Eval Prompt: Multi-2 (Block + Database + Theming + Caching)

**Grounded in:** os-knowledge-garden `social_ai_indexing` RelatedContentBlock + analytics pattern
**Expected skills:** drupal-plugins-blocks, drupal-database-api, drupal-theming, drupal-caching

**Prompt:**
"Add a block plugin that queries a custom analytics database table to find the top 10 most viewed content items, displays them as a themed list using a Twig template, and includes proper cache tags that invalidate when new view records are inserted."

**Expected activation map:**
- drupal-plugins-blocks: Block class with ContainerFactoryPluginInterface, DI
- drupal-database-api: Select query with aggregation on custom table
- drupal-theming: hook_theme(), template file, #theme render array
- drupal-caching: Cache tags on render array, proper contexts

**Coherence check:** Block build() returns render array with #theme AND #cache. Database query results feed into template variables. Cache tags include custom tag for analytics data freshness.

---

### Eval Prompt: Multi-3 (Forms + Config + Routing + Access)

**Grounded in:** os-knowledge-garden `social_ai_indexing` settings and route protection pattern
**Expected skills:** drupal-forms-api, drupal-config-storage, drupal-routing-controllers, drupal-access-security

**Prompt:**
"Create a settings form with config schema for storing API credentials and a rate limit setting. Add a route at /admin/config/services/ai-settings that serves this form. Restrict access to users with 'administer ai settings' permission. Include the config/install default values and config/schema YAML."

**Expected activation map:**
- drupal-forms-api: ConfigFormBase with validate/submit lifecycle
- drupal-config-storage: Config schema YAML, config/install defaults, Config API usage
- drupal-routing-controllers: Route definition with _form key
- drupal-access-security: Permission definition in .permissions.yml, route _permission requirement

**Coherence check:** Route uses `_form` (not `_controller`). Config schema matches the form fields. Permission name in .permissions.yml matches route requirement. Config/install YAML keys match schema.

---

### Eval Prompt: Multi-4 (Testing + Entities + Views)

**Grounded in:** os-knowledge-garden entity/views testing pattern
**Expected skills:** drupal-testing, drupal-entities-fields, drupal-views-dev

**Prompt:**
"Write kernel tests for a custom EventEnrollment entity that verify: (1) the entity can be created with all base fields, (2) the entity's Views data integration exposes the status field, and (3) a custom Views filter correctly filters by enrollment status."

**Expected activation map:**
- drupal-testing: KernelTestBase, $modules, installEntitySchema, @group
- drupal-entities-fields: Entity creation in test, base field verification
- drupal-views-dev: Views data hook testing, filter plugin testing

**Coherence check:** Test installs entity schema before creating entities. Views assertions use the Views executable API. Filter test creates entities with different statuses and verifies filtering.

---

### Eval Prompt: Multi-5 (Scaffold + Batch/Queue/Cron + Blocks + Theming)

**Grounded in:** os-knowledge-garden `social_ai_indexing` cron + block display pattern
**Expected skills:** drupal-module-scaffold, drupal-batch-queue-cron, drupal-plugins-blocks, drupal-theming

**Prompt:**
"Create a module called content_indexer with a cron job that processes a queue of content items for AI indexing. Add a status block that shows the indexing progress (items processed / total) with a themed template. The queue worker should log results using the logger service."

**Expected activation map:**
- drupal-module-scaffold: .info.yml, module structure, .module file with hook_cron
- drupal-batch-queue-cron: QueueWorker plugin, hook_cron queuing, cron.time setting
- drupal-plugins-blocks: Block plugin with DI for queue/state services
- drupal-theming: hook_theme(), template for status display, library attachment

**Coherence check:** hook_cron in .module file adds items to queue. QueueWorker plugin processes items. Block plugin reads queue/state to show progress. Template renders progress data.

---

### Eval Prompt: Multi-6 (Full Stack - 5+ Skills)

**Grounded in:** Complete os-knowledge-garden module architecture (social_ai_indexing)
**Expected skills:** drupal-module-scaffold, drupal-routing-controllers, drupal-plugins-blocks, drupal-config-storage, drupal-access-security, drupal-caching, drupal-theming

**Prompt:**
"Create a complete Drupal module called ai_dashboard that provides: (1) an admin settings form for API configuration stored in config with schema, (2) a REST endpoint at /api/ai/status that returns JSON with permission-based access control, (3) a dashboard block that displays AI system status with a themed template, proper caching, and an attached JS library for auto-refresh."

**Expected activation map:**
- drupal-module-scaffold: Module structure, .info.yml, .module
- drupal-routing-controllers: Route + controller for JSON endpoint with DI
- drupal-plugins-blocks: Dashboard block with ContainerFactoryPluginInterface
- drupal-config-storage: Config schema, config/install defaults
- drupal-access-security: Custom permission, route protection
- drupal-caching: Cache tags/contexts on block render array
- drupal-theming: hook_theme(), Twig template, JS library attachment

**Coherence check:** All pieces use consistent module namespace. Config form saves to config used by controller and block. Permission protects route. Block has proper cache metadata. Template renders block data with attached library.

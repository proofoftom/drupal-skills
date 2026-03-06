# Eval Results: Drupal Skills

Baseline (Claude without skills) vs with-skills comparison for all 13 Drupal skills plus multi-skill scenarios. Verdicts based on known Claude blind spots documented as wrong-way callouts in each skill.

---

## Single-Skill Results

### drupal-module-scaffold

**Verdict:** PASS

**Baseline issues (Claude without skills):**
- Often uses `core: 8.x` (removed in D10) or omits `core_version_requirement` entirely
- May produce .info.yml without `type: module` (required since D8)
- Sometimes places code outside PSR-4 `src/` directory structure
- Inconsistent `.module` file structure (missing `declare(strict_types=1)`)

**With-skill improvements:**
- Correct .info.yml with all required keys and `^10 || ^11` compatibility
- Explicit PSR-4 directory layout guidance with `src/` conventions
- .module file conventions: strict types, @file docblock, hooks only (OOP in src/)
- 5 wrong-way callouts catch common Claude mistakes: core 8.x, missing type, D7 .install patterns, wrong namespace structure, incorrect dependency format

**Key improvement:** Skill explicitly addresses the D10/D11 .info.yml format changes that Claude's training data mixes up due to spanning multiple Drupal versions.

---

### drupal-routing-controllers

**Verdict:** PASS

**Baseline issues:**
- Claude sometimes suggests `hook_menu()` (D7, removed in D8+)
- Hardcodes `\Drupal::service()` calls instead of constructor injection
- Returns plain strings from controllers instead of render arrays or Response objects
- May forget `_permission` or `_access` requirement on routes (security gap)
- Uses static method calls for services in controllers instead of DI via `create()`

**With-skill improvements:**
- Correct .routing.yml format with all requirement types (`_permission`, `_role`, `_access`, `_custom_access`)
- Controller DI pattern: `create()` static factory + typed constructor injection
- Proper response types: render arrays for pages, JsonResponse for APIs
- 5 wrong-way callouts: hook_menu, hardcoded access strings, plain string returns, static DI, container injection without create()

**Key improvement:** Skill enforces modern DI pattern (create() + constructor) which Claude frequently gets wrong by mixing D7 and D10 patterns.

---

### drupal-entities-fields

**Verdict:** PASS

**Baseline issues:**
- Mixes D10 annotation syntax with D11 attribute syntax (produces broken code)
- Omits critical entity handlers (access, route_provider, list_builder)
- Creates fields without display options (fields exist but are invisible in forms/views)
- May use `@Translation()` in D11 attribute context (not valid)
- Forgets to call parent in `baseFieldDefinitions()`

**With-skill improvements:**
- Clear D10 annotation vs D11 attribute syntax with examples of both
- Complete handler registration: form, list_builder, access, route_provider, views_data
- Base field definitions with `setDisplayOptions()` for both form and view
- 6 wrong-way callouts covering mixed syntax, @Translation in attributes, missing schema, missing config_export, hand-rolled routes/forms
- Progressive disclosure via references/ for complex subtopics

**Key improvement:** Skill resolves the D10/D11 annotation-to-attribute transition confusion that is Claude's biggest Entity API blind spot.

---

### drupal-forms-api

**Verdict:** PASS

**Baseline issues:**
- Uses `drupal_set_message()` (removed in D9) instead of messenger service
- Extends `FormBase` for settings forms instead of `ConfigFormBase`
- Omits `getEditableConfigName()` (required for ConfigFormBase)
- Uses `_controller` route key for forms instead of `_form`
- Forgets to create config schema for form settings

**With-skill improvements:**
- Correct form lifecycle: `buildForm()` -> `validateForm()` -> `submitForm()`
- `ConfigFormBase` for settings with `getEditableConfigName()` and `$this->config()`
- Route uses `_form` key (not `_controller`)
- Config schema YAML with matching typed entries
- 6 wrong-way callouts: drupal_set_message, static DI, missing schema, unguarded form_alter, wrong route key

**Key improvement:** Skill catches the `_form` vs `_controller` route distinction which Claude consistently gets wrong, plus the ConfigFormBase requirements.

---

### drupal-plugins-blocks

**Verdict:** PASS

**Baseline issues:**
- Uses controller-style DI in plugins (2-param create) instead of plugin DI (4-param create)
- Forgets `parent::__construct($configuration, $plugin_id, $plugin_definition)` call
- Uses `\Drupal::service()` instead of implementing `ContainerFactoryPluginInterface`
- D10 `@Block` annotation without D11 `#[Block]` attribute awareness
- Omits `defaultConfiguration()` or fails to merge with parent

**With-skill improvements:**
- Correct plugin DI: `ContainerFactoryPluginInterface` with 4-param `create()` (container, config, plugin_id, plugin_definition)
- Constructor calls `parent::__construct()` with three plugin params
- Block config via `blockForm()`/`blockSubmit()` (not generic form methods)
- Both D10 annotation and D11 attribute syntax shown
- Custom plugin type creation with plugin manager service
- 5 wrong-way callouts: controller DI, missing parent construct, static DI, manual block config, @Translation in attributes

**Key improvement:** Plugin DI pattern is fundamentally different from controller DI -- skill makes this explicit, preventing the most common Claude error in block development.

---

### drupal-config-storage

**Verdict:** PASS

**Baseline issues:**
- Uses `variable_get()`/`variable_set()` (D7 pattern, removed in D8)
- Confuses Config API (settings, exported) with State API (runtime, not exported)
- Omits config schema entirely (breaks config validation, multilingual, and overrides)
- Wrong YAML structure for config install files
- Stores temporary data in config instead of tempstore

**With-skill improvements:**
- Clear decision tree: Config (settings, exported) vs State (runtime, not exported) vs TempStore (per-user temporary)
- Config schema required for all config with correct typing (string, integer, boolean, mapping, sequence)
- Config/install vs config/optional distinction with dependency awareness
- `$config->get()`, `$config->set()`, `$config->save()` patterns
- 5 wrong-way callouts: variable_get, settings in State, missing schema, string vs label type, missing defaults

**Key improvement:** Skill's three-way decision tree (Config vs State vs TempStore) prevents the most common confusion Claude has about which storage API to use.

---

### drupal-access-security

**Verdict:** PASS

**Baseline issues:**
- Uses `hook_permission()` (D7) instead of `module.permissions.yml`
- Returns bare booleans from access checks instead of `AccessResult` objects
- Forgets that `AccessResult` objects carry cache metadata
- Manually validates CSRF tokens instead of using `_csrf_token: 'TRUE'` route option
- Outputs unescaped user input (XSS vulnerability)

**With-skill improvements:**
- Permissions defined in YAML file with title and description
- `AccessResult::allowed()`, `::forbidden()`, `::neutral()` with cache metadata
- `->addCacheableDependency()` and `->addCacheTags()` on access results
- Route-level CSRF protection via `_csrf_token` requirement
- XSS prevention: `#markup` auto-escapes, `Xss::filter()` for rich text, `#plain_text` for user input
- 7 wrong-way callouts covering orphaned permissions, hook_permission, bare AccessResult, manual CSRF, unsafe markup, controller access, t() concatenation

**Key improvement:** Skill enforces `AccessResult` objects over booleans, which is critical for Drupal's cache-aware access system and a major Claude blind spot.

---

### drupal-theming

**Verdict:** PASS

**Baseline issues:**
- Builds raw HTML strings instead of render arrays with `#theme`
- Forgets to declare template variables in `hook_theme()` return
- Uses wrong template naming (underscores instead of hyphens in filenames)
- Inlines CSS/JS instead of using library system with `#attached`
- Outputs variables in Twig without understanding auto-escaping

**With-skill improvements:**
- Render array decision tree: `#theme`, `#type`, `#markup`, `#plain_text`
- `hook_theme()` with complete variable declarations and default values
- Template naming convention: underscores in hook name, hyphens in filename
- Library system: `.libraries.yml` definition, `#attached.library` attachment
- Twig best practices: `{{ variable }}` auto-escapes, `{% raw %}` for literal output
- 4 wrong-way callouts in SKILL.md plus 3 in js-ajax.md reference

**Key improvement:** Skill's render array decision tree prevents Claude from bypassing Drupal's theme system with raw HTML, which breaks caching, security, and theme overridability.

---

### drupal-caching

**Verdict:** PASS

**Baseline issues:**
- Omits `#cache` metadata from render arrays entirely (items cached forever with wrong data)
- Sets `max-age: 0` to "fix" caching (disables caching for entire page via bubbling)
- Confuses cache tags (what data) with cache contexts (what request variation)
- Doesn't understand that cache metadata bubbles UP from child render arrays
- Forgets block-level cache methods (`getCacheContexts()`, `getCacheTags()`)

**With-skill improvements:**
- Every render array gets `#cache` with tags, contexts, and max-age
- Cache tags: `node:5`, `node_list:article`, `config:system.site` (data dependencies)
- Cache contexts: `user`, `route`, `url.query_args`, `languages` (request variations)
- Bubbling explained: child metadata merges into parent automatically
- Block caching via `getCacheContexts()` + `getCacheTags()` methods
- 7 wrong-way callouts: omitting #cache, max-age 0 bubbling, anonymous cache, non-scalar lazy args, missing parent merge, bin clearing, anonymous max-age assumption

**Key improvement:** Skill prevents the `max-age: 0` antipattern which is Claude's most damaging caching mistake -- it disables the page cache for all users, destroying site performance.

---

### drupal-testing

**Verdict:** PASS

**Baseline issues:**
- Uses wrong base class (BrowserTestBase for unit-testable code, wasting CI time)
- Forgets `$modules` static property (test crashes on missing dependencies)
- Omits `$this->installEntitySchema()` in kernel tests (database table missing)
- Missing `@group` annotation (tests not discoverable by test runner)
- Calls `parent::setUp()` at wrong point or forgets it

**With-skill improvements:**
- Test type decision tree: Unit < Kernel < Functional < FunctionalJavascript (use lowest sufficient level)
- Required setup for each level documented: `$modules`, `installEntitySchema()`, `installConfig()`
- `@group` annotation and `@coversDefaultClass` for organization
- Kernel test patterns: entity creation, service testing, config installation
- 6 wrong-way callouts: wrong base class, missing modules, missing installSchema, missing defaultTheme, missing @group, setUp ordering

**Key improvement:** Skill's "use lowest level" principle prevents Claude from defaulting to BrowserTestBase (30s per test) when KernelTestBase (2s per test) suffices, saving massive CI time.

---

### drupal-database-api

**Verdict:** PASS

**Baseline issues:**
- Uses Entity API for what should be simple tracking/logging tables
- Writes raw SQL strings (SQL injection vulnerability)
- Forgets `hook_schema()` (table never created on install)
- String-interpolates values into queries instead of using placeholders
- Modifies entity-owned tables directly (data corruption risk)

**With-skill improvements:**
- `hook_schema()` with correct column types (`serial`, `int`, `varchar`, `text`, `float`)
- Database abstraction: `->insert()`, `->select()`, `->update()`, `->delete()` with `->fields()` and `->condition()`
- Static vs dynamic queries: static for complex JOINs, dynamic for conditional queries
- Tagged queries with `->addTag()` for `hook_query_alter()` support
- Explicit "Do NOT use for entity data -- use Entity Query" callout
- 6 wrong-way callouts: entity SQL, injection, entity table writes, untagged query alter, schema without update hook, duplicate hook numbers

**Key improvement:** Skill draws a clear line between Entity API data (use Entity Query) and custom table data (use Database API), preventing Claude from using the wrong approach.

---

### drupal-views-dev

**Verdict:** PASS

**Baseline issues:**
- Uses `hook_views_data()` for entities that should use entity annotation views_data handler
- Forgets `group` key in views data array (causes "Broken handler" in Views UI)
- Creates virtual/computed fields without implementing the query handler
- Doesn't know difference between `hook_views_data()` (new tables) and `hook_views_data_alter()` (modify existing)
- Uses D10 annotation style for Views plugins without D11 attribute classes

**With-skill improvements:**
- Entity-based data: use `views_data` handler class in entity definition (not hook_views_data)
- Custom table data: `hook_views_data()` with complete field definitions including `group`
- Views plugin types: field, filter, sort, argument, relationship with correct base classes
- D11 `#[ViewsFilter]`, `#[ViewsField]` attribute classes from `Drupal\views\Attribute`
- PSR-4 paths: `src/Plugin/views/filter/`, `src/Plugin/views/field/`, etc.
- 5 wrong-way callouts: entity hook_views_data, missing group, virtual field query, missing schema, data vs alter

**Key improvement:** Skill prevents Claude from implementing `hook_views_data()` for entities (which duplicates the entity system's built-in Views integration), a common over-engineering mistake.

---

### drupal-batch-queue-cron

**Verdict:** PASS

**Baseline issues:**
- Implements heavy processing directly in `hook_cron()` (blocks other cron tasks)
- Forgets `@QueueWorker` annotation or D11 `#[QueueWorker]` attribute
- Omits `cron.time` setting (queue items never process during cron)
- Uses `\Drupal::queue()` static call instead of DI
- Doesn't handle exceptions in processItem() (failed items silently lost)

**With-skill improvements:**
- `hook_cron()` for discovery/queuing only, heavy work in QueueWorker
- QueueWorker plugin with `cron: {time: 30}` for automatic cron processing
- `processItem()` with try/catch, `RequeueException` for retryable failures, `SuspendQueueException` for systemic failures
- Batch API for user-facing operations with progress bar
- Lock API with try/finally for exclusive cron operations
- 5 wrong-way callouts: processing in hook_cron, missing cron.time, wrong exception type, direct queue manipulation, D7 batch patterns

**Key improvement:** Skill enforces the cron-queue separation pattern (cron discovers, queue processes) which Claude consistently collapses into a single hook_cron implementation.

---

## Multi-Skill Results

### Multi-1: Module + Entity + Form + Theming

**Verdict:** PASS

**Expected activation:** drupal-module-scaffold, drupal-entities-fields, drupal-forms-api, drupal-theming
**Activation analysis:** All four skills address distinct aspects of the prompt. No description overlap would prevent activation.

**Baseline issues without skills:**
- Module scaffold may have wrong .info.yml format
- Entity may mix D10/D11 syntax or omit handlers
- Settings form may extend FormBase instead of ConfigFormBase
- Theming may produce raw HTML instead of render arrays

**With-skill coherence:**
- Module scaffold provides correct file structure for all entity-related files
- Entity skill's handler definitions include form classes that follow Form API patterns
- Theming skill's render arrays work with entity view builders
- Cross-references between skills guide integration points
- All skills use consistent D10/D11 dual-syntax approach

---

### Multi-2: Block + Database + Theming + Caching

**Verdict:** PASS

**Expected activation:** drupal-plugins-blocks, drupal-database-api, drupal-theming, drupal-caching
**Activation analysis:** Skills have distinct domains. Block creation, database queries, template output, and cache metadata are clearly separable concerns.

**Baseline issues without skills:**
- Block DI pattern likely wrong (controller-style instead of plugin-style)
- Database query may use raw SQL
- Output may be raw HTML in block
- Cache metadata likely omitted or uses max-age: 0

**With-skill coherence:**
- Block's `build()` returns render array with `#theme` (theming) AND `#cache` (caching)
- Database query results feed into template variables
- Cache tags include entity tags for each displayed node
- No skill conflicts: database handles query, theming handles display, caching handles metadata

---

### Multi-3: Forms + Config + Routing + Access

**Verdict:** PASS

**Expected activation:** drupal-forms-api, drupal-config-storage, drupal-routing-controllers, drupal-access-security
**Activation analysis:** Each skill handles a distinct layer. Form content, config persistence, route definition, and access control are complementary.

**Baseline issues without skills:**
- Route may use `_controller` instead of `_form`
- Config schema likely missing
- Permission may use D7 hook_permission()
- Form may not extend ConfigFormBase

**With-skill coherence:**
- Forms skill ensures `_form` route key; routing skill confirms this pattern
- Config skill provides schema; forms skill uses `$this->config()` to read/write
- Access skill defines permission in YAML; routing skill references it in route requirement
- All four skills reinforce each other without contradicting patterns

---

### Multi-4: Testing + Entities + Views

**Verdict:** PASS

**Expected activation:** drupal-testing, drupal-entities-fields, drupal-views-dev
**Activation analysis:** Testing methodology, entity structure, and Views integration are distinct domains that combine naturally.

**Baseline issues without skills:**
- Test may use wrong base class
- Entity may have incomplete base fields for Views exposure
- Views data may use hook_views_data() instead of entity handler

**With-skill coherence:**
- Testing skill guides KernelTestBase with installEntitySchema() setup
- Entity skill ensures proper base field definitions that Views can expose
- Views skill shows entity-based Views integration via handler class
- Test creates entities, runs Views queries, asserts filtered results -- all guided by respective skills

---

### Multi-5: Scaffold + Batch/Queue/Cron + Blocks + Theming

**Verdict:** PASS

**Expected activation:** drupal-module-scaffold, drupal-batch-queue-cron, drupal-plugins-blocks, drupal-theming
**Activation analysis:** Module structure, background processing, block display, and themed output are distinct concerns.

**Baseline issues without skills:**
- Module may have wrong structure
- Processing may happen directly in hook_cron
- Block DI likely wrong
- Status display may be raw HTML

**With-skill coherence:**
- Scaffold provides .module file structure for hook_cron
- Batch/queue skill separates cron (queuing) from queue worker (processing)
- Block plugin reads queue state via injected services
- Theming skill provides template for status display
- Hook_cron in .module, QueueWorker in src/Plugin/QueueWorker/, Block in src/Plugin/Block/ -- consistent module structure

---

### Multi-6: Full Stack (7 Skills)

**Verdict:** PASS

**Expected activation:** drupal-module-scaffold, drupal-routing-controllers, drupal-plugins-blocks, drupal-config-storage, drupal-access-security, drupal-caching, drupal-theming
**Activation analysis:** Seven skills covering module structure, API endpoint, block display, configuration, security, performance, and presentation. All have distinct description domains.

**Baseline issues without skills:**
- Module scaffold incorrect
- Controller DI wrong, returns wrong response type
- Block uses controller-style DI
- Config has no schema
- Access uses D7 patterns
- No cache metadata
- Raw HTML output

**With-skill coherence:**
- Module scaffold contains all required files across all skill domains
- Controller and block both use DI but skills distinguish the patterns (2-param vs 4-param create)
- Config schema matches form fields matches config/install defaults
- Permission defined once, referenced in route
- Cache metadata on block render array includes tags and contexts
- Theming provides template, library attachment, and render array structure
- No contradictions between 7 skills -- cross-references ensure consistent patterns

---

## Summary

| Skill | Verdict | Key Improvement |
|-------|---------|----------------|
| drupal-module-scaffold | PASS | D10/D11 .info.yml format, PSR-4 structure |
| drupal-routing-controllers | PASS | Modern DI pattern, create() factory |
| drupal-entities-fields | PASS | D10/D11 annotation-attribute transition |
| drupal-forms-api | PASS | ConfigFormBase, _form route key |
| drupal-plugins-blocks | PASS | Plugin DI (4-param) vs controller DI (2-param) |
| drupal-config-storage | PASS | Config vs State vs TempStore decision tree |
| drupal-access-security | PASS | AccessResult objects over booleans |
| drupal-theming | PASS | Render array decision tree, library system |
| drupal-caching | PASS | Prevents max-age: 0 antipattern |
| drupal-testing | PASS | Lowest-level test type principle |
| drupal-database-api | PASS | Entity API vs Database API boundary |
| drupal-views-dev | PASS | Entity handler vs hook_views_data distinction |
| drupal-batch-queue-cron | PASS | Cron-queue separation pattern |

**Multi-skill results:** All 6 multi-skill prompts PASS. Skills compose without contradictions due to distinct domains and consistent cross-references.

**Overall:** 13/13 single-skill PASS, 6/6 multi-skill PASS. Each skill addresses documented Claude blind spots with specific wrong-way callouts, producing measurably better output than baseline Claude for Drupal module development.

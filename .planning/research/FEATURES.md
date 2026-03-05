# Feature Landscape

**Domain:** Claude skills for Drupal module development
**Researched:** 2026-03-05

## Table Stakes

Features that every skill MUST have. Missing these means Claude produces incorrect or non-idiomatic Drupal code -- worse than its baseline training data.

### Cross-Cutting (All 13 Skills)

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Correct file placement and naming conventions | Drupal enforces PSR-4 paths, YAML naming (module.routing.yml, not routes.yml). Wrong paths = silent failures. | Low | Claude's base knowledge often gets namespace paths wrong (e.g., src/Controller vs src/controllers) |
| YAML syntax accuracy | Routes, services, config schemas, permissions all use YAML. One wrong indent = broken module. | Low | Must show exact YAML structure with correct keys, not approximate |
| D10 baseline code with D11 annotations | Book is D10. D11 moved annotations to PHP attributes. Skills must show both or code breaks on one version. | Med | D10: `@Block(...)` annotation. D11: `#[Block(...)]` attribute. Show D10 primary, D11 in callout |
| Dependency injection patterns | Every service, controller, form, plugin needs DI. `create()` + `__construct()` pattern is Drupal's #1 pattern. | Med | Claude frequently omits `create()` or uses wrong container methods |
| Cross-references to related skills | Skills must point to each other (e.g., forms skill references routing skill for form routes) | Low | Prevents developers from getting partial answers |
| Drupal coding standards compliance | `@file` docblocks, use statement ordering, `$this->t()` for strings, snake_case for hooks | Low | Table stakes for any Drupal shop; Claude often mixes PSR-12 with Drupal standards |

### Skill 1: drupal-module-scaffold

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| info.yml structure with all required/optional keys | Module won't install without correct info.yml. Core version compatibility, dependencies syntax. | Low | Must cover `type`, `name`, `description`, `core_version_requirement`, `package`, `dependencies` |
| Module file structure / directory layout | Developers need to know where to put files before writing them | Low | src/, config/install, config/schema, templates/, css/, js/ |
| .module file and hook system basics | When to use .module vs service vs event subscriber | Low | Common mistake: putting logic in .module when it should be a service |
| .install file (install/uninstall hooks, update hooks) | Schema creation, default config on install, update_N hooks | Med | Critical for module lifecycle management |
| .libraries.yml structure | CSS/JS attachment patterns | Low | Must match Drupal's library definition format exactly |
| services.yml structure | Service registration, tagging, arguments syntax | Med | Must get argument format right (`@service_name`, `%parameter%`) |

### Skill 2: drupal-routing-controllers

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Route definition YAML syntax | path, defaults (_controller, _form, _title), requirements (_permission, _role, _access) | Low | Most common task; wrong keys = route doesn't register |
| Controller class structure | Extending ControllerBase, returning render arrays (NOT strings), DI via create() | Med | Claude often returns strings instead of render arrays |
| Route parameters and entity upcasting | `{node}` auto-loads entities, custom parameter converters | Med | Huge time-saver; Claude rarely gets upcasting right without guidance |
| Service definition and DI | Creating services, injecting into controllers, `@service` references | Med | Foundational pattern used everywhere |
| Event subscribers and dispatching | EventSubscriberInterface, getSubscribedEvents(), dispatching custom events | Med | Used for redirects, request/response manipulation |
| Menu links (from Ch 5) | menu links YAML, local tasks, local actions, contextual links | Med | Reference file content; menus are tightly coupled to routes |

### Skill 3: drupal-forms-api

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Form class structure (FormBase vs ConfigFormBase) | getFormId(), buildForm(), validateForm(), submitForm() lifecycle | Med | Must cover both base classes and when to use each |
| Form element types and properties | `#type`, `#title`, `#default_value`, `#required`, `#options`, `#states` | Med | Dozens of element types; cover the common 10-15 |
| Form routing (`_form` key in routing.yml) | Different from `_controller`; Claude often mixes them up | Low | |
| Form altering (hook_form_alter, hook_form_FORM_ID_alter) | Modifying contrib/core forms is 50% of form work in Drupal | Med | Must show proper alter patterns, not rebuilding forms |
| Custom submit/validate handlers | Adding handlers to existing forms, multi-step patterns | Med | |
| Config form patterns (getEditableConfigNames, config save) | Config API integration is the whole point of ConfigFormBase | Med | |
| AJAX in forms (from Ch 12 reference) | `#ajax` property, callback patterns, commands | High | Reference file; complex but critical for modern Drupal UX |

### Skill 4: drupal-plugins-blocks

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Block plugin annotation/attribute structure | Plugin ID, admin_label, category | Low | D10 annotation vs D11 attribute syntax |
| BlockBase extension pattern | build(), blockForm(), blockSubmit(), access() methods | Med | Core plugin pattern |
| Block configuration (schema + form) | Blocks with configurable settings stored in block config | Med | |
| Custom plugin type creation | Manager, interface, annotation/attribute, base class, YAML discovery | High | Chapter 7 content; complex but powerful |
| Plugin discovery mechanisms | Annotation, YAML, Hook-based discovery | Med | Understanding which to use when |

### Skill 5: drupal-entities-fields

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Content vs configuration entity distinction | When to use each, what differs (bundles, fields, exportability) | Med | Fundamental conceptual knowledge |
| Content entity type annotation/attribute | All annotation keys: id, label, handlers, keys, links, base_table, etc. | High | Massive annotation; must be complete and correct |
| Entity handlers (list builder, form, access, storage, view builder) | Each handler class and its role | High | |
| Base field definitions (baseFieldDefinitions method) | Field types, settings, display options, constraints | High | Most complex part of entity creation |
| Entity queries (entityQuery, entityTypeManager) | Loading, querying, CRUD operations on entities | Med | Daily-use API |
| Custom field types (widget + formatter + type) from Ch 9 | FieldType, FieldWidget, FieldFormatter plugin triplet | High | Three plugins that must work together |
| Entity validation | Constraint plugins, validation API | Med | |
| Bundles and configurable fields | Bundle entity types, field_config, field_storage_config | High | |

### Skill 6: drupal-config-storage

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| State API vs Config API vs TempStore | When to use each (runtime state vs deployable config vs session data) | Med | Most common conceptual confusion in Drupal |
| Config CRUD (`\Drupal::config()`, editable config, save) | Reading and writing simple config | Low | |
| Config schema YAML | schema/*.schema.yml with type, label, mapping keys | Med | Required for config translation; Claude often omits |
| Config install/optional directories | config/install vs config/optional, dependency resolution | Med | Wrong directory = config not installed or orphaned |
| Private and shared TempStore | Per-user vs shared session-like storage | Med | |
| Config overrides (settings.php, module overrides) | How config can be overridden at different layers | Med | |

### Skill 7: drupal-access-security

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Permission definitions (permissions.yml) | Static and dynamic permission definitions | Low | |
| Route access requirements | _permission, _role, _access, _custom_access | Low | |
| Custom access checkers | AccessCheckInterface, applies/access methods | Med | |
| Entity access handlers | EntityAccessControlHandler, checkAccess, checkCreateAccess | Med | |
| Node access grants system | hook_node_access_records, hook_node_grants | High | Complex subsystem; get wrong = security hole |
| CSRF protection | _csrf_token requirement, FormBuilder CSRF | Low | Security critical |
| XSS prevention (from Ch 18) | Twig auto-escaping, Xss::filter, Html::escape, check_markup | Med | Security critical |
| SQL injection prevention | Parameterized queries, never concatenate user input | Low | |

### Skill 8: drupal-theming

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Theme hooks and hook_theme() | Defining theme hooks, variables, template mapping | Med | |
| Twig template syntax | Variables, filters, functions, Drupal-specific (url(), path(), t()) | Med | |
| Render arrays (#type, #theme, #markup, #prefix/#suffix) | The heart of Drupal's render system | Med | |
| Libraries (CSS/JS) definition and attachment | .libraries.yml, #attached, drupal_attach_library | Med | |
| Theme hook suggestions | Dynamic template selection based on context | Med | |
| Preprocess functions | template_preprocess_HOOK pattern | Med | |
| Layouts API | Defining layouts, layout plugins | Med | |

### Skill 9: drupal-caching

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Cache tags, contexts, max-age | The three pillars of Drupal's cache metadata | Med | Must be correct or pages serve stale/wrong data |
| #cache property in render arrays | Adding cache metadata to render arrays | Low | |
| CacheableMetadata and bubbling | How cache metadata bubbles up through render tree | Med | |
| Cache API (cache bins, get/set/invalidate) | Direct cache backend usage | Med | |
| Lazy builders and placeholders | #lazy_builder for personalized content in cached pages | High | Complex but critical for performance |
| Block caching | getCacheContexts, getCacheTags, getCacheMaxAge on blocks | Med | |

### Skill 10: drupal-testing

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Test type selection (Unit vs Kernel vs Functional vs FunctionalJS) | When to use each type; most common mistake is wrong test type | Med | |
| PHPUnit configuration for Drupal | phpunit.xml setup, module test discovery | Med | |
| Unit test patterns | Mocking with prophecy/phpunit mocks, testing isolated logic | Med | |
| Kernel test patterns | KernelTestBase, module installation, entity creation in tests | Med | |
| Functional test patterns | BrowserTestBase, page interaction, form submission | Med | |
| Test traits and base classes | Which traits to use (UserCreationTrait, NodeCreationTrait, etc.) | Med | |

### Skill 11: drupal-database-api

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Database connection and query types | Select, insert, update, delete, merge queries | Med | |
| Schema API | hook_schema(), table/field definitions, indexes | Med | |
| Dynamic vs static queries | When to use each, security implications | Med | |
| Pager integration | PagerSelectExtender for paginated queries | Med | |
| Transaction handling | $connection->startTransaction() patterns | Med | |
| Query alters (hook_query_TAG_alter) | Modifying queries from other modules | Med | |

### Skill 12: drupal-views-dev

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Exposing data to Views (hook_views_data) | Table definitions, field definitions, relationships | High | Complex array structure; must be exact |
| Custom Views field plugin | ViewsFieldBase extension, render method | Med | |
| Custom Views filter and argument plugins | Plugin patterns for custom filtering/arguments | Med | |
| Views hooks (hook_views_query_alter, hook_views_pre_render) | Altering Views programmatically | Med | |

### Skill 13: drupal-batch-queue-cron

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Batch API (operations, finished callback, progressive) | batch_set(), operation callbacks, progress tracking | Med | |
| Queue API (QueueWorkerBase plugin, cron-based processing) | createItem, claimItem, deleteItem lifecycle | Med | |
| hook_cron implementation | Cron task patterns, avoiding timeout issues | Low | |
| Logging (from Ch 3 reference) | LoggerChannelTrait, \Drupal::logger, custom log channels | Low | |
| Lock API | Preventing concurrent execution | Med | |


## Differentiators

Features that set these skills apart from Claude's baseline Drupal knowledge. Without these, a developer might as well just ask Claude directly.

| Feature | Value Proposition | Complexity | Applies To |
|---------|-------------------|------------|------------|
| **"Wrong way" callouts** -- explicit anti-patterns with explanations | Claude's base knowledge generates plausible but subtly wrong Drupal code. Skills that say "NEVER do X because Y" prevent the most common Claude mistakes. | Med | All skills |
| **Complete file context** -- showing which files to create/modify for each task | Claude often generates a controller but forgets the routing.yml, or creates an entity without the schema. Skills listing "for this task, you need files A, B, C" prevent incomplete implementations. | Med | All skills |
| **Config schema completeness** | Claude almost never generates config schema files. Skills that always include schema when config is involved prevent i18n and config validation failures. | Med | config-storage, forms-api, plugins-blocks, entities-fields |
| **Correct DI boilerplate for each context** | DI pattern differs between Controller (create()), Form (create()), Block (create()), Subscriber (constructor), Service (services.yml). Showing the exact pattern per context type. | Med | routing-controllers, forms-api, plugins-blocks, entities-fields |
| **Entity annotation completeness** | The content entity type annotation has 20+ keys. Claude typically generates 5-6. A complete annotated reference prevents hours of debugging. | High | entities-fields |
| **D10/D11 migration callouts** | Explicit "In D11, replace @Annotation with #[Attribute]" for every plugin type prevents version confusion. | Med | All plugin-related skills |
| **Test project grounding** | Examples based on os-knowledge-garden real patterns (Search API processors, event subscribers, blocks) so skills are battle-tested against real code. | Med | All skills |
| **Cache metadata completeness** | Claude almost never adds cache tags/contexts to render arrays. A skill that drills "every render array MUST have #cache" prevents performance bugs. | Med | caching, theming, plugins-blocks |
| **hook_form_alter defensive patterns** | Showing how to safely alter forms (check element exists before modifying, use #weight not #order) prevents crashes from missing assumptions. | Med | forms-api |
| **Entity query vs database query decision tree** | When to use entityQuery vs direct DB queries. Claude defaults to DB queries when entity queries are correct. | Low | database-api, entities-fields |
| **Update hook patterns for production** | Batch update hooks, post_update hooks, proper sequencing. Claude generates update hooks that timeout on production databases. | Med | database-api, batch-queue-cron, entities-fields |
| **Permission/access layering** | Showing how route access, entity access, field access, and node grants layer together prevents security holes from only implementing one layer. | High | access-security |
| **Lazy builder patterns for cached pages** | Showing when and how to use #lazy_builder for personalized content in cached pages. Claude never suggests this. | High | caching, theming |
| **Views data array structure reference** | The hook_views_data() return array is deeply nested and poorly documented. A complete annotated structure reference is extremely valuable. | High | views-dev |
| **Queue worker error handling** | What happens when a queue worker throws. RequeueException vs SuspendQueueException vs letting it fail. | Med | batch-queue-cron |


## Anti-Features

Features to explicitly NOT include in skills. These would bloat skills past the 500-line limit, confuse developers, or go stale quickly.

| Anti-Feature | Why Avoid | What to Do Instead |
|--------------|-----------|-------------------|
| **Exhaustive form element reference** | There are 50+ form element types. Listing all bloats the skill past 500 lines and duplicates api.drupal.org. | Cover the top 15 most-used types. Link to api.drupal.org for the full list. |
| **Full Twig syntax reference** | Twig syntax is well-documented elsewhere and not Drupal-specific. | Cover Drupal-specific Twig functions/filters only (url(), path(), t(), render(), without()). |
| **Contrib module-specific patterns** | Views, Paragraphs, Commerce, etc. have their own APIs that change independently. Including them makes skills stale. | Focus on core APIs only. Mention "see contrib documentation" where relevant. |
| **Drupal installation/setup instructions** | Not module development. Different domain entirely. | Out of scope. Assume Drupal is installed and working. |
| **Drush command reference (beyond custom commands)** | Drush docs are well-maintained; duplicating them adds no value. | Show how to CREATE custom Drush commands (Skill 13). Don't catalog existing commands. |
| **Performance benchmarks or optimization guides** | Stale quickly, environment-dependent, not code-generation knowledge. | Cover caching patterns (Skill 9) which is the code-level performance lever. |
| **Migration API** | Complex subsystem not covered in the book. Would require a 14th skill. | Out of scope for this project. |
| **Detailed OOP/PHP concepts** | Developers using these skills already know PHP. Explaining interfaces/traits wastes lines. | Assume PHP proficiency. Show Drupal-specific patterns only. |
| **Full security audit checklists** | Security is contextual. A checklist gives false confidence. | Cover the specific prevention patterns (XSS, SQLI, CSRF) in access-security skill. |
| **Deprecated D7/D8 patterns** | Including old patterns creates confusion. Claude might use them. | Only show current D10 patterns. Never show "how it used to work." |
| **Admin UI screenshots or descriptions** | Skills are for code generation, not UI navigation. | Describe what the code creates, not how to navigate to it. |
| **Composer/package management** | Well-documented elsewhere; not module development code. | Out of scope. Assume composer dependencies are handled. |


## Feature Dependencies

```
drupal-module-scaffold ──> ALL OTHER SKILLS (every skill assumes module exists)

drupal-routing-controllers ──> drupal-forms-api (forms need routes with _form key)
drupal-routing-controllers ──> drupal-plugins-blocks (blocks use route-based links)
drupal-routing-controllers ──> drupal-access-security (access requires route understanding)

drupal-entities-fields ──> drupal-database-api (entities use Schema API under the hood)
drupal-entities-fields ──> drupal-views-dev (Views expose entity data)
drupal-entities-fields ──> drupal-forms-api (entity forms)
drupal-entities-fields ──> drupal-access-security (entity access handlers)

drupal-config-storage ──> drupal-forms-api (config forms)
drupal-config-storage ──> drupal-entities-fields (config entities)
drupal-config-storage ──> drupal-module-scaffold (config/install, config/schema dirs)

drupal-theming ──> drupal-caching (render arrays carry cache metadata)
drupal-theming ──> drupal-routing-controllers (controllers return render arrays)

drupal-plugins-blocks ──> drupal-caching (block caching methods)
drupal-plugins-blocks ──> drupal-access-security (block access method)

drupal-testing ──> ALL DOMAIN SKILLS (tests validate code from all domains)

drupal-batch-queue-cron ──> drupal-database-api (update hooks with batches)
drupal-batch-queue-cron ──> drupal-entities-fields (queue workers process entities)
```

## MVP Recommendation

### Wave 1 (Foundational -- all other skills depend on these)

1. **drupal-module-scaffold** -- Every skill assumes a module exists. Ship first so other skills can reference it.
2. **drupal-routing-controllers** -- Routes/services/DI are the backbone of every module. Most common developer task.
3. **drupal-entities-fields** -- Most complex and most valuable. Claude gets entities badly wrong without guidance. Highest ROI skill.

### Wave 2 (Core workflow -- enables the most common development tasks)

4. **drupal-forms-api** -- Forms are 30%+ of module development work.
5. **drupal-plugins-blocks** -- Blocks are the most common plugin type. Custom plugin types unlock advanced patterns.
6. **drupal-config-storage** -- Config vs state vs tempstore confusion causes real bugs.

### Wave 3 (Correctness layer -- prevents subtle bugs)

7. **drupal-access-security** -- Security must be right. No "good enough" for permissions.
8. **drupal-theming** -- Render arrays, templates, libraries for UI output.
9. **drupal-caching** -- Performance correctness. Without this, sites are slow or serve wrong data.
10. **drupal-testing** -- Test patterns for validating everything else.

### Wave 4 (Specialized -- less common but still needed)

11. **drupal-database-api** -- Direct DB access when entities aren't appropriate.
12. **drupal-views-dev** -- Programmatic Views integration.
13. **drupal-batch-queue-cron** -- Background processing, cron, logging.

**Defer nothing** -- all 13 skills are scoped to book content. The wave ordering is about dependency management and developer workflow frequency, not scope cuts.

## Prioritization Rationale

The key insight is that Claude's base Drupal knowledge is **approximately D8-era and shallow**. It knows function names but gets structural patterns wrong:

- It returns strings from controllers instead of render arrays
- It omits `create()` methods for dependency injection
- It forgets config schema files
- It uses D7 patterns (e.g., `drupal_render()` instead of render arrays with `#type`)
- It doesn't add cache metadata to render arrays
- It generates incomplete entity annotations

The skills that correct the MOST FREQUENT Claude mistakes should ship first. That's routing/controllers (every module has routes), entities (most complex, most wrong), and forms (most common interaction pattern).

## Sources

- Sipos D., "Drupal 10 Module Development," 4th ed., 2023 -- primary source for all domain content
- os-knowledge-garden project -- real-world validation of patterns (routes, services, blocks, Search API processors, event subscribers, templates)
- Existing skill-creator SKILL.md format (context7 skill example) -- anatomy constraints
- polished-tickling-owl.md -- project execution plan with skill definitions

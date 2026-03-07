# Phase 3: Presentation and Quality - Research

**Researched:** 2026-03-05
**Domain:** Drupal module development skills for Claude Code (theming, caching, testing, database API)
**Confidence:** HIGH

## Summary

Phase 3 builds four Claude Code skills covering Drupal's presentation layer (theming, caching), quality assurance (testing), and direct database interaction (Database API). These compose with Phase 1 (foundations) and Phase 2 (core workflow) skills to complete the module development workflow, enabling Claude to produce fully themed output, correctly cached render arrays, automated tests, and database queries when needed.

Each skill must follow the established skill-creator anatomy: YAML frontmatter, sub-500-line SKILL.md body, references/ subdirectory, decision-guide format, wrong-way callouts (minimum 3, aim for 5+), D10/D11 dual syntax where applicable, and cross-references with graceful degradation. The four domains in this phase have significant cross-referencing needs -- theming produces render arrays that caching metadata applies to, testing covers test types for all other skills, and database API connects to entity/query systems from Phase 1.

The highest-risk skill is drupal-theming due to covering render arrays (foundational concept), Twig templates, theme hooks, hook_theme(), preprocess functions, libraries, attributes, AND a JS/Ajax reference file. This is a large surface area requiring careful scoping to stay under 500 lines with progressive disclosure via the reference file. The drupal-caching skill must clearly teach cache tags, contexts, and max-age as properties that go on EVERY render array -- not optional decoration. The drupal-testing skill covers four PHPUnit test types with distinct base classes, directory structures, and use cases. The drupal-database-api skill is the most straightforward, covering Schema API, dynamic queries, and static queries with clear decision guidance on when to use entities vs direct queries.

**Primary recommendation:** Build skills in this order: (1) drupal-theming (render arrays are foundational for caching), (2) drupal-caching (depends on render array understanding from theming), (3) drupal-database-api (straightforward, independent), (4) drupal-testing (cross-cuts all skills, benefits from completed context for test examples).

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| PRES-01 | drupal-theming skill covers render arrays, Twig templates, theme hooks, preprocess functions, with JS/Ajax reference file | Book Ch 4 (theme system, render arrays, hook_theme, templates, preprocess, libraries, attributes, layouts) + Ch 12 (JavaScript behaviors, drupalSettings, Ajax API, States system) |
| PRES-02 | drupal-caching skill covers cache tags, contexts, max-age, lazy builders, cache invalidation | Book Ch 11 (cacheability metadata, cache tags/contexts/max-age on render arrays, block plugin caching, lazy builders, auto-placeholdering, Cache API for custom entries, cache bins) |
| PRES-03 | drupal-testing skill covers PHPUnit test types, kernel tests, functional tests, browser tests | Book Ch 17 (Unit tests with UnitTestCase, Kernel tests with KernelTestBase, Functional tests with BrowserTestBase, FunctionalJavaScript tests, test registration, directory structure, assertions) |
| PRES-04 | drupal-database-api skill covers database abstraction layer, schema API, dynamic queries | Book Ch 8 (hook_schema, Schema API field types, database connection service, static queries, dynamic SelectInterface queries, joins, range queries, pagers, INSERT/UPDATE/DELETE, query altering, update hooks) |
</phase_requirements>

## Standard Stack

This phase produces Claude Code skill files (markdown + YAML), not executable code. The "stack" is the Drupal APIs the skills teach Claude to generate.

### Core Drupal APIs Covered

| API | Drupal Version | Purpose | Book Source |
|-----|---------------|---------|-------------|
| Theme/Render API | D10/D11 | Render arrays, Twig templates, theme hooks, preprocess | Ch 4 |
| Libraries/Assets | D10/D11 | CSS/JS attachment via libraries.yml | Ch 4 |
| JavaScript/Ajax API | D10/D11 | Behaviors, drupalSettings, Ajax commands | Ch 12 |
| Cache API | D10/D11 | Cache metadata, tags, contexts, max-age, lazy builders | Ch 11 |
| PHPUnit Testing | D10/D11 | Unit, Kernel, Functional, FunctionalJavaScript tests | Ch 17 |
| Database/Schema API | D10/D11 | hook_schema, dynamic queries, database abstraction | Ch 8 |

### D10 vs D11 Syntax Differences (Phase 3 Specific)

| Feature | D10 Syntax | D11 Syntax | Impact |
|---------|-----------|------------|--------|
| Theme system | No syntax changes | No syntax changes | Render arrays, hook_theme, Twig are stable across versions |
| Libraries | No syntax changes | No syntax changes | Libraries API is stable |
| JavaScript | jQuery still used, `once` library | Same | once library decoupled from jQuery plugin pre-D10 |
| Cache API | No syntax changes | No syntax changes | Cache metadata system is stable |
| PHPUnit | PHPUnit 9.x | PHPUnit 10.x | D11 uses PHPUnit 10; @group annotation still works but attributes preferred |
| Database API | No syntax changes | No syntax changes | Schema API and query builder are stable |

**Key D11 testing note:** Drupal 11 uses PHPUnit 10 which deprecates some annotations in favor of PHP attributes. However, `@group` annotation still works. The test base classes (UnitTestCase, KernelTestBase, BrowserTestBase) remain identical. Skills should note this transition.

## Architecture Patterns

### Skill Directory Structure (Phase 3)

```
skills/
+-- drupal-theming/
|   +-- SKILL.md              # <500 lines: render arrays, hook_theme, Twig, preprocess, libraries
|   +-- references/
|       +-- js-ajax.md          # JavaScript behaviors, drupalSettings, Ajax API, States system (Ch 12)
+-- drupal-caching/
|   +-- SKILL.md              # <500 lines: cache tags, contexts, max-age, lazy builders, Cache API
|   +-- references/
|       +-- .gitkeep
+-- drupal-testing/
|   +-- SKILL.md              # <500 lines: Unit, Kernel, Functional, FunctionalJavascript test types
|   +-- references/
|       +-- .gitkeep
+-- drupal-database-api/
    +-- SKILL.md              # <500 lines: Schema API, static queries, dynamic queries, update hooks
    +-- references/
        +-- .gitkeep
```

### Pattern: Render Array Decision Tree (drupal-theming)

```
What are you rendering?
+-- Simple text/markup? -> Use #markup (sanitized by Xss::filterAdmin)
+-- Plain text (fully escaped)? -> Use #plain_text
+-- Custom themed output? -> Define hook_theme(), use #theme, create Twig template
+-- Existing theme hook (table, item_list, links)? -> Use #theme with core hook name
+-- Render element (standardized component)? -> Use #type (form elements, links, etc.)
```

### Pattern: Cache Metadata Decision Tree (drupal-caching)

```
For EVERY render array, ask:
1. What does it depend on? -> cache tags (e.g., node:5, config:module.settings, node_list)
2. What does it vary by? -> cache contexts (e.g., user, user.roles, url.path, languages)
3. How long should it live? -> max-age (Cache::PERMANENT by default, 0 for never cache)

Special cases:
+-- Highly dynamic content? -> Use lazy builders (#lazy_builder) with auto-placeholdering
+-- Block plugin? -> Override getCacheContexts()/getCacheTags() on block class
+-- Access results? -> AccessResult also carries cache metadata, must set it
```

### Pattern: Test Type Decision Tree (drupal-testing)

```
What are you testing?
+-- Single class methods with mockable dependencies? -> Unit test (UnitTestCase)
+-- Component needing database/services but not browser? -> Kernel test (KernelTestBase)
+-- Page behavior, form submission, navigation? -> Functional test (BrowserTestBase)
+-- JavaScript interactions, Ajax? -> FunctionalJavascript test (WebDriverTestBase)

Rule of thumb: Use the LOWEST level test type that covers your need (faster tests).
```

### Pattern: Database Query Decision Tree (drupal-database-api)

```
How should you access data?
+-- Working with entities? -> Use Entity Query (NOT direct database)
+-- Need complex SQL that entity query can't handle? -> Use database API, but load entities by ID afterward
+-- Custom tables (non-entity)? -> Use hook_schema() + database API
+-- Simple query? -> $database->query("SQL", [':placeholder' => $value])
+-- Complex/alterable query? -> $database->select('table', 'alias')->fields()->condition()->execute()
```

### Anti-Patterns to Avoid

- **Hardcoding HTML in controllers:** Always use render arrays with #theme or #markup. Never return raw HTML strings from controllers.
- **Missing cache metadata on render arrays:** Every render array that displays entity or config data MUST include #cache with appropriate tags, contexts, and max-age.
- **Using Functional tests when Kernel tests suffice:** Functional tests are slow (full Drupal install per test). Use Kernel tests when browser interaction is not needed.
- **Direct SQL for entity data:** Always use Entity Query and entity storage handlers. Only use database API for custom tables or queries Entity Query cannot handle.
- **Forgetting `once()` in JavaScript behaviors:** Without `once()`, behaviors re-fire on Ajax loads, duplicating DOM elements.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| HTML output | String concatenation in PHP | Render arrays + Twig templates | Render arrays enable caching, altering, theming |
| CSS/JS inclusion | `<script>` tags or inline styles | Libraries API (module.libraries.yml) | Aggregation, dependency management, conditional loading |
| Cache invalidation | Manual cache clearing logic | Cache tags with CacheableDependencyInterface | Automatic invalidation when tagged data changes |
| Form element visibility | Custom JavaScript for show/hide | #states API | Declarative, works with form rebuilds |
| Test browser simulation | curl/HTTP client in tests | BrowserTestBase with Mink | Integrated assertions, session management, form helpers |
| Database schema | Raw SQL CREATE TABLE | hook_schema() | Cross-database compatibility, automatic install/uninstall |

**Key insight:** Drupal's render pipeline, cache system, and test framework are deeply integrated. Hand-rolling any of these loses the framework's automatic handling of cache invalidation, security filtering, and test isolation.

## Common Pitfalls

### Pitfall 1: Render Arrays Without Cache Metadata
**What goes wrong:** Render array displays entity/config data but omits #cache tags. Content becomes stale after updates.
**Why it happens:** Caching is invisible during development (usually disabled). Code "works" without cache metadata.
**How to avoid:** Treat #cache as mandatory on every render array. Use `$entity->getCacheTags()` and `$config->getCacheTags()` to get tags.
**Warning signs:** Stale content after editing, content only updates after cache clear.

### Pitfall 2: max-age 0 Without Understanding Consequences
**What goes wrong:** Setting `'max-age' => 0` on a render array bubbles up to the page level, preventing the ENTIRE page from being cached by Dynamic Page Cache.
**Why it happens:** Developers set max-age 0 as a "quick fix" for dynamic content.
**How to avoid:** Use lazy builders (#lazy_builder) to isolate uncacheable components. Only the lazy-built placeholder is uncacheable; the rest of the page can still be cached.
**Warning signs:** Performance degradation on authenticated user pages.

### Pitfall 3: Internal Page Cache vs Dynamic Page Cache Confusion
**What goes wrong:** max-age 0 works for authenticated users (Dynamic Page Cache respects it) but NOT for anonymous users (Internal Page Cache ignores bubbled max-age).
**Why it happens:** Two separate cache systems with different behaviors.
**How to avoid:** For truly uncacheable anonymous content, use the page_cache_kill_switch service. The book's testing chapter demonstrates this exact bug.
**Warning signs:** Anonymous users see stale content while authenticated users see correct content.

### Pitfall 4: Wrong Test Base Class
**What goes wrong:** Extending BrowserTestBase (Functional) for a test that only needs database access. Test runs 10-100x slower than needed.
**Why it happens:** Developers default to the most "complete" test type without considering whether a lighter type suffices.
**How to avoid:** Use the decision tree: Unit < Kernel < Functional < FunctionalJavascript. Always choose the lowest level that works.
**Warning signs:** Test suite takes excessively long to run.

### Pitfall 5: Missing Module List in Kernel/Functional Tests
**What goes wrong:** Test fails with cryptic errors about missing services, schemas, or entity types.
**Why it happens:** Kernel tests only load specified modules (not all installed modules). Functional tests install a minimal Drupal with specified modules only.
**How to avoid:** Add required modules to `protected static $modules = [...]`. Expect trial-and-error to find the complete list. Install schemas manually in Kernel tests with `$this->installSchema()` and `$this->installEntitySchema()`.
**Warning signs:** "Service not found", "Table not found", EntityTypeManager errors in tests.

### Pitfall 6: Using hook_theme() Wrong
**What goes wrong:** Theme hook defined without proper variables array, or template not placed in templates/ directory, or template name doesn't match hook name (underscores to hyphens).
**Why it happens:** Naming conventions are implicit. hook_theme() expects `hello_world_salutation` hook to map to `hello-world-salutation.html.twig` in `templates/`.
**How to avoid:** Always follow the naming convention: underscores in hook name become hyphens in template filename. Place templates in `templates/` directory. Define all variables with defaults in hook_theme().
**Warning signs:** Blank output, "template not found" warnings.

### Pitfall 7: Direct Table Queries Without Placeholders
**What goes wrong:** SQL injection vulnerability from concatenating user input into query strings.
**Why it happens:** Developers used to other frameworks write raw SQL.
**How to avoid:** Always use placeholders (`:name`) in static queries, or use the dynamic query builder which handles escaping. Never concatenate values into SQL strings.
**Warning signs:** Any `$database->query("... $variable ...")` pattern.

## Code Examples

Verified patterns from book source (Ch 4, 8, 11, 12, 17):

### Defining a Theme Hook (hook_theme)

```php
// Source: Book Ch 4 - Theming our Hello World module
/**
 * Implements hook_theme().
 */
function hello_world_theme($existing, $type, $theme, $path) {
  return [
    'hello_world_salutation' => [
      'variables' => ['salutation' => NULL, 'target' => NULL, 'overridden' => FALSE],
    ],
  ];
}
```

Template file: `templates/hello-world-salutation.html.twig`

```twig
{# Source: Book Ch 4 #}
<div {{ attributes }}>
  {{ salutation }}
  {% if target %}
    <span class="salutation-target">{{ target }}</span>
  {% endif %}
</div>
```

### Preprocess Function

```php
// Source: Book Ch 4
/**
 * Default preprocessor for the hello_world_salutation theme hook.
 */
function template_preprocess_hello_world_salutation(&$variables) {
  $variables['attributes'] = [
    'class' => ['salutation'],
  ];
}
```

### Render Array with Cache Metadata

```php
// Source: Book Ch 11 - Using the cache metadata
$render = [
  '#theme' => 'hello_world_salutation',
  '#salutation' => ['#markup' => $salutation],
  '#cache' => [
    'tags' => $config->getCacheTags(),
    'contexts' => ['user'],
    'max-age' => Cache::PERMANENT,
  ],
];
```

### Lazy Builder Pattern

```php
// Source: Book Ch 11 - Lazy building
$build['salutation'] = [
  '#lazy_builder' => [
    'hello_world.salutation:renderSalutation', // service:method
    [],                                         // arguments (must be scalar)
  ],
  '#create_placeholder' => TRUE,
];
```

### Library Definition and Attachment

```yaml
# Source: Book Ch 4 - module_name.libraries.yml
hello_world_clock:
  version: 1.x
  js:
    js/hello_world_clock.js: {}
  dependencies:
    - core/jquery
    - core/drupal
    - core/once
```

```php
// Attaching to render array
$render['#attached'] = [
  'library' => ['hello_world/hello_world_clock'],
];
```

### JavaScript Behavior Pattern

```javascript
// Source: Book Ch 12
(function (Drupal, $) {
  "use strict";

  Drupal.behaviors.helloWorldClock = {
    attach: function (context, settings) {
      $(once('helloWorldClock', '.salutation')).each(function() {
        // DOM manipulation here, runs once per element
      });
    }
  };
})(Drupal, jQuery);
```

### Passing PHP Values to JavaScript

```php
// Source: Book Ch 12 - Drupal settings
$render['#attached']['drupalSettings']['hello_world']['afternoon'] = TRUE;
```

```javascript
// In JavaScript behavior
if (settings.hello_world !== undefined &&
    settings.hello_world.hello_world_clock.afternoon !== undefined) {
  // Use the PHP-provided value
}
```

### Hook Schema (Database)

```php
// Source: Book Ch 8 - The Schema API
/**
 * Implements hook_schema().
 */
function sports_schema() {
  $schema = [];
  $schema['teams'] = [
    'description' => 'The table that holds team data.',
    'fields' => [
      'id' => [
        'description' => 'The primary identifier.',
        'type' => 'serial',
        'unsigned' => TRUE,
        'not null' => TRUE,
      ],
      'name' => [
        'type' => 'varchar',
        'length' => 255,
        'not null' => TRUE,
      ],
    ],
    'primary key' => ['id'],
  ];
  return $schema;
}
```

### Dynamic Select Query

```php
// Source: Book Ch 8 - Select queries
$result = $database->select('players', 'p')
  ->fields('p')
  ->condition('id', 1)
  ->execute();

foreach ($result as $record) {
  $name = $record->name;
}
```

### Static Query with Placeholders

```php
// Source: Book Ch 8 - Running queries
$result = $database->query(
  "SELECT * FROM {players} WHERE [id] = :id",
  [':id' => 1]
);
```

### Join Query

```php
// Source: Book Ch 8 - More complex select queries
$query = $database->select('players', 'p');
$query->join('teams', 't', 't.id = p.team_id');
$query->addField('p', 'name', 'player_name');
$query->addField('t', 'name', 'team_name');
$result = $query
  ->fields('p', ['id', 'data'])
  ->condition('p.id', 1)
  ->execute();
```

### Unit Test

```php
// Source: Book Ch 17 - Unit tests
namespace Drupal\Tests\hello_world\Unit;

use Drupal\hello_world\Calculator;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Calculator class methods.
 *
 * @group hello_world
 */
class CalculatorTest extends UnitTestCase {

  public function testAdd() {
    $calculator = new Calculator(10, 5);
    $this->assertEquals(15, $calculator->add());
  }
}
```

### Kernel Test

```php
// Source: Book Ch 17 - Kernel tests
namespace Drupal\Tests\sports\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test the TeamCleaner QueueWorker plugin.
 *
 * @group sports
 */
class TeamCleanerTest extends KernelTestBase {

  protected static $modules = ['sports'];

  public function testProcessItem() {
    $this->installSchema('sports', 'teams');
    $database = $this->container->get('database');

    $fields = ['name' => 'Team name'];
    $id = $database->insert('teams')->fields($fields)->execute();

    $records = $database->query(
      "SELECT id FROM {teams} WHERE id = :id",
      [':id' => $id]
    )->fetchAll();
    $this->assertNotEmpty($records);

    // ... test logic, then assert deletion
  }
}
```

### Functional (Browser) Test

```php
// Source: Book Ch 17 - Functional tests
namespace Drupal\Tests\hello_world\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Basic testing of the main Hello World page.
 *
 * @group hello_world
 */
class HelloWorldPageTest extends BrowserTestBase {

  protected static $modules = ['hello_world', 'user', 'node'];
  protected $defaultTheme = 'stark';

  public function testPage() {
    $this->drupalGet('/hello');
    $this->assertSession()->pageTextContains('Our first route');
  }
}
```

### Common Theme Hooks (table, item_list, links)

```php
// Source: Book Ch 4 - Common theme hooks
// Table
$build = [
  '#theme' => 'table',
  '#header' => ['Column 1', 'Column 2'],
  '#rows' => [
    ['Row 1 Col 1', 'Row 1 Col 2'],
    ['Row 2 Col 1', 'Row 2 Col 2'],
  ],
];

// Item list
$build = [
  '#theme' => 'item_list',
  '#items' => ['Item 1', 'Item 2'],
  '#list_type' => 'ol',  // optional, defaults to 'ul'
];

// Links
$build = [
  '#theme' => 'links',
  '#links' => [
    ['title' => 'Link 1', 'url' => Url::fromRoute('<front>')],
    ['title' => 'Link 2', 'url' => Url::fromRoute('my_module.route')],
  ],
  '#set_active_class' => TRUE,
];
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `$(document).ready()` | `Drupal.behaviors` with `once()` | D8+ | Behaviors re-fire on Ajax/BigPipe loads |
| jQuery `$.once()` plugin | Standalone `once()` library | D10 | Decoupled from jQuery, `core/once` dependency |
| PHPUnit 9 annotations only | PHPUnit 10 supports PHP attributes | D11 | `@group` still works, attributes preferred in D11 |
| `drupal_render()` procedural | `RendererInterface` service | D8+ | OOP rendering, service injection |
| Theme functions (PHP rendering) | Twig templates only | D8+ | All theme hooks use Twig templates, no PHP theme functions |

**Deprecated/outdated:**
- `drupal_render()`: Use the renderer service (`\Drupal::service('renderer')`) or return render arrays from controllers (auto-rendered)
- PHP theme functions: All rendering uses Twig templates since D8. No `theme_*()` functions.
- `jQuery.once()` plugin: Replaced by standalone `once()` library in D10

## Open Questions

1. **PHPUnit version differences D10 vs D11**
   - What we know: D10 uses PHPUnit 9, D11 uses PHPUnit 10. Base classes are identical. `@group` annotations work in both.
   - What's unclear: Specific PHPUnit 10 deprecation warnings that might affect test patterns.
   - Recommendation: Note in skill that `@group` works across both versions. D11 test patterns remain the same at the SKILL.md level of abstraction.

2. **FunctionalJavascript test setup complexity**
   - What we know: Requires ChromeDriver/Selenium, more complex CI setup. Book covers it briefly.
   - What's unclear: How much detail to include vs keeping skill under 500 lines.
   - Recommendation: Cover FunctionalJavascript tests briefly (base class, when to use, basic example). Detailed setup is environment-specific and not suitable for a skill file.

3. **Ajax API depth for reference file**
   - What we know: Ch 12 covers Ajax links, Ajax forms, Ajax commands (ReplaceCommand, InvokeCommand, etc.), States system. Large surface area.
   - What's unclear: How much fits in the js-ajax.md reference file while staying useful.
   - Recommendation: Focus reference file on: (1) behavior/once pattern, (2) drupalSettings, (3) Ajax form callbacks with AjaxResponse + commands, (4) States API for form elements. Skip complex Ajax link patterns.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Manual validation via automated shell checks |
| Config file | None -- skills are markdown files, not executable code |
| Quick run command | `wc -l < skills/drupal-*/SKILL.md` (line count check) |
| Full suite command | `for f in skills/drupal-*/SKILL.md; do echo "$f:"; wc -l < "$f"; grep -c "WRONG:" "$f"; grep -c "if installed\|if available" "$f"; done` |

### Phase Requirements to Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| PRES-01 | drupal-theming SKILL.md valid | automated check | `test -f skills/drupal-theming/SKILL.md && wc -l < skills/drupal-theming/SKILL.md` | Wave 0 |
| PRES-01 | js-ajax.md reference file | automated check | `test -f skills/drupal-theming/references/js-ajax.md` | Wave 0 |
| PRES-02 | drupal-caching SKILL.md valid | automated check | `test -f skills/drupal-caching/SKILL.md && wc -l < skills/drupal-caching/SKILL.md` | Wave 0 |
| PRES-03 | drupal-testing SKILL.md valid | automated check | `test -f skills/drupal-testing/SKILL.md && wc -l < skills/drupal-testing/SKILL.md` | Wave 0 |
| PRES-04 | drupal-database-api SKILL.md valid | automated check | `test -f skills/drupal-database-api/SKILL.md && wc -l < skills/drupal-database-api/SKILL.md` | Wave 0 |

### Sampling Rate
- **Per task commit:** Line count + wrong-way callout count + cross-ref count for modified skill
- **Per wave merge:** Full suite check across all 4 new skills
- **Phase gate:** All skills exist, under 500 lines, minimum callouts/cross-refs, frontmatter valid

### Wave 0 Gaps
- [ ] `skills/drupal-theming/` directory and SKILL.md
- [ ] `skills/drupal-theming/references/js-ajax.md`
- [ ] `skills/drupal-caching/` directory and SKILL.md
- [ ] `skills/drupal-testing/` directory and SKILL.md
- [ ] `skills/drupal-database-api/` directory and SKILL.md

## Sources

### Primary (HIGH confidence)
- Book source: "Sipos D. Drupal 10 Module Development, 4th ed, 2023" -- Ch 4 (Theming), Ch 8 (Database API), Ch 11 (Caching), Ch 12 (JavaScript/Ajax), Ch 17 (Automated Testing)
- Existing skills in `skills/` directory -- established patterns for SKILL.md anatomy, frontmatter, wrong-way callouts, cross-references, reference files

### Secondary (MEDIUM confidence)
- Drupal.org Cache API docs: https://www.drupal.org/docs/8/api/cache-api/cache-api
- Drupal.org Libraries/Assets docs: https://www.drupal.org/docs/creating-modules/adding-assets-css-js-to-a-drupal-module-via-librariesyml

### Tertiary (LOW confidence)
- PHPUnit 10 deprecation details for D11 -- training data only, flagged for validation

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - all APIs documented in book source with complete code examples
- Architecture: HIGH - established skill patterns from Phase 1/2, directory structure proven
- Pitfalls: HIGH - book explicitly demonstrates caching bugs caught by tests (Ch 17 reveals Ch 11 bug)

**Research date:** 2026-03-05
**Valid until:** 2026-04-05 (stable APIs, book content is static)

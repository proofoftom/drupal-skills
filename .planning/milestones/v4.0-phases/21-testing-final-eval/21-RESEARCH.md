# Phase 21: Testing + Final Eval - Research

**Researched:** 2026-03-08
**Domain:** Drupal 10 PHPUnit testing (Kernel + Functional), phpcs compliance, three-tier eval pipeline (static + runtime + browser)
**Confidence:** HIGH

## Summary

Phase 21 is the final v4.0 phase. It has two distinct workstreams that serve different purposes: (1) writing PHPUnit tests for the v4.0 features as an eval round (TEST-01 through TEST-03), and (2) designing and running the three-tier eval pipeline that measures skill value (EVAL-01 through EVAL-04). Both workstreams follow the established eval-driven cadence from Phases 18-20.

The testing workstream focuses on Kernel tests for REST API endpoints and Functional tests for the board page. REST endpoint testing is the primary domain gap -- the existing test suite (6 files from v3.0) covers entity CRUD, access control, settings form, dashboard, cron/queue, and entity forms, but has zero coverage of the custom REST controllers (TaskApiController, ProjectApiController, KanbanController) added in Phases 18-20. The critical testing technique is using Symfony's HttpKernelInterface in Kernel tests to make programmatic HTTP requests and assert JSON response shapes, status codes, and cache tags without the overhead of BrowserTestBase. This is faster than Functional tests and sufficient for controller logic that does not require browser simulation. Functional tests are needed only for the board page (verifying HTML rendering, drupalSettings population, library attachment, local task tab presence).

The eval workstream follows the exact pattern from Phases 17-20: design static assertions targeting drupal-testing skill patterns, set up ddev instances from the template, run headless Haiku with/without plugin, draft runtime assertions during runs, grade with eval-grader agent, compare deltas. The primary skill under test is drupal-testing, with all other skills as regression context. phpcs compliance (TEST-03) is validated as a runtime assertion, not a PHPUnit test -- it runs as `ddev exec phpcs --standard=Drupal,DrupalPractice` on the generated test files.

**Primary recommendation:** Structure the eval prompt to have Haiku write NEW tests for the v4.0 REST API and board page features (not duplicate existing v3.0 tests). Static assertions should target the testing skill's WRONG/RIGHT callouts: correct base class selection (KernelTestBase for API, BrowserTestBase for page), `$modules` completeness, `installEntitySchema()` calls, `$defaultTheme = 'stark'`, and `@group` annotation. Runtime assertions should include actual PHPUnit execution (kernel tests pass, functional tests pass) since that is the strongest signal.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| TEST-01 | Kernel tests for REST endpoint response shapes and access control | Use `$this->container->get('http_kernel')->handle(Request::create(...))` pattern for programmatic HTTP requests in KernelTestBase. Assert response status codes, JSON body shapes, and cache tag headers. Set current user via `$this->container->get('current_user')->setAccount($user)` for permission testing. Existing EntityCrudTest and AccessControlTest provide module dependency and setUp patterns. |
| TEST-02 | Functional tests for board page rendering, local task tab presence, drupalSettings population | BrowserTestBase with `$defaultTheme = 'stark'` (or 'claro' for admin). Test `drupalGet('/admin/content/project/{id}/board')`, assert 200 status, `elementExists('css', '#kanban-app')` for mount point, `pageTextContains('Board')` for local task tab. drupalSettings verification via `$this->assertSession()->responseContains('drupalSettings')` or checking raw page content for the settings JSON. |
| TEST-03 | phpcs compliance on all new and modified PHP files | Not a PHPUnit test. Run as runtime assertion: `ddev exec phpcs --standard=Drupal,DrupalPractice modules/custom/group_ai_pm/` with `--extensions=php,module,install`. Requires drupal/coder installed via composer. Zero errors = pass. |
| EVAL-01 | Static eval assertions targeting wiring (library deps, drupalSettings attachment, CSRF fetch in JS) | N/A for Phase 21 -- this was already evaluated in Phases 18-19. Phase 21 static assertions should target drupal-testing skill patterns: @group annotation, correct base class, $modules array completeness, installEntitySchema() calls, $defaultTheme property, parent::setUp() ordering. |
| EVAL-02 | Runtime eval assertions (drush-based endpoint verification, module enable, permission checks) | Phase 21 runtime: module re-enables after test files added, test files in correct directory structure, PHP lint passes, PHPUnit discovers tests, kernel tests pass, functional tests pass, phpcs passes. Same pattern as Phase 17 runtime assertions. |
| EVAL-03 | Browser eval assertions (board renders, drag-drop works, AJAX toggles function) | Phase 21 does NOT need new browser assertions for the board itself (that was Phase 18-19 scope). Browser eval can verify the board still renders after test-related changes, but the primary signal is from PHPUnit pass/fail. Consider a single browser assertion confirming the board page loads after module changes. |
| EVAL-04 | Three-tier eval results per phase with delta measurement (with-plugin vs without-plugin) | Same pipeline as Phases 18-20: headless Haiku with/without plugin, eval-grader agent grades both, compute delta. Three tiers: static (evals.json), runtime (drush + phpunit execution), browser (optional, single board-loads check). Aggregate v4.0 delta report across all 4 phases. |
</phase_requirements>

## Standard Stack

### Core

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| PHPUnit | 9 (D10) / 10 (D11) | Test framework | Drupal core's standard test runner. Base classes identical across D10/D11. |
| KernelTestBase | core | Partial bootstrap tests for service/entity/controller testing | Fastest test type that has database + services. Sufficient for REST endpoint testing via HttpKernelInterface. |
| BrowserTestBase | core | Full Drupal install with simulated browser | Required for page rendering, form submission, HTML assertions. Needed for board page tests. |
| drupal/coder | ^8.3 | phpcs standards (Drupal + DrupalPractice) | Official Drupal coding standards package. Provides both sniff sets. |

### Supporting

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| HttpKernelInterface | Symfony/core | Programmatic HTTP requests in Kernel tests | Test REST controller response shapes without full Drupal install overhead |
| UserCreationTrait | core | Create test users in Kernel tests | Test permission-based access on REST endpoints |
| AssertContentTrait | core | Assert response content in Kernel tests | Verify JSON response bodies contain expected keys |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Kernel + HttpKernel for API | BrowserTestBase for API | BrowserTestBase is 10-100x slower per test class. Kernel is sufficient for JSON endpoints. |
| Kernel for API access | Unit tests with mocks | Unit tests cannot verify the full request/routing/controller pipeline. Kernel tests exercise real routing. |
| Manual phpcs | PHPUnit phpcs test | phpcs is better as a separate CI/runtime check, not a PHPUnit test. Mixing concerns. |

**Installation:**
```bash
# In ddev environment
ddev composer require --dev drupal/coder squizlabs/php_codesniffer
ddev exec vendor/bin/phpcs --config-set installed_paths vendor/drupal/coder/coder_sniffer
```

## Architecture Patterns

### Recommended Test Structure
```
modules/custom/group_ai_pm/
  tests/
    src/
      Kernel/
        EntityCrudTest.php         (existing)
        AccessControlTest.php      (existing)
        CronQueueTest.php          (existing)
        RestApiTest.php            (NEW - Phase 21)
      Functional/
        DashboardTest.php          (existing)
        EntityFormIntegrationTest.php  (existing)
        SettingsFormTest.php       (existing)
        BoardPageTest.php          (NEW - Phase 21)
```

### Pattern 1: Kernel Test for REST Endpoints via HttpKernelInterface
**What:** Use the HTTP kernel to make programmatic requests to custom controller routes in Kernel tests. This tests the full routing + controller + response pipeline without the overhead of BrowserTestBase.
**When to use:** Testing JSON API endpoints that return structured data, do not require browser rendering.
**Example:**
```php
// Source: Drupal.org - Making HTTP requests in Kernel tests
use Symfony\Component\HttpFoundation\Request;
use Drupal\Tests\user\Traits\UserCreationTrait;

class RestApiTest extends KernelTestBase {
  use UserCreationTrait;

  protected static $modules = [
    'group_ai_pm', 'system', 'user', 'field',
    'datetime', 'text', 'options', 'views', 'group',
  ];

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('project');
    $this->installEntitySchema('task');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->installConfig(['group_ai_pm']);
  }

  public function testKanbanEndpointReturnsGroupedTasks() {
    // Create user with permission and set as current user.
    $user = $this->createUser(['access group_ai_pm dashboard', 'view project', 'view task']);
    $this->container->get('current_user')->setAccount($user);

    // Create project and tasks.
    $project = $this->createTestProject($user);
    $this->createTestTask($project, 'todo');
    $this->createTestTask($project, 'in_progress');

    // Make HTTP request via kernel.
    $request = Request::create(
      '/api/kanban/project/' . $project->id(),
      'GET',
      ['_format' => 'json']
    );
    $request->headers->set('Accept', 'application/json');

    $response = $this->container->get('http_kernel')->handle($request);
    $this->assertEquals(200, $response->getStatusCode());

    $data = json_decode($response->getContent(), TRUE);
    $this->assertArrayHasKey('columns', $data);
    $this->assertArrayHasKey('todo', $data['columns']);
    $this->assertArrayHasKey('in_progress', $data['columns']);
    $this->assertCount(1, $data['columns']['todo']);
  }
}
```

### Pattern 2: Functional Test for Board Page Rendering
**What:** BrowserTestBase to verify the board page renders correctly with all expected elements.
**When to use:** Testing page rendering, local task tabs, library attachment, drupalSettings.
**Example:**
```php
class BoardPageTest extends BrowserTestBase {
  protected static $modules = [
    'group_ai_pm', 'system', 'user', 'field',
    'datetime', 'text', 'options', 'views', 'group',
  ];
  protected $defaultTheme = 'stark';

  public function testBoardPageRendersWithMountPoint() {
    $user = $this->drupalCreateUser([
      'access group_ai_pm dashboard',
      'view project',
      'create project',
    ]);
    $this->drupalLogin($user);

    $project = \Drupal::entityTypeManager()
      ->getStorage('project')
      ->create(['title' => 'Test', 'uid' => $user->id()]);
    $project->save();

    $this->drupalGet('/admin/content/project/' . $project->id() . '/board');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementExists('css', '#kanban-app');
    $this->assertSession()->responseContains('drupalSettings');
  }
}
```

### Pattern 3: phpcs as Runtime Assertion
**What:** Run phpcs as a ddev exec command, not as a PHPUnit test.
**When to use:** Verifying coding standards compliance across all PHP files.
**Example:**
```bash
ddev exec vendor/bin/phpcs \
  --standard=Drupal,DrupalPractice \
  --extensions=php,module,install \
  modules/custom/group_ai_pm/tests/
```

### Anti-Patterns to Avoid
- **Using BrowserTestBase for REST endpoint testing:** 10-100x slower than Kernel tests. REST endpoints return JSON, not HTML -- no browser simulation needed.
- **Testing phpcs within PHPUnit:** Mixes concerns. phpcs is a static analysis tool, not a behavior test.
- **Skipping `installEntitySchema()` for entity types:** The #1 cause of "Table not found" errors in Kernel tests.
- **Missing `@group group_ai_pm` annotation:** Tests will not be discovered by the test runner.
- **Using `$defaultTheme = 'starterkit_theme'`:** The testing skill's CRITICAL NEVER -- starterkit_theme is not available in test environments.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| HTTP request simulation in tests | Custom curl/file_get_contents | `$this->container->get('http_kernel')->handle(Request::create(...))` | Full routing pipeline, proper service container, transaction support |
| Test user creation in Kernel | Manual user entity creation | `UserCreationTrait::createUser()` | Handles role creation, permission assignment, all boilerplate |
| JSON response assertion | Manual json_decode + assertEquals per key | `assertArrayHasKey()` + structured assertions | Clearer failure messages, no false negatives from key ordering |
| phpcs execution | Custom file-reading/parsing | `vendor/bin/phpcs --standard=Drupal,DrupalPractice` | Official tool, maintained by Drupal community, covers all edge cases |

**Key insight:** Drupal's test infrastructure handles the hard parts (database setup, service container, user permissions, routing). The test code itself should be straightforward entity creation + HTTP request + assertion chains.

## Common Pitfalls

### Pitfall 1: Missing Modules in Kernel Test $modules Array
**What goes wrong:** "Service not found" or "Table not found" errors in Kernel tests.
**Why it happens:** Kernel tests only load explicitly listed modules. The `group_ai_pm` module depends on `datetime`, `text`, `options`, `views`, and `group`. Missing any of these causes cryptic failures.
**How to avoid:** Use the exact same `$modules` array as the existing tests: `['group_ai_pm', 'system', 'user', 'field', 'datetime', 'text', 'options', 'views', 'group']`.
**Warning signs:** "The 'datetime' field type does not exist" or "Unknown column 'status' in field list."

### Pitfall 2: Entity Upcasting Not Available in HttpKernel Kernel Tests
**What goes wrong:** Controller receives a raw string ID instead of an entity object when using `$this->container->get('http_kernel')->handle()`.
**Why it happens:** Entity parameter upcasting (via ParamConverterManager) may not be fully initialized in Kernel tests depending on how modules are bootstrapped.
**How to avoid:** Ensure `'system'` module is in `$modules` array and that `$this->installConfig(['system'])` is called. If upcasting still fails, pass the entity ID and test the controller's ability to handle both cases, or install the full routing system.
**Warning signs:** Controller type-hint errors (expected ProjectInterface, got string).

### Pitfall 3: CSRF Validation in Kernel Tests
**What goes wrong:** PATCH/POST/DELETE requests return 403 due to missing CSRF token in Kernel tests.
**Why it happens:** Routes with `_csrf_request_header_token: 'TRUE'` validate the X-CSRF-Token header against the session token. In Kernel tests, there is no session by default.
**How to avoid:** For Kernel tests focused on response shape/access control, consider testing the controller methods directly (unit-style) OR generate a valid session token via `\Drupal::csrfToken()->get('rest')`. Alternatively, test GET endpoints in Kernel tests and test mutation endpoints in Functional tests where session management is automatic.
**Warning signs:** All PATCH/POST tests return 403 even with correct permissions.

### Pitfall 4: Kernel Test Transactions and Entity State
**What goes wrong:** Entity state from one test method leaks into another, or entities created in setUp() are not visible in HttpKernel requests.
**Why it happens:** Kernel tests wrap each test method in a database transaction that is rolled back after the test. HttpKernel requests may use a separate database connection.
**How to avoid:** Create all test entities within the test method, not in setUp(). Or use `$this->container->get('database')` to verify entities exist before making HTTP requests.
**Warning signs:** Entities created in setUp() not found by controllers, or "Entity not found" errors in otherwise correct tests.

### Pitfall 5: Functional Tests Extremely Slow
**What goes wrong:** Each BrowserTestBase test class installs a full Drupal site, taking 30-60 seconds per class even before any test methods run.
**Why it happens:** BrowserTestBase does a full `drupal_install()` per test class with all listed modules.
**How to avoid:** Minimize the number of Functional test classes. Put all board page assertions into a single test class with multiple test methods. Use Kernel tests for everything that does not need browser simulation.
**Warning signs:** PHPUnit runtime exceeding 5 minutes for a handful of tests.

### Pitfall 6: Eval Pipeline -- Both Variants Failing Same Tests
**What goes wrong:** With and without variants score identically, producing 0% delta.
**Why it happens:** Assertions target standard Drupal patterns that Haiku already knows (e.g., "test file exists", "extends KernelTestBase"). These are not differentiating -- both variants get them right.
**How to avoid:** Target assertions at non-obvious drupal-testing skill patterns: cron testing via service (not direct function call), `$defaultTheme = 'stark'` (not starterkit_theme or missing), field module dependencies in $modules (datetime for datetime fields, options for list_string), `installConfig()` for module config. These are the WRONG/RIGHT callouts in the skill.
**Warning signs:** Phase 17 had initial 0% delta before skill patches focused on CRITICAL NEVER and WRONG/RIGHT callouts.

## Code Examples

### Kernel REST Test -- GET Endpoint with JSON Response
```php
// Verified pattern from Drupal.org kernel HTTP request docs
public function testKanbanEndpointResponseShape() {
  $user = $this->createUser(['access group_ai_pm dashboard', 'view project', 'view task']);
  $this->container->get('current_user')->setAccount($user);

  $project = $this->entityTypeManager->getStorage('project')->create([
    'title' => 'Test Project',
    'uid' => $user->id(),
  ]);
  $project->save();

  $task = $this->entityTypeManager->getStorage('task')->create([
    'title' => 'Test Task',
    'project' => $project->id(),
    'status' => 'todo',
    'priority' => 'high',
    'uid' => $user->id(),
  ]);
  $task->save();

  $request = Request::create('/api/kanban/project/' . $project->id(), 'GET', ['_format' => 'json']);
  $request->headers->set('Accept', 'application/json');
  $response = $this->container->get('http_kernel')->handle($request);

  $this->assertEquals(200, $response->getStatusCode());
  $data = json_decode($response->getContent(), TRUE);

  // Verify response shape.
  $this->assertArrayHasKey('projectId', $data);
  $this->assertArrayHasKey('columns', $data);
  $this->assertCount(1, $data['columns']['todo']);

  // Verify task shape.
  $task_data = $data['columns']['todo'][0];
  $this->assertArrayHasKey('id', $task_data);
  $this->assertArrayHasKey('title', $task_data);
  $this->assertArrayHasKey('status', $task_data);
  $this->assertArrayHasKey('priority', $task_data);
  $this->assertEquals('Test Task', $task_data['title']);
  $this->assertEquals('high', $task_data['priority']);
}
```

### Kernel REST Test -- Access Denied Without Permission
```php
public function testKanbanEndpointDeniesUnauthorized() {
  $user = $this->createUser([]);  // No permissions.
  $this->container->get('current_user')->setAccount($user);

  $project = $this->entityTypeManager->getStorage('project')->create([
    'title' => 'Test Project',
    'uid' => 1,
  ]);
  $project->save();

  $request = Request::create('/api/kanban/project/' . $project->id(), 'GET', ['_format' => 'json']);
  $response = $this->container->get('http_kernel')->handle($request);

  $this->assertEquals(403, $response->getStatusCode());
}
```

### Functional Test -- Board Page with Local Task Tab
```php
public function testBoardPageHasLocalTaskTab() {
  $user = $this->drupalCreateUser([
    'access group_ai_pm dashboard',
    'view project',
    'create project',
  ]);
  $this->drupalLogin($user);

  $project = \Drupal::entityTypeManager()
    ->getStorage('project')
    ->create(['title' => 'Board Test Project', 'uid' => $user->id()]);
  $project->save();

  // Visit the project canonical page.
  $this->drupalGet('/admin/content/project/' . $project->id());
  $this->assertSession()->statusCodeEquals(200);
  // Verify the "Board" local task tab exists.
  $this->assertSession()->linkExists('Board');

  // Visit the board page directly.
  $this->drupalGet('/admin/content/project/' . $project->id() . '/board');
  $this->assertSession()->statusCodeEquals(200);
  // Verify the Vue mount point exists.
  $this->assertSession()->elementExists('css', '#kanban-app');
}
```

### phpcs Runtime Assertion
```bash
# Run in ddev context
ddev exec vendor/bin/phpcs \
  --standard=Drupal,DrupalPractice \
  --extensions=php,module,install \
  --report=summary \
  modules/custom/group_ai_pm/
# Expected: no errors
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| REST module ResourceTestBase | Custom controller + HttpKernel in Kernel tests | Drupal 9+ | No dependency on REST module for custom controllers |
| BrowserTestBase for all tests | KernelTestBase with HttpKernel for API tests | Always recommended | 10-100x speedup for non-browser tests |
| @group annotation only | @group + #[Group] attribute (D11) | PHPUnit 10 / D11 | @group annotation still works in both, use it for compatibility |
| Manual phpcs runs | CI/runtime assertions | Standard practice | Catches regressions automatically |

**Deprecated/outdated:**
- `$defaultTheme = 'starterkit_theme'`: Was never correct for tests. Use 'stark' or 'claro'.
- `@coversDefaultClass`: Still works but PHPUnit 10 prefers `#[CoversClass]`. The annotation is fine for D10 compatibility.
- Direct `\Drupal::` calls in test classes: DrupalPractice will flag these in src/ files but they are acceptable in test files since tests are not services.

## Eval Pipeline Design (Phase 21 Specific)

### Skills Under Test
| Skill | Eval Role |
|-------|-----------|
| drupal-testing | PRIMARY -- all static assertions target its WRONG/RIGHT patterns |
| drupal-coding-standards | SECONDARY -- phpcs compliance as runtime assertion |
| All v4.0 skills | REGRESSION -- existing module must still function after test files added |

### Eval Prompt Design Principles
1. Ask Haiku to write NEW tests for the v4.0 REST API and board page features
2. Provide the full module context (all accumulated files from Phases 18-20)
3. Do NOT ask for tests that duplicate existing v3.0 coverage (entity CRUD, access control, cron/queue, settings form, dashboard, entity forms)
4. Focus on: REST endpoint response shapes, REST access control, board page rendering, drupalSettings population, local task tab presence

### Static Assertion Targets (drupal-testing skill patterns)
| Skill Pattern | Assertion |
|---------------|-----------|
| Correct base class selection | REST tests use KernelTestBase, board tests use BrowserTestBase |
| @group annotation | Every test class has `@group group_ai_pm` |
| $modules completeness | Includes datetime, text, options for entity field dependencies |
| installEntitySchema() | Calls for project, task, user entities |
| $defaultTheme | BrowserTestBase classes set `'stark'` (CRITICAL NEVER: not 'starterkit_theme') |
| parent::setUp() first | setUp() calls parent::setUp() as first statement |
| Namespace/directory match | tests/src/Kernel/ for kernel, tests/src/Functional/ for functional |
| Cron via service | If cron tested, uses `$this->container->get('cron')->run()` not direct function call |
| installConfig() | Calls `$this->installConfig(['group_ai_pm'])` if module config needed |

### Runtime Assertion Targets
| ID | Assertion | Command Pattern |
|----|-----------|-----------------|
| rt-1 | Module re-enables cleanly | `ddev drush cr && ddev drush pm:list --status=enabled \| grep group_ai_pm` |
| rt-2 | Test files in correct directory structure | `glob tests/src/{Kernel,Functional}/*Test.php` |
| rt-3 | No PHP syntax errors | `php -l` on all test files |
| rt-4 | PHPUnit discovers tests | `vendor/bin/phpunit --list-tests modules/custom/group_ai_pm/tests/` |
| rt-5 | Kernel tests pass | `vendor/bin/phpunit --group group_ai_pm modules/custom/group_ai_pm/tests/src/Kernel/` |
| rt-6 | Functional tests pass | `vendor/bin/phpunit --group group_ai_pm modules/custom/group_ai_pm/tests/src/Functional/` |
| rt-7 | phpcs passes on test files | `vendor/bin/phpcs --standard=Drupal,DrupalPractice tests/` |

### Browser Assertion (Minimal)
| ID | Assertion |
|----|-----------|
| br-1 | Board page still loads and renders #kanban-app after test files added (regression check) |

### Delta Report Scope
Phase 21 completes v4.0. The final delta report must include:
- Phase 21 (Testing + Final Eval): testing skill delta
- v4.0 aggregate: weighted average across Phases 18-21
- Cross-milestone comparison: v3.0 aggregate (73.3% -> 90.0% = +16.7%) vs v4.0 aggregate

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | PHPUnit 9 (Drupal 10) |
| Config file | `web/core/phpunit.xml` (copy from phpunit.xml.dist, configure SIMPLETEST_DB) |
| Quick run command | `ddev exec bash -c 'cd /var/www/html/web && SIMPLETEST_DB="sqlite://localhost//var/www/html/web/sites/default/files/.ht.sqlite" ../vendor/bin/phpunit --group group_ai_pm modules/custom/group_ai_pm/tests/src/Kernel/ 2>&1 \| tail -10'` |
| Full suite command | `ddev exec bash -c 'cd /var/www/html/web && SIMPLETEST_DB="sqlite://localhost//var/www/html/web/sites/default/files/.ht.sqlite" SIMPLETEST_BASE_URL="http://localhost" ../vendor/bin/phpunit --group group_ai_pm modules/custom/group_ai_pm/tests/ 2>&1'` |

### Phase Requirements -> Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| TEST-01 | REST endpoint response shapes + access control | Kernel | `phpunit modules/custom/group_ai_pm/tests/src/Kernel/RestApiTest.php` | Wave 0 (NEW) |
| TEST-02 | Board page rendering, local task tab, drupalSettings | Functional | `phpunit modules/custom/group_ai_pm/tests/src/Functional/BoardPageTest.php` | Wave 0 (NEW) |
| TEST-03 | phpcs compliance | CLI | `phpcs --standard=Drupal,DrupalPractice modules/custom/group_ai_pm/` | N/A (runtime cmd) |
| EVAL-01 | Static assertions for testing patterns | eval-grader | Grader reads code against expectations | Wave 0 (evals.json) |
| EVAL-02 | Runtime assertions (drush, phpunit) | eval-grader | Grader runs ddev commands | Wave 0 (runtime-assertions.json) |
| EVAL-03 | Browser assertions (board renders) | eval-browser | Agent-browser navigates board page | Wave 0 (browser-assertions in evals.json) |
| EVAL-04 | Delta report | orchestrator | Compare with/without results | Post-eval computation |

### Sampling Rate
- **Per task commit:** Quick kernel test run (< 30 seconds)
- **Per wave merge:** Full suite (kernel + functional, 1-3 minutes)
- **Phase gate:** Full suite green + phpcs green before /gsd:verify-work

### Wave 0 Gaps
- [ ] `eval/v4/phase-21-evals.json` -- static expectations (14-16 assertions targeting testing skill)
- [ ] `eval/v4/phase-21-runtime-assertions.json` -- runtime assertions (7-8 assertions including PHPUnit execution)
- [ ] PHPUnit + core-dev installed in ddev instances: `ddev composer require --dev drupal/core-dev phpunit/phpunit`
- [ ] phpunit.xml configured with SIMPLETEST_DB and SIMPLETEST_BASE_URL
- [ ] phpcs + drupal/coder installed: `ddev composer require --dev drupal/coder squizlabs/php_codesniffer`

## Open Questions

1. **HttpKernel vs BrowserTestBase for REST access control**
   - What we know: HttpKernel in Kernel tests can test GET endpoints. CSRF-protected PATCH/POST/DELETE routes are harder in Kernel tests because session/token management is manual.
   - What's unclear: Whether Haiku will naturally choose Kernel tests for REST endpoints or default to BrowserTestBase for everything.
   - Recommendation: Design the eval prompt to explicitly mention REST endpoints can be tested in Kernel tests. The static assertions should check for KernelTestBase usage on API test classes.

2. **Existing test coverage overlap**
   - What we know: 6 existing test files cover entity CRUD, access control, cron/queue, settings form, dashboard, and entity forms. Phase 21 should NOT duplicate these.
   - What's unclear: Whether Haiku will write only NEW tests or also rewrite existing tests (which would break the accumulated module).
   - Recommendation: The eval prompt should explicitly say "write tests for the v4.0 features added in Phases 18-20" and list the specific controllers/routes to test. Do NOT say "write comprehensive tests for the module" which invites duplication.

3. **Kernel test entity upcasting reliability**
   - What we know: Some Drupal kernel test environments do not fully initialize ParamConverterManager, causing entity upcasting to fail.
   - What's unclear: Whether the `group_ai_pm` API routes with `options.parameters.project.type: entity:project` will work in Kernel HTTP requests.
   - Recommendation: If kernel HTTP tests fail on upcasting, the fallback is to test controllers directly via method calls rather than HTTP requests. This is acceptable but less realistic. Flag this as a potential runtime assertion failure that is NOT skill-related.

## Sources

### Primary (HIGH confidence)
- Drupal.org -- [Making HTTP requests in Kernel tests](https://www.drupal.org/docs/develop/automated-testing/phpunit-in-drupal/making-http-requests-programmatically-in-kernel-tests) -- programmatic HTTP in KernelTestBase
- drupal-testing SKILL.md -- base class selection, setUp patterns, $modules requirements, @group annotation
- drupal-coding-standards SKILL.md -- phpcs compliance patterns
- Existing test files in `modules/group_ai_pm/tests/` -- proven module dependency lists, setUp patterns

### Secondary (MEDIUM confidence)
- Phase 17 eval results -- testing skill produced +7.1% delta after skill patches (CRITICAL NEVER for $defaultTheme, cron-via-service)
- Phase 17 evals.json and runtime-assertions.json -- proven assertion patterns for testing eval rounds

### Tertiary (LOW confidence)
- None -- all findings verified against primary sources

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - PHPUnit/KernelTestBase/BrowserTestBase are Drupal's official test stack, extensively documented
- Architecture: HIGH - HttpKernel pattern verified in official Drupal docs, existing test files provide proven $modules lists
- Pitfalls: HIGH - Phase 17 empirically demonstrated these pitfalls (kernel test failures, 0% initial delta, skill patch effectiveness)

**Research date:** 2026-03-08
**Valid until:** 2026-04-08 (30 days - stable Drupal testing infrastructure)

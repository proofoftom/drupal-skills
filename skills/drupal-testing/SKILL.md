---
name: drupal-testing
description: |
  Write PHPUnit tests for Drupal modules using the correct test type and base class.
  Use when asked to write tests, create test coverage, add unit/kernel/functional tests,
  or verify Drupal module behavior with automated testing. Guides choosing the LOWEST
  sufficient test level (Unit < Kernel < Functional < FunctionalJavascript) and covers
  required setUp patterns: $modules, installEntitySchema(), installConfig(), @group.
  Do NOT use for manual testing or debugging (this is for automated PHPUnit tests).
---

# Drupal Testing

All four Drupal test types run on PHPUnit. D10 uses PHPUnit 9; D11 uses PHPUnit 10. Base classes and assertion APIs are identical across both versions.

## What type of test do you need?

Choose the LOWEST level that covers the behavior under test. Lower = faster feedback.

**Pure class logic with mockable dependencies, no Drupal services?**
YES -> Unit test (UnitTestCase). No Drupal bootstrap. Fastest.
NO -> Keep reading.

**Needs database, services, config, or entities but NOT browser interaction?**
YES -> Kernel test (KernelTestBase). Partial bootstrap. Fast.
NO -> Keep reading.

**Needs page navigation, form submission, HTTP responses, but NOT JavaScript?**
YES -> Functional test (BrowserTestBase). Full Drupal install. Slower.
NO -> Keep reading.

**Needs JavaScript interactions, Ajax callbacks, dynamic DOM?**
YES -> FunctionalJavascript test (WebDriverTestBase). Requires ChromeDriver. Slowest.

> WRONG: Using BrowserTestBase when KernelTestBase suffices. Functional tests install a full Drupal site per test class -- 10-100x slower than Kernel tests. Only use Functional when you need browser simulation (page navigation, form submission, HTML assertions).
> RIGHT: If you only need the database, services, or config, use KernelTestBase. It boots a lightweight kernel without a full site install.

## Test directory structure

```
modules/custom/my_module/
  tests/
    src/
      Unit/                      -> extends UnitTestCase
      Kernel/                    -> extends KernelTestBase
      Functional/                -> extends BrowserTestBase
      FunctionalJavascript/      -> extends WebDriverTestBase
    modules/                     -> test-only helper modules
```

### Namespace rules

- `tests/src/Unit/CalculatorTest.php` -> `Drupal\Tests\my_module\Unit\CalculatorTest`
- `tests/src/Kernel/ImporterTest.php` -> `Drupal\Tests\my_module\Kernel\ImporterTest`
- `tests/src/Functional/PageTest.php` -> `Drupal\Tests\my_module\Functional\PageTest`

File name MUST end in `Test.php`. Test methods MUST start with `test` (or use `@test` annotation).

## Test registration (@group)

Every test class MUST have a `@group` annotation in its PHPDoc:

```php
/**
 * Tests the Calculator class methods.
 *
 * @group my_module
 */
class CalculatorTest extends UnitTestCase {
```

- D10 (PHPUnit 9): `@group` annotation required
- D11 (PHPUnit 10): `@group` annotation still works. PHP 8 attributes (`#[Group('my_module')]`) also supported but `@group` is compatible across both versions

> WRONG: Omitting the `@group` annotation. Without `@group`, the test will not be discovered by Drupal's test runner or when running `phpunit --group my_module`.
> RIGHT: Every test class gets `@group module_name` in the class PHPDoc. This is required for test discovery.

## Unit tests (UnitTestCase)

Extends `Drupal\Tests\UnitTestCase`. No Drupal bootstrap -- pure PHP testing.

Best for: utility classes, data transformations, business logic, value objects.

### Complete unit test example

```php
<?php

namespace Drupal\Tests\my_module\Unit;

use Drupal\my_module\Calculator;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Calculator class methods.
 *
 * @group my_module
 */
class CalculatorTest extends UnitTestCase {

  /**
   * @var \Drupal\my_module\Calculator
   */
  protected $calculator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->calculator = new Calculator(10, 5);
  }

  /**
   * Tests the add() method.
   */
  public function testAdd() {
    $this->assertEquals(15, $this->calculator->add());
  }

  /**
   * Tests the subtract() method.
   */
  public function testSubtract() {
    $this->assertEquals(5, $this->calculator->subtract());
  }

}
```

### Mocking dependencies

Use `$this->createMock()` for service dependencies. Prescribe behavior with `expects()`:

```php
$entity_manager = $this->createMock(EntityTypeManagerInterface::class);
$storage = $this->createMock(EntityStorageInterface::class);

$entity_manager->expects($this->any())
  ->method('getStorage')
  ->with('user')
  ->willReturn($storage);

$storage->expects($this->once())
  ->method('load')
  ->with(1)
  ->willReturn($mock_user);
```

Use `$this->getConfigFactoryStub()` for config dependencies:

```php
$config_factory = $this->getConfigFactoryStub([
  'my_module.settings' => ['key' => 'value'],
]);
```

Common assertions: `assertEquals`, `assertTrue`, `assertFalse`, `assertNull`, `assertCount`, `assertInstanceOf`, `assertEmpty`, `assertNotEmpty`.

## Kernel tests (KernelTestBase)

Extends `Drupal\KernelTests\KernelTestBase`. Boots Drupal kernel with specified modules. Has database access.

Best for: service integration, database operations, entity CRUD, plugin managers, config handling.

### Complete Kernel test example

```php
<?php

namespace Drupal\Tests\my_module\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the TeamCleaner QueueWorker plugin.
 *
 * @group my_module
 */
class TeamCleanerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['my_module', 'system', 'user'];

  /**
   * Tests the processItem() method.
   */
  public function testProcessItem() {
    $this->installSchema('my_module', 'teams');
    $database = $this->container->get('database');

    // Insert a test record.
    $id = $database->insert('teams')
      ->fields(['name' => 'Test team'])
      ->execute();

    $records = $database->query("SELECT id FROM {teams} WHERE id = :id", [':id' => $id])->fetchAll();
    $this->assertNotEmpty($records);

    // Process the item (deletes the team).
    $worker = new TeamCleaner([], NULL, NULL, $database);
    $data = new \stdClass();
    $data->id = $id;
    $worker->processItem($data);

    $records = $database->query("SELECT id FROM {teams} WHERE id = :id", [':id' => $id])->fetchAll();
    $this->assertEmpty($records);
  }

}
```

### Kernel setUp() pattern

```php
protected function setUp(): void {
  parent::setUp();  // ALWAYS call parent first

  // Install schemas for custom tables.
  $this->installSchema('my_module', 'my_table');

  // Install entity schemas (creates all tables for an entity type).
  $this->installEntitySchema('node');
  $this->installEntitySchema('user');

  // Install default config shipped with module.
  $this->installConfig(['my_module']);
}
```

### Accessing services in Kernel tests

```php
$service = $this->container->get('my_module.my_service');
$entity_type_manager = $this->container->get('entity_type.manager');
```

> WRONG: Missing modules in the `$modules` array. Kernel tests only load listed modules. Missing modules cause cryptic "Service not found" or "Table not found" errors. Include ALL dependency modules your code needs.
> RIGHT: List every module your test depends on: `protected static $modules = ['my_module', 'system', 'user', 'node', 'file'];`. When you get "service not found" errors, check which module provides it and add that module.

> WRONG: Forgetting `installSchema()` for custom tables. Unlike full Drupal installs, Kernel tests do NOT run hook_schema() automatically. Custom tables will not exist, causing "Table not found" errors.
> RIGHT: Call `$this->installSchema('module', 'table_name')` in setUp() or the test method for every custom table. Use `$this->installEntitySchema('entity_type')` for entity tables. Use `$this->installConfig(['module'])` for default config.

## Functional tests (BrowserTestBase)

Extends `Drupal\Tests\BrowserTestBase`. Installs a full Drupal site with a simulated browser (Mink). No JavaScript support.

Best for: page rendering, form submission, access control, HTTP responses, user flows.

### Complete Functional test example

```php
<?php

namespace Drupal\Tests\my_module\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Hello World page and configuration form.
 *
 * @group my_module
 */
class HelloWorldPageTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['my_module', 'user', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the main page displays correct content.
   */
  public function testPage() {
    $this->drupalGet('/hello');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Hello');
  }

  /**
   * Tests the configuration form requires permission.
   */
  public function testConfigForm() {
    // Anonymous user gets 403.
    $this->drupalGet('/admin/config/my-settings');
    $this->assertSession()->statusCodeEquals(403);

    // Authenticated user with permission gets 200.
    $account = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/config/my-settings');
    $this->assertSession()->statusCodeEquals(200);

    // Submit the form.
    $edit = ['salutation' => 'Custom greeting'];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved');
  }

}
```

### Key BrowserTestBase methods

| Method | Purpose |
|--------|---------|
| `$this->drupalGet('/path')` | Navigate to a page |
| `$this->drupalLogin($user)` | Log in as a user |
| `$this->drupalLogout()` | Log out |
| `$this->drupalCreateUser(['perm'])` | Create user with permissions |
| `$this->submitForm($edit, 'Button')` | Fill and submit a form |
| `$this->assertSession()` | Returns WebAssert for chaining |

### Common assertSession() assertions

| Assertion | Purpose |
|-----------|---------|
| `->statusCodeEquals(200)` | HTTP response code |
| `->pageTextContains('text')` | Text appears anywhere on page |
| `->pageTextNotContains('text')` | Text does NOT appear |
| `->linkExists('Link text')` | Link with text exists |
| `->fieldExists('field_name')` | Form field exists |
| `->elementExists('css', '#my-id')` | Element matches CSS selector |
| `->elementTextContains('css', 'h1', 'Title')` | Element contains text |
| `->addressEquals('/expected/path')` | Current URL matches |

> WRONG: Missing `$defaultTheme` property. BrowserTestBase requires `protected $defaultTheme = 'stark'` (or another installed theme). Without it, tests fail with an unclear error about missing theme.
> RIGHT: Always set `protected $defaultTheme = 'stark';` on BrowserTestBase subclasses. Stark is the minimal theme with no extra markup. Use `'claro'` if testing admin UI specifically.

## FunctionalJavascript tests (WebDriverTestBase)

Extends `Drupal\FunctionalJavascriptTests\WebDriverTestBase`. Requires ChromeDriver or Selenium.

Best for: Ajax callbacks, JavaScript behaviors, dynamic DOM updates. Same page navigation as BrowserTestBase.

```php
<?php

namespace Drupal\Tests\my_module\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests JavaScript timer on the Hello World page.
 *
 * @group my_module
 */
class TimeTest extends WebDriverTestBase {

  protected static $modules = ['my_module'];
  protected $defaultTheme = 'stark';

  public function testTimer() {
    $this->drupalGet('/hello');
    // Wait for JavaScript to insert the time element.
    $element = $this->assertSession()->waitForElement('css', '.time-widget');
    $this->assertNotNull($element);
  }

}
```

Additional methods: `$this->assertSession()->waitForElement('css', '.selector')`, `waitForText('text')`, `waitForElementVisible()`. Use these instead of `sleep()` to wait for dynamic content.

## setUp() patterns

### Always call parent first

```php
protected function setUp(): void {
  parent::setUp();  // CRITICAL: must be first line
  // Your setup code after...
}
```

> WRONG: Forgetting to call `parent::setUp()` or calling it after your setup code. The parent setUp() bootstraps the test environment. Without it (or with it called too late), tests fail with cryptic errors about missing services or database connections.
> RIGHT: `parent::setUp()` is always the FIRST line in setUp(). Install schemas, create entities, and set up test data AFTER the parent call.

### Kernel setUp()

```php
protected function setUp(): void {
  parent::setUp();
  $this->installSchema('my_module', 'my_custom_table');
  $this->installEntitySchema('node');
  $this->installEntitySchema('user');
  $this->installConfig(['my_module']);
}
```

### Functional setUp()

```php
protected function setUp(): void {
  parent::setUp();
  $this->adminUser = $this->drupalCreateUser([
    'administer site configuration',
    'access content',
  ]);
}
```

## Data providers

Use `@dataProvider` for parameterized tests to avoid repetition:

```php
/**
 * Tests arithmetic operations.
 *
 * @dataProvider arithmeticProvider
 */
public function testArithmetic($a, $b, $expected) {
  $calculator = new Calculator($a, $b);
  $this->assertEquals($expected, $calculator->add());
}

/**
 * Data provider for testArithmetic().
 */
public function arithmeticProvider() {
  return [
    'positive numbers' => [10, 5, 15],
    'negative numbers' => [-3, -7, -10],
    'mixed numbers' => [10, -5, 5],
    'zeros' => [0, 0, 0],
  ];
}
```

D10 uses `@dataProvider` annotation. D11 also supports `#[DataProvider('methodName')]` PHP attribute but the annotation works in both versions.

## Running tests

```bash
# Run all tests for a module (Drupal test runner)
php core/scripts/run-tests.sh --module my_module

# Run all tests for a module (PHPUnit directly)
vendor/bin/phpunit modules/custom/my_module/tests/

# Run by group
vendor/bin/phpunit --group my_module

# Run a specific test file
vendor/bin/phpunit modules/custom/my_module/tests/src/Unit/CalculatorTest.php

# Run a single test method
vendor/bin/phpunit --filter testAdd modules/custom/my_module/tests/src/Unit/CalculatorTest.php
```

For Kernel, Functional, and FunctionalJavascript tests, ensure `phpunit.xml` (in the `core/` directory) has:
- `SIMPLETEST_DB`: database connection string
- `SIMPLETEST_BASE_URL`: site URL (for Functional tests)
- `MINK_DRIVER_ARGS_WEBDRIVER`: Selenium endpoint (for FunctionalJavascript tests)

## D10/D11 differences

| Feature | D10 (PHPUnit 9) | D11 (PHPUnit 10) |
|---------|-----------------|-------------------|
| `@group` annotation | Required | Works (also supports `#[Group]` attribute) |
| `@dataProvider` | Required | Works (also supports `#[DataProvider]` attribute) |
| `@coversDefaultClass` | Works | Works (also supports `#[CoversClass]` attribute) |
| Base classes | Same | Same (UnitTestCase, KernelTestBase, BrowserTestBase, WebDriverTestBase) |
| Assertion methods | Same | Same |

The `@` annotation syntax is safe across both versions. PHP 8 attributes are optional in D11.

## Cross-references

See also: **drupal-forms-api** (if installed) for testing forms with `submitForm()` and form validation assertions. If not available, use `$this->submitForm(['field_name' => 'value'], 'Submit button text')` in BrowserTestBase to fill and submit forms.

See also: **drupal-entities-fields** (if installed) for testing entity creation with `installEntitySchema()` in Kernel tests. If not available, call `$this->installEntitySchema('entity_type')` in setUp() for each entity type your test needs.

See also: **drupal-database-api** (if installed) for testing custom table operations with `installSchema()` in Kernel tests. If not available, call `$this->installSchema('module', 'table')` in setUp() for each custom table.

See also: **drupal-routing-controllers** (if installed) for testing route access and page responses with `drupalGet()`. If not available, use `$this->drupalGet('/path')` and `$this->assertSession()->statusCodeEquals(200)` in BrowserTestBase.

See also: **drupal-caching** (if installed) for testing cache invalidation behavior. If not available, verify cache tags with `$this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'tag')` in Functional tests.

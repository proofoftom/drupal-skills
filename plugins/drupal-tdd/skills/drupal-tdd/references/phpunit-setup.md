# PHPUnit Setup for a Fresh Drupal Project

If the project was scaffolded with `composer create-project drupal/recommended-project` and you ran `composer require --dev drupal/core-dev`, you have `vendor/bin/phpunit` but no project-level config. PHPUnit won't know how to bootstrap Drupal.

## Minimum phpunit.xml.dist

At the project root (one level above `web/`):

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="web/core/tests/bootstrap.php" colors="true">
  <php>
    <env name="SIMPLETEST_BASE_URL" value="http://localhost:8000"/>
    <env name="SIMPLETEST_DB" value="sqlite://localhost//dev/shm/test.sqlite"/>
    <ini name="error_reporting" value="32767"/>
    <ini name="memory_limit" value="-1"/>
  </php>
  <testsuites>
    <testsuite name="Custom modules">
      <directory suffix="Test.php">./web/modules/custom</directory>
    </testsuite>
  </testsuites>
</phpunit>
```

What each line does:

- `bootstrap="web/core/tests/bootstrap.php"` — Drupal's PHPUnit bootstrap. Without this you get `Class "Drupal\Tests\BrowserTestBase" not found`.
- `SIMPLETEST_BASE_URL` — URL where your running site answers HTTP. Must match whatever web server you have (`php -S`, DDEV, Lando, etc.) including the port.
- `SIMPLETEST_DB` — throwaway database for tests. SQLite in `/dev/shm` is fast (tmpfs) and ephemeral.
- `error_reporting=32767` (`E_ALL`) — surface every deprecation so your test log flags PHP 8.x issues early.
- `memory_limit=-1` — some Drupal tests are memory-hungry; don't let PHP's default 128M OOM them.
- `testsuite` directory — restricts `vendor/bin/phpunit` with no args to your custom modules.

## DDEV / Lando variations

### DDEV

The site runs at `https://<project>.ddev.site`. Override in `phpunit.xml.dist`:

```xml
<env name="SIMPLETEST_BASE_URL" value="https://mysite.ddev.site"/>
<env name="SIMPLETEST_DB" value="mysql://db:db@db/db"/>
<env name="BROWSERTEST_OUTPUT_DIRECTORY" value="/tmp"/>
```

Tests run from inside the web container: `ddev exec vendor/bin/phpunit web/modules/custom/my_module`.

### MySQL instead of SQLite

If SQLite throws version errors (Drupal 11 needs SQLite 3.26+):

```xml
<env name="SIMPLETEST_DB" value="mysql://user:pass@127.0.0.1/dbname#test"/>
```

The `#test` suffix is a prefix Drupal uses for test tables so they don't collide with site tables.

## SQLite version errors

If you see `SQLite 3.26 or newer is required`, either update SQLite on your host, install PHP's PDO-SQLite with a newer build, or switch to MySQL per above.

## Running tests

```bash
# Everything PHPUnit can find based on testsuites
vendor/bin/phpunit

# Narrow to a module
vendor/bin/phpunit web/modules/custom/atdc

# Narrow to a file
vendor/bin/phpunit web/modules/custom/atdc/tests/src/Functional/BlogPageTest.php

# Narrow to a single method
vendor/bin/phpunit --filter testBlogPage

# Stop on first failure for a tight red loop
vendor/bin/phpunit --stop-on-failure

# Human-readable output
vendor/bin/phpunit --testdox
```

## BrowserTestBase gotcha: theme

Every `BrowserTestBase` subclass needs a `$defaultTheme` property or the test bails on "no theme set":

```php
protected $defaultTheme = 'stark';
```

Use `stark` — the minimal marker-free theme — for functional tests unless you specifically want to test theme output. Using the site's actual theme slows tests and introduces noise.

## When tests hang or time out

- **"Connection refused" on `drupalGet()`** — the web server referenced by `SIMPLETEST_BASE_URL` isn't running. Start it (`php -S localhost:8000 -t web` in one terminal; run phpunit in another).
- **Tests randomly slow on SQLite** — move the DB to `/dev/shm` (tmpfs) to avoid disk I/O per test.
- **`BrowserTestBase` tests taking 30+ seconds each** — that's normal. Each one installs a fresh Drupal from scratch. If you have many of them, consider whether some could move to Kernel.

## .gitignore

Tests generate artifacts under `sites/simpletest/` and sometimes `sites/default/files/simpletest/`. Ignore them:

```gitignore
sites/simpletest/
sites/default/files/simpletest/
```

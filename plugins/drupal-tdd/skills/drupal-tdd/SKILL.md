---
name: drupal-tdd
description: |
  Test-driven development for Drupal modules: red/green/refactor cadence, outside-in
  test ordering, test-first feature growth. Use when the user says "TDD this",
  "write the test first", "red-green-refactor", or wants a failing test next rather
  than code next. Pairs with drupal-testing (which covers base classes and boilerplate)
  -- this skill answers "in what order do I grow the code?".
---

# Drupal TDD

Test-driven development in Drupal. You write a failing test, watch it fail for the reason you expect, write the simplest code that makes it pass, then refactor with the tests as a safety net.

This skill covers the *cadence* and *discipline*. For base-class reference (UnitTestCase vs KernelTestBase vs BrowserTestBase, assertion API, setUp patterns), use the companion **drupal-testing** skill.

Outside-in approach: start with a Functional test for user-visible behavior, drop to Kernel tests when the thing you need to assert is awkward to express through a browser, and reach for Unit tests only at the edges.

## What this skill is non-negotiable about

Only two things:

1. **Test-first, not test-after.** The test exists, and has been watched to fail for the right reason, *before* the implementation code is written. That's the thing that actually shapes the design.
2. **Outside-in ordering.** Start at the level the user sees (Functional), drop to Kernel/Unit only when the assertion is awkward at the higher level.

Everything else below — per-assertion commits, batched cycles, the exact shape of "simplest green" — is a *spectrum*, not a compliance checklist. See "Cycle granularity" below before using this skill to audit a plan or PR.

## The red-green-refactor cadence

The classic rhythm:

1. **Red** — Write a test that *fails for the right reason*. If it passes on the first run, you haven't tested anything new. If it fails with a setup error (missing service, missing table, missing module), fix the setup until you get a genuine assertion failure — that's the real red.
2. **Green** — Write the *simplest* code that makes the test pass. Hard-coded values are fine. Empty arrays are fine. The goal is the green bar, not elegance.
3. **Refactor** — With tests green, clean up. Extract classes, inject dependencies, rename. Tests stay green throughout — if one goes red, you broke something; revert and try a smaller step.

> This rhythm only works if you trust the tests. After you get a test to pass, **break it on purpose** (change the expected value, delete the new code) and confirm it goes red. Then put it back. Confidence comes from tests that fail when they should.

## Cycle granularity: strict vs. batched is a spectrum

"One red→green→refactor cycle at a time with a commit between each" is the *tightest* expression of TDD and produces the cleanest git history. It's not the only legitimate form.

| Style                        | What it looks like                                                                 | When it fits                                                                                                   |
|------------------------------|------------------------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------|
| **Strict per-cycle**         | One assertion → minimal code → commit → repeat. 10–15 tiny commits per feature.     | Unfamiliar problem, risky refactor, paired programming, learning a new codebase — whenever you want maximum safety and fine-grained revert points. |
| **Batched cycles**           | Write several related failing tests as a cohesive set, then implement until all green. Each set is one commit (or a small handful). | Small, well-understood features where the assertions clearly form one spec. "TDD planning ahead." |
| **Task-level test-first**    | Write the tests for a task, watch them fail, implement the task, commit once.       | Feature slices in a larger plan (e.g. phase/task workflows) where the plan is the unit of review.              |
| **Test-after**               | Implement first, write tests to cover existing code.                                | **Anti-pattern.** Not TDD. Tests won't shape the design and tend to mirror the code rather than the contract. |

The first three are all TDD. The line is drawn at **test-after**, not at commit granularity.

If you're auditing a plan or PR with this skill: the substantive checks are (a) tests exist *before* the implementation is written, (b) tests were run and observed red for the right reason, (c) outside-in ordering. Pushing a plan from "batched" to "strict per-cycle" multiplies commits without changing shipped code — only do it when the team wants the finer git history for its own sake, or when the feature is risky enough to benefit from tighter revert points.

> Per-assertion commits are a practice, not a principle. Don't mandate a structural re-plan for a feature that's already test-first and outside-in — that's rearranging chairs around the wins.

## Outside-in test ordering

Start where the user sees the feature, drop to lower levels only when the assertion is awkward at the higher level.

```
Functional  →  Kernel  →  Unit
(browser)      (services)   (pure PHP + mocks)
```

- **Functional (BrowserTestBase)** — Start here. Assert the page exists, the content renders, the right nodes show up, unauthorized users get 403. Your user-facing contract lives here.
- **Kernel (KernelTestBase)** — Drop down when you need to assert something a browser makes clunky: ordering of items in a collection, exact return shape of a service method, database-level behavior. Faster than Functional but still has the container and DB.
- **Unit (UnitTestCase)** — Reach for this only for pure logic with no Drupal dependencies, or when you explicitly want to test interaction with mocked collaborators. If the unit test is mostly `$mock->willReturn(...)` setup, you're testing the mock, not the code — prefer a Kernel test instead.

> WHY outside-in: a Functional test exercises the real stack — routing, permissions, controller DI, theme, render pipeline. If it passes, the feature *works*. Lower-level tests narrow in on behavior that's hard to pin down from outside, but they can all pass while the integrated feature is broken. Functional tests catch wiring mistakes the inner tests can't.

## Write the simplest thing that passes

When a test goes red, your job is to get it green with minimum code — not the final design. You'll refactor next.

**Example — "the /blog page returns 200."** The simplest green: a route that points at an empty controller returning `[]`. Not a repository. Not a query. An empty array.

**Example — "posts are visible on /blog."** Now empty `[]` fails. The simplest green: load all nodes and render their titles. Not filtering, not ordering, not a repository. Just the loop.

**Example — "only published posts are shown."** Now the loop is too broad. Add `'status' => TRUE` to the query. Still in the controller; still no repository.

Refactoring to a `PostNodeRepository` comes *after* the behavior is green — and you only refactor when a new test makes the current shape awkward (e.g. "posts are ordered by created date" is easier to pin down on a repository method than through the browser).

Trying to write the "right" design up front is the opposite of TDD. Let the tests pull the design out of you.

> Caveat for small, well-understood features: when the final shape is obvious and cheap (e.g. a 3-card block with a neutral default), jumping straight to the final implementation after a batched red set is fine. "Simplest thing first" guards against over-designing a repository/abstraction you don't yet need — not against writing the obvious 10-line controller in one pass. Use judgment; don't fragment trivial implementations into artificial red-green steps just to perform the ceremony.

## Fail first, then fix

When you're asserting a specific order, count, or value, set up the test data so the *naive* implementation fails. Then fix it.

```php
// Create posts deliberately in the WRONG order so the default
// load order can't accidentally pass the test.
PostBuilder::create()->setCreatedDate('-1 week')->setTitle('Post one')->getPost();
PostBuilder::create()->setCreatedDate('-8 days')->setTitle('Post two')->getPost();
PostBuilder::create()->setCreatedDate('yesterday')->setTitle('Post three')->getPost();

// Expect them sorted by created date ascending — 'Post two' first.
self::assertNodeTitlesAreSame(['Post two', 'Post one', 'Post three'], $nodes);
```

If you create the data already sorted and the code happens to preserve insertion order, the test passes without the sort logic. You proved nothing. Shuffle the arrangement so the expected output only occurs if the code actually sorts.

## The TDD loop for a new Drupal feature

Treat this as a recipe. Each step produces a commit.

1. **Decide the user-visible behavior** ("an anonymous user can load `/blog` and see post titles").
2. **Write a Functional test** asserting that behavior. Run it. Confirm red for the right reason (usually 404 — the route doesn't exist).
3. **Create the route + controller stub** returning `[]`. Run tests. Red changes to "expected text not found."
4. **Add the minimum implementation** to satisfy the assertion (load nodes, render titles). Green.
5. **Commit.**
6. **Add the next assertion** (only published, only posts, correct order, …). Back to step 2 — each new assertion is its own red-green-refactor cycle.
7. **Refactor when the code asks for it.** Controller doing too much → extract a Repository. Repository hard-to-test setup → extract a Builder for test data. Tests stay green the whole time.

## Command-line workflow

Keep the feedback loop tight:

```bash
# Run one test method while iterating
vendor/bin/phpunit --filter testBlogPage

# Stop on first failure so you don't wade through noise
vendor/bin/phpunit --stop-on-failure

# Human-readable output grouped by class
vendor/bin/phpunit --testdox

# Run only the file you're working on
vendor/bin/phpunit web/modules/custom/atdc/tests/src/Functional/BlogPageTest.php
```

Once the feature is green, re-run the *whole* module's suite to confirm you didn't regress anything.

## Debugging a confusing failure

When a Functional test fails with a status code you don't expect, dump the response before guessing:

```php
var_dump($this->getSession()->getPage()->getContent());
```

This prints the rendered HTML (including Drupal error messages) so you can see *why* the page returned 500 or 403. Remove the `var_dump` before committing.

For Kernel tests, if you see "Table … not found," the schema isn't installed — `installSchema()` / `installEntitySchema()` is missing. If you see "Service not found," a module is missing from `$modules`.

## When to write a Functional test vs. a Kernel test

Both can often cover the same behavior. Pick the lower level *only* when the higher level would be painful.

| What you're asserting                          | Prefer      |
|------------------------------------------------|-------------|
| Page exists at URL X / returns status N         | Functional  |
| Specific text is visible on the page            | Functional  |
| Unauthorized user is blocked                    | Functional  |
| Form submission produces an entity              | Functional  |
| Ordering of items in a collection               | Kernel      |
| Repository returns only published / of bundle X | Kernel      |
| Exact shape of a service method's return value  | Kernel      |
| Pure transformation with no Drupal deps         | Unit        |
| Guard that throws InvalidArgumentException      | Unit        |

If you're unsure, start Functional. You can always drop down later.

> Duplicate coverage is usually wasted effort. If the functional test already proves "unpublished posts don't appear," a kernel test for the same behavior adds nothing — you can't make the kernel test fail without making the functional test fail too. Pick the level that *uniquely* pins down the behavior.

## Test data: build it, don't hand-craft it

Inside the third or fourth test you'll feel the pain of repeating `createNode(['type' => 'post', 'title' => …, 'created' => (new DrupalDateTime(…))->getTimestamp(), 'status' => …])`. That's the signal to extract a **Builder**.

```php
PostBuilder::create()
  ->setTitle('Post one')
  ->setCreatedDate('-1 week')
  ->isPublished()
  ->setTags(['Drupal', 'PHP'])
  ->getPost();
```

See `references/test-data-builders.md` for the full PostBuilder pattern (class layout, chaining via `return $this`, guarding unset fields).

## Custom assertions for readable tests

When the same assertion block appears in multiple tests, extract it into a private static method with a name that describes *what* is being checked. Tests read like prose instead of array-map gymnastics.

```php
self::assertNodeTitlesAreSame(
  ['Post two', 'Post one', 'Post three'],
  $nodes,
);
```

See `references/custom-assertions.md` for the extraction pattern.

## Test modules and test-only configuration

Fresh Drupal installs created per test don't have your fields or content types. You have two options:

1. **Quick** — install the config ad-hoc in the test (`$this->installConfig(['my_module'])`) if the config ships with your real module.
2. **Clean** — create a *hidden* sub-module (e.g. `my_module/modules/my_module_test`) whose `config/install/` holds fields, content types, and vocabularies the tests need. It stays invisible to site admins but can be listed in `$modules` inside your test class.

See `references/test-modules-and-config.md` for the hidden-submodule layout, exporting configs via `drush config:export`, and resolving "Base table or view not found" errors.

## Configuring PHPUnit for a fresh project

If the project has no `phpunit.xml.dist`, see `references/phpunit-setup.md` for the minimum config (bootstrap path, `SIMPLETEST_BASE_URL`, SQLite `SIMPLETEST_DB`, test suite directory).

## Worked example

For a full walkthrough — from empty project to a blog module with Functional, Kernel, and Unit tests, driven test-first — see `references/worked-example-blog.md`. It progresses through a representative sequence: 200-on-/blog, posts-visible, ordering, published-only, bundle-filter, tags-with-test-config, Unit + mocks.

## References index

- `references/worked-example-blog.md` — end-to-end TDD example (start here if unfamiliar with the flow)
- `references/test-data-builders.md` — PostBuilder pattern, chaining, null-guards
- `references/custom-assertions.md` — extracting private static assertion methods
- `references/test-modules-and-config.md` — hidden sub-modules, config/install, schema installation
- `references/phpunit-setup.md` — phpunit.xml.dist from scratch

## Anti-patterns to avoid

Real anti-patterns — not stylistic preferences.

- **Writing the implementation first, then "adding tests" to cover it.** That's test-after, not test-driven. The tests won't shape the design, and you'll find yourself writing tests that match the code you already wrote — they'll pass on the first run, testing nothing.
- **Skipping "fail for the right reason."** Always watch the test go red before making it green. If you never saw it fail, you don't actually know what it's testing. This is the most common silent failure mode of test-first workflows.
- **Testing mocks instead of behavior.** A Unit test where every assertion checks a value that was defined in `$mock->willReturn(...)` is an echo chamber. Either test real collaborators (Kernel) or assert something the code *did* with the mock, not something the mock returned to itself.
- **Asserting method-call counts (`expects($this->once())`) on internals.** Couples the test to implementation. Refactor becomes impossible without rewriting tests. Only assert call counts when the *number of calls* is the contract under test (rare — think caching or rate-limiting).
- **One giant *test method* per feature.** A test *method* (not a commit, not a task) should drive one behavior. `testBlogPageWorks()` that sets up 15 things and has 20 assertions tells you nothing when it goes red. Split into `testBlogPageIsReachable`, `testPostsAreVisible`, etc. This is about method granularity, not commit granularity — two different things.
- **Sharing state between tests.** Each test gets a fresh Drupal install. Don't rely on data created in a previous test. If setup is expensive, extract it to `setUp()` or a Builder — don't cross-contaminate.

**Not anti-patterns** (common misreadings):

- *Writing several failing tests up front for a cohesive feature, then implementing to green.* This is "batched TDD" or "TDD planning ahead" — fine for small features with a clear spec.
- *Implementing the obvious 10-line controller in one step instead of fragmenting into artificial red-green micro-steps.* "Simplest thing first" means "don't over-design a premature abstraction," not "write deliberately wrong code then fix it for ceremony."
- *Test-first development at task granularity rather than per-assertion.* Valid. The substantive wins (tests shape design, outside-in ordering, tests observed red) are present regardless of commit count.

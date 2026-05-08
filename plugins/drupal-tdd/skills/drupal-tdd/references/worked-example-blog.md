# Worked Example: TDD a Blog Module

End-to-end walkthrough adapted from Oliver Davies' "Test-Driven Drupal." Each step is one red-green-refactor cycle that ends in a commit.

The module is `atdc` (Automated Testing in Drupal Course) inside `web/modules/custom/atdc`. Tests live in `tests/src/`.

## 0. Groundwork (one-time)

Before the TDD loop starts, you need a project that can run tests. If you already have one, skip.

```bash
composer create-project drupal/recommended-project atdc-project
cd atdc-project
composer require --dev drupal/core-dev
php -S localhost:8000 -t web &
```

Create the module skeleton:

```bash
mkdir -p web/modules/custom/atdc/tests/src/Functional
cat > web/modules/custom/atdc/atdc.info.yml <<'YAML'
name: ATDC
type: module
core_version_requirement: ^10 || ^11
package: Custom
YAML
```

See `phpunit-setup.md` for the minimum `phpunit.xml.dist`.

## 1. Cycle: "/blog returns 200"

### Red

```php
// web/modules/custom/atdc/tests/src/Functional/BlogPageTest.php
<?php

namespace Drupal\Tests\atdc\Functional;

use Drupal\Tests\BrowserTestBase;
use Symfony\Component\HttpFoundation\Response;

class BlogPageTest extends BrowserTestBase {

  protected $defaultTheme = 'stark';
  protected static $modules = ['atdc', 'node'];

  public function testBlogPage(): void {
    $this->drupalGet('/blog');
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
  }

}
```

`vendor/bin/phpunit --filter testBlogPage` → 404 (expected). **Red for the right reason.**

### Green

The simplest thing that returns 200 for `/blog`: a route + a controller that returns an empty render array.

```yaml
# web/modules/custom/atdc/atdc.routing.yml
atdc.blog:
  path: /blog
  defaults:
    _controller: Drupal\atdc\Controller\BlogPageController
    _title: Blog
  requirements:
    _permission: access content
```

```php
// web/modules/custom/atdc/src/Controller/BlogPageController.php
<?php
namespace Drupal\atdc\Controller;

class BlogPageController {
  public function __invoke(): array {
    return [];
  }
}
```

Run. **Green.** Commit.

## 2. Cycle: "posts are visible on /blog"

### Red

```php
public function testPostsAreVisible(): void {
  $this->createContentType(['type' => 'post']);
  $this->createNode(['type' => 'post', 'title' => 'First post']);
  $this->createNode(['type' => 'post', 'title' => 'Second post']);
  $this->createNode(['type' => 'post', 'title' => 'Third post']);

  $this->drupalGet('/blog');
  $assert = $this->assertSession();
  $assert->pageTextContains('First post');
  $assert->pageTextContains('Second post');
  $assert->pageTextContains('Third post');
}
```

Fails: "text 'First post' not found." **Red for the right reason.**

### Green

Simplest thing: load all nodes and render their titles. No repository, no filtering.

```php
use Drupal\Core\Controller\ControllerBase;

class BlogPageController extends ControllerBase {
  public function __invoke(): array {
    $nodes = $this->entityTypeManager()->getStorage('node')->loadMultiple();
    $build = ['content' => ['#theme' => 'item_list', '#items' => []]];
    foreach ($nodes as $node) {
      $build['content']['#items'][] = $node->label();
    }
    return $build;
  }
}
```

Green. **Break it on purpose** (comment out the foreach) to confirm the test fails, revert, commit.

## 3. Refactor: extract PostNodeRepository

Tests are green. No new behavior — just clean up the controller by moving the node-loading into a dedicated class that we can test directly later.

```php
// src/Repository/PostNodeRepository.php
<?php
namespace Drupal\atdc\Repository;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

final class PostNodeRepository {

  public function __construct(
    private EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /** @return array<int, NodeInterface> */
  public function findAll(): array {
    return $this->entityTypeManager->getStorage('node')->loadMultiple();
  }

}
```

Register it as a service:

```yaml
# atdc.services.yml
services:
  Drupal\atdc\Repository\PostNodeRepository:
    arguments: ['@entity_type.manager']
```

Inject into the controller via `create()`:

```php
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\atdc\Repository\PostNodeRepository;

class BlogPageController extends ControllerBase {

  public function __construct(private PostNodeRepository $postNodeRepository) {}

  public static function create(ContainerInterface $container): self {
    return new self($container->get(PostNodeRepository::class));
  }

  public function __invoke(): array {
    $build = ['content' => ['#theme' => 'item_list', '#items' => []]];
    foreach ($this->postNodeRepository->findAll() as $node) {
      $build['content']['#items'][] = $node->label();
    }
    return $build;
  }
}
```

Tests stay green through the refactor. If they don't, revert and try smaller steps.

## 4. Cycle: "posts are ordered by created date" (drop to Kernel)

This ordering assertion is awkward in a Functional test — you'd have to scrape HTML and parse positions. Drop to Kernel.

### Red

```php
// tests/src/Kernel/PostNodeRepositoryTest.php
<?php
namespace Drupal\Tests\atdc\Kernel;

use Drupal\atdc\Repository\PostNodeRepository;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\NodeInterface;
use Drupal\Tests\node\Traits\NodeCreationTrait;

class PostNodeRepositoryTest extends EntityKernelTestBase {

  use NodeCreationTrait;

  protected static $modules = ['node', 'atdc'];

  public function testPostsAreReturnedByCreatedDate(): void {
    // Create posts INTENTIONALLY out of order.
    $this->createNode([
      'created' => (new DrupalDateTime('-1 week'))->getTimestamp(),
      'title' => 'Post one', 'type' => 'post',
    ]);
    $this->createNode([
      'created' => (new DrupalDateTime('-8 days'))->getTimestamp(),
      'title' => 'Post two', 'type' => 'post',
    ]);
    $this->createNode([
      'created' => (new DrupalDateTime('yesterday'))->getTimestamp(),
      'title' => 'Post three', 'type' => 'post',
    ]);

    $repository = $this->container->get(PostNodeRepository::class);
    $nodes = $repository->findAll();

    self::assertSame(
      ['Post two', 'Post one', 'Post three'],
      array_map(fn (NodeInterface $n) => $n->label(), $nodes),
    );
  }

}
```

Fails: the repository returns them in insertion order. **Red.**

### Green

Sort inside `findAll()`:

```php
public function findAll(): array {
  $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple();
  uasort($nodes, fn (NodeInterface $a, NodeInterface $b)
    => $a->getCreatedTime() <=> $b->getCreatedTime());
  return array_values($nodes);
}
```

`array_values` resets the keys — the test compares array-identical, so numeric keys must be `0, 1, 2`, not the node IDs.

Green. Functional tests still pass (titles are still rendered, just in a new order the functional test doesn't care about). Commit.

## 5. Refactor: PostBuilder for test data

The three `createNode(['created' => …, 'title' => …, 'type' => 'post'])` blocks are noise. Extract a builder.

```php
// src/Builder/PostBuilder.php
<?php
namespace Drupal\atdc\Builder;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

final class PostBuilder {

  private ?DrupalDateTime $created = NULL;
  private string $title = '';
  private bool $isPublished = TRUE;

  public static function create(): self { return new self(); }

  public function setTitle(string $title): self {
    $this->title = $title;
    return $this;
  }

  public function setCreatedDate(string $time): self {
    $this->created = new DrupalDateTime($time);
    return $this;
  }

  public function isPublished(): self { $this->isPublished = TRUE; return $this; }
  public function isNotPublished(): self { $this->isPublished = FALSE; return $this; }

  public function getPost(): NodeInterface {
    $post = Node::create([
      'type' => 'post',
      'title' => $this->title,
      'status' => $this->isPublished,
    ]);
    if ($this->created !== NULL) {
      $post->setCreatedTime($this->created->getTimestamp());
    }
    $post->save();
    return $post;
  }

}
```

Rewrite the kernel test:

```php
PostBuilder::create()->setCreatedDate('-1 week')->setTitle('Post one')->getPost();
PostBuilder::create()->setCreatedDate('-8 days')->setTitle('Post two')->getPost();
PostBuilder::create()->setCreatedDate('yesterday')->setTitle('Post three')->getPost();
```

Green. Commit.

## 6. Cycle: extract a custom assertion

The comparison via `array_map` reads poorly. Extract:

```php
/**
 * @param array<int, string> $expectedTitles
 * @param array<int, NodeInterface> $nodes
 */
private static function assertNodeTitlesAreSame(array $expectedTitles, array $nodes): void {
  self::assertSame(
    $expectedTitles,
    array_map(fn (NodeInterface $n) => $n->label(), $nodes),
  );
}
```

```php
self::assertNodeTitlesAreSame(['Post two', 'Post one', 'Post three'], $nodes);
```

Reads like English. Green. Commit.

## 7. Cycle: "only published posts appear"

### Red

```php
public function testOnlyPublishedPostsAreShown(): void {
  PostBuilder::create()->setTitle('Post one')->isPublished()->getPost();
  PostBuilder::create()->setTitle('Post two')->isNotPublished()->getPost();
  PostBuilder::create()->setTitle('Post three')->isPublished()->getPost();

  $this->drupalGet('/blog');
  $assert = $this->assertSession();
  $assert->pageTextContains('Post one');
  $assert->pageTextNotContains('Post two');
  $assert->pageTextContains('Post three');
}
```

Fails — `loadMultiple()` returns unpublished nodes too.

### Green

```php
public function findAll(): array {
  $nodes = $this->entityTypeManager
    ->getStorage('node')
    ->loadByProperties(['status' => TRUE]);
  uasort($nodes, fn (NodeInterface $a, NodeInterface $b)
    => $a->getCreatedTime() <=> $b->getCreatedTime());
  return array_values($nodes);
}
```

Green. Commit.

## 8. Cycle: "only 'post' bundle appears"

### Red

```php
public function testOnlyPostBundleAppears(): void {
  PostBuilder::create()->setTitle('Post one')->getPost();
  $this->createContentType(['type' => 'page']);
  $this->createNode(['type' => 'page', 'title' => 'Not a post']);

  $this->drupalGet('/blog');
  $this->assertSession()->pageTextContains('Post one');
  $this->assertSession()->pageTextNotContains('Not a post');
}
```

Fails — all content types show up.

### Green

```php
->loadByProperties(['status' => TRUE, 'type' => 'post']);
```

Green. Commit.

## 9. Cycle: Unit test + mocks at the edge

A Unit test is appropriate when you want to pin down pure logic — e.g., a `PostWrapper` that throws if the wrapped node isn't a `post`.

```php
// tests/src/Unit/PostWrapperTest.php
<?php
namespace Drupal\Tests\atdc\Unit;

use Drupal\atdc\PostWrapper;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;

final class PostWrapperTest extends UnitTestCase {

  /** @test */
  public function it_wraps_a_post(): void {
    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn('post');

    $wrapper = new PostWrapper($node);
    self::assertSame('post', $wrapper->getType());
  }

  /** @test */
  public function it_rejects_non_posts(): void {
    self::expectException(\InvalidArgumentException::class);

    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn('page');
    new PostWrapper($node);
  }

}
```

```php
// src/PostWrapper.php
<?php
namespace Drupal\atdc;

use Drupal\node\NodeInterface;

final class PostWrapper {
  public function __construct(private NodeInterface $post) {
    if ($post->bundle() !== 'post') {
      throw new \InvalidArgumentException();
    }
  }
  public function getType(): string { return $this->post->bundle(); }
}
```

The mock doesn't echo back what it was told to return — the assertion is about what `PostWrapper` *does* with the node (calls `bundle()`, uses it for a guard). That's a legitimate unit test.

## Summary of commits

1. Module skeleton + phpunit.xml.dist
2. /blog returns 200
3. Posts render as item list
4. Refactor: controller → PostNodeRepository
5. Kernel test: ordering by created date
6. Refactor: PostBuilder
7. Custom assertion: assertNodeTitlesAreSame
8. Only published posts shown
9. Only 'post' bundle shown
10. Unit test: PostWrapper guard

Each commit has a passing test suite. If you broke the chain, a previous commit is always a safe point to revert to.

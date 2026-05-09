# Test Data Builders

The Builder pattern gives you a fluent, named-parameter-style API for constructing test fixtures. It beats repeated `createNode([...])` arrays for three reasons: no key typos, hides details (like `DrupalDateTime`), and makes tests read like prose.

## When to extract a builder

Not on day one. You'll feel the pull around the third or fourth test that duplicates the same `createNode([...])` array. Extract when:

- The same entity-creation block appears in 3+ tests.
- A test has to reach into Drupal internals (`DrupalDateTime`, `Term::create()`, field API) just to set up a fixture.
- Array keys are becoming a source of bugs (`'creation'` vs `'created'`).

## Anatomy

A builder has four parts:

1. **Static `create()` constructor** — so you can write `PostBuilder::create()` instead of `new PostBuilder()`. Purely stylistic, but it makes chaining read better.
2. **Fluent setters** — each returns `$this` to allow chaining. Named after what they set (`setTitle`, `setCreatedDate`, `isPublished`, `isNotPublished`, `setTags`).
3. **Private state** — typed properties with safe defaults. `NULL` for "not set" when you need to distinguish unset from default.
4. **Terminal method** — `getPost()`, `build()`, or similar. Creates the entity, saves it, returns it.

```php
<?php
namespace Drupal\atdc\Builder;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;

final class PostBuilder {

  private ?DrupalDateTime $created = NULL;
  private string $title = '';
  private bool $isPublished = TRUE;
  /** @var string[] */
  private array $tags = [];

  public static function create(): self {
    return new self();
  }

  public function setTitle(string $title): self {
    $this->title = $title;
    return $this;
  }

  public function setCreatedDate(string $time = 'now'): self {
    $this->created = new DrupalDateTime($time);
    return $this;
  }

  public function isPublished(): self {
    $this->isPublished = TRUE;
    return $this;
  }

  public function isNotPublished(): self {
    $this->isPublished = FALSE;
    return $this;
  }

  /** @param string[] $tags */
  public function setTags(array $tags): self {
    $this->tags = $tags;
    return $this;
  }

  public function getPost(): NodeInterface {
    $post = Node::create([
      'type' => 'post',
      'title' => $this->title,
      'status' => $this->isPublished,
    ]);

    // Only set created if explicitly provided — otherwise Drupal sets it.
    if ($this->created !== NULL) {
      $post->setCreatedTime($this->created->getTimestamp());
    }

    if ($this->tags !== []) {
      $terms = [];
      foreach ($this->tags as $tagName) {
        $term = Term::create(['name' => $tagName, 'vid' => 'tags']);
        $term->save();
        $terms[] = $term;
      }
      $post->set('field_tags', $terms);
    }

    $post->save();
    return $post;
  }

}
```

## Why chain

```php
PostBuilder::create()
  ->setTitle('Drupal 11 Release')
  ->setCreatedDate('-2 days')
  ->isPublished()
  ->setTags(['Drupal', 'Release'])
  ->getPost();
```

Reads top-to-bottom as a description of the fixture. Compare to:

```php
$post = Node::create([
  'type' => 'post',
  'title' => 'Drupal 11 Release',
  'status' => TRUE,
  'created' => (new DrupalDateTime('-2 days'))->getTimestamp(),
]);
$post->save();

$tag1 = Term::create(['name' => 'Drupal', 'vid' => 'tags']); $tag1->save();
$tag2 = Term::create(['name' => 'Release', 'vid' => 'tags']); $tag2->save();
$post->set('field_tags', [$tag1, $tag2])->save();
```

Same outcome. Much noisier. And when the sixth test also needs two tags, the builder saves you from copy-pasting five lines.

## Null-guards on optional setters

Be careful when a builder property is "unset" vs. "set to a default." In the example above, `$created` is `NULL` by default — we skip the `setCreatedTime()` call, so Drupal's default (`time()`) kicks in. If we'd defaulted `$created = new DrupalDateTime()` in the constructor, it'd be captured at builder-instantiation time, not at `save()` time — subtly wrong.

When defaulting is dangerous (the default would clobber Drupal's own logic), initialize to `NULL` and guard:

```php
if ($this->created !== NULL) {
  $post->setCreatedTime($this->created->getTimestamp());
}
```

## Adding a method by TDD

Don't add a builder method until a test demands it.

1. Test calls `->isPublished()`. Error: method doesn't exist.
2. Add the emptiest possible method: `public function isPublished(): self { return $this; }`.
3. Test still fails (logic isn't there yet, but the method exists).
4. Now write the property + assignment logic.
5. Test passes.

This keeps the builder's API driven by actual test needs, not speculation.

## When NOT to extract a builder

- **One-off fixtures.** If only one test needs a node with a weird shape, `Node::create([...])` inline is fine.
- **Trivial fixtures.** `createUser()` with no args doesn't need a `UserBuilder`.
- **Fixtures shared across module boundaries.** If two modules need the same builder, put it in the source module's `src/Builder/` (public API) rather than `tests/src/`. Otherwise the second module can't see it.

## Builders are source code, not test code

If your `PostBuilder` has non-trivial logic (the `field_tags` branch above, for example), it deserves its own test. Write a `PostBuilderTest` — a Kernel test that asserts "builder returns a published post with the given tags." You're testing the builder *so you can trust it* in the tests of the real code.

```php
/** @test */
public function it_returns_a_post_with_tags(): void {
  $node = PostBuilder::create()
    ->setTitle('test')
    ->setTags(['Drupal', 'PHP', 'Testing'])
    ->getPost();

  self::assertInstanceOf(NodeInterface::class, $node);
  self::assertSame('post', $node->bundle());

  $tags = $node->get('field_tags')->referencedEntities();
  self::assertCount(3, $tags);
  self::assertSame('Drupal', $tags[0]->label());
  self::assertSame('PHP', $tags[1]->label());
  self::assertSame('Testing', $tags[2]->label());
}
```

If `field_tags` isn't available in the test environment, that's a test-module-and-config problem — see `test-modules-and-config.md`.

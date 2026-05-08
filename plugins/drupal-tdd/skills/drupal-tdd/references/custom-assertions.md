# Custom Assertions

Extracting assertion blocks into named private static methods makes tests read like specification. The method name describes *what* is being checked; the method body holds the mechanical how.

## The pattern

Inline, asserting node order is noisy:

```php
self::assertSame(
  ['Post two', 'Post one', 'Post three'],
  array_map(fn (NodeInterface $n) => $n->label(), $nodes),
);
```

Extracted:

```php
self::assertNodeTitlesAreSame(['Post two', 'Post one', 'Post three'], $nodes);
```

## Where to put it

A private static method at the bottom of the test class:

```php
/**
 * @param array<int, string> $expectedTitles
 * @param array<int, NodeInterface> $nodes
 */
private static function assertNodeTitlesAreSame(
  array $expectedTitles,
  array $nodes,
): void {
  self::assertSame(
    $expectedTitles,
    array_map(fn (NodeInterface $node) => $node->label(), $nodes),
  );
}
```

## When to extract

Three questions:

1. **Does the inline assertion need a mental parse?** `array_map` with a closure is common but not free — you have to read it to confirm it returns titles. A named method saves that re-read.
2. **Will this assertion appear in multiple tests?** Two usages is the threshold. If three tests want "assert the node titles, in order, match this list," extract.
3. **Does the method name describe the contract better than the body?** `assertOnlyPublishedNodes($nodes)` is worth extracting. `assertSame($a, $b)` is not — the name adds nothing.

## Shared assertions across classes

When the same assertion is useful in multiple test classes, lift it to a trait or base class:

```php
// tests/src/Traits/NodeAssertionsTrait.php
<?php
namespace Drupal\Tests\atdc\Traits;

use Drupal\node\NodeInterface;

trait NodeAssertionsTrait {

  /**
   * @param array<int, string> $expectedTitles
   * @param array<int, NodeInterface> $nodes
   */
  protected static function assertNodeTitlesAreSame(
    array $expectedTitles,
    array $nodes,
  ): void {
    static::assertSame(
      $expectedTitles,
      array_map(fn (NodeInterface $n) => $n->label(), $nodes),
    );
  }

}
```

Then `use NodeAssertionsTrait;` in any test that wants it.

Note the `protected` visibility on traits (not `private`) — PHP inheritance/trait rules allow traits to see protected methods but not private ones across the composition boundary.

## Don't extract every last thing

A test with one or two simple assertions doesn't benefit from a custom assertion. This is fine and readable:

```php
$this->drupalGet('/blog');
$this->assertSession()->statusCodeEquals(200);
$this->assertSession()->pageTextContains('Blog');
```

Extracting `self::assertBlogPageIsVisible()` around it would hide the details for no readability gain — you'd have to click through to understand what the test does.

Extract when the *mechanics* are obscuring the *intent*. Leave inline when they don't.

## Assertion naming

Good names:

- `assertNodeTitlesAreSame(expected, nodes)` — reads as "assert node titles are same as expected"
- `assertOnlyPublishedPosts($nodes)` — states the invariant
- `assertPageContainsTags($tags)` — describes the check

Bad names:

- `checkTitles()` — "check" is vague; what if it fails, does it return false or throw?
- `verifyOrder()` — order of what, how?
- `assertResult()` — says nothing

Method name = sentence fragment that reads naturally after `self::` or `$this->`.

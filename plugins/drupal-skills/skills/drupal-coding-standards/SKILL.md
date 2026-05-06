---
name: drupal-coding-standards
description: |
  Drupal coding standards for phpcs compliance. Covers cuddled brace style,
  docblock requirements, nullable parameter types, and general formatting.
  Load as a BASELINE for ALL Drupal code generation -- not a domain skill but
  a quality baseline loaded alongside any domain skill.
---

# Drupal Coding Standards (phpcs Compliance)

These patterns ensure generated code passes `phpcs --standard=Drupal,DrupalPractice` without errors.

## Cuddled brace style

Drupal uses "cuddled" control structures. Opening braces on the SAME line as the keyword. `else`, `catch`, `finally` cuddle with the closing brace.

> WRONG: Brace on its own line or uncuddled else/catch:
> ```php
> if ($condition)
> {
>   doSomething();
> }
> else
> {
>   doOther();
> }
> ```

> RIGHT: Cuddled braces -- `} else {`, `} catch (...) {`, `} finally {` all on one line:
> ```php
> if ($condition) {
>   doSomething();
> }
> else {
>   doOther();
> }
>
> try {
>   riskyOperation();
> }
> catch (SuspendQueueException $e) {
>   throw $e;
> }
> catch (\Exception $e) {
>   handleError($e);
> }
> ```

## Docblock requirements

Every class, method, function, and constant MUST have a docblock.

> WRONG: Class or public method without docblock:
> ```php
> class MyController extends ControllerBase {
>   public function build() {
>     return ['#markup' => 'Hello'];
>   }
> }
> ```

> RIGHT: All classes and public methods get docblocks with @param/@return:
> ```php
> /**
>  * Provides a page for displaying custom content.
>  */
> class MyController extends ControllerBase {
>
>   /**
>    * Builds the page content.
>    *
>    * @return array
>    *   A render array.
>    */
>   public function build() {
>     return ['#markup' => 'Hello'];
>   }
>
> }
> ```

### Hook implementation docblocks

> WRONG: Hook with @param/@return tags:
> ```php
> /**
>  * Implements hook_cron().
>  *
>  * @return void
>  */
> function my_module_cron() {
> ```

> RIGHT: Hook implementations use ONLY the `Implements` line -- no @param or @return:
> ```php
> /**
>  * Implements hook_cron().
>  */
> function my_module_cron() {
> ```

### @file docblocks

`@file` docblocks are ONLY for procedural files (`.module`, `.install`, `.theme`).

> WRONG: `@file` in a class file:
> ```php
> <?php
> /**
>  * @file
>  * Contains \Drupal\my_module\Controller\MyController.
>  */
> namespace Drupal\my_module\Controller;
> ```

> RIGHT: No `@file` in class files. Only in procedural files:
> ```php
> <?php
> /**
>  * @file
>  * Hook implementations for the My Module module.
>  */
> ```

## Inline `@var` type hints

When a variable's type is not self-evident from the code, add an inline `@var` docblock using the **fully qualified class name** and the **variable name at the end**. Reference: [Drupal PHP docs standards — in-line code comments](https://project.pages.drupalcode.org/coding_standards/php/documentation/#in-line-code-comments).

> WRONG: Polymorphic return type with no inline hint:
> ```php
> $node = $this->entityTypeManager->getStorage('node')->load(123);
> $title = $node->getTitle();  // IDE/static-analysis can't know $node is NodeInterface
> ```

> RIGHT: FQCN `@var` placed AFTER null guards so narrowing is accurate:
> ```php
> $node = $this->entityTypeManager->getStorage('node')->load(123);
> if (!$node) {
>   return [];
> }
> /** @var \Drupal\node\NodeInterface $node */
> $title = $node->getTitle();
> ```

**Trigger list** — required when you see any of these:

- `->load()` / `->loadMultiple()` / `->loadByProperties()` on entity storage
- `->getStorage('entity_type')` when the handle is used as a specific subtype
- `\Drupal::service('id')` or `$container->get('id')` where the return is polymorphic
- `foreach ($entities as $entity)` where `$entity` is used as a specific entity subtype
- Any assignment from a factory, decorator, or lazy resolver

**Rules:**

- **Always FQCN.** Do NOT add a `use` import solely to shorten the `@var` docblock — in procedural scripts the `use` will show as "unused" by static analyzers. Use the fully qualified name inside the `@var` instead.
- **After null guards, not before.** A `@var NodeInterface` placed before the null check lies about the variable's actual type at that point (it might be `NULL`). Place it after the guard so narrowing is accurate.
- **Variable name at the end.** `/** @var \Drupal\node\NodeInterface $node */` — not `/** @var $node \Drupal\node\NodeInterface */`.
- **Double opening asterisk** (`/**`) — single-asterisk comments (`/*`) are NOT parsed by IDEs or static analyzers.

## Nullable parameter types

When a typed parameter defaults to `NULL`, the type MUST use `?` nullable syntax.

> WRONG: Implicit nullable (deprecated PHP 8.4, error in PHP 9):
> ```php
> public function init(ViewExecutable $view, DisplayPluginBase $display, array $options = NULL) {
> ```

> RIGHT: Explicit `?type` nullable:
> ```php
> public function init(ViewExecutable $view, DisplayPluginBase $display, ?array $options = NULL) {
> ```

Critical when overriding parent methods with nullable parameters.

## General formatting

- **One class per file** -- each `.php` in `src/` has exactly one class/interface/trait
- **Short array syntax** -- `[]` not `array()`
- **Trailing commas** in multi-line arrays
- **Single blank line** between methods
- **No closing `?>` tag**
- **Two blank lines** before class declaration (after use statements)
- **Space after control keywords** -- `if (`, `foreach (`, `while (`

## DrupalPractice service patterns

The `DrupalPractice` phpcs standard flags service anti-patterns. The `GlobalDrupal` sniff fails any `\Drupal::` static call inside a class file under `src/`.

> WRONG: `\Drupal::service()`, `\Drupal::entityTypeManager()`, or any `\Drupal::` call in controllers, forms, services, or list builders:
> ```php
> class MyController extends ControllerBase {
>   public function build() {
>     $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple();
>   }
> }
> ```

> RIGHT: Inject via `create()` + constructor. `\Drupal::` is ONLY valid in `.module` procedural files:
> ```php
> class MyController extends ControllerBase {
>   public function __construct(
>     protected EntityTypeManagerInterface $entityTypeManager,
>   ) {}
>   public static function create(ContainerInterface $container) {
>     return new static($container->get('entity_type.manager'));
>   }
> }
> ```

This applies to ALL classes: controllers, forms, list builders, event subscribers, services. Any class that can implement `ContainerInjectionInterface` MUST inject dependencies.

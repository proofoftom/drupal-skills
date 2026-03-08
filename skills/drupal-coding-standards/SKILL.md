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

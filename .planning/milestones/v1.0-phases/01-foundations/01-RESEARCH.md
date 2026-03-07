# Phase 1: Foundations - Research

**Researched:** 2026-03-05
**Domain:** Drupal module development skills for Claude Code (module scaffold, routing/controllers, entities/fields)
**Confidence:** HIGH

## Summary

Phase 1 builds three foundational Claude Code skills extracted from the Drupal 10 Module Development book (Sipos, 4th ed, 2023). Each skill must follow skill-creator anatomy (YAML frontmatter, sub-500-line body, references/ subdirectory), use decision-guide format, and produce complete file ecosystems covering both D10 annotation syntax and D11 PHP attribute syntax.

The three skills cover: (1) module scaffolding -- .info.yml, PSR-4 namespaces, .module files; (2) routing and controllers -- route definitions, controllers, services, DI, with menus as a reference file; (3) entities and fields -- content and config entity types, base field definitions, entity handlers, with files/images as a reference file. These are the most cross-referenced capabilities in Drupal development; every subsequent skill depends on patterns established here.

The highest-risk area is drupal-entities-fields due to its large API surface and the significant syntax differences between D10 annotations and D11 PHP attributes (entity type plugins were converted to attributes in Drupal 11.1.0). The skill template pattern established in this phase (directory structure, frontmatter conventions, wrong-way callout format, dual-syntax presentation) becomes the model for all subsequent phases.

**Primary recommendation:** Build the module-scaffold skill first (simplest, establishes template), then routing-controllers (moderate complexity), then entities-fields (highest complexity, benefits from patterns established by the first two).

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| SKIL-01 | Each skill follows SKILL.md anatomy (YAML frontmatter, <500 line body, references/ subdirectory) | Skill-creator anatomy documented; progressive disclosure pattern (metadata -> body -> references) |
| SKIL-02 | Each skill uses decision-guide format (decision trees, not reference docs) | Skill best practices: imperative instructions, decision trees, explain "why" not just "what" |
| SKIL-03 | Each skill includes "wrong way" callouts for patterns Claude commonly generates incorrectly | Common Claude/Drupal mistakes documented: missing cache metadata, static service calls, wrong DI patterns |
| SKIL-04 | Each skill produces complete file ecosystems (PHP classes paired with required YAML files) | Book examples show paired files: .info.yml + .module, .routing.yml + Controller.php, entity class + .schema.yml |
| SKIL-05 | Each skill shows D10 annotation syntax with D11 PHP attribute syntax alongside | D11.1.0 converted entity type annotations to attributes; conversion pattern documented with before/after |
| SKIL-06 | Each skill directory is self-contained and works independently when installed to ~/.claude/skills/ | Skill-creator: each skill is a directory with SKILL.md + optional resources, no external dependencies |
| SKIL-07 | Each skill includes advisory cross-references to related skills that degrade gracefully | Skills should reference related skills with "if available" language; graceful degradation when skill not installed |
| FOUN-01 | drupal-module-scaffold skill covers module creation, .info.yml, PSR-4 namespaces, .module file patterns | Book Ch 1-2: complete module creation walkthrough, info.yml keys, PSR-4 src/ structure, hook patterns |
| FOUN-02 | drupal-routing-controllers skill covers routes, controllers, services, DI, with menus reference file | Book Ch 2 + Ch 5: routing.yml, Controller class, services.yml, DI via create(), menus/local tasks/actions |
| FOUN-03 | drupal-entities-fields skill covers content/config entities, base fields, entity handlers, custom fields | Book Ch 6-7: ContentEntityType, ConfigEntityType, baseFieldDefinitions(), handlers, config schemas |
</phase_requirements>

## Standard Stack

This phase produces Claude Code skill files (markdown + YAML), not executable code. The "stack" is the Drupal APIs the skills teach Claude to generate.

### Core Drupal APIs Covered

| API | Drupal Version | Purpose | Book Chapters |
|-----|---------------|---------|---------------|
| Module system | D10/D11 | .info.yml, .module, hook system | Ch 1-2 |
| Routing system | D10/D11 | .routing.yml, controllers, route parameters | Ch 2 |
| Service container | D10/D11 | .services.yml, DI, ContainerInjectionInterface | Ch 2 |
| Entity API | D10/D11 | ContentEntityType, ConfigEntityType, fields | Ch 6-7 |
| Menu system | D10/D11 | .links.menu.yml, local tasks, local actions | Ch 5 |

### Skill Tooling

| Tool | Purpose | When to Use |
|------|---------|-------------|
| skill-creator (anthropics/skills) | Eval loop, benchmarking, description optimization | After each skill is drafted, to validate improvement over baseline |
| evals/evals.json | Test case definitions for with-skill vs baseline comparison | Per-skill validation |
| scripts/aggregate_benchmark | Aggregate eval results across iterations | After eval runs complete |

### D10 vs D11 Syntax Differences

| Feature | D10 Syntax | D11 Syntax (11.1.0+) | Impact |
|---------|-----------|----------------------|--------|
| Entity type plugins | `@ContentEntityType(...)` annotation | `#[ContentEntityType(...)]` PHP attribute | All entity skills must show both |
| Block plugins | `@Block(...)` annotation | `#[Block(...)]` PHP attribute | Future phases, but pattern established here |
| Translation in annotations | `@Translation("...")` | `new TranslatableMarkup("...")` | All plugin definitions affected |
| Class references | String `"Drupal\...\ClassName"` | `ClassName::class` | Attribute syntax allows ::class |
| Array syntax in plugins | `{ "key" = "value" }` | `['key' => 'value']` | Standard PHP array syntax in attributes |

## Architecture Patterns

### Skill Directory Structure (Template for All Skills)

```
skills/
├── drupal-module-scaffold/
│   ├── SKILL.md              # <500 lines, frontmatter + decision guide
│   └── references/
│       └── (none needed for this skill)
├── drupal-routing-controllers/
│   ├── SKILL.md              # <500 lines, frontmatter + decision guide
│   └── references/
│       └── menus.md           # Local tasks, local actions, contextual links
└── drupal-entities-fields/
    ├── SKILL.md              # <500 lines, frontmatter + decision guide
    └── references/
        └── files-images.md    # File/image field handling
```

### SKILL.md Anatomy (from skill-creator)

```yaml
---
name: drupal-module-scaffold
description: |
  Scaffold Drupal modules with correct .info.yml, PSR-4 namespace structure,
  and .module files. Use when asked to create a new Drupal module, start a
  custom module, or set up module boilerplate.
---
```

Followed by markdown body with:
1. **Decision tree** (not reference docs) -- "Are you creating a new module? -> Do you need dependencies? -> ..."
2. **Complete file ecosystems** -- every PHP class shown with its paired YAML file
3. **Wrong-way callouts** -- patterns Claude commonly gets wrong, with correct alternatives
4. **D10/D11 dual syntax** -- annotation and attribute examples side-by-side
5. **Cross-references** -- "See also: drupal-routing-controllers (if available)" with graceful degradation

### Pattern: Decision Guide Format

Skills should use decision trees, not API reference docs. The skill tells Claude WHEN to do things, not just HOW.

```markdown
## Creating a Module

### What files do you need?

ALWAYS create:
- `module_name.info.yml` -- required, makes Drupal recognize the module
- `src/` directory -- required for PSR-4 autoloading

CREATE WHEN NEEDED:
- `module_name.module` -- only if implementing hooks
- `module_name.routing.yml` -- only if defining routes
- `module_name.services.yml` -- only if defining services
```

### Pattern: Wrong-Way Callouts

```markdown
> WRONG: Claude often generates `\Drupal::service('my_service')` inside controllers.
> RIGHT: Inject services via `create()` method and constructor. Static calls bypass DI
> and make testing impossible.
```

### Pattern: D10/D11 Dual Syntax

```markdown
**D10 (annotation):**
```php
/**
 * @ContentEntityType(
 *   id = "product",
 *   label = @Translation("Product"),
 *   ...
 * )
 */
```

**D11.1+ (attribute):**
```php
#[ContentEntityType(
  id: 'product',
  label: new TranslatableMarkup('Product'),
  ...
)]
```
```

### Anti-Patterns to Avoid

- **Reference doc style:** Don't list every API method. Skills are decision guides that tell Claude WHEN to use patterns, not API docs.
- **D10-only examples:** Every code example must show both D10 annotation and D11 attribute syntax where applicable (entity types, plugins).
- **Orphan PHP files:** Never show a PHP class without its paired YAML file (controller needs .routing.yml, service needs .services.yml, entity needs .schema.yml).
- **Static service calls in classes:** `\Drupal::service()` is for procedural code (.module files) only. Classes must use dependency injection via `create()` + constructor.
- **Missing cache metadata:** This is a Phase 3 concern, but foundational skills should note that render arrays need cache metadata (cross-reference to drupal-caching skill).

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Skill eval pipeline | Custom test scripts | skill-creator eval loop (evals.json + benchmark scripts) | Standardized comparison, variance analysis, viewer UI |
| Module boilerplate generators | Custom scaffolding scripts | Skill decision trees that guide Claude | Skills are prompts, not code generators |
| Entity CRUD routes | Manual route definitions | AdminHtmlRouteProvider handler | Auto-generates all entity admin routes from links annotation |
| Entity forms | Manual Form API forms | ContentEntityForm / EntityForm base classes | Auto-builds forms from base field definitions |
| Entity list pages | Manual controllers | EntityListBuilder handler | Standard list with operations, pagination |

**Key insight:** Drupal's Entity API provides enormous amounts of boilerplate via handlers and route providers. Skills must teach Claude to leverage these defaults rather than hand-rolling CRUD infrastructure.

## Common Pitfalls

### Pitfall 1: Skill Body Exceeds 500 Lines
**What goes wrong:** Skill tries to cover too much in the main SKILL.md body.
**Why it happens:** Entity API has massive surface area; tempting to include everything.
**How to avoid:** Use progressive disclosure. Main SKILL.md covers decisions and core patterns. Reference files in references/ hold detailed examples, field type lists, handler options. SKILL.md tells Claude WHEN to read reference files.
**Warning signs:** Body line count approaching 400 means it's time to extract to references/.

### Pitfall 2: Claude Uses Static Service Calls
**What goes wrong:** Claude generates `\Drupal::service('entity_type.manager')` inside Controller or Form classes.
**Why it happens:** Drupal's procedural layer uses `\Drupal::` calls extensively; Claude sees these in documentation and training data.
**How to avoid:** Every skill must include a wrong-way callout for this. Show the correct DI pattern: `create()` + constructor injection. Explain WHY (testability, decoupling).
**Warning signs:** Any `\Drupal::` call inside a class extending ControllerBase, FormBase, or similar.

### Pitfall 3: Missing Paired YAML Files
**What goes wrong:** Claude generates a Controller class but forgets the .routing.yml entry, or creates a service class but forgets .services.yml.
**Why it happens:** Claude focuses on the PHP class and treats YAML as secondary.
**How to avoid:** Skills must always show complete file ecosystems. "When you create X.php, you MUST also create/update Y.yml." Use checklists.
**Warning signs:** PHP file without corresponding YAML configuration.

### Pitfall 4: Wrong Entity Handler Defaults
**What goes wrong:** Claude hand-writes entity routes, forms, and list builders that duplicate what handlers provide automatically.
**Why it happens:** Book shows custom handlers for learning purposes; Claude doesn't distinguish "custom for education" from "use defaults in practice."
**How to avoid:** Decision tree: "Do you need custom behavior? NO -> use default handler. YES -> extend default handler."
**Warning signs:** Manual route definitions for entity CRUD when AdminHtmlRouteProvider would suffice.

### Pitfall 5: D11 Attribute Syntax Errors
**What goes wrong:** Claude mixes annotation and attribute syntax, or uses annotation key=value syntax inside attributes.
**Why it happens:** D10 documentation dominates training data; D11 attributes are newer.
**How to avoid:** Show clear side-by-side examples. Key differences: `=` becomes `:`, `@Translation("...")` becomes `new TranslatableMarkup("...")`, `{ "key" = "value" }` becomes `['key' => 'value']`, class strings become `ClassName::class`.
**Warning signs:** `=` signs inside `#[...]` attributes, `@Translation` inside attributes.

### Pitfall 6: .info.yml Mistakes
**What goes wrong:** Claude uses `core: 8.x` (Drupal 8 style), omits `core_version_requirement`, or uses wrong dependency format.
**Why it happens:** Old Drupal 8 patterns in training data.
**How to avoid:** Skill must show current format: `core_version_requirement: ^10` (or `^10 || ^11`). Dependencies use `project:module` format.
**Warning signs:** `core:` key instead of `core_version_requirement:`.

### Pitfall 7: Config Entity Missing Schema
**What goes wrong:** Claude creates a ConfigEntityType class but forgets the config/schema/*.schema.yml file.
**Why it happens:** Schema feels optional since entities "work" without it initially.
**How to avoid:** Skill must pair config entity classes with their schema files. Schema is REQUIRED for proper config export, translation, and validation.
**Warning signs:** ConfigEntityType without corresponding .schema.yml file.

## Code Examples

### Module Scaffold -- Complete File Ecosystem

**`hello_world.info.yml`:**
```yaml
name: Hello World
description: 'Hello World module'
type: module
core_version_requirement: ^10 || ^11
package: Custom
```

**`src/` directory structure (PSR-4):**
```
hello_world/
├── hello_world.info.yml
├── hello_world.module          # Only if implementing hooks
├── hello_world.routing.yml     # Only if defining routes
├── hello_world.services.yml    # Only if defining services
└── src/                        # PSR-4 autoloading root
    ├── Controller/             # Route controllers
    ├── Form/                   # Form classes
    └── Entity/                 # Entity type classes
```

### Route + Controller -- Paired Files

**`hello_world.routing.yml`:**
```yaml
hello_world.hello:
  path: '/hello'
  defaults:
    _controller: '\Drupal\hello_world\Controller\HelloWorldController::helloWorld'
    _title: 'Our first route'
  requirements:
    _permission: 'access content'
```

**`src/Controller/HelloWorldController.php`:**
```php
namespace Drupal\hello_world\Controller;

use Drupal\Core\Controller\ControllerBase;

class HelloWorldController extends ControllerBase {

  public function helloWorld() {
    return [
      '#markup' => $this->t('Hello World'),
    ];
  }

}
```

### Service + DI -- Correct Pattern

**`hello_world.services.yml`:**
```yaml
services:
  hello_world.salutation:
    class: Drupal\hello_world\HelloWorldSalutation
    arguments: ['@config.factory']
```

**Controller with injected service:**
```php
class HelloWorldController extends ControllerBase {

  protected $salutation;

  public function __construct(HelloWorldSalutation $salutation) {
    $this->salutation = $salutation;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('hello_world.salutation')
    );
  }

  public function helloWorld() {
    return [
      '#markup' => $this->salutation->getSalutation(),
    ];
  }

}
```

### Content Entity Type -- D10 Annotation

```php
/**
 * @ContentEntityType(
 *   id = "product",
 *   label = @Translation("Product"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\products\ProductListBuilder",
 *     "form" = {
 *       "default" = "Drupal\products\Form\ProductForm",
 *       "add" = "Drupal\products\Form\ProductForm",
 *       "edit" = "Drupal\products\Form\ProductForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     }
 *   },
 *   base_table = "product",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/product/{product}",
 *     "add-form" = "/admin/structure/product/add",
 *     "edit-form" = "/admin/structure/product/{product}/edit",
 *     "delete-form" = "/admin/structure/product/{product}/delete",
 *     "collection" = "/admin/structure/product",
 *   }
 * )
 */
class Product extends ContentEntityBase implements ProductInterface {}
```

### Content Entity Type -- D11 Attribute (11.1.0+)

```php
use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;

#[ContentEntityType(
  id: 'product',
  label: new TranslatableMarkup('Product'),
  handlers: [
    'view_builder' => EntityViewBuilder::class,
    'list_builder' => ProductListBuilder::class,
    'form' => [
      'default' => ProductForm::class,
      'add' => ProductForm::class,
      'edit' => ProductForm::class,
      'delete' => ContentEntityDeleteForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  base_table: 'product',
  admin_permission: 'administer site configuration',
  entity_keys: [
    'id' => 'id',
    'label' => 'name',
    'uuid' => 'uuid',
  ],
  links: [
    'canonical' => '/admin/structure/product/{product}',
    'add-form' => '/admin/structure/product/add',
    'edit-form' => '/admin/structure/product/{product}/edit',
    'delete-form' => '/admin/structure/product/{product}/delete',
    'collection' => '/admin/structure/product',
  ],
)]
class Product extends ContentEntityBase implements ProductInterface {}
```

### Config Entity Type -- D10 Annotation

```php
/**
 * @ConfigEntityType(
 *   id = "importer",
 *   label = @Translation("Importer"),
 *   handlers = {
 *     "list_builder" = "Drupal\products\ImporterListBuilder",
 *     "form" = {
 *       "add" = "Drupal\products\Form\ImporterForm",
 *       "edit" = "Drupal\products\Form\ImporterForm",
 *       "delete" = "Drupal\products\Form\ImporterDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "importer",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/importer/add",
 *     "edit-form" = "/admin/structure/importer/{importer}/edit",
 *     "delete-form" = "/admin/structure/importer/{importer}/delete",
 *     "collection" = "/admin/structure/importer"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "url",
 *     "plugin",
 *     "update_existing",
 *     "source"
 *   }
 * )
 */
class Importer extends ConfigEntityBase implements ImporterInterface {}
```

### Base Field Definitions

```php
public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
  $fields = parent::baseFieldDefinitions($entity_type);

  $fields['name'] = BaseFieldDefinition::create('string')
    ->setLabel(t('Name'))
    ->setDescription(t('The name of the Product.'))
    ->setSettings(['max_length' => 255, 'text_processing' => 0])
    ->setDefaultValue('')
    ->setDisplayOptions('view', [
      'label' => 'hidden',
      'type' => 'string',
      'weight' => -4,
    ])
    ->setDisplayOptions('form', [
      'type' => 'string_textfield',
      'weight' => -4,
    ])
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

  $fields['created'] = BaseFieldDefinition::create('created')
    ->setLabel(t('Created'))
    ->setDescription(t('The time that the entity was created.'));

  $fields['changed'] = BaseFieldDefinition::create('changed')
    ->setLabel(t('Changed'))
    ->setDescription(t('The time that the entity was last edited.'));

  return $fields;
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `@ContentEntityType` annotation | `#[ContentEntityType]` PHP attribute | D11.1.0 | Skills must show both syntaxes |
| `@ConfigEntityType` annotation | `#[ConfigEntityType]` PHP attribute | D11.1.0 | Skills must show both syntaxes |
| `@Translation("...")` | `new TranslatableMarkup("...")` | D11.1.0 | All plugin definitions affected |
| `core: 8.x` in .info.yml | `core_version_requirement: ^10` | D9+ | Old format no longer works |
| Annotation-based discovery only | Dual annotation + attribute discovery | D10.2.0 | Backward compatible; attributes preferred in D11 |

**Deprecated/outdated:**
- `core: 8.x` key in .info.yml: Replaced by `core_version_requirement` since Drupal 9
- Doctrine annotation dependency: Being removed; PHP native attributes are the future
- `hook_menu()`: Replaced by routing system since Drupal 8 (still appears in old docs)

## Open Questions

1. **D11 ConfigEntityType attribute syntax**
   - What we know: ContentEntityType attribute syntax is well-documented via api.drupal.org. ConfigEntityType uses variadic constructor params to inherit from EntityType.
   - What's unclear: Exact syntax for config_export in attribute form (may use named parameter or array).
   - Recommendation: Use `config_export: [...]` array syntax in attribute. Verify against Drupal 11.x core source during skill drafting.

2. **Skill-creator eval prompts for Drupal**
   - What we know: Eval loop compares with-skill vs baseline outputs using evals.json test cases.
   - What's unclear: What specific Drupal prompts best demonstrate skill improvement for these three domains.
   - Recommendation: Ground eval prompts in os-knowledge-garden project tasks (e.g., "create a module like social_ai_indexing with a route and controller"). The project has real examples of routes, services, blocks, and entity usage.

3. **Optimal cross-reference format**
   - What we know: Skills must reference related skills with graceful degradation.
   - What's unclear: Exact format that works best when skill is/isn't installed.
   - Recommendation: Use format: "See also: drupal-routing-controllers skill (if installed at ~/.claude/skills/). If not available, ensure you create a .routing.yml file alongside any controller."

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | skill-creator eval loop (anthropics/skills) |
| Config file | evals/evals.json per skill |
| Quick run command | Run single eval via skill-creator subagent |
| Full suite command | `python -m scripts.aggregate_benchmark` after all evals |

### Phase Requirements -> Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| SKIL-01 | Skill follows anatomy (frontmatter, <500 lines, references/) | manual-only | Visual inspection of SKILL.md structure | N/A |
| SKIL-02 | Decision-guide format | manual-only | Review SKILL.md for decision trees vs reference docs | N/A |
| SKIL-03 | Wrong-way callouts present | manual-only | Grep SKILL.md for "WRONG:" or equivalent markers | N/A |
| SKIL-04 | Complete file ecosystems | eval | Eval prompt asks to create module; check output has paired PHP+YAML | Wave 0 |
| SKIL-05 | D10/D11 dual syntax | manual-only | Visual inspection of code examples in SKILL.md | N/A |
| SKIL-06 | Self-contained skill directory | manual-only | Verify no external file dependencies | N/A |
| SKIL-07 | Cross-references with graceful degradation | manual-only | Check cross-reference language | N/A |
| FOUN-01 | Module scaffold skill produces correct output | eval | Eval: "Create a Drupal module called X" with-skill vs baseline | Wave 0 |
| FOUN-02 | Routing skill produces correct output | eval | Eval: "Add a page at /foo to module X" with-skill vs baseline | Wave 0 |
| FOUN-03 | Entity skill produces correct output | eval | Eval: "Create a custom content entity type" with-skill vs baseline | Wave 0 |

### Sampling Rate
- **Per task commit:** Manual review of skill structure + single eval run
- **Per wave merge:** Full eval suite for all three skills
- **Phase gate:** All three skills pass skill-creator eval showing improvement over baseline

### Wave 0 Gaps
- [ ] `evals/evals.json` for drupal-module-scaffold -- eval prompts grounded in os-knowledge-garden
- [ ] `evals/evals.json` for drupal-routing-controllers -- eval prompts for route/controller/service creation
- [ ] `evals/evals.json` for drupal-entities-fields -- eval prompts for content and config entity creation
- [ ] Skill-creator scripts available in workspace for running evals

## Sources

### Primary (HIGH confidence)
- Book: "Drupal 10 Module Development" (Sipos, 4th ed, 2023) -- Ch 1-2, 5, 6-7 read directly
- [Entity type plugins converted in 11.1.0](https://www.drupal.org/node/3505422) -- D11 attribute syntax change record
- [Plugin implementations should use PHP attributes](https://www.drupal.org/node/3395575) -- Attribute conversion guide with syntax examples
- [Drupal api.drupal.org Node entity](https://api.drupal.org/api/drupal/core%21modules%21node%21src%21Entity%21Node.php/class/Node/11.x) -- D11 attribute syntax for Node entity
- [skill-creator SKILL.md](https://github.com/anthropics/skills/blob/main/skills/skill-creator/SKILL.md) -- Skill anatomy, eval workflow, workspace structure
- [Claude Code skills documentation](https://code.claude.com/docs/en/skills) -- Skill format, frontmatter, references

### Secondary (MEDIUM confidence)
- [Skill authoring best practices](https://platform.claude.com/docs/en/agents-and-tools/agent-skills/best-practices) -- Skill writing guidelines
- [QED42: PHP Attributes in Drupal](https://www.qed42.com/insights/exploring-the-power-of-php-attributes-in-drupal-development) -- Attribute conversion examples
- os-knowledge-garden project -- Real custom module examples (social_ai_indexing, localnodes_platform) inspected directly

### Tertiary (LOW confidence)
- [Common Claude Drupal mistakes](https://rayburgess.info/2025/09/16/opinions-and-ramblings-about-my-work/claude-code-and-drupal-a-synthesis/) -- Community observations on Claude + Drupal issues
- D11 ConfigEntityType attribute exact syntax -- inferred from ContentEntityType pattern, needs verification against core source

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - Book content read directly, Drupal APIs well-documented
- Architecture: HIGH - Skill-creator anatomy well-documented, Drupal module patterns are stable
- Pitfalls: HIGH - Common Claude mistakes verified through community reports and direct book analysis
- D11 syntax: MEDIUM - ContentEntityType attribute verified via api.drupal.org; ConfigEntityType attribute inferred

**Research date:** 2026-03-05
**Valid until:** 2026-04-05 (stable domain, book content doesn't change, D11 API stable)

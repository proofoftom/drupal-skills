# Phase 2: Core Workflow - Research

**Researched:** 2026-03-05
**Domain:** Drupal module development skills for Claude Code (forms, plugins/blocks, config/state, access/security)
**Confidence:** HIGH

## Summary

Phase 2 builds four Claude Code skills covering Drupal's daily-use development patterns: Form API, plugin/block system, configuration/state storage, and access control/security. These compose with the Phase 1 foundational skills (module scaffold, routing/controllers, entities/fields) to cover the core workflow patterns that Drupal developers use every day.

Each skill must follow the same skill-creator anatomy established in Phase 1: YAML frontmatter, sub-500-line SKILL.md body, references/ subdirectory, decision-guide format, wrong-way callouts, D10/D11 dual syntax, and cross-references with graceful degradation. The four domains have significant cross-referencing needs -- forms connect to config storage, blocks are plugins with config forms, access control applies to routes/entities/blocks, and config schemas underpin config forms and config entities.

The highest-risk skill is drupal-plugins-blocks due to covering both block plugins (common) and custom plugin type boilerplate (complex). The drupal-config-storage skill must clearly distinguish three storage systems (Config API, State API, TempStore) with a decision tree. The drupal-access-security skill spans permissions, route access, entity access, and XSS/CSRF prevention -- broad surface area requiring careful scoping. The drupal-forms-api skill is the most straightforward, covering a well-defined lifecycle (buildForm/validateForm/submitForm) with form altering via hooks.

**Primary recommendation:** Build skills in this order: (1) drupal-forms-api (simplest lifecycle, smallest API surface), (2) drupal-config-storage (forms depend on config storage, so this fills the gap), (3) drupal-plugins-blocks (blocks have config forms, uses patterns from 1 and 2), (4) drupal-access-security (cross-cuts all other skills, benefits from completed context).

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| CORE-01 | drupal-forms-api skill covers Form API lifecycle, form altering, submit handlers, validation | Book Ch 2: FormBase/ConfigFormBase, buildForm/validateForm/submitForm, hook_form_alter/hook_form_FORM_ID_alter, form element types, custom submit handlers |
| CORE-02 | drupal-plugins-blocks skill covers block plugins, custom plugin types, plugin discovery | Book Ch 2 (blocks), Ch 7 (custom plugin types): BlockBase, @Block/#[Block], ContainerFactoryPluginInterface, DefaultPluginManager, annotation/attribute discovery |
| CORE-03 | drupal-config-storage skill covers Config API, State API, TempStore, config schemas, with i18n reference file | Book Ch 5 (State API, TempStore), Ch 6 (Config API, config storage, config schema): ConfigFactory, getEditable/get, State service, PrivateTempStore/SharedTempStore, config/schema/*.schema.yml |
| CORE-04 | drupal-access-security skill covers permissions, access handlers, route access, CSRF/XSS prevention | Book Ch 10 (access control), Appendix (XSS/CSRF): permissions.yml, AccessResult, _permission/_custom_access/_csrf_token, EntityAccessControlHandler, Html::escape/Xss::filter |
</phase_requirements>

## Standard Stack

This phase produces Claude Code skill files (markdown + YAML), not executable code. The "stack" is the Drupal APIs the skills teach Claude to generate.

### Core Drupal APIs Covered

| API | Drupal Version | Purpose | Book Source |
|-----|---------------|---------|-------------|
| Form API | D10/D11 | Form lifecycle, validation, submission, altering | Ch 2, Ch 5 |
| Block Plugin API | D10/D11 | Custom block plugins with config forms | Ch 2 |
| Plugin System | D10/D11 | Custom plugin types, DefaultPluginManager, discovery | Ch 7 |
| Config API | D10/D11 | Simple config, ConfigFormBase, config schema | Ch 2, Ch 6 |
| State API | D10/D11 | Key/value state storage | Ch 5 |
| TempStore | D10/D11 | Private and shared temporary storage | Ch 5 |
| Access System | D10/D11 | Permissions, AccessResult, route/entity access | Ch 10 |
| Security | D10/D11 | CSRF tokens, XSS sanitization | Ch 10, Appendix |

### D10 vs D11 Syntax Differences (Phase 2 Specific)

| Feature | D10 Syntax | D11 Syntax (10.2+/11.x) | Impact |
|---------|-----------|------------------------|--------|
| Block plugins | `@Block(id="...", admin_label=@Translation("..."))` | `#[Block(id: "...", admin_label: new TranslatableMarkup("..."))]` | Block skill must show both |
| Custom plugin types | Annotation-based discovery via Doctrine | Attribute-based discovery via PHP native attributes | Custom plugin type skill must show both |
| Translation in plugins | `@Translation("...")` | `new TranslatableMarkup("...")` | All plugin definitions affected |
| Form API | No syntax changes | No syntax changes | Forms are framework-level, identical across D10/D11 |
| Config API | No syntax changes | No syntax changes | Config system is stable across versions |
| Access system | No syntax changes | No syntax changes | Access patterns are stable across versions |

## Architecture Patterns

### Skill Directory Structure (Phase 2)

```
skills/
├── drupal-forms-api/
│   ├── SKILL.md              # <500 lines: form lifecycle, form altering, config forms
│   └── references/
│       └── (none needed -- form elements are in api.drupal.org, out of scope per REQUIREMENTS.md)
├── drupal-plugins-blocks/
│   ├── SKILL.md              # <500 lines: block plugins, custom plugin types, discovery
│   └── references/
│       └── (none needed -- focused on patterns, not element catalog)
├── drupal-config-storage/
│   ├── SKILL.md              # <500 lines: Config API, State API, TempStore, config schema
│   └── references/
│       └── i18n.md            # Configuration translation patterns (CORE-03 requirement)
└── drupal-access-security/
    ├── SKILL.md              # <500 lines: permissions, access handlers, route access, CSRF/XSS
    └── references/
        └── (none needed -- patterns are self-contained)
```

### Pattern: Form API Lifecycle Decision Tree

```markdown
## What kind of form do you need?

**Is it a configuration/settings form?**
YES -> Extend `ConfigFormBase`. Implement `getEditableConfigNames()`, `getFormId()`, `buildForm()`, `submitForm()`.
NO -> Continue below.

**Is it a standalone form (contact, search, custom)?**
YES -> Extend `FormBase`. Implement `getFormId()`, `buildForm()`, `submitForm()`, optionally `validateForm()`.
NO -> Continue below.

**Is it a confirmation form (delete, irreversible action)?**
YES -> Extend `ConfirmFormBase`. Implement `getQuestion()`, `getCancelUrl()`, `submitForm()`.
NO -> You probably need one of the above.
```

### Pattern: Config vs State vs TempStore Decision Tree

```markdown
## Where should you store this data?

**Is it admin-configurable settings (site name, API keys, feature toggles)?**
YES -> Use **Config API**. Stored in config YAML, exportable, requires schema.
NO -> Continue below.

**Is it system state (last cron run, flag, environment marker)?**
YES -> Use **State API**. Key/value store, NOT exportable, NOT for human editing.
NO -> Continue below.

**Is it temporary per-user data (form drafts, wizard progress, locks)?**
YES -> Use **TempStore**. Auto-expires. Use PrivateTempStore for per-user, SharedTempStore for multi-user.
NO -> You probably need Config API or a custom entity.
```

### Pattern: Block Plugin with DI (D10 Annotation + D11 Attribute)

**D10:**
```php
/**
 * @Block(
 *   id = "my_block",
 *   admin_label = @Translation("My Block"),
 * )
 */
class MyBlock extends BlockBase implements ContainerFactoryPluginInterface {
  // DI via create() with 4 params: $configuration, $plugin_id, $plugin_definition, ...services
}
```

**D11.1+:**
```php
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\StringTranslation\TranslatableMarkup;

#[Block(
  id: "my_block",
  admin_label: new TranslatableMarkup("My Block"),
)]
class MyBlock extends BlockBase implements ContainerFactoryPluginInterface {
  // Same DI pattern -- create() signature unchanged
}
```

### Pattern: Custom Plugin Type Boilerplate

A custom plugin type requires three components:
1. **Plugin Manager** -- extends `DefaultPluginManager`, registered as a service
2. **Annotation/Attribute class** -- defines plugin properties
3. **Plugin Interface** -- extends `PluginInspectionInterface` (and often `ContainerFactoryPluginInterface`)

### Anti-Patterns to Avoid

- **Reference doc style for form elements:** Requirements explicitly exclude "exhaustive form element reference" (api.drupal.org covers that). The skill should teach WHEN to use forms, not list every `#type`.
- **Confusing Config API with State API:** The most common mistake. Config is exportable, human-edited. State is environment-specific, code-managed. The skill needs a clear decision tree.
- **Static service calls in form/block classes:** Same pitfall as Phase 1. Forms and blocks MUST use DI via `create()` + constructor.
- **Missing config schema:** Config forms that save to Config API MUST have a config/schema/*.schema.yml file. Without it, config export, validation, and translation fail silently.
- **Block DI signature confusion:** Plugin DI differs from controller DI. Plugin `create()` takes 4 params (`$container, $configuration, $plugin_id, $plugin_definition`), not just `$container`. Constructor must call `parent::__construct($configuration, $plugin_id, $plugin_definition)`.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Config form boilerplate | Manual form + config save | `ConfigFormBase` | Handles editable config names, parent submit, immutable/editable distinction |
| Entity delete confirmation | Manual delete form | `ConfirmFormBase` or `ContentEntityDeleteForm` | Standard UX, handles redirect, messaging |
| Block plugin config storage | Custom config save logic | `blockForm()` + `blockSubmit()` on `BlockBase` | Block config stored automatically by block system |
| Permission discovery | Manual permission arrays | `module_name.permissions.yml` + `permission_callbacks` | Drupal's PermissionHandler scans YAML automatically |
| CSRF token generation | Manual token management | `_csrf_token: 'TRUE'` route requirement | Drupal auto-generates and validates tokens on route links |
| Entity access control | Manual access checks in controllers | `EntityAccessControlHandler` subclass | Integrates with entity system, triggers access hooks |
| Config override per environment | Custom env-switching logic | `$config` overrides in `settings.php` | Standard Drupal pattern for env-specific config |

**Key insight:** Drupal provides base classes (`ConfigFormBase`, `FormBase`, `BlockBase`, `EntityAccessControlHandler`) that handle enormous amounts of boilerplate. Skills must teach Claude to extend these base classes rather than building from scratch.

## Common Pitfalls

### Pitfall 1: Form Validation Without setErrorByName
**What goes wrong:** Claude implements `validateForm()` but uses `drupal_set_message()` or throws exceptions instead of `$form_state->setErrorByName()`.
**Why it happens:** General PHP validation patterns don't match Drupal's form validation flow.
**How to avoid:** Skill must show that `validateForm()` uses `$form_state->setErrorByName('field_name', $this->t('Error message'))` to flag specific form elements. This prevents form submission and highlights the offending element.
**Warning signs:** Any validation that doesn't use `setErrorByName()`.

### Pitfall 2: Wrong DI Signature for Plugins
**What goes wrong:** Claude uses controller-style DI (`create(ContainerInterface $container)`) in block/plugin classes instead of the plugin DI signature with 4 parameters.
**Why it happens:** Controller DI is seen more frequently in training data.
**How to avoid:** Skill must clearly show that `ContainerFactoryPluginInterface::create()` requires `($container, $configuration, $plugin_id, $plugin_definition)`, and the constructor must call `parent::__construct($configuration, $plugin_id, $plugin_definition)`.
**Warning signs:** Plugin `create()` method with only 1 parameter.

### Pitfall 3: Config vs State Confusion
**What goes wrong:** Claude stores environment-specific data (last cron run, API tokens) in Config API, or stores admin settings in State API.
**Why it happens:** Both are key/value stores; the distinction isn't obvious without understanding exportability.
**How to avoid:** Clear decision tree: Config = exportable, human-editable, requires schema. State = environment-specific, code-managed, no schema.
**Warning signs:** `\Drupal::state()->set()` for settings that should be in config form, or `$config->set()` for runtime flags.

### Pitfall 4: Missing Config Schema for Simple Config
**What goes wrong:** Claude creates a `ConfigFormBase` form that saves to config, but forgets the `config/schema/module_name.schema.yml` file.
**Why it happens:** Forms "work" without schema in development; schema is only enforced during config export/import and translation.
**How to avoid:** Skill must pair every `ConfigFormBase` with its config schema file. Schema type is `config_object` for simple config (not `config_entity`).
**Warning signs:** `$this->config('module.settings')->set(...)` without a corresponding schema file.

### Pitfall 5: AccessResult Without Cache Metadata
**What goes wrong:** Claude returns `AccessResult::allowed()` or `AccessResult::forbidden()` without adding cache contexts or tags.
**Why it happens:** Access results are cached, and without proper cache metadata, access decisions become stale.
**How to avoid:** Skill must show `->addCacheContexts(['user.permissions'])` or `->addCacheTags(['node:1'])` on access results. Use `AccessResult::allowedIfHasPermission($account, 'permission')` which handles caching automatically.
**Warning signs:** Bare `AccessResult::allowed()` without `->addCacheContexts()` or `->cachePerPermissions()`.

### Pitfall 6: Using hook_form_alter Without Checking Form ID
**What goes wrong:** Claude implements `hook_form_alter()` without filtering by `$form_id`, accidentally altering all forms on the site.
**Why it happens:** The hook signature doesn't enforce filtering; developers must check manually.
**How to avoid:** Skill must show both options: `hook_form_alter()` with `$form_id` check, OR the more targeted `hook_form_FORM_ID_alter()` which only fires for a specific form.
**Warning signs:** `hook_form_alter()` without any conditional on `$form_id`.

### Pitfall 7: Hardcoded Permission Strings
**What goes wrong:** Claude references a permission string in route requirements but forgets to define it in `module_name.permissions.yml`.
**Why it happens:** The route and permissions file are separate; easy to miss the pairing.
**How to avoid:** Skill must pair every `_permission: 'my custom permission'` in routing.yml with the corresponding permissions.yml entry. This is the same "paired files" pattern from Phase 1.
**Warning signs:** Permission string in routes that doesn't exist in any permissions.yml.

## Code Examples

### Form API -- ConfigFormBase Complete Example

**Route (module_name.routing.yml):**
```yaml
module_name.settings:
  path: '/admin/config/module-name/settings'
  defaults:
    _form: '\Drupal\module_name\Form\SettingsForm'
    _title: 'Module Settings'
  requirements:
    _permission: 'administer site configuration'
  options:
    _admin_route: TRUE
```

**Form class (src/Form/SettingsForm.php):**
```php
namespace Drupal\module_name\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['module_name.settings'];
  }

  public function getFormId() {
    return 'module_name_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('module_name.settings');

    $form['message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message'),
      '#default_value' => $config->get('message'),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('module_name.settings')
      ->set('message', $form_state->getValue('message'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
```

**Config schema (config/schema/module_name.schema.yml):**
```yaml
module_name.settings:
  type: config_object
  label: 'Module Name settings'
  mapping:
    message:
      type: text
      label: 'Message'
```

### Block Plugin -- D10 Annotation with DI

```php
namespace Drupal\module_name\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\module_name\MyService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a custom block.
 *
 * @Block(
 *   id = "module_name_custom_block",
 *   admin_label = @Translation("Custom Block"),
 * )
 */
class CustomBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected $myService;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, MyService $my_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->myService = $my_service;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_name.my_service')
    );
  }

  public function build() {
    return [
      '#markup' => $this->myService->getMessage(),
    ];
  }

}
```

### Block Plugin -- D11.1+ Attribute Syntax

```php
namespace Drupal\module_name\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\module_name\MyService;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[Block(
  id: "module_name_custom_block",
  admin_label: new TranslatableMarkup("Custom Block"),
)]
class CustomBlock extends BlockBase implements ContainerFactoryPluginInterface {
  // Same DI pattern as D10 -- create() and constructor identical
}
```

### Permissions Definition

**module_name.permissions.yml:**
```yaml
administer module_name:
  title: 'Administer Module Name'
  description: 'Perform administration tasks for Module Name.'
  restrict access: true

access module_name content:
  title: 'Access Module Name content'
  description: 'View Module Name content.'
```

### Custom Access Checker

**Route requirement:**
```yaml
module_name.my_page:
  path: '/my-page/{node}'
  defaults:
    _controller: '\Drupal\module_name\Controller\MyController::view'
  requirements:
    _custom_access: '\Drupal\module_name\Access\MyAccessChecker::access'
```

**Access checker class:**
```php
namespace Drupal\module_name\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

class MyAccessChecker {

  public function access(AccountInterface $account, NodeInterface $node = NULL) {
    if (!$node) {
      return AccessResult::neutral();
    }
    return AccessResult::allowedIf($node->getOwnerId() === $account->id())
      ->addCacheContexts(['user'])
      ->addCacheTags(['node:' . $node->id()]);
  }

}
```

### CSRF Protection on Routes

```yaml
module_name.trigger_action:
  path: '/module-name/trigger/{node}'
  defaults:
    _controller: '\Drupal\module_name\Controller\ActionController::trigger'
  requirements:
    _permission: 'access content'
    _csrf_token: 'TRUE'
```

Links to this route are built with `Url::fromRoute()` which automatically appends the CSRF token.

### State API Usage (in a service)

```php
use Drupal\Core\State\StateInterface;

class MyService {

  protected $state;

  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  public function getLastRun() {
    return $this->state->get('module_name.last_run', 0);
  }

  public function setLastRun($timestamp) {
    $this->state->set('module_name.last_run', $timestamp);
  }

}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `@Block(...)` annotation | `#[Block(...)]` PHP attribute | D10.2.0 | Block skills must show both syntaxes |
| Custom annotation classes | Custom PHP attribute classes | D10.2.0 | Custom plugin type skill must show both |
| `variable_get()`/`variable_set()` | Config API + State API | D8+ | D7 pattern still in Claude's training data |
| `hook_permission()` | `module_name.permissions.yml` | D8+ | YAML-based permission definition |
| `drupal_set_message()` | `\Drupal::messenger()->addMessage()` | D8.5+ | Deprecated function, Claude may still generate old form |
| Manual CSRF tokens | `_csrf_token: 'TRUE'` route requirement | D8+ | Declarative CSRF protection |

**Deprecated/outdated:**
- `variable_get()`/`variable_set()`: D7 State-like API, replaced by Config API and State API
- `drupal_set_message()`: Deprecated in D8.5, use Messenger service
- `hook_permission()`: D7 hook, replaced by YAML permissions file
- `drupal_form_submit()`: D7 programmatic form submission, use `\Drupal::formBuilder()->submitForm()` in D10+
- `form_set_error()`: D7 validation, replaced by `$form_state->setErrorByName()`

## Open Questions

1. **i18n reference file scope for drupal-config-storage**
   - What we know: CORE-03 requires "with i18n reference file." The book covers config translation in Ch 13.
   - What's unclear: How deep to go on config translation patterns. The skill body is already dense with Config API + State API + TempStore.
   - Recommendation: The i18n reference file should cover config translation basics -- `config/schema/*.schema.yml` `translatable: true` flag, translation override API, and config language negotiation. Keep it focused on how config schema enables translation rather than full i18n coverage.

2. **Custom plugin type annotation vs attribute syntax**
   - What we know: The book shows custom plugin types with annotation-based discovery. D11 supports attribute-based discovery for custom types as well.
   - What's unclear: Exact boilerplate for custom attribute classes (the book predates D11 attributes).
   - Recommendation: Show both patterns. For D11, the annotation class becomes a PHP attribute class extending `\Drupal\Component\Plugin\Attribute\Plugin`. The manager's constructor uses `AttributeClassDiscovery` instead of `AnnotatedClassDiscovery` (or the dual `AttributeBridgeDecorator` for backward compatibility).

3. **Block access method**
   - What we know: BlockBase has a `blockAccess()` method that can be overridden for custom access logic on blocks.
   - What's unclear: How prominently to feature this vs the general access control patterns.
   - Recommendation: Include `blockAccess()` in the drupal-plugins-blocks skill as a brief section, and cross-reference drupal-access-security for the full access system.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | skill-creator eval loop (anthropics/skills) |
| Config file | evals/evals.json per skill directory |
| Quick run command | `Skill(skill="skill-creator", args="eval <skill-dir>")` |
| Full suite command | `python -m scripts.aggregate_benchmark` |

### Phase Requirements -> Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| CORE-01 | Forms skill produces correct Form API lifecycle code | eval | Eval: "Create a settings form for module X" with-skill vs baseline | Wave 0 |
| CORE-02 | Plugins/blocks skill generates correct block plugin | eval | Eval: "Create a custom block plugin" with-skill vs baseline | Wave 0 |
| CORE-03 | Config/storage skill produces correct Config/State/TempStore patterns | eval | Eval: "Store a setting with config schema" with-skill vs baseline | Wave 0 |
| CORE-04 | Access/security skill generates correct permissions and access handlers | eval | Eval: "Add permission-based access to a route" with-skill vs baseline | Wave 0 |
| SKIL-01 | Each skill follows SKILL.md anatomy | manual-only | Visual inspection: frontmatter, <500 lines, references/ dir | N/A |
| SKIL-02 | Decision-guide format | manual-only | Review for decision trees | N/A |
| SKIL-03 | Wrong-way callouts present | manual-only | Grep for "WRONG:" markers | N/A |
| SKIL-04 | Complete file ecosystems | eval | Check output has paired PHP+YAML | Wave 0 |
| SKIL-05 | D10/D11 dual syntax | manual-only | Inspect code examples | N/A |
| SKIL-06 | Self-contained directory | manual-only | Verify no external deps | N/A |
| SKIL-07 | Cross-references degrade gracefully | manual-only | Check "if installed" phrasing | N/A |

### Sampling Rate
- **Per task commit:** Manual review of skill structure + single eval run
- **Per wave merge:** Full eval suite for all four skills
- **Phase gate:** All four skills pass skill-creator eval showing improvement over baseline

### Wave 0 Gaps
- [ ] `evals/evals.json` for drupal-forms-api -- eval prompts for form creation and altering
- [ ] `evals/evals.json` for drupal-plugins-blocks -- eval prompts for block and custom plugin type creation
- [ ] `evals/evals.json` for drupal-config-storage -- eval prompts for config/state/tempstore usage
- [ ] `evals/evals.json` for drupal-access-security -- eval prompts for permission and access control

## Sources

### Primary (HIGH confidence)
- Book: "Drupal 10 Module Development" (Sipos, 4th ed, 2023) -- Ch 2 (Form API, blocks), Ch 5 (State API, TempStore), Ch 6 (Config API), Ch 7 (custom plugin types), Ch 10 (access control), Appendix (XSS/CSRF)
- [Drupal.org: Attribute-based plugins](https://www.drupal.org/docs/drupal-apis/plugin-api/attribute-based-plugins) -- D11 Block attribute syntax verified
- [Drupal.org: Plugin implementations should use PHP attributes](https://www.drupal.org/node/3395575) -- Attribute conversion guide
- [Drupal.org: Configuration schema/metadata](https://www.drupal.org/docs/drupal-apis/configuration-api/configuration-schemametadata) -- Config schema format verified
- [Drupal.org: Defining and using configuration](https://www.drupal.org/docs/develop/creating-modules/defining-and-using-your-own-configuration-in-drupal) -- Simple config patterns
- Phase 1 completed skills (drupal-module-scaffold, drupal-routing-controllers, drupal-entities-fields) -- Established patterns for skill anatomy, cross-references, wrong-way callouts

### Secondary (MEDIUM confidence)
- [Drupal.org: Create a custom block plugin](https://www.drupal.org/docs/creating-modules/creating-custom-blocks/create-a-custom-block-plugin) -- Block plugin patterns
- [Drupalize.Me: PHP Attributes for Drupal Plugins](https://drupalize.me/blog/php-attributes-drupal-plugins) -- Attribute conversion examples
- [Drupal API: Configuration API group](https://api.drupal.org/api/drupal/core!core.api.php/group/config_api/11.x) -- Config API reference

### Tertiary (LOW confidence)
- Custom attribute class boilerplate for custom plugin types in D11 -- inferred from Block attribute pattern, needs verification against core source during skill drafting

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - Book content read directly, Drupal APIs well-documented, Phase 1 patterns established
- Architecture: HIGH - Skill anatomy proven in Phase 1, decision-guide format template exists
- Pitfalls: HIGH - Common mistakes verified through book examples and Phase 1 experience
- D11 attribute syntax for blocks: HIGH - Verified via Drupal.org official docs
- D11 custom plugin type attributes: MEDIUM - Inferred from standard pattern, needs verification

**Research date:** 2026-03-05
**Valid until:** 2026-04-05 (stable domain, book content fixed, D11 API stable)

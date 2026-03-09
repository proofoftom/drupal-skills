# Phase 14: Module Foundation - Research

**Researched:** 2026-03-08
**Domain:** Drupal custom content entity types, module scaffolding, CRUD routing, forms, and configuration
**Confidence:** HIGH

## Summary

Phase 14 builds the `group_ai_pm` Drupal module from scratch with two custom content entities (Project and Task), full CRUD routes via entity route providers, entity forms, an entity list builder with sortable columns, and a ConfigFormBase settings page. This is a well-trodden Drupal pattern with excellent coverage in existing skills (drupal-module-scaffold, drupal-entities-fields, drupal-routing-controllers, drupal-forms-api, drupal-config-storage). The module must declare Group and AI module dependencies in `.info.yml` but does NOT integrate with them yet (that is Phase 15).

The module lives at `modules/group_ai_pm/` in the repo root, separate from the plugin packaging. It must be self-contained and installable via `drush en group_ai_pm -y` on a Drupal 10 site that has Group and AI modules present. The entities need proper `EntityOwnerTrait` for owner tracking, `EntityChangedTrait` for timestamps, `list_string` base fields for status/priority, `entity_reference` for project-task relationships, and `datetime` for due dates.

This phase also requires generating a without-plugin baseline (EVAL-02) to establish what Claude produces without skill guidance, for later comparison with the plugin-assisted code.

**Primary recommendation:** Follow standard Drupal content entity patterns exactly as documented in the existing drupal-entities-fields and drupal-module-scaffold skills. Use `AdminHtmlRouteProvider` for auto-generated CRUD routes. Use `ContentEntityForm` with a custom `save()` override for entity forms. Use `ConfigFormBase` for the settings form. Keep the module dependency-aware but not dependency-coupled -- declare Group and AI in `.info.yml` dependencies but write no integration code yet.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| SCAF-01 | Module skeleton with .info.yml declaring Group and AI module dependencies | Standard drupal-module-scaffold pattern; .info.yml with `dependencies: [group:group, ai:ai, ai_agents:ai_agents]` |
| SCAF-02 | Composer.json with pinned versions for drupal/group, drupal/ai, drupal/ai_agents | Standard composer.json for Drupal modules; pin `drupal/group: "^3.3"`, `drupal/ai: "^1.2"`, `drupal/ai_agents: "^1.2"` |
| SCAF-03 | Module directory structure follows PSR-4 with src/, config/, templates/ directories | Standard PSR-4 layout per drupal-module-scaffold skill |
| ENTY-01 | Project custom content entity with title, description, status, and owner base fields | ContentEntityBase with EntityOwnerTrait, EntityChangedTrait; list_string for status |
| ENTY-02 | Task custom content entity with title, description, status, priority, assignee, due date, and project reference fields | ContentEntityBase with entity_reference to Project and User, datetime for due_date, list_string for status/priority |
| ENTY-03 | Entity form handlers for Project and Task with proper validation | ContentEntityForm extending with custom save() for messages/redirect |
| ENTY-04 | Entity list builders for Project and Task with sortable columns | EntityListBuilder with buildHeader()/buildRow() and tableSort() in load() |
| ROUTE-01 | Entity CRUD routes via entity route providers for Project and Task | AdminHtmlRouteProvider handles all CRUD routes from links definition |
| ROUTE-02 | Custom dashboard controller showing project overview within a group | ControllerBase with DI; returns render array listing projects with task counts |
| ROUTE-03 | Module settings form (ConfigFormBase) with config schema for default statuses and AI provider | ConfigFormBase + config/install/*.settings.yml + config/schema/*.schema.yml |
| ROUTE-04 | Entity form classes with proper form validation and submit handlers | Covered by ENTY-03; validation via validateForm() or entity-level constraints |
| EVAL-02 | Without-plugin baseline generated per phase for comparison | Headless `claude -p` with haiku, no plugin; generates baseline code for grading |
</phase_requirements>

## Standard Stack

### Core

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Drupal Core | ^10.4 \|\| ^11 | Base CMS framework | All contrib module deps require ^10.4 minimum (AI module bottleneck) |
| drupal/group | ^3.3 | Group entity framework | Declared dependency; not used until Phase 15 |
| drupal/ai | ^1.2 | AI abstraction layer | Declared dependency; not used until Phase 15 |
| drupal/ai_agents | ^1.2 | Agent framework | Declared dependency; not used until Phase 15 |

### Supporting

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| drupal/key | ^1.18 | API key storage | Required by drupal/ai; transitive dependency |

### Drupal Core APIs Used (no additional install)

| API | Purpose | Notes |
|-----|---------|-------|
| Entity API | ContentEntityBase, ContentEntityForm, EntityListBuilder | Core of this phase |
| Form API | ConfigFormBase, FormStateInterface | Settings form |
| Config API | ConfigFactoryInterface, config/install, config/schema | Default settings and schema |
| Routing | AdminHtmlRouteProvider, .routing.yml | CRUD routes + custom dashboard |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Custom content entities | Node types (content types) | Nodes add unwanted UI complexity (revisions, menu links, URL aliases). Custom entities are cleaner for non-content data like tasks. Use custom entities. |
| AdminHtmlRouteProvider | Manual .routing.yml entries | Manual routes are error-prone and duplicate what the provider auto-generates. Use AdminHtmlRouteProvider. |
| ContentEntityForm | Custom FormBase | ContentEntityForm auto-builds forms from baseFieldDefinitions. Only override save(). Use ContentEntityForm. |
| EntityListBuilder | Views for listings | EntityListBuilder is simpler for admin listings. Views integration comes in Phase 16. Use EntityListBuilder now. |

**Installation (for development/testing):**
```bash
# From a Drupal project root with ddev
ddev composer require 'drupal/group:^3.3' 'drupal/ai:^1.2' 'drupal/ai_agents:^1.2'
ddev drush en group ai ai_agents -y

# Copy or symlink module
cp -r /path/to/drupal-skills/modules/group_ai_pm web/modules/custom/
ddev drush en group_ai_pm -y
```

## Architecture Patterns

### Recommended Module Structure

```
modules/group_ai_pm/
  group_ai_pm.info.yml              # Module declaration with dependencies
  group_ai_pm.module                # Hook implementations (hook_theme only for now)
  group_ai_pm.routing.yml           # Custom routes (dashboard, settings)
  group_ai_pm.permissions.yml       # Custom permissions
  group_ai_pm.links.menu.yml        # Admin menu links
  group_ai_pm.links.task.yml        # Local task tabs (if needed)
  group_ai_pm.links.action.yml      # Action links (Add Project, Add Task)
  composer.json                     # Module-level composer metadata
  config/
    install/
      group_ai_pm.settings.yml     # Default settings values
    schema/
      group_ai_pm.schema.yml       # Config schema (REQUIRED)
  src/
    Entity/
      Project.php                   # Content entity: Project
      ProjectInterface.php          # Interface for Project
      Task.php                      # Content entity: Task
      TaskInterface.php             # Interface for Task
    Form/
      ProjectForm.php               # ContentEntityForm for Project
      TaskForm.php                  # ContentEntityForm for Task
      GroupAiPmSettingsForm.php     # ConfigFormBase for module settings
    Controller/
      ProjectDashboardController.php # Custom dashboard controller
    ProjectListBuilder.php          # EntityListBuilder for Project
    TaskListBuilder.php             # EntityListBuilder for Task
```

### Pattern 1: Content Entity with Owner and Timestamps

**What:** Custom content entity extending ContentEntityBase with EntityOwnerTrait and EntityChangedTrait.
**When to use:** Every user-created entity that needs ownership tracking and modification timestamps.
**Example:**

```php
// Source: Drupal Core Entity API + drupal-entities-fields skill
namespace Drupal\group_ai_pm\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the Project entity.
 *
 * @ContentEntityType(
 *   id = "project",
 *   label = @Translation("Project"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\group_ai_pm\ProjectListBuilder",
 *     "form" = {
 *       "default" = "Drupal\group_ai_pm\Form\ProjectForm",
 *       "add" = "Drupal\group_ai_pm\Form\ProjectForm",
 *       "edit" = "Drupal\group_ai_pm\Form\ProjectForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *   },
 *   base_table = "group_ai_pm_project",
 *   admin_permission = "administer group_ai_pm",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/project/{project}",
 *     "add-form" = "/admin/content/project/add",
 *     "edit-form" = "/admin/content/project/{project}/edit",
 *     "delete-form" = "/admin/content/project/{project}/delete",
 *     "collection" = "/admin/content/project",
 *   },
 * )
 */
class Project extends ContentEntityBase implements ProjectInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setRequired(TRUE)
      ->setDefaultValue('planning')
      ->setSettings([
        'allowed_values' => [
          'planning' => 'Planning',
          'active' => 'Active',
          'review' => 'Review',
          'completed' => 'Completed',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time the project was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time the project was last edited.'));

    // Configure the owner field display.
    $fields['uid']
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
```

### Pattern 2: Entity Reference Base Field (Task -> Project)

**What:** An entity_reference base field linking Task to Project entities.
**When to use:** When one entity needs to reference another entity.
**Example:**

```php
// Source: Drupal Entity API, drupal-entities-fields skill
$fields['project'] = BaseFieldDefinition::create('entity_reference')
  ->setLabel(t('Project'))
  ->setDescription(t('The project this task belongs to.'))
  ->setSetting('target_type', 'project')
  ->setSetting('handler', 'default')
  ->setDisplayOptions('view', [
    'label' => 'above',
    'type' => 'entity_reference_label',
    'weight' => -3,
  ])
  ->setDisplayOptions('form', [
    'type' => 'entity_reference_autocomplete',
    'weight' => -3,
    'settings' => [
      'match_operator' => 'CONTAINS',
      'size' => '60',
      'placeholder' => '',
    ],
  ])
  ->setDisplayConfigurable('form', TRUE)
  ->setDisplayConfigurable('view', TRUE);
```

### Pattern 3: Datetime Base Field (Due Date)

**What:** A datetime base field for tracking due dates.
**When to use:** When an entity needs a date/time field.
**Example:**

```php
// Source: Drupal Core datetime module
$fields['due_date'] = BaseFieldDefinition::create('datetime')
  ->setLabel(t('Due date'))
  ->setDescription(t('The date when this task is due.'))
  ->setSettings([
    'datetime_type' => 'date',
  ])
  ->setDisplayOptions('view', [
    'label' => 'above',
    'type' => 'datetime_default',
    'weight' => 2,
  ])
  ->setDisplayOptions('form', [
    'type' => 'datetime_default',
    'weight' => 2,
  ])
  ->setDisplayConfigurable('form', TRUE)
  ->setDisplayConfigurable('view', TRUE);
```

### Pattern 4: EntityListBuilder with Sortable Columns

**What:** Custom list builder that renders entities in a sortable admin table.
**When to use:** Every entity that needs an admin collection page.
**Example:**

```php
// Source: Drupal Core EntityListBuilder API
namespace Drupal\group_ai_pm;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProjectListBuilder extends EntityListBuilder {

  protected $dateFormatter;

  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_type, $storage);
    $this->dateFormatter = $date_formatter;
  }

  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter')
    );
  }

  public function buildHeader() {
    $header['id'] = [
      'data' => $this->t('ID'),
      'field' => 'id',
      'specifier' => 'id',
    ];
    $header['title'] = [
      'data' => $this->t('Title'),
      'field' => 'title',
      'specifier' => 'title',
    ];
    $header['status'] = [
      'data' => $this->t('Status'),
      'field' => 'status',
      'specifier' => 'status',
    ];
    $header['created'] = [
      'data' => $this->t('Created'),
      'field' => 'created',
      'specifier' => 'created',
      'sort' => 'desc',
    ];
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['title'] = $entity->toLink();
    $row['status'] = $entity->get('status')->value;
    $row['created'] = $this->dateFormatter->format($entity->get('created')->value, 'short');
    return $row + parent::buildRow($entity);
  }

  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->accessCheck(TRUE)
      ->pager(50);
    $header = $this->buildHeader();
    $query->tableSort($header);
    return $query->execute();
  }

}
```

### Pattern 5: ContentEntityForm with Custom Save

**What:** Entity form extending ContentEntityForm that overrides save() for status messages and redirect.
**When to use:** Every entity that needs create/edit forms.
**Example:**

```php
// Source: Drupal Core Entity API
namespace Drupal\group_ai_pm\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class ProjectForm extends ContentEntityForm {

  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $status = parent::save($form, $form_state);

    if ($status === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Project %label has been created.', [
        '%label' => $entity->label(),
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('Project %label has been updated.', [
        '%label' => $entity->label(),
      ]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $status;
  }

}
```

### Pattern 6: ConfigFormBase Settings Form

**What:** Module settings form using ConfigFormBase with config schema.
**When to use:** When a module needs admin-configurable settings.
**Example (3-file ecosystem):**

```php
// src/Form/GroupAiPmSettingsForm.php
namespace Drupal\group_ai_pm\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class GroupAiPmSettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['group_ai_pm.settings'];
  }

  public function getFormId() {
    return 'group_ai_pm_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('group_ai_pm.settings');

    $form['default_project_status'] = [
      '#type' => 'select',
      '#title' => $this->t('Default project status'),
      '#options' => [
        'planning' => $this->t('Planning'),
        'active' => $this->t('Active'),
      ],
      '#default_value' => $config->get('default_project_status'),
    ];

    $form['default_task_status'] = [
      '#type' => 'select',
      '#title' => $this->t('Default task status'),
      '#options' => [
        'todo' => $this->t('To Do'),
        'in_progress' => $this->t('In Progress'),
      ],
      '#default_value' => $config->get('default_task_status'),
    ];

    $form['ai_provider'] = [
      '#type' => 'textfield',
      '#title' => $this->t('AI provider'),
      '#description' => $this->t('Machine name of the AI provider to use.'),
      '#default_value' => $config->get('ai_provider'),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('group_ai_pm.settings')
      ->set('default_project_status', $form_state->getValue('default_project_status'))
      ->set('default_task_status', $form_state->getValue('default_task_status'))
      ->set('ai_provider', $form_state->getValue('ai_provider'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
```

```yaml
# config/install/group_ai_pm.settings.yml
default_project_status: 'planning'
default_task_status: 'todo'
ai_provider: ''
```

```yaml
# config/schema/group_ai_pm.schema.yml
group_ai_pm.settings:
  type: config_object
  label: 'Group AI PM settings'
  mapping:
    default_project_status:
      type: string
      label: 'Default project status'
    default_task_status:
      type: string
      label: 'Default task status'
    ai_provider:
      type: string
      label: 'AI provider machine name'
```

### Anti-Patterns to Avoid

- **Using `GroupContent` or `addContent()` API names**: Group 3.x renamed these to `GroupRelationship` and `addRelationship()`. However, Phase 14 does NOT implement Group integration -- just declare the dependency. The actual Group API usage comes in Phase 15.
- **Using `\Drupal::service()` in classes**: Always inject via `create()` + constructor. Static calls are only acceptable in `.module` files.
- **Hand-writing CRUD routes when AdminHtmlRouteProvider is configured**: The route provider generates all CRUD routes from the `links` definition. Only add custom routes for non-CRUD pages (dashboard, settings).
- **Skipping config schema for ConfigFormBase**: The settings form appears to work without schema, but config export/import fails silently. Always create `config/schema/*.schema.yml`.
- **Omitting `config/install/*.settings.yml`**: Without default values, the config object does not exist until someone saves the form. Code reading config before that gets NULL.
- **Using `drupal_set_message()`**: Removed in Drupal 11. Use `$this->messenger()->addStatus()`.
- **Making AI/Group integration code in this phase**: Phase 14 declares dependencies only. Keep the module functional without any Group or AI API calls so it can be tested standalone.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Entity CRUD routes | Manual .routing.yml entries for add/edit/delete/view/collection | `AdminHtmlRouteProvider` handler | Generates 5+ routes from the `links` definition; hand-writing duplicates and introduces bugs |
| Entity form building | Custom FormBase with manual field rendering | `ContentEntityForm` extending | Auto-builds form from baseFieldDefinitions; only override save() for messages/redirect |
| Entity deletion form | Custom ConfirmFormBase | `ContentEntityDeleteForm` | Standard delete confirmation with proper entity cleanup |
| Owner field | Manual uid BaseFieldDefinition | `EntityOwnerTrait::ownerBaseFieldDefinitions()` | Includes default value callback, proper display settings, interface methods |
| Changed timestamp | Manual timestamp update logic | `EntityChangedTrait` | Automatically managed by entity system on save |
| Created timestamp | Manual timestamp setting | `BaseFieldDefinition::create('created')` | Auto-set on entity creation |
| Config form submit button | Manual submit button in buildForm | `parent::buildForm()` call | ConfigFormBase adds the submit button and "saved" message automatically |

**Key insight:** Drupal's Entity API has extensive built-in functionality. ContentEntityForm, AdminHtmlRouteProvider, EntityListBuilder, EntityOwnerTrait, and EntityChangedTrait handle 80% of the boilerplate. Override only what you need (save messages, custom columns, validation).

## Common Pitfalls

### Pitfall 1: Missing Entity Keys in Annotation

**What goes wrong:** Entity type definition omits `owner` from entity_keys when using EntityOwnerTrait, causing "Call to undefined method" errors.
**Why it happens:** EntityOwnerTrait requires the `owner` key mapped to the uid field name.
**How to avoid:** Always include `"owner" = "uid"` in entity_keys when using EntityOwnerTrait.
**Warning signs:** Fatal error on entity create/load mentioning `getOwner()` or `getOwnerId()`.

### Pitfall 2: Table Name Collisions

**What goes wrong:** Using generic `base_table` names like `project` or `task` that collide with other modules.
**Why it happens:** Table names are global in Drupal's database.
**How to avoid:** Prefix base table names with the module name: `group_ai_pm_project`, `group_ai_pm_task`.
**Warning signs:** Database errors on module install about existing tables.

### Pitfall 3: Missing Display Options on Base Fields

**What goes wrong:** Base fields are defined but do not appear in entity forms or view displays.
**Why it happens:** Fields without `setDisplayOptions('form', ...)` are not included in the auto-generated ContentEntityForm. Fields without `setDisplayOptions('view', ...)` are not shown on entity view pages.
**How to avoid:** Always set both `form` and `view` display options on every field that should be user-visible. Use `setDisplayConfigurable('form', TRUE)` and `setDisplayConfigurable('view', TRUE)` to allow admin customization.
**Warning signs:** Entity form is missing expected fields; entity view page shows nothing.

### Pitfall 4: EntityListBuilder getEntityIds() Not Using tableSort

**What goes wrong:** List page renders a table with header columns but clicking headers does nothing (no sorting).
**Why it happens:** The default `getEntityIds()` uses a simple query without `tableSort()`. Must override it.
**How to avoid:** Override `getEntityIds()` and call `$query->tableSort($this->buildHeader())` on the entity query. Headers must use the `specifier` key for sortable columns.
**Warning signs:** Table headers are plain text instead of clickable sort links.

### Pitfall 5: Forgetting to Declare datetime Module Dependency

**What goes wrong:** Task entity with a `datetime` base field causes fatal errors on sites without the datetime module enabled.
**Why it happens:** The `datetime` field type is provided by Drupal core's `datetime` module, which is not always enabled.
**How to avoid:** Add `drupal:datetime` to the module's `.info.yml` dependencies.
**Warning signs:** "Field type 'datetime' is unknown" or similar plugin not found error on module install.

### Pitfall 6: Settings Form Route Without _admin_route Option

**What goes wrong:** Settings form renders with the frontend theme instead of the admin theme.
**Why it happens:** Routes under `/admin/config/` do NOT automatically use admin theme unless `_admin_route: TRUE` is set.
**How to avoid:** Always add `options: { _admin_route: TRUE }` to admin-facing routes.
**Warning signs:** Settings page looks wrong, uses frontend theme styling.

### Pitfall 7: Config Schema Type Mismatch

**What goes wrong:** Config values are cast to wrong types after export/import. Boolean becomes string "1", integer becomes "10" as string.
**Why it happens:** Schema types control casting. If schema says `type: string` but the value should be integer, export/import produces strings.
**How to avoid:** Match schema types exactly to intended data types: `integer` for numbers, `boolean` for true/false, `string` for text.
**Warning signs:** Config values work in UI but break programmatically after config import.

## Code Examples

### group_ai_pm.info.yml

```yaml
# Source: drupal-module-scaffold skill + REQUIREMENTS.md
name: 'Group AI Project Management'
type: module
description: 'AI-enhanced project management within Drupal Groups.'
core_version_requirement: ^10 || ^11
package: 'Group'
configure: group_ai_pm.settings
dependencies:
  - drupal:datetime
  - group:group
  - ai:ai
  - ai_agents:ai_agents
```

### group_ai_pm.routing.yml (custom routes only)

```yaml
# Source: drupal-routing-controllers and drupal-forms-api skills
# Note: Entity CRUD routes are auto-generated by AdminHtmlRouteProvider.
# Only define custom routes here.

group_ai_pm.settings:
  path: '/admin/config/group_ai_pm/settings'
  defaults:
    _form: '\Drupal\group_ai_pm\Form\GroupAiPmSettingsForm'
    _title: 'Group AI PM Settings'
  requirements:
    _permission: 'administer group_ai_pm'
  options:
    _admin_route: TRUE

group_ai_pm.dashboard:
  path: '/admin/content/project-dashboard'
  defaults:
    _controller: '\Drupal\group_ai_pm\Controller\ProjectDashboardController::overview'
    _title: 'Project Dashboard'
  requirements:
    _permission: 'access group_ai_pm dashboard'
  options:
    _admin_route: TRUE
```

### group_ai_pm.permissions.yml

```yaml
# Source: drupal-access-security skill
administer group_ai_pm:
  title: 'Administer Group AI PM'
  description: 'Manage Group AI PM settings and all entities.'
  restrict access: true

access group_ai_pm dashboard:
  title: 'Access project dashboard'
  description: 'View the project dashboard overview page.'
```

### group_ai_pm.links.menu.yml

```yaml
# Source: drupal-entities-fields skill (entity listing pattern)
entity.project.collection:
  title: 'Projects'
  route_name: entity.project.collection
  description: 'List all projects'
  parent: system.admin_content
  weight: 10

entity.task.collection:
  title: 'Tasks'
  route_name: entity.task.collection
  description: 'List all tasks'
  parent: system.admin_content
  weight: 11

group_ai_pm.settings:
  title: 'Group AI PM'
  route_name: group_ai_pm.settings
  description: 'Configure Group AI Project Management settings.'
  parent: system.admin_config_system
  weight: 50
```

### group_ai_pm.links.action.yml

```yaml
# Source: drupal-entities-fields skill
entity.project.add_form:
  route_name: entity.project.add_form
  title: 'Add Project'
  appears_on:
    - entity.project.collection

entity.task.add_form:
  route_name: entity.task.add_form
  title: 'Add Task'
  appears_on:
    - entity.task.collection
```

### composer.json (module-level)

```json
{
  "name": "drupal/group_ai_pm",
  "type": "drupal-module",
  "description": "AI-enhanced project management within Drupal Groups.",
  "license": "GPL-2.0-or-later",
  "require": {
    "drupal/group": "^3.3",
    "drupal/ai": "^1.2",
    "drupal/ai_agents": "^1.2"
  },
  "minimum-stability": "stable"
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `@Translation("text")` annotations | `new TranslatableMarkup("text")` attributes | Drupal 11.1 | D10 still uses annotations; both syntax shown in skill. Target D10 annotation with D11 attribute as fallback per CLAUDE.md |
| `drupal_set_message()` | `$this->messenger()->addStatus()` | Drupal 8.5 (removed in D11) | Must use messenger service in all new code |
| `hook_menu()` for routes | `.routing.yml` files | Drupal 8 | D7 legacy; routing YAML is the only approach |
| Manual entity forms | `ContentEntityForm` auto-building | Drupal 8 | Entity forms auto-build from baseFieldDefinitions display options |
| `EntityOwnerInterface` manual impl | `EntityOwnerTrait` | Drupal 9 | Trait provides standard owner field + interface methods |
| `GroupContent` / `addContent()` | `GroupRelationship` / `addRelationship()` | Group 3.0 | Phase 14 only declares dependency; Phase 15 uses the 3.x API |

**Deprecated/outdated:**
- `variable_get()` / `variable_set()`: Drupal 7 only. Use Config API or State API.
- `drupal_set_message()`: Removed in Drupal 11. Use `$this->messenger()->addMessage()`.
- `core: 8.x` in .info.yml: Legacy format. Use `core_version_requirement: ^10 || ^11`.

## Validation Architecture

### Test Framework

| Property | Value |
|----------|-------|
| Framework | PHPUnit (Drupal integrated, via drupal/core-dev) |
| Config file | `phpunit.xml` or `phpunit.xml.dist` at Drupal root (not in module) |
| Quick run command | `ddev exec phpunit --group group_ai_pm --filter ProjectEntityTest` |
| Full suite command | `ddev exec phpunit --group group_ai_pm` |

### Phase Requirements -> Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| SCAF-01 | Module installs with dependencies present | smoke | `ddev drush en group_ai_pm -y && ddev drush cr` | Wave 0 (inline) |
| SCAF-02 | composer.json has correct dependency versions | manual | Inspect `modules/group_ai_pm/composer.json` | N/A |
| SCAF-03 | PSR-4 directory structure correct | manual | `ls -R modules/group_ai_pm/src/` | N/A |
| ENTY-01 | Project entity CRUD works | smoke | `ddev drush entity:create project --title="Test"` (Phase 17 adds kernel test) | Wave 0 |
| ENTY-02 | Task entity CRUD works | smoke | `ddev drush entity:create task --title="Test"` (Phase 17 adds kernel test) | Wave 0 |
| ENTY-03 | Entity forms render and submit | smoke | Manual browser check or `curl` admin form pages | Wave 0 |
| ENTY-04 | List builders show sortable columns | smoke | `curl -s /admin/content/project` \| grep "sortable" | Wave 0 |
| ROUTE-01 | CRUD routes exist | smoke | `ddev drush route:list --path=/admin/content/project` | Wave 0 |
| ROUTE-02 | Dashboard controller renders | smoke | `curl -s /admin/content/project-dashboard` returns 200 | Wave 0 |
| ROUTE-03 | Settings form saves config | smoke | Submit form + `ddev drush config:get group_ai_pm.settings` | Wave 0 |
| ROUTE-04 | Form validation prevents bad input | smoke | Submit form with invalid data, verify error | Wave 0 |
| EVAL-02 | Baseline code generated | manual | Run headless baseline script | N/A |

### Sampling Rate

- **Per task commit:** `ddev drush en group_ai_pm -y && ddev drush cr` (module installs without error)
- **Per wave merge:** Full smoke test: install module + create entities + check list pages + submit settings form
- **Phase gate:** All smoke tests green + `phpcs --standard=Drupal,DrupalPractice modules/group_ai_pm/`

### Wave 0 Gaps

- [ ] `modules/group_ai_pm/` -- entire module directory (does not exist yet)
- [ ] Drupal test environment with Group + AI modules -- need ddev setup with contrib modules
- [ ] phpcs configuration -- need `drupal/coder` installed in test environment
- [ ] Baseline eval script -- need `eval/v3/baseline-phase14.sh` for EVAL-02

## Open Questions

1. **Entity link paths: `/admin/content/` vs `/admin/structure/`?**
   - What we know: Content entities typically go under `/admin/content/`; config entities go under `/admin/structure/`. Projects and Tasks are content entities.
   - What's unclear: Whether to use `/admin/content/project` or `/project/{project}` (non-admin paths) since in Phase 15 these entities will be group-scoped and may need non-admin paths.
   - Recommendation: Use `/admin/content/project` for now (standard admin paths). Phase 15 can add group-scoped routes that override the admin paths for group members.

2. **Task entity: separate entity type or bundle of a shared entity?**
   - What we know: Requirements specify Project and Task as separate entities (ENTY-01, ENTY-02). They have different base fields.
   - What's unclear: Could they share a common base entity with bundles?
   - Recommendation: Keep them as separate entity types per requirements. They have sufficiently different fields and behaviors to justify separate types.

3. **Module machine name namespace: `project` and `task` vs `group_ai_pm_project` and `group_ai_pm_task`?**
   - What we know: Entity type IDs are global. Short names like `project` risk collisions with other modules.
   - What's unclear: Whether the long prefix makes entity references unwieldy.
   - Recommendation: Use short but descriptive IDs: `project` and `task`. Prefix base table names with `group_ai_pm_` to avoid table collisions. The entity type ID namespace is less crowded than database tables, and shorter IDs make code more readable. If collisions are a concern, use `gapm_project` / `gapm_task` as a compromise.

4. **EVAL-02 baseline: what prompt to use?**
   - What we know: The without-plugin baseline needs a natural development prompt that describes Phase 14's scope without referencing specific skills.
   - What's unclear: Exact prompt wording.
   - Recommendation: "Create a Drupal 10 module called group_ai_pm with Project and Task custom content entities, admin CRUD UI with sortable list pages, and a settings form at /admin/config/group_ai_pm/settings. The module should depend on the Group and AI modules. Create all files in /tmp/d10-gapm-baseline/web/modules/custom/group_ai_pm/"

## Sources

### Primary (HIGH confidence)

- drupal-entities-fields SKILL.md -- Content entity patterns, base field definitions, entity handlers, D10/D11 syntax
- drupal-module-scaffold SKILL.md -- .info.yml format, PSR-4 structure, .module file patterns
- drupal-routing-controllers SKILL.md -- .routing.yml, controllers, service DI, route access
- drupal-forms-api SKILL.md -- ConfigFormBase, form lifecycle, validation, form alter
- drupal-config-storage SKILL.md -- Config API, config/install, config/schema, State API
- [Drupal Entity API: Creating a custom content entity](https://www.drupal.org/docs/drupal-apis/entity-api/creating-a-custom-content-entity) -- Official entity creation guide
- [Drupal Entity API: Field definitions](https://www.drupal.org/docs/drupal-apis/entity-api/defining-and-using-content-entity-field-definitions) -- Base field types and display options
- [EntityOwnerTrait API](https://api.drupal.org/api/drupal/core!modules!user!src!EntityOwnerTrait.php/trait/EntityOwnerTrait/8.8.x) -- Owner field standardization
- [EntityListBuilder API](https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Entity!EntityListBuilder.php/class/EntityListBuilder/10) -- List builder with buildHeader/buildRow
- [EntityListBuilder::buildHeader sortable columns](https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Entity%21EntityListBuilder.php/function/EntityListBuilder%3A%3AbuildHeader/8.2.x) -- Specifier key for tableSort

### Secondary (MEDIUM confidence)

- [Create Custom Entity Drupal 11 guide](https://www.augustinfotech.com/blogs/how-to-create-custom-entity-in-drupal-11/) -- D11 attribute syntax examples
- [Entity Type Walkthrough](https://drupal-entity-training.github.io/event/) -- Complete entity type training material
- [Chromatic: Dynamic list field values](https://chromatichq.com/insights/dynamic-default-and-allowed-values-list-fields-drupal-8/) -- list_string allowed_values patterns
- [ContentEntityForm API](https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Entity%21ContentEntityForm.php/11.x) -- ContentEntityForm save() method
- .planning/research/ARCHITECTURE.md -- Module directory structure and entity model
- .planning/research/PITFALLS.md -- Group 3.x API terminology, module install dependencies

### Tertiary (LOW confidence)

- [Datetime field in custom entity](https://www.drupal.org/docs/8/core/modules/datetime-range/how-to-use-datetime-range-in-a-custom-entity) -- Datetime base field configuration (D8 era docs, patterns unchanged)

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH -- all patterns are standard Drupal Entity API with extensive documentation and skill coverage
- Architecture: HIGH -- module structure follows well-documented PSR-4 conventions with entity handlers
- Pitfalls: HIGH -- common entity definition mistakes are well-known and preventable
- EVAL-02 (baseline): MEDIUM -- eval prompt wording needs validation; headless pipeline is proven from v2.0

**Research date:** 2026-03-08
**Valid until:** 2026-04-08 (stable Drupal Entity API patterns, unlikely to change)

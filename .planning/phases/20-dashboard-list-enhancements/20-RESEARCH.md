# Phase 20: Dashboard + List Enhancements - Research

**Researched:** 2026-03-08
**Domain:** Drupal render arrays, AJAX Form API, EntityListBuilder enhancement, dashboard controller patterns
**Confidence:** HIGH

## Summary

Phase 20 adds three capabilities to the existing `group_ai_pm` module: (1) enhanced dashboard with project summary cards showing per-status task count bars and progress percentage, (2) dashboard quick actions, and (3) AJAX status toggle dropdowns on the task list page using pure Drupal AJAX (no Vue). All three requirements involve server-side Drupal patterns with zero Vue.js involvement.

The critical technical challenge is DASH-03: adding `#ajax` form elements to the task list. The current `TaskListBuilder` extends `EntityListBuilder` which is NOT a form -- it renders a plain table. Drupal's `#ajax` property only works on form elements within a form context. The recommended approach is to create a standalone `TaskStatusForm` that wraps the task list or to replace the entity list builder with a form-based controller that renders the table with embedded select elements. For the dashboard (DASH-01, DASH-02), the existing `DashboardController` and `ProjectApiController::summary()` provide the data foundation -- the dashboard controller needs enhancement with proper render arrays, a new theme hook, and CSS.

**Primary recommendation:** Create a `TaskStatusForm` (extends `FormBase`) that embeds select elements with `#ajax` callbacks inside a table, rather than trying to retrofit `#ajax` onto `EntityListBuilder`. For the dashboard, enhance `DashboardController::content()` with a new `group_ai_pm_dashboard` theme hook containing project cards with status bars.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| DASH-01 | Enhanced dashboard with project summary cards showing task count bars per status and progress percentage | Dashboard render array patterns, new theme hook `group_ai_pm_dashboard`, consume existing `ProjectApiController::summary()` data inline (not via API call), CSS progress bars |
| DASH-02 | Dashboard quick actions (New Project, recent project links, Board links) | Drupal `Url::fromRoute()` patterns, render arrays with `#type => 'link'`, action links |
| DASH-03 | AJAX status toggles on TaskListBuilder rows (Drupal #ajax, no Vue dependency) | Form-based approach: `TaskStatusForm` extending `FormBase` with `#ajax` select elements in table, `AjaxResponse` with `ReplaceCommand` for row updates |
</phase_requirements>

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Drupal Form API | Core | AJAX form elements with `#ajax` property | Only way to get AJAX callbacks on form elements |
| `AjaxResponse` + `ReplaceCommand` | Core | Server-side AJAX response commands | Standard Drupal pattern for updating DOM from PHP |
| Render arrays with `#theme` | Core | Dashboard card rendering | Drupal's standard output mechanism |
| `CacheableMetadata` | Core | Cache tags on dashboard | Required for correct cache invalidation |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| `core/drupal.ajax` | Core | AJAX form processing library | Dependency for AJAX form library |
| `core/once` | Core | Prevent duplicate behavior attachment | Dependency for any JS behaviors |
| `core/drupalSettings` | Core | Pass data from PHP to JS | If needed for any dashboard JS |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Standalone `TaskStatusForm` | Override `TaskListBuilder::render()` + implement `FormInterface` | DraggableListBuilder pattern exists but is for ConfigEntity weight ordering, not content entity status changes. A standalone form is cleaner. |
| Theme hook for dashboard | Inline `#markup` | Theme hook is correct for structured output; `#markup` prevents template overrides and is an anti-pattern for complex layouts |
| Consuming summary data inline | Fetching via API endpoint | Dashboard controller has direct entity access; making an HTTP call to self is unnecessary overhead |

**Installation:**
No new packages needed. All patterns use Drupal core APIs.

## Architecture Patterns

### Recommended Project Structure (new/modified files only)
```
modules/group_ai_pm/
  src/
    Controller/
      DashboardController.php      # MODIFY: Enhanced with summary cards
    Form/
      TaskStatusForm.php            # NEW: AJAX status toggle form
  css/
    dashboard.css                   # NEW: Dashboard card/progress bar styles
  templates/
    group-ai-pm-dashboard.html.twig # NEW: Dashboard template
  group_ai_pm.module               # MODIFY: Add dashboard theme hook
  group_ai_pm.libraries.yml        # MODIFY: Add dashboard library
  group_ai_pm.routing.yml          # MODIFY: Add task status form route (if separate)
```

### Pattern 1: AJAX Status Toggle Form (DASH-03 -- MOST CRITICAL)

**What:** A standalone form that renders a task list table with `#ajax` select elements for inline status changes.
**When to use:** When EntityListBuilder needs form elements -- which it cannot natively support.
**Why this approach:** `EntityListBuilder` does not implement `FormInterface`. Drupal's `#ajax` property ONLY works on elements rendered within a form. You cannot simply add `#ajax` to a render array element outside a form context -- the AJAX processing pipeline requires form state.

**Architecture:**

There are two viable approaches for DASH-03:

**Approach A (Recommended): Separate `TaskStatusForm` route**

Create a new `FormBase` form that renders a table of tasks with select elements. This replaces or supplements the entity collection page.

```php
// src/Form/TaskStatusForm.php
namespace Drupal\group_ai_pm\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class TaskStatusForm extends FormBase {

  public function getFormId() {
    return 'group_ai_pm_task_status_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $storage = \Drupal::entityTypeManager()->getStorage('task');
    $task_ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->sort('created', 'DESC')
      ->pager(50)
      ->execute();
    $tasks = $storage->loadMultiple($task_ids);

    $header = [
      $this->t('Title'),
      $this->t('Project'),
      $this->t('Status'),
      $this->t('Priority'),
      $this->t('Due Date'),
      $this->t('Assignee'),
      $this->t('Operations'),
    ];

    $form['tasks'] = [
      '#type' => 'table',
      '#header' => $header,
      '#empty' => $this->t('No tasks found.'),
    ];

    foreach ($tasks as $task_id => $task) {
      $form['tasks'][$task_id]['title'] = [
        '#markup' => $task->getTitle(),
      ];
      $form['tasks'][$task_id]['project'] = [
        '#markup' => $task->get('project')->entity
          ? $task->get('project')->entity->getTitle() : '',
      ];
      $form['tasks'][$task_id]['status'] = [
        '#type' => 'select',
        '#options' => [
          'todo' => $this->t('To Do'),
          'in_progress' => $this->t('In Progress'),
          'review' => $this->t('Review'),
          'done' => $this->t('Done'),
        ],
        '#default_value' => $task->getStatus(),
        '#ajax' => [
          'callback' => '::statusUpdateCallback',
          'event' => 'change',
          'wrapper' => 'task-row-' . $task_id,
        ],
      ];
      // ... remaining columns
      $form['tasks'][$task_id]['#attributes']['id'] = 'task-row-' . $task_id;
    }

    return $form;
  }

  public function statusUpdateCallback(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    // Extract task ID from the triggering element's parents.
    $task_id = $trigger['#array_parents'][1];
    $new_status = $form_state->getValue(['tasks', $task_id, 'status']);

    // Load and update the task entity.
    $task = \Drupal::entityTypeManager()->getStorage('task')->load($task_id);
    $task->setStatus($new_status);
    $task->save();

    // Return the updated row.
    return $form['tasks'][$task_id];
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No-op: status changes happen via AJAX callbacks.
  }

}
```

**Approach B (Alternative): Override `TaskListBuilder::render()` to embed form**

Override the `render()` method to embed a form within the existing list builder output. This is less clean but preserves the entity collection route.

```php
// In TaskListBuilder, override render():
public function render() {
  $build = parent::render();
  // Embed the TaskStatusForm below or instead of the table.
  $build['status_form'] = \Drupal::formBuilder()->getForm(
    'Drupal\group_ai_pm\Form\TaskStatusForm'
  );
  return $build;
}
```

**Recommendation:** Use Approach A. It gives full control over the table structure and cleanly supports `#ajax`. The form can be routed as a separate page or embedded via `\Drupal::formBuilder()->getForm()`.

### Pattern 2: Dashboard with Theme Hook and Summary Data (DASH-01)

**What:** Enhanced dashboard controller that queries task counts per project and renders project summary cards with status bars.
**When to use:** For the dashboard page enhancement.

```php
// In DashboardController::content()
public function content() {
  $build = [];
  $projects = $this->entityTypeManager
    ->getStorage('project')
    ->getQuery()
    ->accessCheck(TRUE)
    ->sort('created', 'DESC')
    ->execute();

  $project_entities = $this->entityTypeManager
    ->getStorage('project')
    ->loadMultiple($projects);

  $project_cards = [];
  $task_storage = $this->entityTypeManager->getStorage('task');

  foreach ($project_entities as $project) {
    $tasks = $task_storage->loadByProperties(['project' => $project->id()]);
    $summary = ['todo' => 0, 'in_progress' => 0, 'review' => 0, 'done' => 0];
    foreach ($tasks as $task) {
      $status = $task->getStatus() ?? 'todo';
      if (isset($summary[$status])) {
        $summary[$status]++;
      }
    }
    $total = array_sum($summary);
    $progress = $total > 0 ? round(($summary['done'] / $total) * 100) : 0;

    $project_cards[] = [
      'title' => $project->getTitle(),
      'status' => $project->getStatus(),
      'url' => $project->toUrl()->toString(),
      'board_url' => Url::fromRoute('group_ai_pm.kanban_board', [
        'project' => $project->id(),
      ])->toString(),
      'summary' => $summary,
      'total' => $total,
      'progress' => $progress,
    ];
  }

  $build['dashboard'] = [
    '#theme' => 'group_ai_pm_dashboard',
    '#project_cards' => $project_cards,
    '#attached' => [
      'library' => ['group_ai_pm/dashboard'],
    ],
    '#cache' => [
      'tags' => ['project_list', 'group_ai_pm_task_list'],
      'contexts' => ['user.permissions'],
    ],
  ];

  return $build;
}
```

### Pattern 3: Quick Actions Section (DASH-02)

**What:** Action links and recent project links at the top of the dashboard.
**When to use:** Dashboard layout.

```php
// Pass quick actions as template variables
$build['dashboard']['#quick_actions'] = [
  'new_project' => [
    'title' => $this->t('New Project'),
    'url' => Url::fromRoute('entity.project.add_form')->toString(),
  ],
];
$build['dashboard']['#recent_projects'] = array_slice($project_cards, 0, 5);
```

### Anti-Patterns to Avoid

- **Adding `#ajax` to EntityListBuilder rows directly:** EntityListBuilder does NOT implement `FormInterface`. The `#ajax` property will be silently ignored because there is no form context for Drupal to process the AJAX callback. This is the #1 pitfall for this phase.
- **Fetching dashboard data via the API endpoint:** The dashboard controller has direct entity storage access. Making an HTTP request to `/api/kanban/project/{id}/summary` from the same server is wasteful. Query entities directly.
- **Using `#markup` for dashboard cards:** `#markup` content is filtered by `Xss::filterAdmin`. Custom HTML structures (progress bars, status badges) should use `#theme` with a Twig template for security and override-ability.
- **Missing cache tags on dashboard:** The dashboard shows aggregated data across all projects and tasks. Without `project_list` and `group_ai_pm_task_list` tags, the dashboard will show stale counts after task/project changes.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| AJAX form callbacks | Custom JavaScript XHR | Drupal `#ajax` + `AjaxResponse` | Form API handles CSRF, form rebuilding, state management |
| Progress percentage bars | Canvas/SVG rendering | CSS width percentage on `<div>` | Pure CSS is simpler, Twig-compatible, no JS needed |
| Table with sortable columns | Custom table HTML | `#type => 'table'` render element or EntityListBuilder | Built-in header sorting, pager integration |
| Status label mapping | Hardcoded strings | `Task::baseFieldDefinitions()` allowed_values | Single source of truth for status options |
| Action links | Raw `<a>` tags | `Url::fromRoute()` + `#type => 'link'` | CSRF protection, access checking, route validation |

**Key insight:** This phase is pure Drupal server-side rendering with no JavaScript frameworks. The `#ajax` form property handles all client-side interactivity via Drupal core's AJAX library.

## Common Pitfalls

### Pitfall 1: EntityListBuilder Cannot Support #ajax
**What goes wrong:** Developer adds `#ajax` properties to elements returned from `TaskListBuilder::buildRow()`. The select renders but AJAX never fires.
**Why it happens:** `EntityListBuilder::render()` builds a `#theme => 'table'` render array, not a form. Drupal's AJAX system requires form context (`FormInterface`, form state, form token).
**How to avoid:** Use a standalone `FormBase` form with `#type => 'table'` for the task list, or embed the form via `\Drupal::formBuilder()->getForm()`.
**Warning signs:** Select element renders but nothing happens on change. No AJAX request visible in browser network tab.

### Pitfall 2: AJAX Wrapper ID Mismatch
**What goes wrong:** The `#ajax['wrapper']` ID does not match any element's HTML `id` attribute, causing the AJAX response to silently fail.
**Why it happens:** In `#type => 'table'`, row wrapper IDs must be set via `$form['table'][$row_key]['#attributes']['id']`. The wrapper ID in `#ajax` must exactly match.
**How to avoid:** Set `$form['tasks'][$task_id]['#attributes']['id'] = 'task-row-' . $task_id` on each row, and use `'wrapper' => 'task-row-' . $task_id` in the `#ajax` definition.
**Warning signs:** AJAX request succeeds (200 response) but DOM does not update. Console error about missing wrapper.

### Pitfall 3: Missing `core/drupal.ajax` Library Dependency
**What goes wrong:** AJAX form elements do not process because the AJAX library is not loaded.
**Why it happens:** When using `#ajax` on form elements rendered via a controller (not a `_form` route), the AJAX processing library may not be automatically attached.
**How to avoid:** Add `core/drupal.ajax` as a dependency in the library definition for any page with AJAX form elements. Or use a `_form` route which auto-attaches the library.
**Warning signs:** Form renders as a standard HTML form with full page reloads on select change.

### Pitfall 4: Haiku Uses ControllerBase Lazy Methods Instead of DI
**What goes wrong:** Haiku generates `$this->entityTypeManager()` (lazy method from ControllerBase) instead of injecting `EntityTypeManagerInterface` via `create()` + constructor.
**Why it happens:** Known Haiku behavior (documented in Phase 18 results). ControllerBase lazy methods work but violate DI best practices.
**How to avoid:** This is a known eval limitation. The skill patches for DI were ineffective for this specific issue. Accept this in eval grading.
**Warning signs:** Controller uses `$this->entityTypeManager()` instead of `$this->entityTypeManager->`.

### Pitfall 5: Dashboard Cache Missing Entity-Level Tags
**What goes wrong:** Dashboard shows stale data after a single task is updated because only list-level cache tags were applied.
**Why it happens:** Using only `project_list` and `group_ai_pm_task_list` tags without also adding individual entity cache tags.
**How to avoid:** Add `$project->getCacheTags()` for each project rendered in the dashboard. The list tags handle new/deleted entities; individual tags handle updates.
**Warning signs:** Creating a new project updates dashboard immediately, but editing an existing project's status does not.

### Pitfall 6: Form AJAX Callback Accessing Wrong Task ID
**What goes wrong:** The AJAX callback cannot determine which task's status was changed.
**Why it happens:** The triggering element's `#array_parents` or `#name` does not correctly map to the task entity ID.
**How to avoid:** Use the task entity ID as the table row key (e.g., `$form['tasks'][$task_id]`), then extract it from `$form_state->getTriggeringElement()['#array_parents'][1]`.
**Warning signs:** Wrong task gets updated, or "entity not found" error in AJAX callback.

## Code Examples

### DASH-01: Dashboard Theme Hook Registration

```php
// In group_ai_pm.module hook_theme()
'group_ai_pm_dashboard' => [
  'variables' => [
    'project_cards' => [],
    'quick_actions' => [],
    'recent_projects' => [],
  ],
],
```

### DASH-01: Dashboard Twig Template with Progress Bars

```twig
{# templates/group-ai-pm-dashboard.html.twig #}
<div{{ attributes.addClass('gapm-dashboard') }}>
  {% if quick_actions %}
    <div class="gapm-dashboard__actions">
      {% for action in quick_actions %}
        <a href="{{ action.url }}" class="gapm-dashboard__action-link button button--primary">
          {{ action.title }}
        </a>
      {% endfor %}
    </div>
  {% endif %}

  {% if project_cards is not empty %}
    <div class="gapm-dashboard__grid">
      {% for card in project_cards %}
        <div class="gapm-dashboard__card">
          <h3 class="gapm-dashboard__card-title">
            <a href="{{ card.url }}">{{ card.title }}</a>
          </h3>
          <div class="gapm-dashboard__card-status">
            <span class="gapm-dashboard__status-badge gapm-dashboard__status-badge--{{ card.status }}">
              {{ card.status|capitalize }}
            </span>
          </div>
          <div class="gapm-dashboard__progress">
            <div class="gapm-dashboard__progress-bar">
              <div class="gapm-dashboard__progress-fill" style="width: {{ card.progress }}%"></div>
            </div>
            <span class="gapm-dashboard__progress-text">{{ card.progress }}% {{ 'complete'|t }}</span>
          </div>
          <div class="gapm-dashboard__status-bars">
            {% for status, count in card.summary %}
              <div class="gapm-dashboard__status-bar gapm-dashboard__status-bar--{{ status }}">
                <span class="gapm-dashboard__bar-label">{{ status|replace({'_': ' '})|capitalize }}</span>
                <span class="gapm-dashboard__bar-count">{{ count }}</span>
              </div>
            {% endfor %}
          </div>
          <div class="gapm-dashboard__card-actions">
            <a href="{{ card.board_url }}" class="gapm-dashboard__board-link">
              {{ 'Board'|t }}
            </a>
          </div>
        </div>
      {% endfor %}
    </div>
  {% else %}
    <p>{{ 'No projects yet. Create your first project to get started.'|t }}</p>
  {% endif %}
</div>
```

### DASH-03: AJAX Callback Returning Updated Row

```php
// Source: Drupal AJAX API documentation
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\MessageCommand;

public function statusUpdateCallback(array &$form, FormStateInterface $form_state) {
  $trigger = $form_state->getTriggeringElement();
  $task_id = $trigger['#array_parents'][1];
  $new_status = $form_state->getValue(['tasks', $task_id, 'status']);

  // Load and update entity.
  $task = $this->entityTypeManager->getStorage('task')->load($task_id);
  if ($task) {
    $task->setStatus($new_status);
    $task->save();
  }

  // Option A: Return the rebuilt row (simple approach).
  return $form['tasks'][$task_id];

  // Option B: Return AjaxResponse with multiple commands (advanced).
  // $response = new AjaxResponse();
  // $response->addCommand(new ReplaceCommand(
  //   '#task-row-' . $task_id,
  //   $form['tasks'][$task_id]
  // ));
  // $response->addCommand(new MessageCommand(
  //   $this->t('Task status updated to @status.', ['@status' => $new_status])
  // ));
  // return $response;
}
```

### DASH-02: Quick Action Links

```php
// Source: Drupal Url API
use Drupal\Core\Url;

$quick_actions = [
  'new_project' => [
    'title' => $this->t('New Project'),
    'url' => Url::fromRoute('entity.project.add_form')->toString(),
  ],
];

// Recent project links with board URLs
foreach (array_slice($project_entities, 0, 5) as $project) {
  $recent[] = [
    'title' => $project->getTitle(),
    'url' => $project->toUrl()->toString(),
    'board_url' => Url::fromRoute('group_ai_pm.kanban_board', [
      'project' => $project->id(),
    ])->toString(),
  ];
}
```

### Library Definition for Dashboard

```yaml
# In group_ai_pm.libraries.yml
dashboard:
  version: 1.x
  css:
    component:
      css/dashboard.css: {}
  dependencies:
    - core/drupal
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `drupal_set_message()` | `$this->messenger()->addMessage()` | Drupal 8.5 (deprecated), 11 (removed) | Must use messenger service |
| jQuery AJAX requests | `#ajax` Form API property | Drupal 8+ | Declarative AJAX, no custom JS needed |
| `hook_menu()` for routes | `.routing.yml` | Drupal 8+ | All routes in YAML |
| Custom tables in controllers | `#type => 'table'` render element | Drupal 8+ | Standard table rendering with built-in features |
| REST Resource plugins | Custom controllers with `CacheableJsonResponse` | Project convention (Phase 18) | Simpler, less boilerplate |

**Deprecated/outdated:**
- `drupal_set_message()`: Removed in D11. Use `$this->messenger()->addMessage()`.
- Direct `\Drupal::` calls in classes: Use DI. Acceptable only in `.module` files.

## Open Questions

1. **Route strategy for TaskStatusForm**
   - What we know: The form needs a route with `_form` key, or can be embedded via `\Drupal::formBuilder()->getForm()`
   - What's unclear: Should it replace the entity collection page or be a separate "Task Overview" page?
   - Recommendation: Use a separate route (e.g., `/admin/content/task/overview`) with `_form` key. Keep the existing `entity.task.collection` as-is. The new form page can be linked from the dashboard. This avoids breaking existing entity CRUD routes.

2. **Dashboard data source: inline queries vs. API endpoint**
   - What we know: `ProjectApiController::summary()` already returns per-status task counts. The dashboard controller could call this internally or re-query.
   - What's unclear: Whether to refactor summary logic into a shared service.
   - Recommendation: Query entities directly in `DashboardController`. The API endpoint exists for JavaScript consumers (Kanban board). Creating a shared service is clean but adds complexity without eval benefit. For eval purposes, direct querying is simpler and more likely to be generated correctly by Haiku.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | PHPUnit (Drupal core test base) |
| Config file | `phpunit.xml` (ddev Drupal instance) |
| Quick run command | `ddev exec phpunit --filter TaskStatusFormTest` |
| Full suite command | `ddev exec phpunit --group group_ai_pm` |

### Phase Requirements to Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| DASH-01 | Dashboard renders project cards with status counts | runtime | `ddev drush php-eval "...check render..."` | Wave 0 |
| DASH-02 | Quick actions (New Project link, Board links) visible | static + runtime | `grep` assertions + `ddev drush eval` | Wave 0 |
| DASH-03 | AJAX status toggle form renders selects, updates entities | runtime | `ddev drush php-eval "...form builder..."` | Wave 0 |

### Sampling Rate
- **Per task commit:** Static assertions (file existence, code patterns)
- **Per wave merge:** Runtime assertions (drush-based entity + form checks)
- **Phase gate:** Full assertion suite green before verify

### Wave 0 Gaps
- [ ] `eval/v4/phase-20-evals.json` -- static assertions for dashboard patterns
- [ ] `eval/v4/phase-20-runtime-assertions.json` -- drush-based functional checks
- [ ] Dashboard template existence check
- [ ] TaskStatusForm class existence check
- [ ] `#ajax` property presence on status select in TaskStatusForm

## Sources

### Primary (HIGH confidence)
- [Drupal AJAX Forms](https://www.drupal.org/docs/drupal-apis/javascript-api/ajax-forms) -- `#ajax` form element property, callback pattern
- [Drupal Core AJAX Commands](https://www.drupal.org/docs/drupal-apis/ajax-api/core-ajax-callback-commands) -- ReplaceCommand, HtmlCommand, AjaxResponse
- [EntityListBuilder API](https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Entity%21EntityListBuilder.php/class/EntityListBuilder/10) -- Confirmed NOT a form, no FormInterface
- [DraggableListBuilder API](https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Config!Entity!DraggableListBuilder.php/class/DraggableListBuilder/10) -- Pattern for entity list + FormInterface
- drupal-theming SKILL.md -- render array patterns, `#theme`, library attachment
- drupal-routing-controllers SKILL.md -- route definitions, `_form` key, DI patterns
- drupal-forms-api SKILL.md -- FormBase lifecycle, `#ajax` form elements
- drupal-theming/references/js-ajax.md -- `Drupal.behaviors`, `once()`, `drupalSettings`, AJAX commands

### Secondary (MEDIUM confidence)
- [AJAX Elements in Drupal Form Tables](https://www.webomelette.com/ajax-elements-drupal-form-tables) -- Pattern for `#ajax` inside `#type => 'table'` with `#pre_render` workaround
- [Updating Form Field Values with AJAX in Drupal 10](https://pasankg.medium.com/updating-form-field-values-with-ajax-callbacks-in-drupal-10-8ecc26692185) -- AJAX callback patterns

### Tertiary (LOW confidence)
- None. All patterns verified with official Drupal API documentation.

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH -- All Drupal core APIs, well-documented, stable across D10/D11
- Architecture: HIGH -- EntityListBuilder limitation verified via API docs; FormBase + `#ajax` pattern is canonical Drupal
- Pitfalls: HIGH -- EntityListBuilder form limitation is documented; wrapper ID issues are well-known; Haiku DI behavior confirmed from Phase 18 results

**Research date:** 2026-03-08
**Valid until:** 2026-04-08 (stable Drupal core APIs, 30-day validity)

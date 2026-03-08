# Architecture Patterns

**Domain:** Vue.js Kanban UX overhaul for existing Drupal 10 project management module (v4.0)
**Researched:** 2026-03-08
**Confidence:** MEDIUM -- Drupal REST resource patterns well-documented; Vue.js embedding in Drupal pages is proven but requires careful Drupal.behaviors integration; Vite build pipeline for Drupal modules is newer territory with solid references but less community consensus than webpack

## System Overview

v4.0 adds a frontend layer to the existing `group_ai_pm` module. The module already has working entities, controllers, templates, and CSS. The new architecture introduces:

1. **REST API layer** -- Custom REST resource plugins exposing Task/Project entities as JSON
2. **Vue.js Kanban component** -- Embedded Vue 3 app mounted on the project view page
3. **Drupal AJAX interactions** -- Status toggles, inline edits via Drupal's AJAX framework
4. **Vite build pipeline** -- Compiles Vue SFCs into Drupal-compatible library assets
5. **Enhanced navigation** -- Local task tabs, dashboard restructure, keyboard shortcuts

```
EXISTING (v3.0)                          NEW (v4.0)
---------------------------------------------
group_ai_pm/                             group_ai_pm/
  src/Entity/Project.php          -->      src/Plugin/rest/resource/
  src/Entity/Task.php             -->        TaskResource.php (REST)
  src/Controller/                 -->        ProjectResource.php (REST)
    DashboardController.php       -->        KanbanResource.php (REST)
    ProjectActionController.php   -->      src/Controller/
  group_ai_pm.routing.yml         -->        KanbanController.php (page shell)
  group_ai_pm.libraries.yml      -->      js/
  css/                            -->        src/ (Vue SFCs, compiled by Vite)
  templates/                      -->          KanbanBoard.vue
                                  -->          KanbanColumn.vue
                                  -->          TaskCard.vue
                                  -->          TaskQuickEdit.vue
                                  -->        dist/ (Vite output, committed)
                                  -->          kanban.js
                                  -->          kanban.css
                                  -->      config/optional/
                                  -->        rest.resource.*.yml
                                  -->      group_ai_pm.services.yml (updated)
```

## Recommended Architecture: Embedded Vue App (Not Decoupled SPA)

Use Vue 3 as an **embedded interactive island** within Drupal admin pages, not as a fully decoupled SPA. The Kanban board is a Vue app mounted on a specific `<div>` rendered by a Drupal controller. All other pages (settings, entity forms, list builders) remain standard Drupal.

**Why embedded over decoupled:**
- The module runs inside Drupal's admin theme (Seven/Claro), not a custom frontend
- Authentication, routing, and page chrome are Drupal's job -- Vue handles one interactive region
- Drupal's AJAX framework handles simple interactions (status toggle on task list pages)
- Vue handles the complex interaction: drag-and-drop Kanban board with real-time column updates

**Why Vue 3 over Alpine.js or HTMX:**
- Kanban drag-and-drop with multi-column state management exceeds Alpine's ergonomic sweet spot
- HTMX cannot handle optimistic UI updates needed for smooth drag-and-drop (the card must move instantly, then sync)
- Vue 3's Composition API + vuedraggable provides exactly the reactivity model needed

## Component Architecture

### Layer 1: REST API (Drupal -> JSON)

Custom REST resource plugins serve entity data as JSON. Use `rest` module resources (not JSON:API) because we need custom response shapes (tasks grouped by status column, aggregated counts) that JSON:API's strict spec does not support without excessive client-side reshaping.

```
REST Resources (new files):
  src/Plugin/rest/resource/
    KanbanResource.php         GET /api/group-ai-pm/kanban/{project}
    TaskStatusResource.php     PATCH /api/group-ai-pm/task/{task}/status
    TaskReorderResource.php    PATCH /api/group-ai-pm/task/{task}/reorder
    TaskQuickEditResource.php  PATCH /api/group-ai-pm/task/{task}/quick-edit
```

**KanbanResource** (GET): Returns tasks for a project grouped by status column.

```php
#[RestResource(
  id: "group_ai_pm_kanban",
  label: new TranslatableMarkup("Kanban Board"),
  uri_paths: [
    "canonical" => "/api/group-ai-pm/kanban/{project}",
  ]
)]
class KanbanResource extends ResourceBase {

  public function get($project) {
    // Load project entity, verify access.
    // Query tasks by project, group by status.
    // Return structured response:
    // { project: {...}, columns: { todo: [...], in_progress: [...], review: [...], done: [...] } }
    $response = new ResourceResponse($data);
    $response->addCacheableDependency($project_entity);
    // Add cache tags for all loaded tasks.
    return $response;
  }
}
```

**TaskStatusResource** (PATCH): Updates a single task's status (drag-drop result).

```php
#[RestResource(
  id: "group_ai_pm_task_status",
  label: new TranslatableMarkup("Task Status Update"),
  uri_paths: [
    "canonical" => "/api/group-ai-pm/task/{task}/status",
  ]
)]
class TaskStatusResource extends ResourceBase {

  public function patch($task, Request $request) {
    // Decode JSON body: { status: "in_progress", weight: 3 }
    // Load task entity, check access('update').
    // Set status, save.
    // Return updated task data.
  }
}
```

### Layer 2: REST Configuration (config/optional)

REST resources must be enabled via config entities. Ship these as `config/optional/` so they auto-install when `rest` module is present.

```yaml
# config/optional/rest.resource.group_ai_pm_kanban.yml
langcode: en
status: true
dependencies:
  module:
    - group_ai_pm
    - rest
    - serialization
    - user
id: group_ai_pm_kanban
plugin_id: group_ai_pm_kanban
granularity: resource
configuration:
  methods:
    - GET
  formats:
    - json
  authentication:
    - cookie
```

```yaml
# config/optional/rest.resource.group_ai_pm_task_status.yml
langcode: en
status: true
dependencies:
  module:
    - group_ai_pm
    - rest
    - serialization
    - user
id: group_ai_pm_task_status
plugin_id: group_ai_pm_task_status
granularity: resource
configuration:
  methods:
    - PATCH
  formats:
    - json
  authentication:
    - cookie
```

### Layer 3: CSRF Token Handling

For write operations using cookie authentication, Drupal requires an `X-CSRF-Token` header. The token is obtained from `/session/token`.

**Vue fetch wrapper pattern:**

```javascript
// js/src/api/drupal.js
async function getCsrfToken() {
  const response = await fetch('/session/token');
  return response.text();
}

async function patchTask(taskId, data) {
  const token = await getCsrfToken();
  return fetch(`/api/group-ai-pm/task/${taskId}/status?_format=json`, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': token,
    },
    body: JSON.stringify(data),
  });
}
```

The CSRF token should be fetched once on page load and cached for the session. It does not change per request. Anonymous users bypass CSRF validation, but the Kanban board requires authentication, so this is always needed.

### Layer 4: Vue.js Components (Compiled SFCs)

```
js/src/
  main.js                  Entry point, mounts Vue app
  api/
    drupal.js              CSRF-aware fetch wrapper
  components/
    KanbanBoard.vue        Root component, loads columns
    KanbanColumn.vue       Single status column with draggable
    TaskCard.vue           Individual task card in column
    TaskQuickEdit.vue      Inline edit modal/popover
    TaskFilters.vue        Filter bar (priority, assignee, due date)
  composables/
    useKanban.js           State management (reactive task arrays per column)
    useKeyboardShortcuts.js  Keyboard navigation
  stores/ (optional)
    kanban.js              Pinia store if state complexity warrants it
```

**KanbanBoard.vue** -- Root component:

```vue
<template>
  <div class="kanban-board" @keydown="handleKeydown">
    <TaskFilters v-model:filters="filters" />
    <div class="kanban-board__columns">
      <KanbanColumn
        v-for="column in columns"
        :key="column.id"
        :column="column"
        :tasks="filteredTasks(column.id)"
        @task-moved="onTaskMoved"
        @task-click="onTaskClick"
      />
    </div>
    <TaskQuickEdit
      v-if="editingTask"
      :task="editingTask"
      @save="onQuickSave"
      @close="editingTask = null"
    />
  </div>
</template>
```

**KanbanColumn.vue** -- Uses vuedraggable for drag-and-drop:

```vue
<template>
  <div class="kanban-column">
    <h3 class="kanban-column__header">
      {{ column.label }} <span class="kanban-column__count">{{ tasks.length }}</span>
    </h3>
    <draggable
      v-model="localTasks"
      :group="{ name: 'tasks' }"
      item-key="id"
      :animation="200"
      @change="onDragChange"
    >
      <template #item="{ element }">
        <TaskCard :task="element" @click="$emit('task-click', element)" />
      </template>
    </draggable>
  </div>
</template>
```

### Layer 5: Drupal Page Shell (Controller + Twig)

A new controller renders the HTML page that contains the Vue mount point. The controller passes initial data via `drupalSettings` to avoid an extra API call on page load.

```php
// src/Controller/KanbanController.php
class KanbanController extends ControllerBase {

  public function board(Project $project) {
    // Pre-load tasks grouped by status for initial render.
    $tasks = $this->loadTasksByStatus($project);

    $build['kanban'] = [
      '#theme' => 'group_ai_pm_kanban',
      '#attached' => [
        'library' => ['group_ai_pm/kanban'],
        'drupalSettings' => [
          'groupAiPm' => [
            'kanban' => [
              'projectId' => $project->id(),
              'columns' => [
                ['id' => 'todo', 'label' => $this->t('To Do')],
                ['id' => 'in_progress', 'label' => $this->t('In Progress')],
                ['id' => 'review', 'label' => $this->t('Review')],
                ['id' => 'done', 'label' => $this->t('Done')],
              ],
              'tasks' => $tasks,
              'csrfTokenUrl' => '/session/token',
              'apiBaseUrl' => '/api/group-ai-pm',
            ],
          ],
        ],
      ],
    ];

    return $build;
  }
}
```

**Twig template** -- Minimal shell with mount target:

```twig
{# templates/group-ai-pm-kanban.html.twig #}
<div{{ attributes.addClass('group-ai-pm-kanban') }}>
  <div id="kanban-app">
    {# Vue 3 mounts here. Content below serves as loading state. #}
    <div class="kanban-loading">
      {{ 'Loading Kanban board...'|t }}
    </div>
  </div>
</div>
```

### Layer 6: Vue-to-Drupal Bridge (main.js + Drupal.behaviors)

The Vue app initializes through Drupal.behaviors to ensure it works with Drupal's AJAX page lifecycle.

```javascript
// js/src/main.js
import { createApp } from 'vue';
import KanbanBoard from './components/KanbanBoard.vue';

(function (Drupal, once, drupalSettings) {
  'use strict';

  Drupal.behaviors.groupAiPmKanban = {
    attach: function (context) {
      once('kanban-app', '#kanban-app', context).forEach(function (element) {
        const config = drupalSettings.groupAiPm.kanban;
        const app = createApp(KanbanBoard, {
          projectId: config.projectId,
          columns: config.columns,
          initialTasks: config.tasks,
          apiBaseUrl: config.apiBaseUrl,
          csrfTokenUrl: config.csrfTokenUrl,
        });
        app.mount(element);
      });
    },
  };

})(Drupal, once, drupalSettings);
```

Key points:
- `once('kanban-app', ...)` prevents double-mounting if Drupal re-attaches behaviors after AJAX
- Initial data comes from `drupalSettings` (server-rendered), not a separate API call
- The Vue app receives Drupal's API URLs as props, not hardcoded paths

### Layer 7: Asset Pipeline (Vite)

Vite compiles Vue SFCs into plain JS/CSS files that Drupal's library system references.

```javascript
// js/vite.config.js
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
  plugins: [vue()],
  build: {
    outDir: path.resolve(__dirname, 'dist'),
    emptyOutDir: true,
    lib: {
      entry: path.resolve(__dirname, 'src/main.js'),
      name: 'GroupAiPmKanban',
      formats: ['iife'],
      fileName: () => 'kanban.js',
    },
    rollupOptions: {
      external: ['Drupal', 'once', 'drupalSettings'],
      output: {
        globals: {
          Drupal: 'Drupal',
          once: 'once',
          drupalSettings: 'drupalSettings',
        },
        assetFileNames: 'kanban.[ext]',
      },
    },
  },
});
```

Critical decisions:
- **IIFE format** (not ESM): Drupal's library system loads scripts with `<script>` tags, not `import`. IIFE wraps the Vue app as a self-executing function.
- **External Drupal globals**: `Drupal`, `once`, and `drupalSettings` are not bundled -- they come from Drupal's core libraries.
- **Stable filenames**: No hashes. `kanban.js` and `kanban.css` stay consistent so `.libraries.yml` can reference them.
- **Committed dist/**: The compiled output is committed to the repo. Drupal modules must work without a build step for end users. Developers run `npm run build` during development.

```yaml
# Updated group_ai_pm.libraries.yml (additions)
kanban:
  version: 1.x
  js:
    js/dist/kanban.js: { minified: true }
  css:
    component:
      js/dist/kanban.css: {}
  dependencies:
    - core/drupal
    - core/once
    - core/drupalSettings
    - core/drupal.ajax
```

### Layer 8: Drupal AJAX for Simple Interactions

For non-Kanban pages (task list, project list, dashboard), use Drupal's built-in AJAX framework for lightweight interactions. Do not use Vue for simple toggles.

**Task status toggle on list pages:**

```php
// In TaskListBuilder::buildRow() or a custom form
$row['status_toggle'] = [
  '#type' => 'select',
  '#options' => ['todo' => 'To Do', 'in_progress' => 'In Progress', ...],
  '#default_value' => $entity->getStatus(),
  '#ajax' => [
    'callback' => '::statusUpdateCallback',
    'event' => 'change',
    'wrapper' => 'task-status-' . $entity->id(),
  ],
];
```

**Why AJAX here instead of Vue:** The task list is a standard Drupal table with simple interactions. Adding Vue for a select dropdown change is overengineering. Drupal's `#ajax` on form elements handles this natively with zero JS code.

## Data Flow Diagrams

### Flow 1: Kanban Board Initial Load

```
1. User navigates to /admin/content/project/{id}/board
2. Drupal routing -> KanbanController::board()
3. Controller queries Task entities for project, groups by status
4. Controller returns render array with:
   - #theme: group_ai_pm_kanban (Twig template)
   - #attached library: group_ai_pm/kanban
   - #attached drupalSettings: task data, column config, API URLs
5. Drupal renders page: admin theme chrome + Twig template + JS/CSS libraries
6. Browser executes kanban.js
7. Drupal.behaviors.groupAiPmKanban.attach() fires
8. once() finds #kanban-app, creates Vue app with drupalSettings data as props
9. Vue renders KanbanBoard -> KanbanColumn[] -> TaskCard[]
10. Board is interactive. No additional API call needed for initial render.
```

### Flow 2: Drag-and-Drop Task Move

```
1. User drags TaskCard from "To Do" column to "In Progress" column
2. vuedraggable fires @change event with:
   { added: { element: task, newIndex: 2 }, removed: null }
3. Vue updates local state IMMEDIATELY (optimistic update)
   - Task moves visually to new column
   - Column counts update
4. Vue calls API: PATCH /api/group-ai-pm/task/{id}/status
   Headers: { X-CSRF-Token: [cached token], Content-Type: application/json }
   Body: { status: "in_progress", weight: 2 }
5. Drupal REST resource:
   a. Validates CSRF token (cookie auth)
   b. Loads Task entity, checks access('update')
   c. Sets status field, saves entity
   d. Returns 200 with updated task JSON
6. Vue confirms: if 200, keep state. If error, ROLLBACK (move card back).
7. Cache tags invalidated on entity save (automatic via Drupal entity system).
```

### Flow 3: Quick Edit (Inline Title/Priority Change)

```
1. User clicks TaskCard -> TaskQuickEdit popover appears
2. User edits title, changes priority dropdown, clicks Save
3. Vue calls API: PATCH /api/group-ai-pm/task/{id}/quick-edit
   Body: { title: "Updated title", priority: "high" }
4. Drupal REST resource:
   a. Validates CSRF token
   b. Loads Task entity, checks access('update')
   c. Updates fields, validates entity, saves
   d. Returns 200 with full updated task JSON
5. Vue updates task in local state from response data
6. TaskCard re-renders with new title/priority badge
```

### Flow 4: Keyboard Navigation

```
1. User presses 'j' -> focus moves to next task card
2. User presses 'k' -> focus moves to previous task card
3. User presses 'l' -> focus moves to next column
4. User presses 'h' -> focus moves to previous column
5. User presses Enter -> opens TaskQuickEdit for focused card
6. User presses 'e' -> navigates to full entity edit form (Drupal page)
7. User presses 'n' -> opens new task form for current column's status
8. User presses '?' -> shows keyboard shortcut help overlay

All handled by useKeyboardShortcuts.js composable.
Focus tracked via reactive ref, applied via CSS class + aria-activedescendant.
```

## Component Boundaries

### New Components (v4.0)

| Component | Responsibility | Communicates With | File |
|-----------|---------------|-------------------|------|
| KanbanResource | Serve tasks grouped by column for a project | Task/Project entity storage | `src/Plugin/rest/resource/KanbanResource.php` |
| TaskStatusResource | Update task status from drag-drop | Task entity | `src/Plugin/rest/resource/TaskStatusResource.php` |
| TaskReorderResource | Update task weight within column | Task entity | `src/Plugin/rest/resource/TaskReorderResource.php` |
| TaskQuickEditResource | Update task title/priority/assignee | Task entity | `src/Plugin/rest/resource/TaskQuickEditResource.php` |
| KanbanController | Render page shell with drupalSettings | Entity storage, Twig | `src/Controller/KanbanController.php` |
| KanbanBoard.vue | Root Vue component, state container | REST API via fetch, child components | `js/src/components/KanbanBoard.vue` |
| KanbanColumn.vue | Draggable column, holds task cards | vuedraggable, parent via events | `js/src/components/KanbanColumn.vue` |
| TaskCard.vue | Display single task in Kanban | Parent via events | `js/src/components/TaskCard.vue` |
| TaskQuickEdit.vue | Inline edit popover | REST API, parent via events | `js/src/components/TaskQuickEdit.vue` |
| TaskFilters.vue | Filter/sort controls above board | Parent via v-model | `js/src/components/TaskFilters.vue` |
| useKanban.js | Reactive state: tasks per column, CRUD ops | REST API wrapper | `js/src/composables/useKanban.js` |
| useKeyboardShortcuts.js | Keyboard navigation logic | DOM focus, useKanban | `js/src/composables/useKeyboardShortcuts.js` |
| drupal.js | CSRF-aware fetch wrapper | /session/token, REST endpoints | `js/src/api/drupal.js` |
| kanban Twig template | Mount point HTML | Drupal theme system | `templates/group-ai-pm-kanban.html.twig` |
| REST config entities | Enable REST resources | Drupal REST config system | `config/optional/rest.resource.*.yml` |
| Vite config | Build Vue SFCs to IIFE | Vite, Vue plugin | `js/vite.config.js` |

### Modified Existing Components

| Existing Component | Modification | Reason |
|-------------------|-------------|--------|
| `group_ai_pm.info.yml` | Add dependency: `drupal:rest`, `drupal:serialization` | REST resources need these core modules |
| `group_ai_pm.routing.yml` | Add kanban board route | New page for Kanban view |
| `group_ai_pm.libraries.yml` | Add `kanban` library entry | Reference compiled Vue assets |
| `group_ai_pm.links.task.yml` | Add "Board" tab to project entity | Kanban view as local task tab |
| `group_ai_pm.permissions.yml` | Add REST access permissions | Control who can use the API |
| `group_ai_pm.module` | Add `group_ai_pm_kanban` theme hook | Template registration |
| `src/Entity/Task.php` | Add `weight` base field | Sort order within Kanban columns |
| `DashboardController.php` | Enhanced dashboard with project cards linking to Kanban | Richer entry point |
| `css/task-cards.css` | Extend with Kanban-specific card styles | Visual consistency |

### Unchanged Components

| Component | Why Unchanged |
|-----------|--------------|
| `src/Entity/Project.php` | Entity structure sufficient; Kanban is a view of Tasks |
| `ProjectAccessControlHandler.php` | Access model unchanged; REST resources check entity access |
| `TaskAccessControlHandler.php` | Same access logic, called by REST resources |
| `ProjectListBuilder.php` | Table listing stays as-is; Kanban is an alternative view |
| `TaskListBuilder.php` | Same rationale |
| `Form/SettingsForm.php` | No new settings needed for Kanban |
| `Plugin/Block/ProjectStatusBlock.php` | Block stays as-is |
| `Plugin/QueueWorker/OverdueNotificationWorker.php` | Queue processing unchanged |
| `Plugin/views/field/ProjectTaskCount.php` | Views integration unchanged |
| `modules/group_ai_pm_ai/` | AI sub-module is independent |
| `tests/` | Existing tests stay; new tests added |

## Patterns to Follow

### Pattern 1: Server-Rendered Initial State via drupalSettings

**What:** Pass the full initial Kanban state (tasks grouped by column) from the PHP controller to Vue via `drupalSettings`, not a separate API call.
**When:** Always for the initial page load.
**Why:** Eliminates a loading spinner and extra HTTP round-trip. The server already has the data when rendering the page. Vue hydrates from this state immediately.

### Pattern 2: Optimistic UI Updates

**What:** Update Vue state immediately on user action, then sync to server. Rollback on error.
**When:** Drag-and-drop, quick edits, status changes.
**Why:** Drag-and-drop must feel instant. A 200-500ms API round-trip with no visual feedback makes the board feel broken. Save the previous state, apply change optimistically, confirm or rollback.

```javascript
// In useKanban.js
async function moveTask(taskId, fromColumn, toColumn, newIndex) {
  const previousState = JSON.parse(JSON.stringify(columns.value));
  // Optimistic update.
  applyMove(taskId, fromColumn, toColumn, newIndex);
  try {
    await api.patchTaskStatus(taskId, { status: toColumn, weight: newIndex });
  } catch (error) {
    // Rollback.
    columns.value = previousState;
    Drupal.announce('Failed to move task. Change reverted.');
  }
}
```

### Pattern 3: Drupal.behaviors Bridge for Vue Mounting

**What:** Mount Vue app inside `Drupal.behaviors.attach()` using `once()` to prevent double-mounting.
**When:** Always. This is the only correct way to initialize JS components in Drupal.
**Why:** Drupal may call `attach()` multiple times (initial load, after AJAX, after BigPipe). `once()` ensures the Vue app is created exactly once. Without this, AJAX-triggered re-attachments would create duplicate Vue instances.

### Pattern 4: IIFE Build Output with External Drupal Globals

**What:** Configure Vite to output an IIFE (Immediately Invoked Function Expression) that treats `Drupal`, `once`, and `drupalSettings` as external globals.
**When:** Always for the Vite build config.
**Why:** Drupal loads libraries as `<script>` tags, not ES modules. The compiled Vue code must execute in the global scope. Drupal core libraries provide the globals; bundling them would create version conflicts.

### Pattern 5: Committed Build Output

**What:** Run `npm run build` during development, commit `js/dist/` to the repository.
**When:** Before every commit that changes Vue source files.
**Why:** Drupal modules must work without a build step. End users install the module and expect it to work. They should not need Node.js or npm. The `js/dist/` directory is the distributable artifact.

### Pattern 6: REST Resources Over JSON:API for Custom Shapes

**What:** Use custom `@RestResource` plugins instead of the core JSON:API module.
**When:** The response shape needs to differ from the strict JSON:API spec (grouped tasks by column, aggregated counts, nested relationships in a single response).
**Why:** JSON:API returns flat, spec-compliant responses. A Kanban board needs tasks pre-grouped by status column with project metadata in a single request. Custom REST resources let us shape the response exactly as the Vue component needs it.

## Anti-Patterns to Avoid

### Anti-Pattern 1: Full SPA Replacing Drupal Admin

**What:** Building the entire admin UX as a Vue SPA with client-side routing.
**Why bad:** Duplicates Drupal's routing, authentication, permission checking, and admin theme. Breaks Drupal's toolbar, contextual links, and admin menu. Creates a maintenance burden of two routing systems. Entity forms, settings pages, and list builders already work well as Drupal pages.
**Instead:** Embed Vue only for the Kanban board. All other pages stay as standard Drupal.

### Anti-Pattern 2: Using JSON:API for the Kanban Endpoint

**What:** Relying on core JSON:API to serve Kanban board data.
**Why bad:** JSON:API returns tasks as a flat collection with relationships as links. The Kanban needs tasks grouped by status with project metadata embedded. This requires multiple requests and client-side grouping, adding latency and complexity.
**Instead:** Custom REST resource that returns exactly `{ columns: { todo: [...], in_progress: [...] } }`.

### Anti-Pattern 3: Bundling Drupal Core Libraries in Vite Build

**What:** Importing `Drupal` or `jQuery` into the Vue build.
**Why bad:** Creates version conflicts. Drupal already loads its own `Drupal` global, `once()`, and `drupalSettings`. A second copy breaks behaviors and events.
**Instead:** Declare them as `external` in Vite config. Access via global scope in the IIFE wrapper.

### Anti-Pattern 4: Loading Vue from CDN

**What:** Using a `<script>` tag to load Vue from unpkg/cdnjs.
**Why bad:** Drupal modules must be self-contained for offline/intranet use. CDN dependency breaks air-gapped deployments. Also prevents tree-shaking.
**Instead:** Install Vue as an npm dependency, bundle via Vite. The compiled output includes only the Vue runtime code used.

### Anti-Pattern 5: Separate CSRF Token Fetch Per Request

**What:** Calling `/session/token` before every PATCH request.
**Why bad:** Doubles the number of HTTP requests. The CSRF token does not change during a session.
**Instead:** Fetch once on page load (or on first write operation), cache in a module-level variable. Refresh only if a 403 response indicates the token expired.

### Anti-Pattern 6: Skipping once() for Vue Mounting

**What:** Mounting Vue directly in a `<script>` tag or without `once()`.
**Why bad:** Drupal re-triggers `Drupal.behaviors.attach()` after every AJAX response and BigPipe delivery. Without `once()`, the Vue app gets mounted multiple times, creating duplicate DOM trees and memory leaks.
**Instead:** Always use `once('kanban-app', '#kanban-app', context)` in the behavior's attach function.

## Build Order (Dependency Chain)

```
Phase A: REST API Layer
  Must be built FIRST -- Vue components depend on API responses.
  1. Add 'weight' base field to Task entity (update hook)
  2. Create KanbanResource (GET) -- returns tasks grouped by status
  3. Create TaskStatusResource (PATCH) -- handles drag-drop
  4. Create TaskReorderResource (PATCH) -- handles within-column reorder
  5. Create TaskQuickEditResource (PATCH) -- handles inline edits
  6. Ship REST config entities in config/optional/
  7. Add REST permissions to .permissions.yml
  8. Verify endpoints with curl before writing any JavaScript

Phase B: Vite Build Pipeline
  Must exist BEFORE Vue components can be compiled.
  1. Initialize npm project in js/ directory (package.json)
  2. Install dependencies: vue, vuedraggable@next, vite, @vitejs/plugin-vue
  3. Create vite.config.js with IIFE output + external globals
  4. Create stub main.js that mounts a "Hello Kanban" div
  5. Run npm run build, verify js/dist/kanban.js exists
  6. Add 'kanban' library to .libraries.yml
  7. Verify library loads on a test page via #attached

Phase C: Page Shell + Drupal Integration
  Controller + route + template for the Kanban page.
  1. Create KanbanController with board() method
  2. Add route to .routing.yml
  3. Add "Board" local task tab to .links.task.yml
  4. Register group_ai_pm_kanban theme hook in .module
  5. Create Twig template with #kanban-app mount point
  6. Pass initial task data via drupalSettings
  7. Verify page renders with loading state

Phase D: Vue Kanban Components
  The interactive frontend. Depends on A (API), B (build), C (mount point).
  1. Create api/drupal.js -- CSRF-aware fetch wrapper
  2. Create KanbanBoard.vue -- root component, receives props from drupalSettings
  3. Create KanbanColumn.vue -- draggable column with vuedraggable
  4. Create TaskCard.vue -- card display with status badge, priority, assignee
  5. Create useKanban.js composable -- state management, optimistic updates
  6. Wire drag-and-drop: column change -> API call -> rollback on error
  7. Build and test full drag-drop flow

Phase E: Enhanced UX
  Polish layer. Depends on D (working Kanban).
  1. Create TaskQuickEdit.vue -- inline edit popover
  2. Create TaskFilters.vue -- filter by priority, assignee, due date
  3. Create useKeyboardShortcuts.js -- j/k/h/l navigation, Enter to edit
  4. Add animations to drag-drop transitions
  5. Style Kanban to match admin theme (Claro compatibility)

Phase F: Dashboard + AJAX Enhancements
  Drupal-side improvements alongside Kanban.
  1. Enhance DashboardController with project cards linking to Kanban
  2. Add AJAX status toggle to task list pages (Drupal #ajax, no Vue)
  3. Add dashboard statistics (tasks by status, overdue count)
  4. Add recent activity feed

Phase G: Testing
  Depends on all above being functional.
  1. Kernel tests for REST resources (response shape, access control)
  2. Functional tests for Kanban page rendering
  3. Browser tests for drag-drop interaction (eval-browser agent)
  4. Static assertions for Vue build output (files exist, correct format)
```

**Critical path:** A (REST) -> B (Vite) -> C (Page Shell) -> D (Vue Components) -> E (UX Polish)

**Parallel opportunities:**
- Phase A and Phase B are independent -- build pipeline and REST resources can be done simultaneously
- Phase F (Dashboard/AJAX) is independent of Phases D/E (Vue Kanban)
- Phase G tests can start as early as Phase A (REST endpoint testing)

## File Map: All New and Modified Files

### New Files

| File | Purpose | Phase |
|------|---------|-------|
| `src/Plugin/rest/resource/KanbanResource.php` | GET tasks by column for project | A |
| `src/Plugin/rest/resource/TaskStatusResource.php` | PATCH task status (drag-drop) | A |
| `src/Plugin/rest/resource/TaskReorderResource.php` | PATCH task weight (reorder) | A |
| `src/Plugin/rest/resource/TaskQuickEditResource.php` | PATCH task fields (inline edit) | A |
| `config/optional/rest.resource.group_ai_pm_kanban.yml` | Enable Kanban GET endpoint | A |
| `config/optional/rest.resource.group_ai_pm_task_status.yml` | Enable task status PATCH | A |
| `config/optional/rest.resource.group_ai_pm_task_reorder.yml` | Enable task reorder PATCH | A |
| `config/optional/rest.resource.group_ai_pm_task_quick_edit.yml` | Enable task quick edit PATCH | A |
| `js/package.json` | npm project definition | B |
| `js/vite.config.js` | Vite build configuration | B |
| `js/src/main.js` | Vue app entry point + Drupal.behaviors bridge | B/D |
| `js/src/api/drupal.js` | CSRF-aware API wrapper | D |
| `js/src/components/KanbanBoard.vue` | Root Kanban component | D |
| `js/src/components/KanbanColumn.vue` | Draggable column | D |
| `js/src/components/TaskCard.vue` | Task card display | D |
| `js/src/components/TaskQuickEdit.vue` | Inline edit popover | E |
| `js/src/components/TaskFilters.vue` | Filter/sort controls | E |
| `js/src/composables/useKanban.js` | State management | D |
| `js/src/composables/useKeyboardShortcuts.js` | Keyboard navigation | E |
| `js/dist/kanban.js` | Compiled Vue app (committed) | B |
| `js/dist/kanban.css` | Compiled styles (committed) | B |
| `src/Controller/KanbanController.php` | Kanban page shell controller | C |
| `templates/group-ai-pm-kanban.html.twig` | Mount point template | C |
| `tests/src/Functional/KanbanPageTest.php` | Kanban page rendering test | G |
| `tests/src/Kernel/RestResourceTest.php` | REST endpoint tests | G |

### Modified Files

| File | Change | Phase |
|------|--------|-------|
| `group_ai_pm.info.yml` | Add `drupal:rest`, `drupal:serialization` dependencies | A |
| `group_ai_pm.routing.yml` | Add kanban board route | C |
| `group_ai_pm.libraries.yml` | Add `kanban` library entry | B |
| `group_ai_pm.links.task.yml` | Add "Board" tab to project entity | C |
| `group_ai_pm.permissions.yml` | Add `access group_ai_pm rest` permission | A |
| `group_ai_pm.module` | Add `group_ai_pm_kanban` theme hook | C |
| `src/Entity/Task.php` | Add `weight` base field definition | A |
| `src/Controller/DashboardController.php` | Enhanced project cards + Kanban links | F |
| `.gitignore` | Add `js/node_modules/` exclusion | B |

## Scalability Considerations

| Concern | At 50 tasks | At 500 tasks | At 5000 tasks |
|---------|------------|-------------|--------------|
| Initial load | All tasks via drupalSettings (~5KB) | Paginate: load 100 per column, lazy-load more | Virtual scrolling per column, server-side pagination |
| Drag-drop | Single PATCH per move | Same (single entity update) | Same (no N+1) |
| API response | Fast (~50ms) | Add entity query cache tags | Consider Views REST export or custom cache layer |
| Vue rendering | No performance concern | No performance concern | Virtual scroll required; consider vue-virtual-scroller |
| CSRF tokens | One fetch per session | Same | Same |

For the v4.0 scope (a project management tool), 500 tasks per project is the realistic upper bound. The architecture handles this without virtualization.

## Sources

- [Custom REST Resources - Drupal.org](https://www.drupal.org/docs/develop/drupal-apis/restful-web-services-api/custom-rest-resources) (HIGH): Plugin annotation, ResourceBase class, REST config entities
- [Custom REST API in Drupal 10/11 - Drupak](https://drupak.com/blog/custom-rest-api-drupal-10-11-rest-resource-plugin-2025) (MEDIUM): Full REST resource example with DI
- [CSRF Access Checking - Drupal.org](https://www.drupal.org/docs/8/api/routing-system/access-checking-on-routes/csrf-access-checking) (HIGH): X-CSRF-Token header requirement, /session/token endpoint
- [vue.draggable.next - GitHub](https://github.com/SortableJS/vue.draggable.next) (HIGH): Vue 3 drag-and-drop, vuedraggable@next, item-key, group prop
- [Creating a Kanban Board with Vue Draggable - JScrambler](https://jscrambler.com/blog/kanban-board-vue-draggable) (MEDIUM): Kanban column pattern with vuedraggable
- [Automating Drupal Front-end with ViteJS - Mario Hernandez](https://mariohernandez.io/blog/automating-your-drupal-front-end-with-vitejs/) (MEDIUM): Vite library mode, consistent filenames, .libraries.yml integration
- [JavaScript API Overview - Drupal.org](https://www.drupal.org/docs/drupal-apis/javascript-api/javascript-api-overview) (HIGH): Drupal.behaviors, once(), drupalSettings
- [Understanding JavaScript Behaviors in Drupal - Lullabot](https://www.lullabot.com/articles/understanding-javascript-behaviors-in-drupal) (HIGH): Behavior lifecycle, attach/detach, context parameter
- [AJAX API - Drupal.org](https://www.drupal.org/docs/develop/drupal-apis/ajax-api) (HIGH): #ajax form element, callback commands
- [Core AJAX Callback Commands - Drupal.org](https://www.drupal.org/docs/develop/drupal-apis/ajax-api/core-ajax-callback-commands) (HIGH): ReplaceCommand, HtmlCommand
- [Local Tasks - Drupal.org](https://www.drupal.org/docs/drupal-apis/menu-api/providing-module-defined-local-tasks) (HIGH): links.task.yml, base_route, parent_id
- [Ways of Using Vue - Vue.js](https://vuejs.org/guide/extras/ways-of-using-vue.html) (HIGH): Embedded components vs SPA, mounting on existing pages
- [Using Vue in a non-SPA - JErickson.net](https://jerickson.net/using-vue-in-non-spa/) (MEDIUM): Loader pattern for embedding Vue in server-rendered pages
- [Vite and Storybook for Drupal - PreviousNext](https://www.previousnext.com.au/blog/vite-and-storybook-frontend-tooling-drupal) (MEDIUM): Vite config for Drupal asset pipeline
- [Vue.js Library - Drupal.org](https://www.drupal.org/project/vuejs) (LOW): Contrib module approach (not recommended for our use case)

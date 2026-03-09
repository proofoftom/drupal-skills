# Phase 18: REST API + Vue Infrastructure + Basic Board - Research

**Researched:** 2026-03-08
**Domain:** Custom REST controllers, Vue 3 embedded island, Vite IIFE build, Kanban board with drag-and-drop in Drupal 10 admin UI
**Confidence:** HIGH

## Summary

Phase 18 is the foundational phase that validates the entire v4.0 technical approach end-to-end. It delivers a working Kanban board embedded in the Drupal admin UI: users navigate to `/admin/content/project/{project}/board`, see tasks as draggable cards in 4 status columns, and can move cards between columns to update task status. The phase covers 26 requirements across three domains: REST API (API-01 through API-08), Vue infrastructure (VUE-01 through VUE-08), and the Kanban board itself (BOARD-01 through BOARD-10).

The architecture is an **embedded Vue island** within Drupal's admin chrome, not a decoupled SPA. A custom Drupal controller (`KanbanController`) renders a page with a `<div id="kanban-app">` mount point, pre-loads all task data into `drupalSettings`, and attaches the compiled Vue library. The Vue app hydrates from `drupalSettings` (no loading spinner for initial data), renders the board, and communicates with custom REST controllers for mutations. Custom controllers with `CacheableJsonResponse` were chosen over both JSON:API (wrong response shape) and REST resource plugins (unnecessary module dependencies). This means **zero new Drupal module dependencies** -- `CacheableJsonResponse` is in `Drupal\Core\Cache`, which is core.

The three critical integration points that will make or break this phase are: (1) CSRF token handling -- REST routes called by JavaScript MUST use `_csrf_request_header_token: 'TRUE'` in route requirements and the Vue app MUST fetch from `/session/token` before any mutation; (2) Drupal.behaviors bridge -- the Vue app MUST mount inside a `once()` guard to prevent double-mounting; (3) `_format: json` on all API routes so Drupal returns JSON error responses, not HTML. Every one of these is a verified pitfall from v3.0 eval experience and official Drupal documentation.

**Primary recommendation:** Build the REST API endpoints first (testable with curl), then the Vite pipeline (testable with a stub), then the page shell (testable by verifying drupalSettings), then the Vue board (testable by dragging cards). Each layer is independently verifiable before the next layer depends on it.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| API-01 | Custom REST controller serving tasks grouped by status column for a project (GET with CacheableJsonResponse) | Custom controllers with CacheableJsonResponse pattern verified -- Drupal\Core\Cache namespace, no extra module deps. Route returns shaped JSON: `{ columns: { todo: [...], in_progress: [...] } }` |
| API-02 | PATCH endpoint for task status update with `_csrf_request_header_token` protection | CSRF mechanism confirmed: `_csrf_request_header_token: 'TRUE'` in route requirements, X-CSRF-Token header from `/session/token`. MUST pair with permission requirement (auto-passes for anonymous). |
| API-03 | PATCH endpoint for task inline edits (title, priority, assignee) with entity access checks | Same CSRF + entity access pattern. TaskAccessControlHandler already has `update` operation checking `edit any task` / `edit own task` permissions. Call `$task->access('update')` in controller. |
| API-04 | POST endpoint for task quick-create from board with status pre-fill | POST route with CSRF + `create task` permission. Accept `{ title, status }` JSON body, create Task entity with project reference from URL parameter. |
| API-05 | GET endpoint for project summary data (task counts per status) for dashboard | Aggregate query via entity storage. CacheableJsonResponse with cache tags `project:{id}` and `task_list`. Not critical path for Phase 18 board but included in API layer. |
| API-06 | Entity-level access checks on all endpoints (not just route-level permission) | CRITICAL: Route `_permission` checks "can user access the feature" but NOT "can user access THIS entity." Controllers must call `$entity->access('view')` / `$entity->access('update')` on every loaded entity. Existing TaskAccessControlHandler and ProjectAccessControlHandler handle the logic. |
| API-07 | `_format: json` route requirement on all API routes | Verified: without `_format: json` in route requirements, Drupal returns HTML error pages that Vue cannot parse. Adding it routes errors through Drupal's JSON exception subscriber. |
| API-08 | Cache tags on all JSON responses (task:{id}, task_list, project:{id}) | CacheableJsonResponse.addCacheableDependency($entity) automatically adds the entity's cache tags. Also add `CacheableMetadata` with custom tags like `task_list` for list invalidation. |
| VUE-01 | Vite build pipeline producing IIFE output with stable filenames, committed to js/dist/ | Vite 6.x library mode with `formats: ['iife']`, `fileName: () => 'kanban.js'`, externalize Drupal globals. Compiled output committed to repo. `node_modules/` gitignored. |
| VUE-02 | Vue 3 externalized as separate Drupal library (js/vendor/vue.global.prod.js) | Vue 3 global production build (~34 KB gz) copied from node_modules to js/vendor/, declared as `group_ai_pm/vue` library with `header: true` and `weight: -20`. Kanban library depends on it. |
| VUE-03 | Drupal.behaviors bridge with once() guard preventing double-mounting | `once('kanban-app', '#kanban-app', context)` inside `Drupal.behaviors.groupAiPmKanban.attach()`. Store app reference on DOM element for cleanup in `detach`. Declare `core/once` as library dependency. |
| VUE-04 | drupalSettings data passing (project ID, API base URL, CSRF token URL, status/priority labels, permissions) | Controller attaches `#attached.drupalSettings.groupAiPm.kanban` with all config. Vue reads on mount. NEVER hardcode paths. Include base URL for proxy/subdirectory installations. |
| VUE-05 | core/drupalSettings and core/once declared as library dependencies | Both MUST appear in kanban library dependencies. Without `core/drupalSettings`, the settings script may load AFTER kanban JS. Without `core/once`, the `once()` function is undefined. |
| VUE-06 | CSRF token fetched once on mount, cached, included in all mutation requests | Fetch from `/session/token` (URL passed via drupalSettings) in `onMounted()` or first write call. Cache in module-level variable. Include as `X-CSRF-Token` header on POST/PATCH/DELETE. Token is per-session, not per-request. |
| VUE-07 | BEM-namespaced CSS (gapm- prefix) using Claro admin theme CSS custom properties | All classes prefixed `gapm-` (e.g., `gapm-kanban__column`, `gapm-task-card--priority-high`). Use Claro CSS custom properties (`var(--color-gray-050)`, `var(--space-m)`). Vue `<style scoped>` adds data attributes for extra isolation. |
| VUE-08 | Bundle size under 100 KB gzipped (Vue runtime + SortableJS + app code), loaded only on board route | Estimated ~65 KB gz total. Library attached ONLY in KanbanController render array, never globally. Vue externalized as separate cacheable library. |
| BOARD-01 | Kanban board view per project with 4 status columns (To Do, In Progress, Review, Done) | Maps 1:1 to existing Task.status allowed_values: `todo`, `in_progress`, `review`, `done`. Zero entity schema changes. Column config passed via drupalSettings. |
| BOARD-02 | Drag-and-drop between columns via vue-draggable-plus/SortableJS updating task status | vue-draggable-plus VueDraggable component with `group: { name: 'tasks' }` enables cross-column drag. `onEnd` callback triggers PATCH to status endpoint. Optimistic update with basic rollback. |
| BOARD-03 | Task cards displaying title, priority badge (color-coded), assignee name, due date | All data from existing entity fields. Priority badge CSS: low=gray, medium=blue, high=orange, critical=red. Assignee display name from user entity. Due date formatted. |
| BOARD-04 | Status-colored column headers with task count badges | Computed property `tasks.filter(t => t.status === columnId).length`. Column header color via CSS class per status. |
| BOARD-05 | Board route at /admin/content/project/{project}/board with "Board" local task tab | Route path MUST follow entity canonical prefix. `base_route: entity.project.canonical` in links.task.yml. Parameter name `{project}` MUST match entity route. `parameters.project.type: entity:project` for upcasting. `_admin_route: TRUE`. |
| BOARD-06 | Loading skeleton, empty column states, and error states | Initial data from drupalSettings = no loading state on first render. Empty column: "No tasks" message with create prompt. Error state: toast notification with retry. Skeleton needed only for subsequent API calls (not initial). |
| BOARD-07 | Responsive column layout (4 columns side-by-side at 1200px+, horizontal scroll on narrow) | CSS flexbox with `min-width` per column. `overflow-x: auto` on board container. Column `min-width: 250px`. At narrow widths, columns maintain min-width and board scrolls horizontally. |
| BOARD-08 | Keyboard alternative to drag-and-drop for status changes (WCAG 2.5.7 compliance) | Status change menu/dropdown per card accessible via button click or keyboard. Not full vim-style navigation (that is deferred). Minimal: a "Move to..." button or select that changes status without dragging. |
| BOARD-09 | Task quick-create via inline title input at column header ("+" button) | "+" button on column header reveals inline text input. Enter submits POST to quick-create endpoint with column's status pre-filled. Card appears in column immediately (optimistic). Escape cancels. |
| BOARD-10 | Server-rendered initial state via drupalSettings (no extra API call on page load) | KanbanController pre-loads all tasks for the project, groups by status, serializes to drupalSettings. Vue app receives as prop, hydrates reactive state. No GET /api/... call on mount. |
</phase_requirements>

## Standard Stack

### Core

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Vue 3 (global prod build) | ^3.5.0 | Reactive framework for Kanban board | Only framework with Drupal ecosystem support (drupal/vuejs contrib, issue #2913628). Composition API maps to composable state management. |
| vue-draggable-plus | ^0.6.0 | Cross-column drag-and-drop with Vue 3 integration | Purpose-built Vue 3 wrapper for SortableJS with Composition API `useDraggable()`. Replaces unmaintained vuedraggable@next. 3,300+ stars, 38K weekly npm downloads. |
| SortableJS | ^1.15.0 | Drag-and-drop engine (peer dep of vue-draggable-plus) | De facto standard for web DnD. Touch support built-in. Cross-browser. |
| Vite | ^6.0.0 | Compile Vue SFCs to IIFE for Drupal | Official Vue 3 build tool. Library mode produces single IIFE file. Dev dependency only. |
| @vitejs/plugin-vue | ^5.0.0 | Vite plugin for Vue SFC compilation | Official companion to Vite for Vue projects. |
| CacheableJsonResponse | Drupal core | JSON responses with cache tags/contexts | In `Drupal\Core\Cache` -- no extra module needed. Integrates with Dynamic Page Cache. |
| Drupal.behaviors + once() | Drupal core | Vue mount lifecycle management | ONLY correct way to init JS in Drupal. Handles AJAX re-attachment. |

### Supporting

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| tinykeys | ^3.0.0 | Keyboard shortcuts (~650 B) | Phase 18 only stubs keyboard shortcuts for BOARD-08 (status change). Full keyboard nav in Phase 19. |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Custom controllers | JSON:API module | JSON:API returns flat entity data; Kanban needs tasks grouped by status. Would require multiple requests + client-side reshaping. |
| Custom controllers | REST resource plugins (@RestResource) | Adds `drupal:rest` + `drupal:serialization` module dependencies. Config entities in config/optional/. More infrastructure for internal admin endpoints. |
| vue-draggable-plus | vuedraggable@next | Unmaintained, not updated for Vue 3 Composition API patterns. |
| vue-draggable-plus | Raw SortableJS | Requires manual Vue reactivity wiring for cross-column moves. |
| Vue 3 | Alpine.js | Cannot handle multi-column stateful DnD + optimistic updates. |
| Vite IIFE | Webpack | Slower, more config, not Vue 3's official tool. |

**Installation (for development):**
```bash
cd modules/group_ai_pm
npm init -y
npm install vue@^3.5.0 vue-draggable-plus@^0.6.0 sortablejs@^1.15.0 tinykeys@^3.0.0
npm install -D vite@^6.0.0 @vitejs/plugin-vue@^5.0.0
cp node_modules/vue/dist/vue.global.prod.js js/vendor/vue.global.prod.js
```

## Architecture Patterns

### Recommended Project Structure (new files for Phase 18)

```
modules/group_ai_pm/
  src/Controller/
    KanbanController.php           # Page shell: renders mount point + drupalSettings
    TaskApiController.php          # REST: GET kanban, PATCH status, PATCH edit, POST create
    ProjectApiController.php       # REST: GET project summary
  templates/
    group-ai-pm-kanban.html.twig   # Mount point template
  js/
    vendor/
      vue.global.prod.js           # Vue 3 production build (committed)
    src/
      main.js                      # Entry: Drupal.behaviors bridge with once()
      api/
        drupal.js                  # CSRF-aware fetch wrapper
      components/
        KanbanBoard.vue            # Root: receives drupalSettings, manages state
        KanbanColumn.vue           # Column: VueDraggable with group option
        TaskCard.vue               # Card: title, priority badge, assignee, due date
        QuickCreateForm.vue        # Inline title input for new tasks
      composables/
        useKanban.js               # Board state: tasks per column, CRUD operations
    dist/
      kanban.js                    # Compiled IIFE (committed)
      kanban.css                   # Extracted CSS (committed)
    package.json
    vite.config.js
  group_ai_pm.routing.yml         # Modified: add API routes + board route
  group_ai_pm.libraries.yml       # Modified: add vue + kanban libraries
  group_ai_pm.links.task.yml      # Modified: add Board tab
  group_ai_pm.module              # Modified: add kanban theme hook
```

### Pattern 1: Custom REST Controller with CacheableJsonResponse

**What:** A plain Drupal controller class returning `CacheableJsonResponse` with entity data shaped for the Kanban board. Route defined in `routing.yml` with `_format: json` requirement.

**When to use:** All Phase 18 API endpoints. This is the approach for ALL v4.0 REST routes.

**Why:** No extra module dependencies. `CacheableJsonResponse` lives in `Drupal\Core\Cache` (core). Routes are defined directly, no config entities needed. Response shape is fully controlled.

**Example:**
```php
// Source: Drupal core CacheableJsonResponse + verified patterns
namespace Drupal\group_ai_pm\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\group_ai_pm\Entity\ProjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class TaskApiController extends ControllerBase {

  public function kanban(ProjectInterface $project) {
    if (!$project->access('view')) {
      throw new AccessDeniedHttpException();
    }

    $task_storage = $this->entityTypeManager()->getStorage('task');
    $task_ids = $task_storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('project', $project->id())
      ->sort('priority', 'DESC')
      ->sort('due_date', 'ASC')
      ->sort('created', 'DESC')
      ->execute();

    $tasks = $task_storage->loadMultiple($task_ids);
    $columns = ['todo' => [], 'in_progress' => [], 'review' => [], 'done' => []];

    foreach ($tasks as $task) {
      if (!$task->access('view')) {
        continue;
      }
      $status = $task->getStatus();
      if (isset($columns[$status])) {
        $columns[$status][] = $this->serializeTask($task);
      }
    }

    $data = [
      'project' => [
        'id' => (int) $project->id(),
        'title' => $project->getTitle(),
      ],
      'columns' => $columns,
    ];

    $response = new CacheableJsonResponse($data);
    $cache_metadata = new CacheableMetadata();
    $cache_metadata->addCacheTags(['task_list', 'project:' . $project->id()]);
    $cache_metadata->addCacheContexts(['user.permissions']);
    $response->addCacheableDependency($cache_metadata);
    $response->addCacheableDependency($project);
    foreach ($tasks as $task) {
      $response->addCacheableDependency($task);
    }

    return $response;
  }
}
```

**Route definition:**
```yaml
group_ai_pm.api.kanban:
  path: '/api/group-ai-pm/project/{project}/kanban'
  defaults:
    _controller: '\Drupal\group_ai_pm\Controller\TaskApiController::kanban'
  requirements:
    _permission: 'access group_ai_pm dashboard'
    _format: json
  options:
    _admin_route: TRUE
    parameters:
      project:
        type: entity:project
    no_cache: TRUE
```

### Pattern 2: CSRF-Protected PATCH Endpoint for JavaScript

**What:** A route with `_csrf_request_header_token: 'TRUE'` that accepts JSON body from JavaScript fetch() calls.

**When to use:** ALL mutation endpoints called by Vue (PATCH status, PATCH edit, POST create).

**CRITICAL distinction:**
- `_csrf_token: 'TRUE'` = validates query parameter token. For server-rendered links (like existing `project.complete`).
- `_csrf_request_header_token: 'TRUE'` = validates `X-CSRF-Token` HTTP header. For JavaScript REST calls.
- Using the wrong one silently 403s every mutation.

**Example:**
```yaml
group_ai_pm.api.task_status:
  path: '/api/group-ai-pm/task/{task}/status'
  defaults:
    _controller: '\Drupal\group_ai_pm\Controller\TaskApiController::updateStatus'
  methods: [PATCH]
  requirements:
    _permission: 'access group_ai_pm dashboard'
    _csrf_request_header_token: 'TRUE'
    _format: json
  options:
    _admin_route: TRUE
    parameters:
      task:
        type: entity:task
```

### Pattern 3: Drupal.behaviors Bridge with once() Guard

**What:** Mount the Vue app inside `Drupal.behaviors.attach()` using `once()` to prevent double-mounting from AJAX/BigPipe re-attachment.

**When to use:** ALWAYS. This is the only correct way to initialize Vue in Drupal.

**Example:**
```javascript
// Source: Drupal JS API overview + once() documentation
(function (Drupal, once, drupalSettings) {
  'use strict';

  Drupal.behaviors.groupAiPmKanban = {
    attach: function (context) {
      once('kanban-app', '#kanban-app', context).forEach(function (element) {
        var config = drupalSettings.groupAiPm.kanban;
        var app = Vue.createApp(KanbanBoard, {
          projectId: config.projectId,
          columns: config.columns,
          initialTasks: config.tasks,
          apiBaseUrl: config.apiBaseUrl,
          csrfTokenUrl: config.csrfTokenUrl,
          permissions: config.permissions,
          statusLabels: config.statusLabels,
          priorityLabels: config.priorityLabels,
        });
        app.mount(element);
        element._vueApp = app;
      });
    },
    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        var elements = context.querySelectorAll
          ? context.querySelectorAll('[data-once="kanban-app"]')
          : [];
        elements.forEach(function (element) {
          if (element._vueApp) {
            element._vueApp.unmount();
            element._vueApp = null;
          }
        });
      }
    },
  };
})(Drupal, once, drupalSettings);
```

### Pattern 4: Server-Rendered Initial State via drupalSettings

**What:** Controller pre-loads all Kanban data and passes it to Vue via `drupalSettings`. Vue hydrates from this data -- no initial API call, no loading spinner.

**When to use:** KanbanController.board() -- the page that hosts the Vue mount point.

**Example:**
```php
public function board(ProjectInterface $project) {
  // Pre-load tasks grouped by status.
  $tasks = $this->loadTasksByStatus($project);

  return [
    '#theme' => 'group_ai_pm_kanban',
    '#project' => $project,
    '#attached' => [
      'library' => ['group_ai_pm/kanban'],
      'drupalSettings' => [
        'groupAiPm' => [
          'kanban' => [
            'projectId' => (int) $project->id(),
            'projectTitle' => $project->getTitle(),
            'apiBaseUrl' => '/api/group-ai-pm',
            'csrfTokenUrl' => '/session/token',
            'columns' => [
              ['id' => 'todo', 'label' => $this->t('To Do')],
              ['id' => 'in_progress', 'label' => $this->t('In Progress')],
              ['id' => 'review', 'label' => $this->t('Review')],
              ['id' => 'done', 'label' => $this->t('Done')],
            ],
            'statusLabels' => [
              'todo' => (string) $this->t('To Do'),
              'in_progress' => (string) $this->t('In Progress'),
              'review' => (string) $this->t('Review'),
              'done' => (string) $this->t('Done'),
            ],
            'priorityLabels' => [
              'low' => (string) $this->t('Low'),
              'medium' => (string) $this->t('Medium'),
              'high' => (string) $this->t('High'),
              'critical' => (string) $this->t('Critical'),
            ],
            'tasks' => $tasks,
            'permissions' => [
              'createTask' => $this->currentUser()->hasPermission('create task'),
              'editAnyTask' => $this->currentUser()->hasPermission('edit any task'),
            ],
          ],
        ],
      ],
    ],
  ];
}
```

### Pattern 5: Vite IIFE Build with External Drupal Globals

**What:** Vite compiles Vue SFCs to a single IIFE file. Drupal globals (`Drupal`, `once`, `drupalSettings`) and Vue are externalized -- they load as separate Drupal libraries.

**When to use:** The Vite build configuration for all Vue source.

**Example:**
```javascript
// vite.config.js
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
  plugins: [vue()],
  build: {
    outDir: resolve(__dirname, 'dist'),
    emptyOutDir: true,
    lib: {
      entry: resolve(__dirname, 'src/main.js'),
      name: 'GroupAiPmKanban',
      formats: ['iife'],
      fileName: () => 'kanban.js',
    },
    rollupOptions: {
      external: ['vue'],
      output: {
        globals: {
          vue: 'Vue',
        },
        assetFileNames: 'kanban.[ext]',
      },
    },
  },
});
```

**CRITICAL:** Drupal globals (`Drupal`, `once`, `drupalSettings`) do NOT need Vite externalization because the IIFE wrapper's `main.js` accesses them from the global scope directly (they are NOT `import`ed). Only `vue` needs externalization because Vue SFC `<script setup>` blocks `import { ref } from 'vue'`.

### Pattern 6: CSRF-Aware Fetch Wrapper

**What:** A thin API service that fetches the CSRF token once and includes it in all mutation requests.

**When to use:** All Vue-to-Drupal API communication.

**Example:**
```javascript
// js/src/api/drupal.js
let csrfToken = null;

async function ensureCsrfToken(csrfTokenUrl) {
  if (!csrfToken) {
    const response = await fetch(csrfTokenUrl);
    csrfToken = await response.text();
  }
  return csrfToken;
}

export async function apiGet(url) {
  const response = await fetch(url + '?_format=json');
  if (!response.ok) {
    throw new Error('API error: ' + response.status);
  }
  return response.json();
}

export async function apiMutate(method, url, data, csrfTokenUrl) {
  const token = await ensureCsrfToken(csrfTokenUrl);
  const response = await fetch(url + '?_format=json', {
    method: method,
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': token,
    },
    body: JSON.stringify(data),
  });
  if (response.status === 403) {
    // Token may have expired -- clear cache and retry once.
    csrfToken = null;
    const retryToken = await ensureCsrfToken(csrfTokenUrl);
    const retry = await fetch(url + '?_format=json', {
      method: method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': retryToken,
      },
      body: JSON.stringify(data),
    });
    if (!retry.ok) {
      throw new Error('API error: ' + retry.status);
    }
    return retry.json();
  }
  if (!response.ok) {
    throw new Error('API error: ' + response.status);
  }
  return response.json();
}
```

### Anti-Patterns to Avoid

- **Using `_csrf_token` instead of `_csrf_request_header_token` for JS routes:** `_csrf_token` validates a URL query parameter, not an HTTP header. JavaScript fetch() sends headers, not query tokens. Silent 403 on every mutation.
- **Omitting `_format: json` from route requirements:** Drupal returns HTML error pages. Vue gets `SyntaxError: Unexpected token '<'` when parsing.
- **Mounting Vue without `once()` guard:** Every AJAX response on the page triggers `attachBehaviors()`. Without `once()`, Vue mounts multiple times = duplicate event listeners, memory leaks, erratic DnD.
- **Bundling Drupal globals in Vite build:** `Drupal`, `once`, `drupalSettings` are provided by core. Bundling them creates version conflicts.
- **Loading Vue from CDN:** Module must work offline/air-gapped. Ship `vue.global.prod.js` in `js/vendor/`.
- **Attaching kanban library globally:** Library MUST attach only in KanbanController, never via hook_page_attachments or global library.
- **Adding `drupal:rest` or `drupal:serialization` module dependencies:** Not needed. Custom controllers with `CacheableJsonResponse` (core) are sufficient.
- **Adding a `weight` field to Task entity:** FEATURES.md explicitly defers manual card ordering. Sort deterministically: priority DESC, due_date ASC, created DESC.
- **Relying on route-level permission alone for entity access:** Route permission checks "can user access this feature." Entity access checks "can user access THIS specific entity." Both are required.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Drag-and-drop | Raw DOM drag events + manual state sync | vue-draggable-plus + SortableJS | Cross-column moves, touch support, animation, clone/ghost -- 100+ edge cases |
| CSRF token management | Per-request token fetch | Single fetch + module-level cache | Token is per-session. Fetching per-request doubles HTTP calls. |
| JSON serialization | Manual `json_encode()` | `CacheableJsonResponse` | Handles cache metadata, headers, content type automatically |
| Vue mount lifecycle | Direct `createApp().mount()` | Drupal.behaviors + `once()` | Only pattern that survives AJAX re-attachment and BigPipe |
| CSS scoping | Manual namespace convention only | Vue `<style scoped>` + BEM prefix | Scoped adds data attributes automatically; BEM handles non-Vue contexts |
| Build pipeline | Webpack / manual bundling | Vite library mode | Official Vue 3 tool. IIFE output, HMR, tree-shaking built-in. |

**Key insight:** The integration layer between Vue and Drupal is where custom code lives. The individual pieces (Vue components, Drupal controllers, SortableJS DnD) are all well-solved. The value is in wiring them together correctly.

## Common Pitfalls

### Pitfall 1: CSRF Token Not Fetched Before First Mutation (CRITICAL)
**What goes wrong:** Vue app loads, GET works, user drags a card, PATCH returns 403. Every mutation fails.
**Why it happens:** Drupal requires `X-CSRF-Token` header on POST/PATCH/DELETE with session auth. Token from `/session/token` must be fetched before first write.
**How to avoid:** Fetch token in `onMounted()` or on first write call. Cache in module-level variable. Pass `/session/token` URL via drupalSettings, never hardcode.
**Warning signs:** 403 on PATCH/POST in Network tab. Watchdog: "X-CSRF-Token request header is missing."

### Pitfall 2: Wrong CSRF Mechanism (CRITICAL)
**What goes wrong:** Route uses `_csrf_token: 'TRUE'` (query parameter variant). Vue sends `X-CSRF-Token` header. Route validator ignores header, returns 403.
**Why it happens:** Drupal has TWO CSRF systems. The existing `project.complete` route correctly uses `_csrf_token` (link-based). New API routes MUST use `_csrf_request_header_token` (header-based).
**How to avoid:** Use `_csrf_request_header_token: 'TRUE'` for ALL routes called by JavaScript. Use `_csrf_token: 'TRUE'` ONLY for server-rendered link actions.
**Warning signs:** 403 despite correct X-CSRF-Token header.

### Pitfall 3: Vue Double-Mounting from Drupal Behaviors
**What goes wrong:** Board renders correctly, then an AJAX operation or BigPipe delivery triggers `attachBehaviors()` again. Vue mounts a second time. Duplicate event listeners, double rendering, memory leak.
**Why it happens:** Drupal calls `attach()` on page load AND after every AJAX response. `createApp()` has no built-in double-mount protection.
**How to avoid:** ALWAYS use `once('kanban-app', '#kanban-app', context)` guard. Implement `detach` handler for cleanup. Declare `core/once` library dependency.
**Warning signs:** Keyboard shortcuts fire twice. Increasing memory usage. Console: "mounting on non-empty DOM node."

### Pitfall 4: HTML Error Responses from Drupal on API Routes
**What goes wrong:** Validation error or access denied returns HTML page. Vue's `response.json()` throws `SyntaxError: Unexpected token '<'`.
**Why it happens:** Without `_format: json` route requirement, Drupal's error handler defaults to HTML.
**How to avoid:** Add `_format: json` to requirements on ALL API routes. Vue API wrapper should also append `?_format=json` as safety measure.
**Warning signs:** Error responses in Network tab showing `text/html` content type.

### Pitfall 5: drupalSettings Missing When Vue Initializes
**What goes wrong:** Vue reads `drupalSettings.groupAiPm.kanban.projectId` -- returns `undefined`. API calls have no project ID.
**Why it happens:** Controller forgot `#attached.drupalSettings`, or kanban library missing `core/drupalSettings` dependency (JS loads before settings script).
**How to avoid:** Declare `core/drupalSettings` dependency in libraries.yml. Always attach settings in controller render array. Validate settings exist in JS before using.
**Warning signs:** Console: "Cannot read properties of undefined". API calls with empty project ID.

### Pitfall 6: Local Task Tab Missing on Entity Page
**What goes wrong:** Board page loads at direct URL but "Board" tab does not appear alongside View/Edit/Delete on the project entity.
**Why it happens:** `base_route` in links.task.yml does not match entity canonical route, or `{project}` parameter name does not match, or route path does not follow entity prefix pattern.
**How to avoid:** Route path: `/admin/content/project/{project}/board`. base_route: `entity.project.canonical`. Parameter: `project` (matching entity). `parameters.project.type: entity:project`.
**Warning signs:** Tab absent but page loads at direct URL. Other entity tabs work normally.

### Pitfall 7: Build Output Not Committed
**What goes wrong:** Module ships without `js/dist/kanban.js`. Mount point renders but no JS executes. Blank board.
**Why it happens:** Developer workflow gitignores build artifacts. Drupal modules must ship pre-built.
**How to avoid:** Commit `js/dist/` and `js/vendor/vue.global.prod.js`. Gitignore `node_modules/` only.
**Warning signs:** Blank mount div. No script tag for kanban.js in page source.

### Pitfall 8: Entity Access Bypass on REST Endpoints
**What goes wrong:** Any authenticated user can view tasks from projects they should not have access to by calling the API directly.
**Why it happens:** Controller checks route permission ("access group_ai_pm dashboard") but not entity-level access ("can this user view this project/task").
**How to avoid:** Call `$project->access('view')` before returning any data. Filter tasks with `$task->access('view')`. Check `$task->access('update')` before mutations.
**Warning signs:** Users seeing tasks from other groups. No access denied on unauthorized project IDs.

## Code Examples

### libraries.yml Additions
```yaml
# Add to existing group_ai_pm.libraries.yml

vue:
  version: 3.5.x
  header: true
  js:
    js/vendor/vue.global.prod.js: { minified: true, weight: -20 }

kanban:
  version: 1.x
  js:
    js/dist/kanban.js: { minified: true }
  css:
    component:
      js/dist/kanban.css: {}
  dependencies:
    - group_ai_pm/vue
    - core/drupal
    - core/drupalSettings
    - core/once
```

### Board Route Definition
```yaml
# Add to group_ai_pm.routing.yml

group_ai_pm.project.board:
  path: '/admin/content/project/{project}/board'
  defaults:
    _controller: '\Drupal\group_ai_pm\Controller\KanbanController::board'
    _title: 'Board'
  requirements:
    _permission: 'access group_ai_pm dashboard'
  options:
    _admin_route: TRUE
    parameters:
      project:
        type: entity:project
```

### Local Task Tab
```yaml
# Add to group_ai_pm.links.task.yml

group_ai_pm.project.board:
  route_name: group_ai_pm.project.board
  title: 'Board'
  base_route: entity.project.canonical
  weight: 5
```

### Theme Hook Registration
```php
// Add to group_ai_pm.module hook_theme()
'group_ai_pm_kanban' => [
  'variables' => [
    'project' => NULL,
  ],
],
```

### Twig Template
```twig
{# templates/group-ai-pm-kanban.html.twig #}
<div{{ attributes.addClass('gapm-kanban-wrapper') }}>
  <div id="kanban-app">
    {# Vue 3 mounts here. Content below serves as no-JS fallback. #}
    <div class="gapm-kanban-loading">
      {{ 'Loading board...'|t }}
    </div>
  </div>
</div>
```

### vue-draggable-plus Column Pattern
```vue
<!-- KanbanColumn.vue -->
<template>
  <div class="gapm-kanban__column" :class="'gapm-kanban__column--' + column.id">
    <div class="gapm-kanban__column-header">
      <span class="gapm-kanban__column-title">{{ column.label }}</span>
      <span class="gapm-kanban__column-count">{{ tasks.length }}</span>
      <button
        class="gapm-kanban__quick-create-btn"
        @click="showQuickCreate = true"
        :aria-label="'Add task to ' + column.label"
      >+</button>
    </div>
    <QuickCreateForm
      v-if="showQuickCreate"
      :status="column.id"
      @created="onTaskCreated"
      @cancel="showQuickCreate = false"
    />
    <VueDraggable
      v-model="localTasks"
      group="tasks"
      :animation="200"
      item-key="id"
      class="gapm-kanban__column-body"
      @end="onDragEnd"
    >
      <template #item="{ element }">
        <TaskCard
          :task="element"
          @status-change="onStatusChange(element, $event)"
        />
      </template>
    </VueDraggable>
    <div v-if="tasks.length === 0 && !showQuickCreate" class="gapm-kanban__empty">
      No tasks
    </div>
  </div>
</template>
```

### Task Serialization (for drupalSettings and API responses)
```php
protected function serializeTask($task) {
  $assignee = $task->get('assignee')->entity;
  return [
    'id' => (int) $task->id(),
    'title' => $task->getTitle(),
    'status' => $task->getStatus(),
    'priority' => $task->getPriority(),
    'assignee' => $assignee ? [
      'id' => (int) $assignee->id(),
      'name' => $assignee->getDisplayName(),
    ] : NULL,
    'dueDate' => $task->getDueDate(),
    'created' => (int) $task->getCreatedTime(),
    'changed' => (int) $task->getChangedTime(),
    'editUrl' => $task->toUrl('edit-form')->toString(),
  ];
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| REST resource plugins (@RestResource) | Custom controllers with CacheableJsonResponse | Drupal 9+ convention shift | No `drupal:rest`/`drupal:serialization` dependency needed. Simpler, fewer files. |
| vuedraggable@next (vue.draggable.next) | vue-draggable-plus | 2023-2024 | Composition API support, active maintenance. `useDraggable()` composable. |
| Webpack for Drupal frontend | Vite for Drupal frontend | 2024-2025 (PreviousNext blog, community) | Faster dev server, simpler config, official Vue 3 tool. |
| `jQuery.once()` | `once()` from `@drupal/once` | Drupal 9.2 | jQuery-free. Imported from `core/once` library. |
| `_csrf_token` for REST | `_csrf_request_header_token` for JS REST | Drupal 8.4 (core CR #2772399) | Header-based CSRF for JavaScript; query-string CSRF for server-rendered links. |

**Deprecated/outdated:**
- `jQuery.once()`: Replaced by `once()` from `@drupal/once` in Drupal 9.2+.
- `vuedraggable@next`: No longer maintained. Use `vue-draggable-plus`.
- REST resource plugins for internal admin APIs: Over-engineered for this use case. Custom controllers are the current community pattern.
- `Pinia/Vuex`: Overkill for single-page state. `ref()` + composables sufficient.

## Open Questions

1. **Vue externalization detail: global vs bundled**
   - What we know: STACK.md recommends externalizing Vue as `js/vendor/vue.global.prod.js`. Vite config externalizes `vue` so the kanban bundle imports it from the global.
   - What's unclear: When `vue.global.prod.js` loads as a `<script>` tag, it sets `window.Vue`. The Vite IIFE build with `external: ['vue']` and `globals: { vue: 'Vue' }` maps `import { ref } from 'vue'` to `window.Vue.ref`. This should work but has not been empirically tested in a Drupal context.
   - Recommendation: Implement as documented. If the global access fails, fall back to bundling Vue inside the IIFE (increases bundle by ~34 KB but eliminates the integration risk). LOW risk -- this is standard Vite library mode behavior.

2. **Optimistic UI rollback scope for Phase 18**
   - What we know: Full optimistic UI with rollback is INTERACT-03 (Phase 19). Phase 18 success criteria says "the board reflects the change without page reload."
   - What's unclear: How much rollback logic to implement in Phase 18 vs Phase 19.
   - Recommendation: Phase 18 should implement basic optimistic update (move card immediately, revert on error) but defer toast notifications and animation polish to Phase 19. The drag-and-drop must feel responsive from day one.

3. **`_format: json` as route requirement vs query parameter**
   - What we know: Adding `_format: json` as a route requirement means the route ONLY accepts JSON requests. Adding it as a query parameter means clients must append `?_format=json`.
   - What's unclear: If `_format: json` is a route requirement, do GET requests also need `?_format=json` from the client? Or does the route requirement handle it?
   - Recommendation: Use `_format: json` as route requirement on all API routes. This makes the route JSON-only (correct for API endpoints). Vue API wrapper should still append `?_format=json` for safety.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | PHPUnit (Drupal TestBase classes) |
| Config file | `phpunit.xml` in Drupal root (ddev provides) |
| Quick run command | `ddev exec phpunit -c /var/www/html/web/core/phpunit.xml --filter KanbanPageTest modules/custom/group_ai_pm/tests/` |
| Full suite command | `ddev exec phpunit -c /var/www/html/web/core/phpunit.xml modules/custom/group_ai_pm/tests/` |

### Phase Requirements -> Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| API-01 | GET kanban returns tasks grouped by status | kernel | `ddev drush php-eval "..."` (curl equivalent) | No - Wave 0 |
| API-02 | PATCH status requires CSRF, updates entity | kernel | `ddev drush php-eval "..."` | No - Wave 0 |
| API-06 | Entity access enforced on API endpoints | kernel | `ddev drush php-eval "..."` | No - Wave 0 |
| API-07 | Error responses are JSON with _format:json | kernel | `ddev drush php-eval "..."` | No - Wave 0 |
| VUE-01 | js/dist/kanban.js exists and is valid IIFE | static | `test -f js/dist/kanban.js` | No - Wave 0 |
| VUE-02 | js/vendor/vue.global.prod.js exists | static | `test -f js/vendor/vue.global.prod.js` | No - Wave 0 |
| VUE-03 | once() guard present in main.js | static (grep) | `grep -q "once(" js/src/main.js` | No - Wave 0 |
| VUE-05 | core/drupalSettings in library deps | static (grep) | `grep -q "core/drupalSettings" group_ai_pm.libraries.yml` | No - Wave 0 |
| BOARD-05 | Board tab appears on project entity | functional | `ddev drush php-eval "..."` + BrowserTestBase | No - Wave 0 |
| BOARD-10 | drupalSettings.groupAiPm.kanban populated | functional | BrowserTestBase page load check | No - Wave 0 |

### Sampling Rate
- **Per task commit:** Static assertions (file existence, grep patterns)
- **Per wave merge:** `ddev drush en group_ai_pm -y && ddev drush cr` + runtime assertions
- **Phase gate:** All eval assertions (static + runtime + browser) green before verify

### Wave 0 Gaps
- [ ] `tests/src/Kernel/RestEndpointTest.php` -- covers API-01, API-02, API-06, API-07
- [ ] `tests/src/Functional/KanbanPageTest.php` -- covers BOARD-05, BOARD-10
- [ ] No new test framework install needed -- existing PHPUnit infrastructure works
- [ ] No new test config needed -- existing `$modules` array needs `group_ai_pm` already present

## Sources

### Primary (HIGH confidence)
- [Drupal CSRF Access Checking](https://www.drupal.org/docs/8/api/routing-system/access-checking-on-routes/csrf-access-checking) -- `_csrf_token` vs `_csrf_request_header_token` distinction, token endpoint
- [CSRF Token Route Protection CR #2772399](https://www.drupal.org/node/2772399) -- Moved out of REST module to core
- [CSRF Header Token Issue #3192874](https://www.drupal.org/project/drupal/issues/3192874) -- Working in custom routes, string 'TRUE' requirement
- [CacheableJsonResponse API](https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Cache!CacheableJsonResponse.php/class/CacheableJsonResponse/8.9.x) -- Core class, no extra modules
- [CacheableJsonResponse Explained](https://stefvanlooveren.me/blog/how-cacheable-json-response-drupal-8-9-10) -- Usage pattern with cache dependencies
- [Drupal JS API Overview](https://www.drupal.org/docs/drupal-apis/javascript-api/javascript-api-overview) -- Drupal.behaviors, once(), drupalSettings
- [Drupal Asset Libraries](https://www.drupal.org/docs/develop/creating-modules/adding-assets-css-js-to-a-drupal-module-via-librariesyml) -- libraries.yml structure
- [Drupal Local Tasks](https://www.drupal.org/docs/drupal-apis/menu-api/providing-module-defined-local-tasks) -- base_route, parameter matching
- [vue-draggable-plus GitHub](https://github.com/Alfred-Skyblue/vue-draggable-plus) -- Vue 3 DnD, v0.6.x
- [vue-draggable-plus API Docs](https://vue-draggable-plus.pages.dev/en/api/) -- useDraggable, VueDraggable component, group option, events
- [SortableJS GitHub](https://github.com/SortableJS/Sortable) -- Core DnD engine
- [Vite Build Options](https://vite.dev/config/build-options) -- build.lib, IIFE format, externals
- [Vite Library Mode](https://vite.dev/guide/build) -- Library mode output
- Existing module source (first-party): `Task.php`, `routing.yml`, `libraries.yml`, `links.task.yml`, `TaskAccessControlHandler.php`

### Secondary (MEDIUM confidence)
- [Lullabot Understanding Behaviors](https://www.lullabot.com/articles/understanding-javascript-behaviors-in-drupal) -- Behavior lifecycle, attach/detach
- [Five Jars Vue + Drupal Integration](https://fivejars.com/blog/how-integrate-vuejs-applications-drupal) -- Mounting patterns
- [PreviousNext Vite for Drupal](https://www.previousnext.com.au/blog/vite-and-storybook-frontend-tooling-drupal) -- Build config for Drupal
- [tinykeys GitHub](https://github.com/jamiebuilds/tinykeys) -- 650 B keyboard binding
- v3.0 Eval Results (Phases 14-17) -- Haiku code generation patterns, declaration-usage gap

### Tertiary (LOW confidence)
- [Drupal Vue.js Library Module](https://www.drupal.org/project/vuejs) -- Community precedent (not used directly)

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH -- All libraries are mature, well-documented, with verified APIs. Vue 3, SortableJS, Vite are de facto standards.
- Architecture: HIGH -- Embedded Vue island pattern verified via Five Jars, PreviousNext, and Drupal community. Custom controllers with CacheableJsonResponse verified as core pattern (no extra deps).
- Pitfalls: HIGH -- All pitfalls verified against official Drupal docs (CSRF, behaviors, _format). Haiku declaration-usage gap confirmed empirically in v3.0 evals.
- Integration wiring: MEDIUM -- The specific combination (Vite IIFE + Vue global external + Drupal.behaviors) is standard individually but the exact wiring has limited community examples. Mitigation: each piece is independently testable.

**Research date:** 2026-03-08
**Valid until:** 2026-04-08 (stable -- all libraries are mature releases)

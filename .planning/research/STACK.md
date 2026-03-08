# Technology Stack: v4.0 UX Overhaul Additions

**Project:** Drupal Skills / group_ai_pm module
**Researched:** 2026-03-08
**Focus:** NEW stack additions for Vue.js Kanban, AJAX interactions, keyboard shortcuts, REST endpoints
**Overall confidence:** MEDIUM-HIGH -- Vue 3 and SortableJS are well-established; Drupal integration patterns verified via official docs; build pipeline approach is opinionated but standard

## Context: What Already Exists

The v3.0 module (`modules/group_ai_pm/`) already has:
- Custom content entities: Project (6 fields), Task (10 fields incl. status with `todo|in_progress|review|done`)
- Entity CRUD with access control, routing at `/admin/content/`
- Twig templates (`group-ai-pm-task-card.html.twig`, `group-ai-pm-project-summary.html.twig`)
- CSS libraries (`task_cards`, `project_summaries`)
- DashboardController returning render arrays
- Group 3.3.x integration, optional AI sub-module
- Dependencies: `drupal:datetime`, `drupal:options`, `drupal:text`, `drupal:views`, `group:group`

This document covers ONLY new additions needed for v4.0.

---

## Recommended Stack Additions

### Frontend Framework: Vue 3 (Production Global Build)

| Attribute | Value |
|-----------|-------|
| **Package** | `vue` |
| **Version** | `3.5.x` (latest stable) |
| **Build** | `vue.global.prod.js` (~34 KB gzipped) |
| **Delivery** | Compiled into module's `js/dist/` via Vite build, declared in `libraries.yml` |
| **Confidence** | HIGH |

**Why Vue 3:**
- The Kanban board is a genuinely interactive, stateful component (drag-drop across columns, optimistic status updates, filtered views). This is exactly the use case where a reactive framework provides real value over vanilla JS or Drupal AJAX.
- Vue 3's Composition API maps cleanly to the Kanban's concerns: a `useKanban()` composable for board state, a `useTasks()` composable for API calls, a `useKeyboardShortcuts()` composable for hotkeys.
- Drupal has an official proposal (issue #2913628) to use Vue.js for admin UIs. Vue is the community-preferred choice for embedded Drupal components.
- Vue 3's global production build can be loaded as a Drupal library without requiring the user to run npm. The Vite-built Kanban app externalizes Vue, so the runtime is loaded once and shared.

**Why NOT petite-vue:** Despite being only 6 KB, petite-vue is no longer actively maintained (last release at Vue 3.2.27), lacks components like Transition (needed for animations), and cannot support the drag-drop library ecosystem. The 34 KB cost of full Vue 3 is acceptable for an admin-only page.

**Why NOT React/Svelte/Alpine:** Vue has the strongest Drupal ecosystem integration (drupal/vuejs module, Decoupled Blocks contrib, community precedent). Alpine.js lacks drag-drop ecosystem. React would work but adds JSX complexity with no Drupal community backing. Svelte is excellent but has zero Drupal contrib integration.

### Drag-and-Drop: SortableJS + vue-draggable-plus

| Attribute | Value |
|-----------|-------|
| **Core library** | `sortablejs` v1.15.x (~12 KB min+gz) |
| **Vue wrapper** | `vue-draggable-plus` v0.6.x (~3 KB min+gz on top of SortableJS) |
| **Confidence** | HIGH |

**Why vue-draggable-plus:**
- Purpose-built Vue 3 wrapper for SortableJS with Composition API support (`useDraggable()` hook).
- Supports component, directive, AND function usage -- component for simple cases, `useDraggable()` for the Kanban where we need fine-grained control.
- Actively maintained (v0.6.1, published ~2 months ago), 3,300+ GitHub stars, 38K weekly npm downloads.
- Cross-column drag (move tasks between status columns) is a first-class feature via SortableJS's `group` option.
- Touch device support via SortableJS's built-in touch handling.

**Why NOT raw SortableJS:** SortableJS alone would require manual Vue 3 reactivity wiring. When a task is dragged between columns, we need Vue's reactivity to update the data model and trigger the PATCH request. vue-draggable-plus handles this bidirectional sync.

**Why NOT vuedraggable (vue.draggable.next):** The original vuedraggable for Vue 3 (`vuedraggable@next`) is outdated and no longer updated to match Vue 3 patterns. vue-draggable-plus was created specifically to replace it.

**Why NOT @dnd-kit:** React-only library. Not applicable.

### Keyboard Shortcuts: tinykeys

| Attribute | Value |
|-----------|-------|
| **Package** | `tinykeys` v3.0.0 |
| **Size** | ~650 bytes min+gz |
| **Confidence** | HIGH |

**Why tinykeys:**
- Tiny (650 B) -- negligible bundle impact. This is a keyboard binding library, not a framework.
- Framework-agnostic vanilla JS. Wrap in a Vue composable (`useKeyboardShortcuts()`) for clean integration.
- Supports modifier keys with `$mod` for cross-platform (Cmd on Mac, Ctrl on Windows/Linux).
- Supports key sequences ("g i" for "go to inbox" style shortcuts like Linear uses).
- No dependencies. Just `tinykeys(window, { "Shift+N": handler })`.
- 2K+ GitHub stars, maintained by Jamie Builds (known for babel/parcel contributions).

**Why NOT mousetrap:** Heavier (~4.5 KB), jQuery-era API design. Still works but tinykeys is smaller and more modern.

**Why NOT Vue-specific keyboard libraries:** vue3-shortkey, @simolation/vue-hotkey etc. are Vue wrappers around heavier libraries. tinykeys at 650 B wrapped in a 20-line composable is lighter and gives us full control.

### API Layer: Custom REST Controllers with JsonResponse

| Attribute | Value |
|-----------|-------|
| **Approach** | Custom Drupal controllers returning `CacheableJsonResponse` |
| **Routes** | Defined in `group_ai_pm.routing.yml` |
| **No new module dependencies** | Uses existing Drupal core Symfony components |
| **Confidence** | MEDIUM-HIGH |

**Why custom controllers over JSON:API:**

JSON:API (Drupal core) is the standard recommendation for entity CRUD, BUT for this specific use case custom controllers are better because:

1. **Kanban-specific payloads.** The Kanban needs tasks grouped by status column with project context, assignee display names, and priority badges -- a shaped response, not raw entity fields. JSON:API returns flat entity data requiring client-side reshaping.

2. **Batch status updates.** Dragging a task between columns needs a single endpoint that: updates the task status, reorders tasks within the target column, and returns the updated column state. JSON:API requires multiple PATCH requests for this.

3. **No additional module enable.** JSON:API module must be enabled and configured for write operations. Custom controllers use existing routing infrastructure already in the module.

4. **CSRF protection already established.** The module already uses `_csrf_token: 'TRUE'` on the project complete route. Same pattern extends to task status PATCH endpoints.

5. **Access control integration.** Custom controllers call existing `TaskAccessControlHandler` directly. JSON:API's access layer is entity-level but doesn't know about Kanban-specific business logic (e.g., "can this user reorder tasks in this project?").

**Specific endpoints needed:**

```
GET  /api/group-ai-pm/project/{project}/kanban    # Tasks grouped by status column
PATCH /api/group-ai-pm/task/{task}/status          # Update task status (drag-drop)
PATCH /api/group-ai-pm/task/{task}                 # Inline edit (title, assignee, etc.)
POST /api/group-ai-pm/project/{project}/task       # Quick-add task from Kanban
GET  /api/group-ai-pm/project/{project}/tasks      # Filtered/sorted task list
```

All return `CacheableJsonResponse` with proper cache tags (`task:{id}`, `task_list`, `project:{id}`) so Drupal's internal page cache and Dynamic Page Cache work correctly.

**When to use JSON:API instead:** If the module were a general-purpose headless backend, JSON:API would be the right choice. For a tightly integrated admin UI with specific data shapes, custom controllers win.

### Drupal AJAX: Core AJAX API (for non-Vue interactions)

| Attribute | Value |
|-----------|-------|
| **Approach** | `use-ajax` CSS class + AjaxResponse commands |
| **No new dependencies** | Uses `drupal:core/drupal.ajax` library |
| **Confidence** | HIGH |

**Why Drupal AJAX for simpler interactions:**

Not everything needs Vue. The PROJECT.md explicitly says "Drupal AJAX for simpler interactions: status toggles, inline editing." The right boundary:

| Interaction | Approach | Why |
|-------------|----------|-----|
| Kanban board | Vue 3 | Complex state: drag-drop, multi-column, optimistic updates |
| Task status toggle (list view) | Drupal AJAX | Single DOM update, no complex state |
| Inline title edit (list view) | Drupal AJAX | Single field, `ReplaceCommand` sufficient |
| Project complete action | Drupal AJAX | Already exists with CSRF token, just needs AJAX upgrade |
| Dashboard stats refresh | Drupal AJAX | Simple content replacement |
| Quick-add task modal | Vue 3 | Part of Kanban context, needs board state awareness |

**Implementation pattern:**

```php
// Controller returns AjaxResponse
$response = new AjaxResponse();
$response->addCommand(new ReplaceCommand('#task-' . $task->id(), $updated_markup));
$response->addCommand(new MessageCommand('Task status updated.'));
return $response;
```

```twig
{# Link with use-ajax class #}
<a href="{{ path('group_ai_pm.task.toggle_status', {'task': task.id}) }}"
   class="use-ajax"
   data-ajax-wrapper="task-{{ task.id }}-status">
  {{ task.status }}
</a>
```

### Build Tool: Vite (Development Only)

| Attribute | Value |
|-----------|-------|
| **Package** | `vite` v6.x |
| **Vue plugin** | `@vitejs/plugin-vue` v5.x |
| **Used by** | Developers contributing to the module's frontend |
| **NOT required by** | End users installing the module (compiled JS shipped) |
| **Confidence** | HIGH |

**Why Vite:**
- Official build tool for Vue 3 (created by same author, Evan You).
- Library mode outputs a single IIFE file that Drupal can load as a library.
- Dev server with HMR for rapid development (proxied through ddev).
- Vite 6.x is current stable as of early 2026.

**Build output strategy:**

```
modules/group_ai_pm/
  js/
    src/                          # Vue source (NOT shipped to production)
      KanbanApp.vue
      components/
        KanbanColumn.vue
        TaskCard.vue
        TaskQuickAdd.vue
      composables/
        useKanban.js
        useTasks.js
        useKeyboardShortcuts.js
      api/
        client.js                 # fetch() wrapper with CSRF token handling
    dist/                         # Vite build output (shipped)
      kanban.js                   # IIFE bundle, externalizes Vue
      kanban.css                  # Extracted CSS
  node_modules/                   # .gitignored
  package.json
  vite.config.js
```

**Vite config approach:**

```javascript
// vite.config.js
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [vue()],
  build: {
    lib: {
      entry: 'js/src/main.js',
      name: 'GroupAiPmKanban',
      fileName: 'kanban',
      formats: ['iife'],
    },
    outDir: 'js/dist',
    rollupOptions: {
      external: ['vue'],
      output: {
        globals: {
          vue: 'Vue',
        },
      },
    },
  },
});
```

**Why IIFE format:** Drupal loads JS via `<script>` tags from `libraries.yml`. IIFE (Immediately Invoked Function Expression) is the correct format for non-module script loading. ES modules would require `type="module"` which Drupal's asset pipeline does not natively support.

### CSS Approach: Scoped Module CSS (No Framework)

| Attribute | Value |
|-----------|-------|
| **Approach** | BEM-namespaced CSS extracted from Vue SFCs + standalone CSS files |
| **Prefix** | `gapm-` (group-ai-pm) for all custom classes |
| **Confidence** | HIGH |

**Why no CSS framework (Tailwind, Bootstrap, etc.):**

1. **Admin context.** The module runs inside Drupal's admin theme (Claro or Gin). Adding a CSS framework would conflict with admin theme styles and bloat the page.
2. **Scoped styles.** Vue SFCs with `<style scoped>` plus BEM naming (`gapm-kanban__column`, `gapm-task-card--priority-high`) prevents style leakage.
3. **Bundle size.** Even Tailwind's purged output adds 10-30 KB. For an admin page with ~20 unique component styles, hand-written CSS is smaller and more maintainable.
4. **Claro compatibility.** Drupal's Claro admin theme uses CSS custom properties for colors, spacing, fonts. Our CSS should USE these variables (`var(--color-primaryActive)`, `var(--space-m)`) rather than fight them.

**CSS custom properties from Claro to leverage:**

```css
/* Use Claro's design tokens, don't reinvent them */
.gapm-kanban__column {
  background: var(--color-gray-050);
  border-radius: var(--border-radius);
  padding: var(--space-m);
}
.gapm-task-card {
  background: var(--color-white);
  box-shadow: var(--shadow-card, 0 1px 3px rgba(0,0,0,0.12));
  border: 1px solid var(--color-gray-200);
}
```

**Animations:** CSS transitions for card movement (`transition: transform 0.2s ease`). SortableJS handles drag ghost styling. Vue's `<Transition>` and `<TransitionGroup>` for list animations.

---

## Drupal Module Dependencies (Changes)

### New Core Module Dependencies

| Module | Why Needed | Already Enabled? |
|--------|-----------|-----------------|
| `drupal:serialization` | Required for `CacheableJsonResponse` and JSON handling in custom controllers | Likely yes (dependency of views, etc.) -- verify |

**No other new core module dependencies.** The REST and JSON:API modules are NOT needed because we use custom controllers.

### No New Contrib Dependencies

The entire v4.0 frontend is self-contained within the module. No new composer packages.

---

## Drupal Libraries (libraries.yml additions)

```yaml
# New libraries for v4.0
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

keyboard_shortcuts:
  version: 1.x
  js:
    js/dist/shortcuts.js: { minified: true }
  dependencies:
    - core/drupal
    - core/drupalSettings

ajax_enhancements:
  version: 1.x
  js:
    js/ajax-enhancements.js: {}
  css:
    component:
      css/ajax-enhancements.css: {}
  dependencies:
    - core/drupal
    - core/drupal.ajax
    - core/once

# Existing libraries (preserved)
task_cards:
  version: 1.x
  css:
    component:
      css/task-cards.css: {}

project_summaries:
  version: 1.x
  css:
    component:
      css/project-summaries.css: {}
```

**Key detail:** Vue is declared as a separate library so it loads once even if multiple components use it. The `header: true` ensures Vue loads before the Kanban app.

---

## NPM Package Dependencies

### package.json (development)

```json
{
  "name": "group-ai-pm-frontend",
  "private": true,
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "preview": "vite preview"
  },
  "dependencies": {
    "vue": "^3.5.0",
    "vue-draggable-plus": "^0.6.0",
    "sortablejs": "^1.15.0",
    "tinykeys": "^3.0.0"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^5.0.0",
    "vite": "^6.0.0"
  }
}
```

### Total Bundle Size Estimate

| Component | Size (min+gz) | Notes |
|-----------|---------------|-------|
| Vue 3 runtime | ~34 KB | Global production build |
| SortableJS | ~12 KB | Core drag-drop engine |
| vue-draggable-plus | ~3 KB | Thin wrapper |
| tinykeys | ~0.65 KB | Keyboard bindings |
| Kanban app code | ~8-15 KB | Our Vue components + composables |
| CSS | ~3-5 KB | Scoped component styles |
| **Total** | **~61-70 KB** | Loaded only on Kanban/dashboard pages |

**Context:** This is an admin-only page. Drupal's admin theme (Claro) already loads ~200 KB of CSS and ~150 KB of JS. Adding ~65 KB for a fully interactive Kanban board is proportional. For comparison, a single CKEditor instance loads ~300 KB.

---

## Alternatives Considered

| Category | Recommended | Alternative | Why Not |
|----------|-------------|-------------|---------|
| Frontend framework | Vue 3 | petite-vue | No longer maintained, lacks Transition/component features needed for Kanban |
| Frontend framework | Vue 3 | Alpine.js | No drag-drop ecosystem, not suited for complex stateful UIs |
| Frontend framework | Vue 3 | React | No Drupal community integration, JSX adds complexity |
| Frontend framework | Vue 3 | Vanilla JS | Drag-drop state management across columns would be fragile without reactivity |
| Drag-drop | vue-draggable-plus | vuedraggable@next | Outdated, not maintained for Vue 3 |
| Drag-drop | vue-draggable-plus | sortablejs-vue3 | Thinner wrapper but less Vue 3 integration (no composable API) |
| Drag-drop | vue-draggable-plus | raw SortableJS | Would need manual Vue reactivity wiring |
| API layer | Custom controllers | JSON:API module | Over-exposes entities, requires multiple requests for Kanban data shape, adds module dependency |
| API layer | Custom controllers | REST module | Over-engineered for internal admin AJAX; custom controllers are simpler |
| API layer | Custom controllers | GraphQL | Massive over-engineering for a single-module admin UI |
| Keyboard shortcuts | tinykeys | mousetrap | 7x larger, jQuery-era design |
| Keyboard shortcuts | tinykeys | @simolation/vue-hotkey | Wraps heavier library; tinykeys + composable is lighter |
| Build tool | Vite | Webpack | Slower, more config, not Vue 3's official tool |
| Build tool | Vite | No build step (CDN Vue) | Would require shipping unminified source, no SFC support, no tree-shaking |
| CSS | Scoped BEM + Claro variables | Tailwind CSS | Conflicts with admin theme, adds purge complexity, 10-30 KB overhead |
| CSS | Scoped BEM + Claro variables | Bootstrap | Massive conflict with Claro, wrong for admin context |
| Simple AJAX | Drupal core AJAX API | Vue for everything | Over-engineering: status toggles and inline edits don't need a framework |

---

## What NOT to Add (Over-Engineering Risks)

| Avoid | Why | Impact if Added |
|-------|-----|----------------|
| **Pinia/Vuex state management** | Kanban state is local to one page; a `ref()` and composable suffice. No cross-page state. | +5 KB bundle, unnecessary abstraction layer |
| **Vue Router** | This is not an SPA. Each Drupal page is a full page load. Vue mounts on specific DOM elements. | Fights Drupal's routing, breaks admin theme navigation |
| **TypeScript** | Eval pipeline uses Haiku for code generation. TS adds compilation complexity with no eval benefit. | Build complexity, Haiku may generate invalid TS |
| **CSS-in-JS** | Drupal's asset pipeline expects `.css` files in `libraries.yml`. CSS-in-JS would bypass cache management. | Breaks Drupal cache aggregation, runtime overhead |
| **WebSocket/real-time** | Single-user admin editing. Polling or simple refetch-on-focus is sufficient for freshness. | Server infrastructure complexity, no user need |
| **Axios/fetch library** | Native `fetch()` with a 30-line wrapper handles CSRF tokens and JSON parsing. No need for a library. | Unnecessary dependency |
| **i18n framework (vue-i18n)** | Strings come from Drupal's translation system via `drupalSettings`. Vue just renders them. | Dual translation systems, maintenance burden |
| **Testing framework (Vitest/Jest)** | Frontend tested via browser eval (agent-browser) not unit tests. Eval methodology is the test. | Dev dependency complexity with no eval pipeline integration |

---

## Integration Points with Existing Module

### Drupal -> Vue Communication

```javascript
// Controller passes data via drupalSettings
$build['#attached']['drupalSettings']['groupAiPm'] = [
  'projectId' => $project->id(),
  'csrfToken' => \Drupal::csrfToken()->get('rest'),
  'apiBase' => '/api/group-ai-pm',
  'statuses' => ['todo', 'in_progress', 'review', 'done'],
  'statusLabels' => ['To Do', 'In Progress', 'Review', 'Done'],
  'currentUserId' => \Drupal::currentUser()->id(),
  'permissions' => [
    'editTasks' => $account->hasPermission('edit any task'),
    'createTasks' => $account->hasPermission('create task'),
  ],
];
```

```javascript
// Vue app reads from drupalSettings
const settings = window.drupalSettings.groupAiPm;
```

### Vue -> Drupal Communication

```javascript
// API client with CSRF token
async function apiCall(method, path, data = null) {
  const response = await fetch(settings.apiBase + path, {
    method,
    headers: {
      'Content-Type': 'application/json',
      'X-Drupal-Ajax-Token': settings.csrfToken,
    },
    body: data ? JSON.stringify(data) : undefined,
  });
  return response.json();
}
```

### Mount Point Pattern

Vue mounts on a specific DOM element rendered by Twig, NOT on the entire page:

```twig
{# kanban-board.html.twig #}
<div id="gapm-kanban-app" data-project-id="{{ project_id }}">
  {# Vue mounts here. Fallback content for no-JS: #}
  <noscript>{{ 'Enable JavaScript for the Kanban board.'|t }}</noscript>
</div>
{{ attach_library('group_ai_pm/kanban') }}
```

### Cache Invalidation Chain

```
Task entity save -> Cache tag 'task:{id}' invalidated
                 -> Cache tag 'task_list' invalidated
                 -> Custom JSON endpoints return fresh data
                 -> Vue refetches on next board load
                 -> Drupal AJAX responses include fresh markup
```

---

## Installation & Build

### For Module Users (No Build Required)

The module ships with pre-compiled JS in `js/dist/` and vendored Vue in `js/vendor/`. No npm install needed.

```bash
# Standard Drupal module install
drush en group_ai_pm -y
drush cr
```

### For Module Developers (Build Required)

```bash
# One-time setup
cd modules/group_ai_pm
npm install

# Development (with HMR via ddev)
npm run dev

# Production build (before committing)
npm run build
# Outputs to js/dist/kanban.js and js/dist/kanban.css

# Copy Vue production build to vendor
cp node_modules/vue/dist/vue.global.prod.js js/vendor/
```

### .gitignore Additions

```
modules/group_ai_pm/node_modules/
modules/group_ai_pm/js/src/    # Source excluded from distribution (optional -- could include)
```

**Decision: Ship source or not?** Ship source files (`js/src/`) for transparency and contributor convenience. The `node_modules/` is always gitignored. Compiled `js/dist/` is committed.

---

## Version Compatibility Matrix

| Package | Version | Compatible With | Notes |
|---------|---------|-----------------|-------|
| vue | ^3.5.0 | All modern browsers, IE not supported | Admin-only, IE support irrelevant |
| sortablejs | ^1.15.0 | Chrome 74+, Firefox 78+, Safari 12+, Edge 79+ | Touch support built-in |
| vue-draggable-plus | ^0.6.0 | Vue ^3.3.0, SortableJS ^1.14.0 | Requires Composition API |
| tinykeys | ^3.0.0 | All modern browsers | No dependencies |
| vite | ^6.0.0 | Node 18+ | Dev only |
| @vitejs/plugin-vue | ^5.0.0 | Vite ^6.0.0, Vue ^3.5.0 | Dev only |
| Drupal core | ^10 \|\| ^11 | PHP 8.1+ (D10) / 8.3+ (D11) | Existing constraint |
| Node.js | ^18 \|\| ^20 | Required for Vite build | Dev only, not runtime |

---

## Sources

- [Vue.js Official Quick Start](https://vuejs.org/guide/quick-start.html) (HIGH) -- Vue 3 global build options, CDN vs bundled
- [Vue.js Production Deployment](https://vuejs.org/guide/best-practices/production-deployment.html) (HIGH) -- Build size optimization
- [vue-draggable-plus GitHub](https://github.com/Alfred-Skyblue/vue-draggable-plus) (HIGH) -- Active maintenance, Vue 3 Composition API support
- [vue-draggable-plus npm](https://www.npmjs.com/package/vue-draggable-plus) (HIGH) -- v0.6.1, 38K weekly downloads
- [vue-draggable-plus API docs](https://vue-draggable-plus.pages.dev/en/api/) (HIGH) -- useDraggable composable, component API
- [SortableJS GitHub](https://github.com/SortableJS/Sortable) (HIGH) -- v1.15.7, framework-agnostic drag-drop
- [tinykeys GitHub](https://github.com/jamiebuilds/tinykeys) (HIGH) -- v3.0.0, 650 B min+gz
- [Drupal.org: Adding assets via libraries.yml](https://www.drupal.org/docs/develop/creating-modules/adding-assets-css-js-to-a-drupal-module-via-librariesyml) (HIGH) -- Official asset pipeline docs
- [Drupal.org: JSON:API vs REST module](https://www.drupal.org/docs/core-modules-and-themes/core-modules/jsonapi-module/jsonapi-vs-cores-rest-module) (HIGH) -- Official comparison, "choose REST for non-entity data"
- [Drupal.org: JSON:API module](https://www.drupal.org/docs/core-modules-and-themes/core-modules/jsonapi-module) (HIGH) -- Zero-config entity exposure, filtering, sorting
- [Drupal.org: Custom REST Resources](https://www.drupal.org/docs/develop/drupal-apis/restful-web-services-api/custom-rest-resources) (HIGH) -- RestResource plugin pattern
- [Drupal.org: AJAX API Basic Concepts](https://www.drupal.org/docs/drupal-apis/ajax-api/basic-concepts) (HIGH) -- use-ajax class, AjaxResponse commands
- [Drupal.org: Core AJAX Commands](https://www.drupal.org/docs/develop/drupal-apis/ajax-api/core-ajax-callback-commands) (HIGH) -- ReplaceCommand, InvokeCommand, MessageCommand
- [Drupal.org: JavaScript API overview](https://www.drupal.org/docs/drupal-apis/javascript-api/javascript-api-overview) (HIGH) -- Drupal.behaviors, drupalSettings, once()
- [Drupal.org: Vue.js Library module](https://www.drupal.org/project/vuejs) (MEDIUM) -- Drupal Vue integration contrib
- [Drupal.org: Proposal for Vue.js in admin UIs](https://www.drupal.org/project/ideas/issues/2913628) (MEDIUM) -- Community precedent
- [Vite Library Mode docs](https://vite.dev/guide/build) (HIGH) -- build.lib config, IIFE output
- [Vite Build Options](https://vite.dev/config/build-options) (HIGH) -- rollupOptions, external, globals
- [Five Jars: Vue.js + Drupal integration](https://fivejars.com/blog/how-integrate-vuejs-applications-drupal) (MEDIUM) -- Practical integration patterns
- [FrontendTools: JS Bundle Size Guide 2025](https://www.frontendtools.tech/blog/reduce-javascript-bundle-size-2025) (MEDIUM) -- Vue 3 ~34KB gzipped baseline
- [Lullabot: Understanding JavaScript behaviors](https://www.lullabot.com/articles/understanding-javascript-behaviors-in-drupal) (MEDIUM) -- Drupal.behaviors deep dive
- [CodimTh: Custom controller JSON response](https://www.codimth.com/blog/web/drupal/custom-controller-json-response-drupal-8) (MEDIUM) -- JsonResponse in custom controllers
- [petite-vue GitHub](https://github.com/vuejs/petite-vue) (MEDIUM) -- No longer actively maintained, "done" status

---
*Stack research for: Drupal Skills v4.0 -- Vue.js Kanban UX Overhaul*
*Researched: 2026-03-08*

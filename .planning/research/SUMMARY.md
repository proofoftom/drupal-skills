# Project Research Summary

**Project:** Drupal Skills v4.0 -- Vue.js Kanban UX Overhaul
**Domain:** Frontend UX overhaul for an existing Drupal 10 project management module
**Researched:** 2026-03-08
**Confidence:** MEDIUM-HIGH

## Executive Summary

v4.0 transforms the existing `group_ai_pm` module from a functional admin CRUD interface into an interactive, keyboard-driven project management tool modeled after Linear's UX. The core technical challenge is embedding a Vue 3 application within Drupal's admin theme (Claro) as an "interactive island" -- Vue owns the Kanban board, Drupal owns everything else (authentication, routing, entity forms, admin chrome). This embedded approach is the correct pattern: a fully decoupled SPA would fight Drupal's admin infrastructure at every turn, while vanilla JS or HTMX cannot deliver the drag-and-drop reactivity and optimistic UI updates that define the Kanban experience.

The recommended stack is deliberately minimal: Vue 3 (Composition API, ~34 KB gzipped), SortableJS via vue-draggable-plus for drag-and-drop (~15 KB), tinykeys for keyboard shortcuts (~650 B), and Vite for compilation. Custom REST controllers (not JSON:API, not REST module resource plugins) serve Kanban-shaped JSON responses. Drupal's native AJAX framework handles simpler interactions on list pages. Total frontend payload targets under 70 KB gzipped, loaded only on the board route. No new contrib dependencies, no CSS frameworks, no state management libraries beyond Vue's built-in `ref()` and composables. The existing entity schema (Project, Task with 4-value status and priority fields) requires zero modifications -- the Kanban columns map 1:1 to the existing `todo/in_progress/review/done` status values.

The primary risks are integration-layer bugs, not architectural flaws. CSRF token handling is the most critical: Drupal has two distinct CSRF mechanisms (`_csrf_token` for link-based actions vs `_csrf_request_header_token` for JavaScript header-based calls), and using the wrong one silently 403s every mutation. The second risk is Drupal.behaviors double-mounting -- Vue apps inside Drupal pages MUST use `once()` guards or AJAX operations create duplicate app instances. The third risk, specific to the eval pipeline, is Haiku's "declaration-usage gap": it builds REST infrastructure and Vue components but fails to wire them together (drupalSettings, CSRF tokens, library dependencies). Skills must document the complete connected data flow, not individual pieces.

## Key Findings

### Recommended Stack

The stack adds Vue 3, SortableJS/vue-draggable-plus, tinykeys, and Vite to the existing module. No new Drupal contrib dependencies. The only new core module dependency is `drupal:serialization` (likely already enabled). Vue was chosen over Alpine.js (insufficient for multi-column DnD state), React (no Drupal ecosystem), HTMX (cannot do optimistic UI), and petite-vue (unmaintained, lacks Transition). Custom controllers with `CacheableJsonResponse` were chosen over JSON:API (requires multiple requests for Kanban data shapes) and REST module resource plugins (see Gaps section for rationale). See [STACK.md](STACK.md) for full details.

**Core technologies:**
- **Vue 3** (^3.5.0, global production build): Reactive framework for the Kanban board -- the only component complex enough to warrant a framework
- **vue-draggable-plus** (^0.6.0) + **SortableJS** (^1.15.0): Cross-column drag-and-drop with Vue 3 Composition API support
- **tinykeys** (^3.0.0): 650-byte keyboard shortcut binding, wrapped in a Vue composable
- **Vite** (^6.0.0): Compiles Vue SFCs to IIFE format for Drupal's library system. Dev dependency only.
- **Drupal AJAX API** (core): Status toggles and inline edits on list pages -- no Vue needed for simple interactions
- **Custom REST controllers**: Kanban-shaped JSON responses with `CacheableJsonResponse` and proper cache tags

**Explicitly avoided:** Pinia/Vuex (overkill for single-page state), Vue Router (fights Drupal routing), TypeScript (Haiku generates invalid TS), CSS frameworks (conflict with Claro), WebSockets (single-user admin context), Axios (native fetch + 30-line wrapper suffices).

### Expected Features

The feature landscape splits cleanly into 11 table stakes, 16 differentiators, and 11 anti-features. The existing entity schema supports all features without modification. See [FEATURES.md](FEATURES.md) for full prioritization matrix and dependency graph.

**Must have (table stakes):**
- Kanban board view per project with 4 status columns
- Drag-and-drop between columns (the defining Kanban interaction)
- Task cards with title, priority badge, assignee, due date
- REST/JSON endpoints for Vue (GET tasks by project, PATCH status)
- Task quick-create from board (inline title input)
- Board route with "Board" local task tab on project entity
- Loading, empty, and error states
- Keyboard alternative to drag-and-drop (WCAG 2.2 SC 2.5.7 requirement)

**Should have (differentiators -- what separates this from existing Drupal PM modules):**
- Keyboard shortcuts (S=status, P=priority, C=create, arrow navigation) -- no Drupal PM module has this
- Command palette (Ctrl+K) with fuzzy search -- Linear's "most beloved feature"
- Task detail slide-over panel with inline editing
- Optimistic UI with error rollback (makes DnD feel instant)
- Filter bar (assignee, priority) with URL persistence
- Context menu on right-click
- AJAX status toggles on entity list pages (pure Drupal, no Vue)
- Enhanced dashboard with project summary cards

**Defer (anti-features -- explicitly NOT building):**
- Swimlanes (filter bar achieves the same insight at 10x less complexity)
- WebSocket real-time updates (poll every 30-60s instead; single-user admin context)
- Gantt chart / timeline view (massive frontend complexity, not a Kanban feature)
- Custom workflow states (4 fixed statuses match universal Kanban model)
- Sprint/cycle management (separate sub-module scope)
- Manual card reordering within columns (sort deterministically: priority > due date > created)
- Rich text editor in cards (link to Drupal entity form for description editing)

### Architecture Approach

The architecture is an embedded Vue island within Drupal's admin theme, not a decoupled SPA. Vue mounts on a single `<div id="kanban-app">` rendered by a Drupal controller. Initial board state is server-rendered via `drupalSettings` to eliminate an extra API round-trip. The Vite build outputs IIFE format with Drupal globals (`Drupal`, `once`, `drupalSettings`) treated as externals. Compiled `js/dist/` is committed to the repo so the module works without Node.js. The Drupal.behaviors bridge with `once()` ensures Vue mounts exactly once despite Drupal's re-attachment lifecycle. See [ARCHITECTURE.md](ARCHITECTURE.md) for full component boundaries, data flow diagrams, and build order.

**Major components:**
1. **REST API Layer** (custom controllers) -- Serves tasks grouped by status column, handles PATCH for DnD and inline edits, all with `CacheableJsonResponse` and cache tags
2. **Vue Kanban App** (KanbanBoard > KanbanColumn > TaskCard) -- Reactive board with drag-and-drop via vue-draggable-plus, optimistic updates, keyboard navigation
3. **Drupal Page Shell** (KanbanController + Twig template) -- Renders mount point, passes initial state via `drupalSettings`, attaches Vue library
4. **Vite Build Pipeline** -- Compiles Vue SFCs to IIFE, externalizes Drupal globals, outputs stable filenames for `libraries.yml`
5. **Drupal AJAX Layer** (independent of Vue) -- Status toggles on list pages via `#ajax` form elements and `AjaxResponse` commands
6. **Composables** (useKanban, useKeyboardShortcuts) -- State management and keyboard navigation as reusable Vue composition functions

### Critical Pitfalls

See [PITFALLS.md](PITFALLS.md) for the full 15-pitfall analysis with recovery strategies.

1. **CSRF token mechanism confusion** -- Drupal has TWO CSRF systems. REST routes called by JavaScript MUST use `_csrf_request_header_token: 'TRUE'` (header-based), NOT `_csrf_token: 'TRUE'` (query-string, for server-rendered links). Using the wrong one silently 403s every mutation. Fetch token from `/session/token` once on mount, cache it, include as `X-CSRF-Token` header.

2. **Vue double-mounting from Drupal.behaviors** -- Drupal calls `attachBehaviors()` on page load AND after every AJAX response/BigPipe delivery. Vue `createApp()` has no double-mount protection. MUST use `once('kanban-app', ...)` guard. Implement `detach` handler for cleanup. Declare `core/once` as library dependency.

3. **JSON error responses require `_format: json` route requirement** -- Without it, Drupal returns HTML error pages that Vue cannot parse. Add `_format: json` as a route requirement on ALL API routes so Drupal's JSON exception subscriber handles errors.

4. **Committed build output is mandatory** -- Drupal modules must work without Node.js. Compiled `js/dist/` MUST be committed. `node_modules/` is gitignored but `dist/` is not.

5. **Haiku declaration-usage gap** (eval-specific) -- Haiku generates REST controllers and Vue components but does not wire them together: omits `drupalSettings` attachment, skips CSRF token fetch, forgets `core/drupalSettings` library dependency. Skills must document the COMPLETE connected flow as a single pattern, not separate pieces. Eval assertions must test wiring, not just file existence.

## Implications for Roadmap

Based on the combined research, the natural phase structure follows the architecture's dependency chain. The critical path is: REST API -> Vite Pipeline -> Page Shell -> Vue Board -> Interactions -> Polish. Some work can parallelize (Vite pipeline alongside REST, Dashboard/AJAX alongside Vue interactions).

### Phase 1: REST API + Vue Infrastructure + Basic Board

**Rationale:** Everything depends on working endpoints and a functional Vue-in-Drupal mount. This phase validates the entire technical approach end-to-end. If DnD works on a real Drupal page, every subsequent phase is layering on top.

**Delivers:** Working Kanban board with drag-and-drop task status changes on a Drupal admin page.

**Addresses (from FEATURES.md):** Kanban board view, drag-and-drop between columns, task cards with metadata, REST endpoints, board route with local task tab, loading/empty states, priority visual indicators, column headers, responsive layout.

**Avoids (from PITFALLS.md):** CSRF mechanism confusion (#1, #5) -- correct from first endpoint. Double-mounting (#2) -- `once()` pattern established. JSON error responses (#3, #15) -- `_format: json` on all routes. Bundle not committed (#4) -- Vite pipeline with committed dist/. drupalSettings wiring (#7) -- library dependency + controller attachment. Local task tab (#8) -- parameter matching. Claro conflicts (#10) -- namespaced CSS from day one. Access checks (#13) -- entity-level access on all endpoints.

**Stack elements:** Custom controllers with `CacheableJsonResponse`, Vue 3, vue-draggable-plus, SortableJS, Vite, tinykeys (stub only).

**Includes:**
- Custom REST controllers: GET kanban by project, PATCH task status
- Vite build pipeline with IIFE output and committed dist/
- KanbanController page shell with drupalSettings
- Vue app: KanbanBoard, KanbanColumn, TaskCard components
- Drupal.behaviors bridge with once() guard
- Board route and "Board" local task tab
- CSRF token fetch wrapper (api/drupal.js)
- BEM-namespaced CSS with Claro variable integration

### Phase 2: Interactions + Detail Panel

**Rationale:** Phase 1 proves the board works. Phase 2 makes it a real working surface. Optimistic UI, inline editing, and keyboard shortcuts are what elevate this above "fancy status dropdown" into "Linear-quality tool."

**Delivers:** Full task management from the board without needing entity edit forms. Keyboard-driven workflow.

**Addresses (from FEATURES.md):** Task detail slide-over panel, inline title editing, optimistic UI with rollback, keyboard shortcuts (S/P/A/C + arrows), quick-create from column header, filter bar with URL state, context menu, due date visual warnings, assignee avatars, task count badges.

**Avoids (from PITFALLS.md):** Optimistic rollback (#9) -- full rollback pattern with error toast. Keyboard conflicts (#12) -- input-focus guards, test in Claro with toolbar. Vue/AJAX boundary (#14) -- clear separation defined.

**Stack elements:** tinykeys composable, vue-draggable-plus animation config.

**Includes:**
- POST endpoint for task quick-create
- PATCH endpoint for inline edits (title, priority, assignee)
- TaskDetailPanel.vue (slide-over with inline editing)
- useKeyboardShortcuts.js composable
- Optimistic update pattern with rollback in useKanban.js
- FilterBar.vue with URL query param sync
- ContextMenu.vue
- Board display options (localStorage card density)

### Phase 3: Command Palette + Dashboard + Polish

**Rationale:** The board is fully functional after Phase 2. Phase 3 adds the "peak UX" command palette, overhauls the dashboard entry point, adds AJAX enhancements to list pages (independent of Vue), and polishes animations and accessibility.

**Delivers:** Command palette for power users, enhanced dashboard for overview, AJAX list view improvements, animation refinement, accessibility hardening.

**Addresses (from FEATURES.md):** Command palette (Ctrl+K), dashboard overhaul with project cards, AJAX status toggles on list pages, board display options, full keyboard navigation (ARIA), shortcut help overlay, smooth drag animations.

**Avoids (from PITFALLS.md):** Vue/AJAX conflict (#14) -- AJAX list enhancements are pure Drupal, no Vue. Bundle size (#6) -- lazy-load command palette via dynamic import.

**Includes:**
- CommandPalette.vue with fuzzy search and action dispatch
- Enhanced DashboardController with project summary cards
- AJAX status toggles on TaskListBuilder (pure Drupal #ajax)
- GET /api/group-ai-pm/projects/summary endpoint
- Animation polish (drag ghost, card transitions)
- ARIA attributes, screen reader announcements
- Shortcut help overlay (? key)
- Bundle size optimization (code splitting, lazy loading)

### Phase 4: Testing + Final Eval

**Rationale:** Functional code must be verified. Kernel tests for REST endpoints, functional tests for page rendering, and eval assertions validate that skills produce correct wiring.

**Delivers:** Test coverage, eval results validating skill effectiveness for Vue/REST patterns.

**Includes:**
- Kernel tests: REST response shapes, access control, CSRF validation
- Functional tests: Board page renders, local task tab appears, drupalSettings populated
- Eval evals.json: Static assertions for wiring (library deps, drupalSettings, CSRF token fetch)
- Eval runtime assertions: drush-based endpoint verification
- phpcs compliance verification

### Phase Ordering Rationale

- **Phase 1 must come first** because the REST API and Vue infrastructure are prerequisites for everything. Both the architecture dependency chain and feature dependency graph confirm this -- the Kanban board is the foundation, not a feature that can be deferred.
- **Phase 2 before Phase 3** because keyboard shortcuts, inline editing, and optimistic UI are what make the board genuinely usable. The command palette depends on the keyboard system from Phase 2.
- **Phase 3 is the polish layer** because the dashboard overhaul, AJAX list enhancements, and command palette add value on top of a working board but are not prerequisites for each other. AJAX list work is independent of Vue entirely.
- **Phase 4 last** because tests validate completed functionality. However, REST endpoint tests can start as early as Phase 1 completion.
- **Parallelization opportunities:** Within Phase 1, REST API development and Vite pipeline setup are independent. Within Phase 3, dashboard overhaul and AJAX list enhancements are independent of each other and of the command palette.

### Research Flags

Phases likely needing deeper research during planning:
- **Phase 1 (REST + Vue Infrastructure):** The STACK.md and ARCHITECTURE.md disagree on the API approach: STACK recommends custom controllers with `CacheableJsonResponse`, ARCHITECTURE recommends REST module `@RestResource` plugins. This must be resolved before implementation. Custom controllers are simpler and avoid `rest`/`serialization` module dependencies. See Gaps section for recommendation.
- **Phase 2 (Keyboard + Detail Panel):** Focus management across Vue components and Drupal admin toolbar needs testing. No single authoritative reference for this pattern.

Phases with standard patterns (skip research-phase):
- **Phase 3 (Dashboard + AJAX):** Drupal AJAX is extremely well-documented. Dashboard enhancement is standard controller/template work.
- **Phase 4 (Testing):** Kernel and Functional test patterns are established from v3.0 eval pipeline.

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | Vue 3, SortableJS, tinykeys are mature. Vite for Drupal is newer but well-referenced. All sources official docs or high-confidence community. |
| Features | MEDIUM-HIGH | Feature list well-grounded in Linear patterns and existing Drupal PM modules. Anti-feature decisions are sound. Complexity estimates need validation during implementation. |
| Architecture | MEDIUM | Embedded Vue island pattern is proven but Drupal-specific integration (behaviors bridge, IIFE build) has less community consensus than standard SPA patterns. Disagreement between STACK and ARCHITECTURE on REST approach. |
| Pitfalls | HIGH | CSRF and behaviors pitfalls verified against official Drupal docs and issue queues. Haiku declaration-usage gap validated empirically in v3.0 evals. |

**Overall confidence:** MEDIUM-HIGH

### Gaps to Address

- **REST approach disagreement:** STACK.md recommends custom controllers; ARCHITECTURE.md recommends REST module `@RestResource` plugins. Custom controllers avoid adding `drupal:rest` and `drupal:serialization` as module dependencies, use `CacheableJsonResponse` directly, and are simpler for internal admin endpoints. RestResource plugins provide config-driven enablement but add dependency weight. **Recommendation: Go with custom controllers per STACK.md.** The routes are internal to the module, not meant for external consumption, so the config-driven REST module approach adds complexity without benefit.

- **Vue externalization vs bundling:** STACK.md recommends externalizing Vue as a separate Drupal library (`js/vendor/vue.global.prod.js`). ARCHITECTURE.md's Vite config suggests treating Vue as an external global too, but does not address how it gets on the page. **Recommendation: Externalize Vue as STACK.md suggests** -- declare a `vue` library in libraries.yml with the global production build, reference it as a dependency of the `kanban` library. This adds ~34 KB as a cacheable asset and follows the correct Drupal pattern for shared JS libraries.

- **Weight field on Task entity:** ARCHITECTURE.md adds a `weight` base field to Task entity for within-column ordering. FEATURES.md explicitly lists "manual card reordering" as an anti-feature, recommending deterministic sort (priority > due date > created). **Recommendation: Do NOT add a weight field.** Sort deterministically per FEATURES.md. This avoids a schema change and the fractional indexing complexity that accompanies manual ordering.

- **Eval assertion strategy for Vue/REST patterns:** Haiku's declaration-usage gap is the biggest eval risk. Standard file-existence assertions will pass while the app is broken. Assertions must verify: (1) `core/drupalSettings` in library dependencies, (2) `#attached.drupalSettings` in controller render array, (3) CSRF token fetch in JavaScript, (4) `_csrf_request_header_token` in route requirements. This is a design task for each phase's eval round.

## Sources

### Primary (HIGH confidence)
- [Drupal CSRF Access Checking](https://www.drupal.org/docs/8/api/routing-system/access-checking-on-routes/csrf-access-checking) -- CSRF token mechanism distinction
- [Drupal Asset Libraries](https://www.drupal.org/docs/develop/creating-modules/adding-assets-css-js-to-a-drupal-module-via-librariesyml) -- libraries.yml, drupalSettings
- [Drupal JavaScript API Overview](https://www.drupal.org/docs/drupal-apis/javascript-api/javascript-api-overview) -- Drupal.behaviors, once(), attach lifecycle
- [Drupal AJAX API](https://www.drupal.org/docs/drupal-apis/ajax-api) -- #ajax, AjaxResponse, core commands
- [Drupal Local Tasks](https://www.drupal.org/docs/drupal-apis/menu-api/providing-module-defined-local-tasks) -- base_route, parameter matching
- [Vue.js Production Deployment](https://vuejs.org/guide/best-practices/production-deployment.html) -- Build optimization, global build
- [vue-draggable-plus](https://github.com/Alfred-Skyblue/vue-draggable-plus) -- Vue 3 DnD, Composition API
- [SortableJS](https://github.com/SortableJS/Sortable) -- Cross-column drag, animation, touch support
- [tinykeys](https://github.com/jamiebuilds/tinykeys) -- Keyboard binding, 650 B
- [Vite Library Mode](https://vite.dev/guide/build) -- IIFE output, external globals
- [Linear Board Layout](https://linear.app/docs/board-layout) -- UX patterns, keyboard controls
- v3.0 Eval Results (Phases 14-17) -- Haiku code generation patterns, empirical

### Secondary (MEDIUM confidence)
- [Five Jars Vue + Drupal Integration](https://fivejars.com/blog/how-integrate-vuejs-applications-drupal) -- Mounting patterns
- [PreviousNext Vite for Drupal](https://www.previousnext.com.au/blog/vite-and-storybook-frontend-tooling-drupal) -- Build config
- [Lullabot Understanding Behaviors](https://www.lullabot.com/articles/understanding-javascript-behaviors-in-drupal) -- Behavior lifecycle deep dive
- [Drupal Vue.js Library Module](https://www.drupal.org/project/vuejs) -- Community precedent
- [Burndown Module](https://www.drupal.org/project/burndown) -- Drupal PM comparison baseline
- [Kanban UX Best Practices](https://www.multiboard.dev/posts/best-practices-kanban-columns) -- Column design patterns

### Tertiary (LOW confidence)
- [Drupal HTMX Proposal](https://www.drupal.org/project/drupal/issues/3404409) -- Context only, not implemented
- [Vue.js in Drupal Admin Proposal](https://www.drupal.org/project/ideas/issues/2913628) -- Community direction signal

---
*Research completed: 2026-03-08*
*Supersedes v3.0 SUMMARY.md (2026-03-07)*
*Ready for roadmap: yes*

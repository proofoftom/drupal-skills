# Requirements: Drupal Skills v4.0 — UX Overhaul

**Defined:** 2026-03-08
**Core Value:** Transform group_ai_pm from functional admin CRUD into a polished, interactive project management tool with Vue.js Kanban boards, AJAX interactions, and visual polish.

## v4.0 Requirements

Requirements for v4.0 milestone. Extends the existing v3.0 module (39 files, entities, Group integration, AI tools all in place). Zero entity schema changes.

### REST API Layer

- [ ] **API-01**: Custom REST controller serving tasks grouped by status column for a project (GET endpoint with CacheableJsonResponse)
- [ ] **API-02**: PATCH endpoint for task status update (drag-and-drop target) with `_csrf_request_header_token` protection
- [ ] **API-03**: PATCH endpoint for task inline edits (title, priority, assignee) with entity access checks
- [ ] **API-04**: POST endpoint for task quick-create from board with status pre-fill
- [ ] **API-05**: GET endpoint for project summary data (task counts per status) for dashboard
- [ ] **API-06**: Entity-level access checks on all endpoints (not just route-level permission)
- [ ] **API-07**: `_format: json` route requirement on all API routes for proper JSON error responses
- [ ] **API-08**: Cache tags on all JSON responses (task:{id}, task_list, project:{id}) for Drupal cache integration

### Vue Infrastructure

- [ ] **VUE-01**: Vite build pipeline producing IIFE output with stable filenames, committed to js/dist/
- [ ] **VUE-02**: Vue 3 externalized as separate Drupal library (js/vendor/vue.global.prod.js)
- [ ] **VUE-03**: Drupal.behaviors bridge with once() guard preventing double-mounting
- [ ] **VUE-04**: drupalSettings data passing (project ID, API base URL, CSRF token URL, status/priority labels, permissions)
- [ ] **VUE-05**: core/drupalSettings and core/once declared as library dependencies
- [ ] **VUE-06**: CSRF token fetched once on mount, cached, included in all mutation requests
- [ ] **VUE-07**: BEM-namespaced CSS (gapm- prefix) using Claro admin theme CSS custom properties
- [ ] **VUE-08**: Bundle size under 100 KB gzipped (Vue runtime + SortableJS + app code), loaded only on board route

### Kanban Board (Table Stakes)

- [ ] **BOARD-01**: Kanban board view per project with 4 status columns (To Do, In Progress, Review, Done)
- [ ] **BOARD-02**: Drag-and-drop between columns via vue-draggable-plus/SortableJS updating task status
- [ ] **BOARD-03**: Task cards displaying title, priority badge (color-coded), assignee name, due date
- [ ] **BOARD-04**: Status-colored column headers with task count badges
- [ ] **BOARD-05**: Board route at /admin/content/project/{project}/board with "Board" local task tab
- [ ] **BOARD-06**: Loading skeleton, empty column states, and error states
- [ ] **BOARD-07**: Responsive column layout (4 columns side-by-side at 1200px+, horizontal scroll on narrow)
- [ ] **BOARD-08**: Keyboard alternative to drag-and-drop for status changes (WCAG 2.5.7 compliance)
- [ ] **BOARD-09**: Task quick-create via inline title input at column header ("+" button)
- [ ] **BOARD-10**: Server-rendered initial state via drupalSettings (no extra API call on page load)

### Interactions & Detail

- [ ] **INTERACT-01**: Task detail slide-over panel (right side, board still visible) with full task metadata
- [ ] **INTERACT-02**: Inline title editing on task cards (click-to-edit, Enter to save, Escape to cancel)
- [ ] **INTERACT-03**: Optimistic UI updates with error rollback and toast notifications on drag-and-drop
- [ ] **INTERACT-04**: Context menu on right-click (Change Status, Change Priority, Assign, Edit, Delete)
- [ ] **INTERACT-05**: Filter bar (assignee, priority) with dismissible pills and URL query param persistence
- [ ] **INTERACT-06**: Smooth drag animations (card lift shadow, destination highlight, settle easing)
- [ ] **INTERACT-07**: Drag-and-drop ghost/preview (reduced opacity at source, card follows cursor)

### Visual Polish

- [ ] **VISUAL-01**: Due date visual warnings (red border = overdue, amber = due today, subtle = within 3 days)
- [ ] **VISUAL-02**: Assignee avatars (user picture or colored initials fallback) on task cards
- [ ] **VISUAL-03**: Board display options (show/hide card properties, compact vs expanded, localStorage persistence)

### Dashboard & List Enhancements

- [ ] **DASH-01**: Enhanced dashboard with project summary cards showing task count bars per status and progress percentage
- [ ] **DASH-02**: Dashboard quick actions (New Project, recent project links, Board links)
- [ ] **DASH-03**: AJAX status toggles on TaskListBuilder rows (Drupal #ajax, no Vue dependency)

### Testing & Eval

- [ ] **TEST-01**: Kernel tests for REST endpoint response shapes and access control
- [ ] **TEST-02**: Functional tests for board page rendering, local task tab presence, drupalSettings population
- [ ] **TEST-03**: phpcs compliance on all new and modified PHP files
- [ ] **EVAL-01**: Static eval assertions targeting wiring (library deps, drupalSettings attachment, CSRF fetch in JS)
- [ ] **EVAL-02**: Runtime eval assertions (drush-based endpoint verification, module enable, permission checks)
- [ ] **EVAL-03**: Browser eval assertions (board renders, drag-drop works, AJAX toggles function) via eval-browser
- [ ] **EVAL-04**: Three-tier eval results per phase with delta measurement (with-plugin vs without-plugin)

## Deferred Requirements

Tracked for v5.0+. Not in current roadmap.

### Keyboard Power User

- **KB-01**: Single-key shortcuts (S=status, P=priority, A=assign, C=create)
- **KB-02**: Command palette (Ctrl+K) with fuzzy search and action dispatch
- **KB-03**: Full keyboard navigation (arrow keys between cards/columns, ARIA activedescendant)
- **KB-04**: Shortcut help overlay (? key)

### Advanced AI (from v3.0 deferrals)

- **AI-06**: AI-powered task creation from natural language
- **AI-07**: Batch AI operations
- **AI-08**: AI-suggested task assignments and status updates

### Analytics

- **DB-01**: Task history tracking database table
- **DB-02**: Views integration for analytics data

## Out of Scope

Explicitly excluded. Documented to prevent scope creep.

| Feature | Reason |
|---------|--------|
| Swimlanes | Filter bar achieves same insight at 10x less complexity |
| WebSocket real-time updates | Single-user admin context; poll on focus instead |
| Gantt chart / timeline view | Massive frontend complexity, not a Kanban feature |
| Custom workflow states | 4 fixed statuses match universal Kanban; existing schema |
| Sprint/cycle management | Separate sub-module scope |
| Manual card reordering within columns | Sort deterministically (priority > due date > created) |
| Rich text editor in cards | Link to entity form for description editing |
| File attachments on cards | Link to entity form for file management |
| Activity log per card | Show "Last updated" instead; revision history on entity page |
| Entity schema changes | Existing Task/Project fields cover all v4.0 features |
| TypeScript | Haiku generates invalid TS; adds build complexity |
| CSS frameworks (Tailwind/Bootstrap) | Conflicts with Claro admin theme |
| Pinia/Vuex state management | ref() + composables sufficient for single-page state |
| Vue Router | Fights Drupal routing; this is an embedded island, not SPA |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| API-01 | Phase 18 | Pending |
| API-02 | Phase 18 | Pending |
| API-03 | Phase 18 | Pending |
| API-04 | Phase 18 | Pending |
| API-05 | Phase 18 | Pending |
| API-06 | Phase 18 | Pending |
| API-07 | Phase 18 | Pending |
| API-08 | Phase 18 | Pending |
| VUE-01 | Phase 18 | Pending |
| VUE-02 | Phase 18 | Pending |
| VUE-03 | Phase 18 | Pending |
| VUE-04 | Phase 18 | Pending |
| VUE-05 | Phase 18 | Pending |
| VUE-06 | Phase 18 | Pending |
| VUE-07 | Phase 18 | Pending |
| VUE-08 | Phase 18 | Pending |
| BOARD-01 | Phase 18 | Pending |
| BOARD-02 | Phase 18 | Pending |
| BOARD-03 | Phase 18 | Pending |
| BOARD-04 | Phase 18 | Pending |
| BOARD-05 | Phase 18 | Pending |
| BOARD-06 | Phase 18 | Pending |
| BOARD-07 | Phase 18 | Pending |
| BOARD-08 | Phase 18 | Pending |
| BOARD-09 | Phase 18 | Pending |
| BOARD-10 | Phase 18 | Pending |
| INTERACT-01 | Phase 19 | Pending |
| INTERACT-02 | Phase 19 | Pending |
| INTERACT-03 | Phase 19 | Pending |
| INTERACT-04 | Phase 19 | Pending |
| INTERACT-05 | Phase 19 | Pending |
| INTERACT-06 | Phase 19 | Pending |
| INTERACT-07 | Phase 19 | Pending |
| VISUAL-01 | Phase 19 | Pending |
| VISUAL-02 | Phase 19 | Pending |
| VISUAL-03 | Phase 19 | Pending |
| DASH-01 | Phase 20 | Pending |
| DASH-02 | Phase 20 | Pending |
| DASH-03 | Phase 20 | Pending |
| TEST-01 | Phase 21 | Pending |
| TEST-02 | Phase 21 | Pending |
| TEST-03 | Phase 21 | Pending |
| EVAL-01 | Phase 21 | Pending |
| EVAL-02 | Phase 21 | Pending |
| EVAL-03 | Phase 21 | Pending |
| EVAL-04 | Phase 21 | Pending |

**Coverage:**
- v4.0 requirements: 46 total (8 API + 8 VUE + 10 BOARD + 7 INTERACT + 3 VISUAL + 3 DASH + 3 TEST + 4 EVAL)
- Mapped: 46/46
- Deferred to v5.0+: 7 (4 keyboard, 3 AI)
- Out of scope: 14 items

---
*Requirements defined: 2026-03-08*
*Traceability updated: 2026-03-08*

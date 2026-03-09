# Roadmap: Drupal Skills

## Milestones

- ✅ **v1.0 Skill Authoring** -- Phases 1-7 (shipped 2026-03-07)
- ✅ **v2.0 Eval & Optimization Loop** -- Phases 8-12 (shipped 2026-03-08)
- ✅ **v3.0 Group AI Project Management** -- Phases 13-17 (shipped 2026-03-08)
- 🚧 **v4.0 UX Overhaul** -- Phases 18-21 (in progress)

## Phases

<details>
<summary>v1.0 Skill Authoring (Phases 1-7) -- SHIPPED 2026-03-07</summary>

- [x] Phase 1: Foundations (3/3 plans) -- completed 2026-03-05
- [x] Phase 2: Core Workflow (4/4 plans) -- completed 2026-03-06
- [x] Phase 3: Presentation and Quality (4/4 plans) -- completed 2026-03-06
- [x] Phase 4: Specialized Patterns (2/2 plans) -- completed 2026-03-06
- [x] Phase 5: Eval, Optimization, and Packaging (2/2 plans) -- completed 2026-03-06
- [x] Phase 6: Live Eval Loop (5/5 plans) -- completed 2026-03-06
- [x] Phase 7: Full Eval-Optimize Loop (6/8 plans) -- completed 2026-03-06 (2 plans carried to v2.0)

Full details: milestones/v1.0-ROADMAP.md

</details>

<details>
<summary>v2.0 Eval & Optimization Loop (Phases 8-12) -- SHIPPED 2026-03-08</summary>

- [x] Phase 8: Eval Infrastructure (2/2 plans) -- completed 2026-03-07
- [x] Phase 9: Eval Prompt Rewrite (2/2 plans) -- completed 2026-03-07
- [x] Phase 10: Pipeline Validation (2/2 plans) -- completed 2026-03-07
- [x] Phase 11: Batch Execution (13/13 plans) -- completed 2026-03-07
- [x] Phase 12: Analysis & Optimization (4/4 plans) -- completed 2026-03-08

Full details: milestones/v2.0-ROADMAP.md

</details>

<details>
<summary>v3.0 Group AI Project Management (Phases 13-17) -- SHIPPED 2026-03-08</summary>

- [x] Phase 13: Plugin Packaging (2/2 plans) -- completed 2026-03-08
- [x] Phase 14: Module Foundation -- completed 2026-03-08
- [x] Phase 15: Group & AI Integration -- completed 2026-03-08
- [x] Phase 16: Views, Theming & Processing -- completed 2026-03-08
- [x] Phase 17: Testing & Final Eval -- completed 2026-03-08

Full details: milestones/v3.0-ROADMAP.md

</details>

### v4.0 UX Overhaul (In Progress)

**Milestone Goal:** Transform group_ai_pm from functional admin CRUD into a polished, interactive project management tool -- Vue.js Kanban boards with drag-and-drop, AJAX interactions, and visual polish -- validated through eval-driven development with three-tier assertions (static + runtime + browser).

- [ ] **Phase 18: REST API + Vue Infrastructure + Basic Board** - Working Kanban board with drag-and-drop on a Drupal admin page
- [ ] **Phase 19: Interactions + Detail Panel + Visual Polish** - Full task management from the board with inline editing and optimistic UI
- [ ] **Phase 20: Dashboard + List Enhancements** - Enhanced dashboard entry point and AJAX list improvements
- [ ] **Phase 21: Testing + Final Eval** - Test coverage and three-tier eval results validating skill effectiveness

## Phase Details

### Phase 18: REST API + Vue Infrastructure + Basic Board
**Goal**: Users can view and manage task status via a drag-and-drop Kanban board embedded in the Drupal admin UI, backed by custom REST endpoints and a Vite-built Vue 3 application
**Depends on**: v3.0 complete (existing module with Project/Task entities, Group integration)
**Requirements**: API-01, API-02, API-03, API-04, API-05, API-06, API-07, API-08, VUE-01, VUE-02, VUE-03, VUE-04, VUE-05, VUE-06, VUE-07, VUE-08, BOARD-01, BOARD-02, BOARD-03, BOARD-04, BOARD-05, BOARD-06, BOARD-07, BOARD-08, BOARD-09, BOARD-10
**Success Criteria** (what must be TRUE):
  1. Navigating to /admin/content/project/{id}/board displays a Kanban board with 4 status columns showing that project's tasks as draggable cards
  2. Dragging a task card from one column to another updates the task's status via a PATCH request with CSRF protection, and the board reflects the change without page reload
  3. Clicking the "+" button on a column header creates a new task pre-filled with that column's status, and the card appears in the column immediately
  4. The board page loads with server-rendered initial state (no loading spinner for initial data) and shows loading/empty/error states for subsequent interactions
  5. A "Board" local task tab appears alongside existing entity tabs, and the board is only accessible to users with appropriate entity-level permissions
**Plans:** 3 plans
Plans:
- [ ] 18-01-PLAN.md -- REST API endpoints (TaskApiController + ProjectApiController + routes)
- [ ] 18-02-PLAN.md -- Vue build pipeline + Drupal integration (Vite, behaviors bridge, CSRF wrapper, libraries)
- [ ] 18-03-PLAN.md -- KanbanController page shell + Vue board components (4 components + composable)

### Phase 19: Interactions + Detail Panel + Visual Polish
**Goal**: Users can manage all task properties directly from the board without navigating to entity edit forms, with optimistic feedback, visual indicators, and polished interaction patterns
**Depends on**: Phase 18
**Requirements**: INTERACT-01, INTERACT-02, INTERACT-03, INTERACT-04, INTERACT-05, INTERACT-06, INTERACT-07, VISUAL-01, VISUAL-02, VISUAL-03
**Success Criteria** (what must be TRUE):
  1. Clicking a task card opens a slide-over panel showing full task metadata, and the board remains visible behind it
  2. Double-clicking a task title on the card enables inline editing -- Enter saves, Escape cancels, and the update persists after page reload
  3. Drag-and-drop shows immediate visual feedback (card lift shadow, destination highlight) and rolls back with a toast notification if the server request fails
  4. Overdue tasks show a red border, due-today tasks show amber, and assignees display as colored-initial avatars on task cards
  5. The filter bar narrows visible cards by assignee and priority, with active filters shown as dismissible pills and persisted in URL query params
**Plans:** 3 plans
Plans:
- [ ] 19-01-PLAN.md -- PHP backend serialization + DELETE endpoint + JS composables + API wrapper
- [ ] 19-02-PLAN.md -- TaskCard enhancements (inline edit, due date, avatar) + drag animations + toast integration
- [ ] 19-03-PLAN.md -- Board-level components (panel, context menu, filter bar, display options) + wiring + Vite rebuild

### Phase 20: Dashboard + List Enhancements
**Goal**: Users have an enhanced dashboard showing project health at a glance, and can toggle task status directly from list views without JavaScript frameworks
**Depends on**: Phase 18 (uses API-05 endpoint for dashboard data)
**Requirements**: DASH-01, DASH-02, DASH-03
**Success Criteria** (what must be TRUE):
  1. The dashboard page shows project summary cards with task count bars per status and a progress percentage, pulling data from the project summary API endpoint
  2. Dashboard quick actions (New Project, recent project links, Board links) are visible and functional without scrolling
  3. Task list pages have inline AJAX status toggle dropdowns that update without page reload, using pure Drupal AJAX (no Vue dependency)
**Plans:** 2 plans
Plans:
- [ ] 20-01-PLAN.md -- Enhanced dashboard with project summary cards, progress bars, quick actions (DASH-01, DASH-02)
- [ ] 20-02-PLAN.md -- TaskStatusForm with AJAX status toggle dropdowns (DASH-03)

### Phase 21: Testing + Final Eval
**Goal**: The complete v4.0 module passes automated tests and coding standards, and three-tier eval results validate that skills produce correct Vue/REST/AJAX wiring
**Depends on**: Phase 18, Phase 19, Phase 20
**Requirements**: TEST-01, TEST-02, TEST-03, EVAL-01, EVAL-02, EVAL-03, EVAL-04
**Success Criteria** (what must be TRUE):
  1. Kernel tests pass for all REST endpoints (response shapes, access control, CSRF validation, cache tags)
  2. Functional tests pass for board page rendering (local task tab present, drupalSettings populated, Vue mount point exists)
  3. `phpcs --standard=Drupal,DrupalPractice` reports zero errors on all new and modified PHP files
  4. Browser eval assertions confirm the board renders, drag-and-drop changes task status, and AJAX list toggles function
  5. Per-phase delta report shows with-plugin vs without-plugin comparison across all three tiers (static + runtime + browser)
**Plans:** 2 plans
Plans:
- [ ] 21-01-PLAN.md -- Design eval assertions (static + runtime + browser targeting drupal-testing skill)
- [ ] 21-02-PLAN.md -- Execute eval pipeline, grade, compute v4.0 aggregate delta, promote

## Progress

**Execution Order:**
Phases execute in numeric order: 18 -> 19 -> 20 -> 21

| Phase | Milestone | Plans Complete | Status | Completed |
|-------|-----------|----------------|--------|-----------|
| 1-7 | v1.0 | 26/28 | Shipped | 2026-03-07 |
| 8-12 | v2.0 | 23/23 | Shipped | 2026-03-08 |
| 13-17 | v3.0 | Complete | Shipped | 2026-03-08 |
| 18. REST API + Vue + Board | v4.0 | 0/3 | Planned | - |
| 19. Interactions + Detail + Visual | v4.0 | 0/3 | Planned | - |
| 20. Dashboard + List | v4.0 | 0/2 | Planned | - |
| 21. Testing + Final Eval | v4.0 | 0/2 | Planned | - |

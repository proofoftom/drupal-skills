# Feature Landscape: v4.0 Vue.js Kanban UX Overhaul

**Domain:** Linear-quality project management UX for an existing Drupal 10 module (group_ai_pm)
**Researched:** 2026-03-08
**Confidence:** MEDIUM-HIGH (Linear UX patterns well-documented; Vue 3 + Drupal integration patterns verified; Drupal AJAX/REST APIs documented in core; some integration specifics are novel)

## Scope

This research covers ONLY new features for v4.0. The existing module (from v3.0) already has:
- Project and Task content entities with full CRUD
- Task fields: title, description, status (todo/in_progress/review/done), priority (low/medium/high/critical), due_date, assignee, project reference
- Project fields: title, description, status (planning/active/review/completed), owner
- Dashboard at /admin/content/project-dashboard (static HTML table showing project count + recent projects)
- Entity list builders (ProjectListBuilder, TaskListBuilder), Views integration
- Group integration, Settings form, permissions system
- AI tools sub-module (optional, CreateProjectTool, QueryProjectsTool)
- CSS libraries: task_cards, project_summaries
- Local task tabs: View/Edit/Delete on both entity types
- Menu links under /admin/content/ for Projects, Tasks, Dashboard
- Action route: /admin/content/project/{project}/complete (CSRF-protected)
- ProjectStatusBlock showing project counts by status

v4.0 transforms this from "functional admin CRUD" to "interactive PM tool."

---

## Table Stakes

Features users expect from any Kanban-based project management interface. Without these, the board feels broken or unfinished.

| Feature | Why Expected | Complexity | Depends On |
|---------|--------------|------------|------------|
| **Kanban board view per project** | The defining feature. Tasks displayed as cards in columns mapped to the existing 4 status values (To Do, In Progress, Review, Done). Every PM tool with "Kanban" in its description has this. Burndown, Views Kanban, OpenLucius -- all Drupal PM contrib modules center on this view. | HIGH | Vue 3 app mount point, REST endpoints for task data |
| **Drag-and-drop between columns** | Moving a card from "To Do" to "In Progress" is the core Kanban interaction. Without it, users must open full edit forms to change status -- defeating the purpose. OpenLucius Kanban and Views Kanban both implement DnD. The bar is set. | HIGH | Kanban board rendered, PATCH endpoint for task status updates, vuedraggable or vue-dnd-kit |
| **Task cards with visible metadata** | Cards must show title, priority badge, assignee name, and due date at a glance. Linear shows properties "as space allows" on cards, with descriptions explicitly hidden. Users scan boards visually; hidden metadata forces clicking each card. | MEDIUM | Existing task entity fields (all present), Vue card component, CSS |
| **Priority visual indicators** | Color-coded badges or icons for Low/Medium/High/Critical. Linear uses exactly four fixed priority levels -- the existing Task.priority field already has exactly these four values. Users need instant priority scanning without reading text. | LOW | Existing priority field values, CSS class mapping |
| **Status-colored column headers** | Each column needs a distinct visual identity. Users orient by color when scanning boards with many cards. Linear uses subtle color in column headers; Kanban best practices emphasize "consistent column colors reduce cognitive load." | LOW | CSS theming on 4 column headers |
| **REST/JSON endpoints for Vue** | Vue app needs to read tasks filtered by project and update task status. Minimum viable: GET tasks by project ID, PATCH task status/priority/assignee. Must handle Drupal's X-CSRF-Token header for write operations. Custom REST controllers (not JSON:API) for tailored response shapes. | HIGH | Custom controller classes, Drupal serialization, CSRF token route |
| **Task quick-create from board** | "+" button at column header to create a task with that column's status pre-filled. Linear has "C" shortcut. Users expect to add tasks without leaving the board. Minimum: title-only inline form that creates task via POST endpoint. | MEDIUM | POST endpoint for task creation, inline form component |
| **Loading and empty states** | Skeleton loaders while fetching, "No tasks yet" for empty columns, error states for failed requests. Without these, the board looks broken during load (common with 50+ tasks) and confusing when a project has no tasks. | LOW | Vue component conditional rendering |
| **Board route with local task tab** | A "Board" local task tab on the project entity canonical page (alongside existing View/Edit/Delete tabs). Route: /admin/content/project/{project}/board. Users navigate to a project and expect the board within Drupal's standard tab navigation. | MEDIUM | New route in routing.yml, new entry in links.task.yml, controller that renders Vue mount div |
| **Responsive column layout** | Columns must scroll vertically when full. Board must render 4 columns side-by-side on standard desktop widths (1200px+). Cards must not overflow columns. On narrower screens, horizontal scroll rather than breaking layout. | MEDIUM | CSS flexbox/grid, overflow-y: auto on columns |
| **Keyboard alternative to drag-and-drop** | WCAG 2.2 SC 2.5.7 (Dragging Movements) requires that any drag functionality also work without dragging. Provide a "Move" button or keyboard shortcut that opens a status selection menu. Not optional -- it is an accessibility requirement. | MEDIUM | Status change menu/dialog per card, keyboard event handlers |

---

## Differentiators

Features that elevate the module from "basic Kanban" to "Linear-quality." Not expected in a Drupal module, but create real delight and efficiency.

| Feature | Value Proposition | Complexity | Depends On |
|---------|-------------------|------------|------------|
| **Keyboard shortcuts** | Linear's defining UX pattern. Single-key shortcuts: S for status, P for priority, A for assign, C for create. Arrow keys to navigate between cards. Linear "was designed so you can take actions in multiple ways" -- keyboard is the fastest. No Drupal PM module does this. | MEDIUM | Keybinding system (Vue composable), focus management, action dispatch |
| **Command palette (Ctrl+K)** | Universal search + action launcher. Type to find tasks, change status, assign users, navigate between projects. Linear's "most beloved feature" -- replaces menus with intent-based navigation. Linear docs: "searching for the action in the command line makes it easy to figure out how to do anything." | HIGH | Vue overlay component, fuzzy search over task titles, action registration system |
| **Inline title editing** | Click a card title to edit in-place. Linear allows clicking any field to edit inline. Removes the "open form, edit field, save, navigate back" friction. Double-click or Enter key to activate. Escape to cancel. | MEDIUM | Contenteditable or input-swap pattern, PATCH endpoint for title field |
| **Context menu on right-click** | Right-click a card: Edit, Change Status (submenu), Change Priority (submenu), Assign (submenu), Delete. Linear provides contextual menus as a primary interaction pattern alongside keyboard and buttons. | MEDIUM | Vue context menu component, submenu rendering, action dispatch to API |
| **Smooth drag animations** | CSS transitions during drag: card lifts with shadow, destination column highlights, card settles with easing on drop. Transforms mechanical DnD into satisfying interaction. vuedraggable/SortableJS supports animation config natively. | LOW-MEDIUM | DnD library animation options, CSS transitions |
| **Optimistic UI updates** | When dragging a card to a new column, update UI immediately and sync to server in background. Revert on failure with error toast. Linear feels instant because it never blocks on network. Critical for perceived performance. | MEDIUM | Vue reactive state, async PATCH with rollback, toast notification component |
| **Task detail slide-over panel** | Click a card to open a right-side panel (board still visible on left) showing full task details. Inline editing of title, status, priority, assignee within the panel. "Edit full page" link to Drupal entity form. Linear uses this pattern; it keeps board context visible. | HIGH | Vue panel/drawer component, full task data endpoint, inline editing components |
| **Filter bar** | Filter board by assignee, priority, or due date range. Active filters display as dismissible pills above the board. Linear's filters are "extremely intuitive." URL query param persistence so filters survive page refresh. | MEDIUM | Vue filter component, client-side filtering (tasks already loaded), URL state sync |
| **Due date visual warnings** | Cards with past-due dates get red border/highlight. Cards due today get yellow/amber. Cards due within 3 days get subtle indicator. Users scan for urgency without reading date text. | LOW | Date comparison logic in Vue computed property, CSS conditional classes |
| **Assignee avatars** | Small circular avatar (Drupal user picture) or colored initials fallback on each card. Linear shows assignees as avatars, not text labels. Increases visual density and scannability. | LOW | User picture URL in API response, initials-generation utility, CSS for circular avatars |
| **Task count badges per column** | Number showing task count in each column header (e.g., "In Progress (7)"). Linear shows these. Helps assess workload distribution and spot bottlenecks at a glance. | LOW | Computed property from reactive task arrays |
| **Board display options** | Toggle which properties show on cards: show/hide priority, assignee, due date. Compact vs expanded card layout. Linear lets users "customize card density." Stored in localStorage per-user. | MEDIUM | Vue settings dropdown, localStorage persistence, card component conditional slots |
| **Full keyboard navigation** | Arrow keys move focus between cards (up/down within column, left/right between columns). Enter opens detail panel. Tab to filter bar. Escape closes panels. ARIA attributes on cards for screen reader announcements. | MEDIUM | Focus management system, aria-activedescendant, keyboard event composition |
| **AJAX status toggles on entity list pages** | On /admin/content/task (existing TaskListBuilder), add a status dropdown per row that updates via Drupal AJAX without page reload. Pure Drupal AJAX -- no Vue dependency. Bridges the gap for users who prefer list view over board view. | MEDIUM | Drupal AJAX Form API (#ajax property), AjaxResponse with ReplaceCommand |
| **Dashboard overhaul** | Replace current static HTML table (just shows project count + recent projects table) with: project summary cards showing task count by status as mini progress bars, recent activity, quick-action buttons ("New Project", "View Board"). | HIGH | New dashboard controller/template, aggregation queries, Vue or Twig components |
| **Drag-and-drop progress indicator** | During drag, show a ghost/preview of the card with reduced opacity at the source position while the dragged card follows the cursor. Standard DnD UX pattern that provides spatial context. | LOW | DnD library ghost/clone options, CSS opacity |

---

## Anti-Features

Features to explicitly NOT build. Each is tempting but adds complexity without proportional value in a Drupal admin module context.

| Anti-Feature | Why Tempting | Why Avoid | What to Do Instead |
|--------------|-------------|-----------|-------------------|
| **Swimlanes** | Linear has them. Groups cards into horizontal rows by assignee/priority/label within each column. | Doubles board complexity to a 2D grid. Requires sub-grouping API, complex layout calculations, more API calls. The existing 4-column layout with filter bar achieves the same insight (show one assignee at a time) with dramatically less complexity. | Use the filter bar to show one assignee or priority level at a time. Same information, 10x simpler UI. |
| **Real-time WebSocket updates** | Linear and modern SaaS tools update boards live when teammates make changes. Feels collaborative. | Drupal has no native WebSocket infrastructure. Adding Mercure, Pusher, or Socket.IO adds deployment complexity (Redis, Node.js sidecar). The module targets small teams using Drupal admin where simultaneous board editing is rare. | Poll for changes every 30-60 seconds when the board tab is focused. Refresh on window focus. Pragmatic for the Drupal deployment context. |
| **Gantt chart / timeline view** | Enterprise PM tools (Asana, Monday.com) have them. Linear recently added project timelines. | Massive frontend complexity: date-range rendering, dependency arrows, zoom controls, horizontal scrolling calendar. Not a Kanban feature and does not exercise Drupal skills being tested. | Show due dates on cards and in detail panel. Use Drupal Views for date-sorted task lists if timeline view is needed. |
| **Custom workflow states** | Let admins define their own columns beyond the 4 fixed statuses. "Every team is different." | Requires: entity update hook to alter allowed_values list, migration logic for existing tasks, board column config UI, form for adding/removing/reordering states. The 4 statuses (todo/in_progress/review/done) match the universal Kanban model and the existing entity schema. | Keep the 4 fixed statuses. If an admin needs more, they can extend Task.status allowed_values via Drupal config and the board will render whatever values exist -- but we do not build a UI for managing them. |
| **Sprint/cycle management** | Linear has Cycles. Jira has Sprints. Burndown module has sprint support. | Adds a new entity type (Sprint), date-range assignment logic, velocity calculations, burndown charts. Massive scope expansion for marginal value in a module whose primary goal is demonstrating Drupal skills. | Projects serve as the grouping mechanism. Due dates on tasks provide time-boxing. If sprints are needed later, it is a separate sub-module. |
| **Multi-select bulk drag** | Select multiple cards (shift-click) and drag them all to another column simultaneously. | Complex DnD interaction: multi-item selection state, composite drag preview, batch PATCH endpoint, error handling for partial failures. Niche use case -- rarely more than 2-3 tasks need the same status change at once. | Provide bulk status change via command palette or a "select and act" pattern: checkbox select cards, then use keyboard shortcut or button to change status of all selected. |
| **Manual card reordering within columns** | Drag cards up/down within a column to set display order. Linear supports this. | Requires a weight/order field on the Task entity (schema change), complex position calculation on drop (fractional indexing like Linear uses, or full rebalancing), migration for existing tasks. | Sort cards within columns deterministically: by priority (critical first) then by due date (soonest first) then by created date (newest first). Predictable ordering without manual arrangement. |
| **Rich text editor in board cards** | CKEditor or ProseMirror for task descriptions inline on the board or in the detail panel. | CKEditor integration inside a Vue overlay is heavy (separate CKEditor Vue wrapper, toolbar configuration, paste handling). Description editing from the board is rare -- users typically write descriptions once when creating the task. | Show description as plain text in the detail panel. Provide a "Edit full page" link to the Drupal entity edit form where CKEditor is already configured on the description field. |
| **File attachments on cards** | Drag files onto cards or upload from detail panel. | File upload requires Drupal's managed file entity system, file field on Task entity (schema change), upload progress UI, file display/download in Vue, storage considerations. Large scope for low Kanban value. | Link to the task edit form for file management. The board focuses on status flow, not document management. |
| **Activity log per card** | Show who changed status/priority/assignee and when, as a timeline per task. | Requires entity change tracking via hook_entity_update (logging every field change to a dedicated table or custom entity), storage schema, timeline rendering component. Significant backend and frontend work. | Show "Last updated: [relative time]" on cards and in detail panel. Full entity revision history is available on the Drupal entity view page if revision support is added later. |

---

## Feature Dependencies

```
[Foundation Layer: REST API]
    Custom REST controller (GroupAiPmApiController)
        |
        +-- GET /api/group-ai-pm/project/{project}/tasks
        |     Returns: JSON array of task objects for a project
        |     Fields: id, title, status, priority, assignee (id+name+avatar_url),
        |             due_date, description, created, changed, edit_url
        |
        +-- PATCH /api/group-ai-pm/task/{task}
        |     Accepts: status, title, priority, assignee (partial updates)
        |     Returns: Updated task object
        |     Requires: X-CSRF-Token header
        |
        +-- POST /api/group-ai-pm/project/{project}/task
        |     Accepts: title, status (optional, defaults to column)
        |     Returns: Created task object
        |     Requires: X-CSRF-Token header
        |
        +-- GET /api/group-ai-pm/projects/summary
              Returns: Project list with task counts per status
              Used by: Dashboard overhaul

    CSRF token endpoint (core: /session/token)
    Permission checks: reuse existing entity access handlers

[Foundation Layer: Vue 3 App]
    Vue 3 compiled bundle
        |
        +-- Drupal library entry in .libraries.yml
        |     Dependencies: core/drupalSettings, core/drupal.ajax (for CSRF)
        |
        +-- drupalSettings data passing:
        |     project_id, csrf_token, current_user, api_base_url,
        |     status_labels, priority_labels
        |
        +-- Build config: Vite for dev, compiled JS committed for production
        |     (Drupal modules ship pre-built assets, no Node.js required at install)
        |
        +-- DnD library: vuedraggable@next (Vue 3 wrapper for SortableJS)
              Touch support built-in, animation config, group option for cross-list drag

[Core Board Layer]
    KanbanBoard.vue ----requires----> API Layer + Vue Foundation
        |
        +-- KanbanColumn.vue (one per status value, rendered from config)
        |       |
        |       +-- TaskCard.vue
        |       |       +-- PriorityBadge.vue (color mapping: low=gray, med=blue, high=orange, critical=red)
        |       |       +-- AssigneeAvatar.vue (user picture or initials)
        |       |       +-- DueDateLabel.vue (with overdue/approaching warnings)
        |       |       +-- QuickActions (keyboard shortcut targets)
        |       |
        |       +-- Column header: status label + task count + quick-create button
        |       +-- QuickCreateForm.vue (inline title input)
        |
        +-- Drag-and-drop via vuedraggable
        |       +-- Optimistic UI (move card in reactive state immediately)
        |       +-- Background PATCH call
        |       +-- Rollback on error + toast notification
        |
        +-- Loading/empty/error states

[Drupal Route Layer]
    Board route: /admin/content/project/{project}/board
        |
        +-- Local task tab entry in links.task.yml
        |     base_route: entity.project.canonical
        |     title: "Board"
        |
        +-- BoardController::content(Project $project)
        |     Renders: mount point <div id="kanban-app"></div>
        |     Attaches: kanban_board library
        |     Passes: drupalSettings with project context
        |
        +-- Permission: 'access group_ai_pm dashboard'

[Interaction Layer] ----requires----> Core Board Layer
    TaskDetailPanel.vue (slide-over from right)
        |
        +-- InlineTitleEditor.vue (click-to-edit)
        +-- StatusSelector.vue (dropdown or button group)
        +-- PrioritySelector.vue
        +-- AssigneeSelector.vue (user autocomplete)
        +-- "Edit full page" link to entity.task.edit_form
        +-- Escape to close
        |
    KeyboardShortcuts.vue (composable)
        |
        +-- Board navigation: arrow keys between cards/columns
        +-- Quick actions: C=create, S=status, P=priority, A=assign
        +-- Panel: Enter=open detail, Escape=close
        +-- Global: ?=show shortcut help overlay
        |
    CommandPalette.vue ----requires----> Keyboard system + API
        |
        +-- Ctrl+K to open overlay
        +-- Fuzzy search over task titles (client-side, tasks already loaded)
        +-- Action items: change status, assign, navigate to project
        +-- Recent actions memory
        |
    ContextMenu.vue
        |
        +-- Right-click on card: status submenu, priority submenu, assign, edit, delete
        +-- Rendered via Vue teleport to body
        |
    FilterBar.vue
        |
        +-- Assignee filter, priority filter, due date range
        +-- Active filter pills with dismiss buttons
        +-- URL query parameter sync for shareable/bookmarkable filtered views

[Dashboard Layer] ----requires----> API Layer
    Enhanced DashboardController
        |
        +-- ProjectCard components (Vue or Twig)
        |     task count bars per status, progress percentage
        +-- Quick actions: New Project, recent project links
        +-- Board links per project

[List Enhancement Layer] ----independent, no Vue---
    AJAX status toggles on TaskListBuilder
        |
        +-- Drupal #ajax render element on status field
        +-- AjaxResponse with ReplaceCommand
        +-- Works on /admin/content/task (existing list page)
```

### Critical Path

1. **REST API Layer** -- Everything else depends on endpoints returning task data as JSON
2. **Vue 3 Infrastructure** -- Build tooling, library registration, drupalSettings, mount point
3. **Kanban Board + Drag-and-Drop** -- Core feature; validates entire approach end-to-end
4. **Board Route + Local Task Tab** -- Makes board discoverable in Drupal navigation
5. **Optimistic UI + Error Handling** -- Makes the board feel responsive rather than laggy
6. **Detail Panel + Inline Editing** -- Makes the board actually usable for task management (not just status changes)
7. **Keyboard Shortcuts** -- Transforms usability from "GUI tool" to "power tool"
8. **Command Palette** -- Peak UX; depends on keyboard system and search infrastructure
9. **Dashboard + List Enhancements** -- Polish layer; can run in parallel with steps 6-8

---

## User Workflows

### Workflow 1: Admin opens the module for the first time

1. Navigate to /admin/content in Drupal admin
2. See "Project Dashboard" in admin content menu (exists today)
3. Click dashboard -- see project summary cards with task distribution bars (replaces current HTML table)
4. Click "Add project" to create first project (existing add form)
5. After saving, redirected to project view page with new "Board" tab visible alongside View/Edit/Delete

### Workflow 2: Daily task management on the Kanban board

1. Navigate to /admin/content/project/{id} and click "Board" tab
2. Board loads showing 4 columns: To Do | In Progress | Review | Done
3. Skeleton loaders show briefly while tasks fetch from API
4. Scan cards: red badge = critical priority, amber card border = due today/overdue
5. Drag a task card from "To Do" to "In Progress" -- card moves immediately (optimistic update)
6. Green toast confirms save; if server fails, card snaps back with red error toast
7. Click "+" on "To Do" column header to quick-create a task
8. Type task title in inline input, press Enter -- card appears at top of column
9. Press S while a card is focused to open status change menu
10. Press C anywhere on the board to create a new task

### Workflow 3: Reviewing task details without leaving the board

1. On the board, click a task card (or press Enter when card is focused)
2. Detail panel slides in from the right side (board still visible, dimmed slightly)
3. Panel shows: title (click to edit inline), description, status selector, priority selector, assignee autocomplete, due date, created/updated timestamps
4. Click the title text -- it becomes an editable input. Type new title, press Enter to save
5. Click priority badge to cycle through Low/Medium/High/Critical (PATCH fires in background)
6. Click "Open full page" link to go to Drupal entity edit form for complex changes
7. Press Escape to close the panel and return to board focus

### Workflow 4: Filtering and finding tasks

1. On the board, click the filter bar above the columns
2. Select "Assignee: Jane" from dropdown -- board filters to show only Jane's tasks
3. Filter pill appears: "Assignee: Jane [x]" -- click x to remove filter
4. Add second filter: "Priority: Critical" -- board shows only Jane's critical tasks
5. URL updates to include filter params (bookmarkable/shareable)
6. Press Ctrl+K to open command palette, type "fix login bug"
7. Matching task appears in results -- press Enter to open its detail panel

### Workflow 5: Keyboard-first power user session

1. Open board, press ? to see keyboard shortcut help overlay
2. Use arrow keys to navigate between cards (up/down in column, left/right across columns)
3. On a card: press S, then select "In Progress" from status menu -- card moves to new column
4. Press C to create new task, type title, Enter to save
5. Press P on a card to change priority without opening detail panel
6. Ctrl+K to search for a specific task by name, Enter to navigate to it
7. Escape to dismiss any open menu/panel/overlay

### Workflow 6: Using list view with AJAX enhancements

1. Navigate to /admin/content/task (existing TaskListBuilder page)
2. See task table with existing columns plus new interactive status dropdown per row
3. Click the status dropdown on a task row -- AJAX updates status without page reload
4. Row visually updates (status text/badge changes), Drupal status message confirms save
5. No Vue dependency -- this works in pure Drupal AJAX, independent of the board

---

## MVP Recommendation

### Phase 1: REST API + Vue Foundation + Basic Board

Build the endpoints and get a working Kanban board with drag-and-drop. This phase validates the entire technical approach -- if the board works with DnD, everything else is layering on top.

Prioritize:
1. Custom REST controller with GET tasks-by-project and PATCH task status endpoints
2. Vue 3 app with KanbanBoard, KanbanColumn, TaskCard components
3. Drag-and-drop via vuedraggable@next (SortableJS wrapper)
4. Board route (/admin/content/project/{project}/board) with local task tab
5. Task cards with title, priority badge, assignee name, due date
6. Loading states and empty column states
7. Keyboard-based status change as DnD alternative (accessibility)

Defer:
- Command palette: Depends on keyboard system and search infrastructure
- Dashboard overhaul: Current dashboard is functional; board is the priority
- AJAX list enhancements: Independent workstream, not blocked by Vue
- Context menu: Nice UX but board works without it
- Display options: Customization layer, not core functionality

### Phase 2: Interactions + Detail Panel

Make the board a real working surface for task management.

Prioritize:
1. Task detail slide-over panel with inline title editing
2. Optimistic UI with error rollback and toast notifications
3. Keyboard shortcuts (S/P/A/C + arrow navigation)
4. Quick-create from column header (POST endpoint)
5. Filter bar (assignee, priority) with URL state

### Phase 3: Command Palette + Dashboard + Polish

Peak UX features and overall module refinement.

Prioritize:
1. Command palette (Ctrl+K) with fuzzy search and action dispatch
2. Dashboard overhaul with project summary cards and progress bars
3. Context menu on right-click
4. Board display options (show/hide card properties, localStorage)
5. AJAX status toggles on list view pages
6. Animation polish, focus management refinement, shortcut help overlay

---

## Complexity Budget

| Category | Feature Count | Avg Complexity | Effort Share |
|----------|--------------|----------------|--------------|
| Table Stakes | 11 | MEDIUM | ~55% of total effort |
| Differentiators | 16 | MEDIUM | ~40% of total effort |
| Anti-Features | 11 | (avoided) | 0% |
| Accessibility | (integrated) | MEDIUM | ~5% (spread across phases) |

The table stakes consume the majority of effort because the REST API layer and Vue 3 infrastructure are foundational HIGH complexity items that every subsequent feature depends on. The board itself, with DnD and responsive layout, is also HIGH complexity. The differentiators layer on top of established foundations and are individually less complex.

---

## Existing Entity Compatibility

All v4.0 features build on the existing entity structure without schema modifications:

| Entity Field | Board Usage | API Exposure | Notes |
|--------------|------------|--------------|-------|
| Task.title | Card title, inline editable | GET/PATCH | Max 255 chars, required |
| Task.status | Column placement, drag determines target | GET/PATCH | Allowed: todo, in_progress, review, done |
| Task.priority | Color badge on card, filter dimension | GET/PATCH | Allowed: low, medium, high, critical |
| Task.assignee | Avatar/name on card, filter dimension | GET/PATCH | entity_reference to user, optional |
| Task.due_date | Date label, overdue warning logic | GET (read only from board) | datetime field, date-only |
| Task.description | Detail panel body text | GET (read only from board) | text_long, edit via entity form |
| Task.project | Board scoping (which project's board) | GET (filter/query param) | entity_reference to project, required |
| Task.uid | "Created by" in detail panel | GET | Owner via EntityOwnerTrait |
| Task.created | Sort dimension, detail panel display | GET | Created timestamp |
| Task.changed | "Last updated" on cards and panel | GET | Changed timestamp |
| Project.title | Board header, dashboard card, breadcrumb | GET | Max 255 chars |
| Project.status | Dashboard card badge, project list filter | GET | Allowed: planning, active, review, completed |
| Project.description | Dashboard card snippet | GET | text_long |
| Project.uid | Dashboard "Owner" display | GET | Owner via EntityOwnerTrait |

**No entity schema changes needed.** The existing 4-value Task.status field maps 1:1 to Kanban columns. The existing 4-value Task.priority field maps 1:1 to Linear's priority model. The existing entity_reference fields (assignee, project) provide all necessary relationships.

---

## Comparison to Existing Drupal PM Modules

| Feature | Burndown | Views Kanban | OpenLucius Board | group_ai_pm v4.0 (target) |
|---------|----------|-------------|-----------------|---------------------------|
| Drupal version | ^9/^10/^11 | ^9/^10/^11/^12 | D8 only | ^10/^11 |
| Board rendering | Custom Twig | Views display plugin | jQuery | Vue 3 SPA |
| Drag-and-drop | jQuery UI | JS event-based | jQuery | vuedraggable (SortableJS) |
| Keyboard shortcuts | No | No | No | Yes (Linear-style) |
| Command palette | No | No | No | Yes (Ctrl+K) |
| Detail slide panel | No (full page) | No | Inline edit | Yes |
| Optimistic UI | No | No | No | Yes |
| Custom statuses | Yes (swimlanes) | Yes (any field) | Fixed | Fixed (4 statuses) |
| Estimation | T-shirt/geometric | No | No | No (out of scope) |
| Sprint support | Yes | No | No | No (anti-feature) |
| Active installs | 39 | Unknown | Unknown | N/A (new) |

The differentiating position is: keyboard-first, optimistic UI, modern Vue 3 rendering. No existing Drupal PM module offers these. The trade-off is fixed workflow statuses -- but the 4-column Kanban model is universally understood.

---

## Sources

- [Linear Board Layout Docs](https://linear.app/docs/board-layout) -- column management, grouping, drag behavior, keyboard controls -- HIGH confidence
- [Linear Conceptual Model](https://linear.app/docs/conceptual-model) -- issue hierarchy, workflow model, entity relationships -- HIGH confidence
- [Linear Keyboard Shortcuts](https://keycombiner.com/collections/linear/) -- comprehensive shortcut reference -- HIGH confidence
- [Linear Redesign Blog](https://linear.app/now/how-we-redesigned-the-linear-ui) -- design philosophy, color reduction -- MEDIUM confidence
- [Burndown Module](https://www.drupal.org/project/burndown) -- D9/10/11 PM module with kanban, 39 installs -- HIGH confidence
- [Views Kanban Module](https://www.drupal.org/project/views_kanban) -- Views-based kanban display plugin -- HIGH confidence
- [OpenLucius Kanban](https://www.drupal.org/project/openlucius_board) -- inline add/edit, DnD status/priority -- MEDIUM confidence
- [Drupal AJAX Forms](https://www.drupal.org/docs/drupal-apis/javascript-api/ajax-forms) -- #ajax render element, callback patterns -- HIGH confidence
- [Drupal AJAX Commands](https://www.drupal.org/docs/drupal-apis/ajax-api/core-ajax-callback-commands) -- ReplaceCommand, OpenDialogCommand -- HIGH confidence
- [Drupal Local Tasks](https://www.drupal.org/docs/drupal-apis/menu-api/providing-module-defined-local-tasks) -- links.task.yml, base_route -- HIGH confidence
- [Drupal REST API Overview](https://www.drupal.org/docs/drupal-apis/restful-web-services-api/restful-web-services-api-overview) -- REST resources, CSRF, authentication -- HIGH confidence
- [Drupal JSON:API](https://www.drupal.org/docs/core-modules-and-themes/core-modules/jsonapi-module) -- zero-config entity exposure (considered, rejected for custom controllers) -- HIGH confidence
- [Drupal Vue.js Integration (Decoupled Blocks)](https://www.drupal.org/docs/contributed-modules/decoupled-blocks-vuejs) -- drupalSettings passing, library registration -- MEDIUM confidence
- [vuedraggable (Vue 3 / SortableJS)](https://github.com/SortableJS/Vue.Draggable) -- DnD library, animation, touch support -- HIGH confidence
- [Kanban Board UX Best Practices](https://www.multiboard.dev/posts/best-practices-kanban-columns) -- column design, WIP limits, color coding -- MEDIUM confidence
- [Kanban Anti-Patterns](https://kanban.university/patterns-and-anti-patterns-for-kanban-board-design/) -- over-engineering, missing WIP limits -- MEDIUM confidence
- [WCAG 2.2 SC 2.5.7 Dragging Movements](https://www.w3.org/WAI/WCAG21/Understanding/keyboard.html) -- keyboard alternatives required for drag -- HIGH confidence
- [Drag-and-Drop Accessibility](https://appinstitute.com/drag-and-drop-design-accessibility-best-practices/) -- ARIA roles, keyboard patterns, screen reader support -- MEDIUM confidence
- [Kanban Board UX Pattern](https://uxpatterns.dev/patterns/data-display/kanban-board) -- detail panel, card design, interaction patterns -- MEDIUM confidence

---
*Feature research for: v4.0 Vue.js Kanban UX Overhaul*
*Researched: 2026-03-08*

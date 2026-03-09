# Phase 19: Interactions + Detail Panel + Visual Polish - Research

**Researched:** 2026-03-08
**Domain:** Vue 3 interactive components (slide-over panel, inline editing, context menu, filter bar, drag animations, toast notifications), date-based visual indicators, and avatar rendering within an existing Drupal 10 embedded Vue island
**Confidence:** HIGH

## Summary

Phase 19 extends the Phase 18 Kanban board (already built and validated at 96.7% eval score) with rich interaction patterns and visual polish. The existing codebase provides a solid foundation: Vue 3 app with KanbanBoard, KanbanColumn, TaskCard, QuickCreateForm components, a useKanban composable with optimistic updates and rollback, CSRF-aware API wrapper, 5 REST endpoints, and Drupal.behaviors bridge with once() guard. Phase 19 adds NO new Drupal routes or entity schema changes -- it is purely frontend component work in the Vue layer plus minor PHP changes to serialize additional data (user picture URLs, task description).

The 10 requirements break into three implementation clusters: (1) **New Vue components** -- TaskDetailPanel (slide-over), ContextMenu, FilterBar, ToastNotification; (2) **Enhancements to existing components** -- TaskCard gets inline title editing, due date visual warnings, assignee avatars, and click-to-open panel handler; KanbanColumn gets enhanced SortableJS animation config; KanbanBoard gets filter state and display options; (3) **Composables** -- useFilters (URL query param sync), useToast (notification queue), useContextMenu (position + visibility). The PHP side needs one change: KanbanController.serializeTask() must include user picture URL and task description in the JSON payload.

All features can be built using Vue 3's built-in reactivity (ref, computed, watch) and the existing composable pattern. No new npm dependencies are needed beyond what Phase 18 already installed. Toast notifications, context menus, and slide-over panels are built as lightweight custom components -- not imported from heavy UI libraries -- keeping the bundle under the 100 KB gzipped budget.

**Primary recommendation:** Build the toast notification system first (needed by optimistic rollback in INTERACT-03), then the slide-over panel (INTERACT-01, largest new component), then enhance TaskCard with inline editing + visual indicators (INTERACT-02, VISUAL-01, VISUAL-02), then add context menu and filter bar (INTERACT-04, INTERACT-05), then polish drag animations and display options (INTERACT-06, INTERACT-07, VISUAL-03).

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| INTERACT-01 | Task detail slide-over panel (right side, board still visible) with full task metadata | Build as a Vue component using CSS `position: fixed` with right-side slide animation. Board remains visible via semi-transparent overlay. Reads full task data from existing useKanban state. PHP: add `description` to serializeTask() JSON. |
| INTERACT-02 | Inline title editing on task cards (click-to-edit, Enter to save, Escape to cancel) | Input-swap pattern (not contenteditable): double-click title toggles from `<span>` to `<input>`, Enter calls existing `updateTask()` in useKanban composable, Escape restores original value. |
| INTERACT-03 | Optimistic UI updates with error rollback and toast notifications on drag-and-drop | useKanban already has optimistic update + rollback. Enhancement: add toast notification on rollback (red "Failed to update task") and on success (subtle green confirmation). Build lightweight useToast composable. |
| INTERACT-04 | Context menu on right-click (Change Status, Change Priority, Assign, Edit, Delete) | Custom ContextMenu.vue using `@contextmenu.prevent`, positioned at cursor via `event.clientX/clientY`. Uses Vue Teleport to render at body level to avoid overflow clipping. Submenus for status and priority. |
| INTERACT-05 | Filter bar (assignee, priority) with dismissible pills and URL query param persistence | FilterBar.vue above the board with dropdown selectors. Active filters shown as pills with dismiss buttons. URL sync via `history.replaceState()` (no Vue Router needed -- this is an embedded island). Reads URL params on mount to restore filters. |
| INTERACT-06 | Smooth drag animations (card lift shadow, destination highlight, settle easing) | SortableJS config: `animation: 150`, `easing: 'cubic-bezier(0.25, 1, 0.5, 1)'`, enhanced `ghostClass` CSS with elevated shadow, `chosenClass` with scale transform, column `dragover` highlight via CSS class toggled on SortableJS `onMove` event. |
| INTERACT-07 | Drag-and-drop ghost/preview (reduced opacity at source, card follows cursor) | SortableJS `ghostClass: 'gapm-task-card--ghost'` (opacity 0.4, blue-tinted background), `dragClass: 'gapm-task-card--dragging'` (slight rotation, elevated shadow). Already partially implemented in Phase 18 -- enhance with better visual styling. |
| VISUAL-01 | Due date visual warnings (red border = overdue, amber = due today, subtle = within 3 days) | Computed property in TaskCard comparing `task.dueDate` to current date. CSS classes: `gapm-task-card--overdue` (red left border + subtle red background), `gapm-task-card--due-today` (amber left border), `gapm-task-card--due-soon` (dotted amber left border). |
| VISUAL-02 | Assignee avatars (user picture or colored initials fallback) on task cards | PHP: extend serializeTask() to include `assignee.pictureUrl` from user entity's `user_picture` field via `file_url_generator` service. Vue: AssigneeAvatar component renders `<img>` if pictureUrl exists, otherwise colored circle with initials derived from display name. Color deterministic from user ID hash. |
| VISUAL-03 | Board display options (show/hide card properties, compact vs expanded, localStorage persistence) | DisplayOptions dropdown in board header. Reactive state stored in `localStorage` keyed by project ID. Options: showPriority, showAssignee, showDueDate, compactMode. TaskCard conditionally renders properties based on display settings. |
</phase_requirements>

## Standard Stack

### Core (Already installed from Phase 18)

| Library | Version | Purpose | Status |
|---------|---------|---------|--------|
| Vue 3 | ^3.3.4 | Reactive framework | Already in js/vendor/vue.global.prod.js |
| vue-draggable-plus | ^0.1.4 | Cross-column drag-and-drop | Already in package.json |
| SortableJS | ^1.15.0 | DnD engine | Already in package.json |
| Vite | ^4.4.9 | IIFE build pipeline | Already in devDependencies |
| @vitejs/plugin-vue | ^4.3.4 | Vue SFC compilation | Already in devDependencies |

### New Dependencies Required

**None.** All Phase 19 features are built with Vue 3 built-in APIs:
- `ref()`, `computed()`, `watch()`, `onMounted()`, `onUnmounted()` -- reactive state
- `<Teleport>` -- context menu and toast rendering at body level
- `<Transition>` / `<TransitionGroup>` -- slide-over, toast, and filter pill animations
- `nextTick()` -- focus management after inline edit activation
- `history.replaceState()` -- URL query param sync (no Vue Router needed)
- `localStorage` -- display options persistence

### Why No New Dependencies

| Feature | Could Use | Why Build Custom Instead |
|---------|-----------|--------------------------|
| Toast notifications | vue-toastification, vue3-toastify | Adds 8-15 KB. Our toasts are simple (message + type + auto-dismiss). ~30 lines of Vue code. |
| Context menu | @imengyu/vue3-context-menu | Adds 15+ KB. Our menu has fixed structure (5 items + 2 submenus). ~60 lines of Vue code. |
| Slide-over panel | Headless UI | Adds 20+ KB. Single panel with fixed layout. ~80 lines of Vue code. |
| Filter URL sync | vue-route-query | Requires Vue Router (which we deliberately excluded). `history.replaceState()` is 10 lines. |

**Bundle budget:** Phase 18 bundle is already within 100 KB gzipped budget. Phase 19 adds ~3-5 KB of component code (no new library deps). Stays well within budget.

### PHP Changes (No New Dependencies)

| Change | Purpose |
|--------|---------|
| `file_url_generator` service in KanbanController | Generate absolute URL for user picture (VISUAL-02) |
| `description` field in serializeTask() | Full task data for detail panel (INTERACT-01) |

## Architecture Patterns

### Phase 19 New/Modified Files

```
modules/group_ai_pm/
  src/Controller/
    KanbanController.php               # MODIFY: add description, pictureUrl, project members to serialization
  js/src/
    main.js                            # MODIFY: minor -- no structural changes expected
    api/
      drupal.js                        # MODIFY: add deleteKanban() method for task deletion from context menu
    components/
      KanbanBoard.vue                  # MODIFY: add FilterBar, DisplayOptions, ToastContainer, handle panel open
      KanbanColumn.vue                 # MODIFY: enhanced SortableJS animation config, drag highlight CSS
      TaskCard.vue                     # MODIFY: inline edit, due date classes, avatar, context menu trigger, click-to-open panel
      TaskDetailPanel.vue              # NEW: slide-over panel with full task metadata
      ContextMenu.vue                  # NEW: right-click menu with status/priority submenus
      FilterBar.vue                    # NEW: filter dropdowns + dismissible pills
      ToastContainer.vue               # NEW: toast notification stack
      AssigneeAvatar.vue               # NEW: user picture or colored initials
      DisplayOptions.vue               # NEW: dropdown for card property visibility
    composables/
      useKanban.js                     # MODIFY: add deleteTask(), expose toast events
      useFilters.js                    # NEW: filter state + URL sync
      useToast.js                      # NEW: notification queue management
      useContextMenu.js                # NEW: position calculation + visibility
      useDisplayOptions.js             # NEW: localStorage-persisted board settings
    kanban.css                         # MODIFY: add styles for new components
  js/dist/
    kanban.js                          # REBUILD after changes
    style.css                          # REBUILD after changes
```

### Pattern 1: Slide-Over Panel (INTERACT-01)

**What:** A fixed-position panel that slides in from the right side of the viewport, with the board remaining visible behind a semi-transparent overlay.

**When to use:** Task detail view from the board.

**Example:**
```vue
<!-- TaskDetailPanel.vue -->
<template>
  <Teleport to="body">
    <Transition name="gapm-panel">
      <div v-if="visible" class="gapm-panel-overlay" @click.self="close">
        <div class="gapm-panel" role="dialog" aria-label="Task details">
          <div class="gapm-panel__header">
            <h2>{{ task.title }}</h2>
            <button @click="close" aria-label="Close panel">&times;</button>
          </div>
          <div class="gapm-panel__body">
            <!-- Status selector, priority selector, assignee, dates, description -->
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
```

**CSS pattern:**
```css
.gapm-panel-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.3);
  z-index: 1000;
  display: flex;
  justify-content: flex-end;
}
.gapm-panel {
  width: 420px;
  max-width: 90vw;
  background: white;
  height: 100vh;
  overflow-y: auto;
  box-shadow: -4px 0 20px rgba(0, 0, 0, 0.15);
}
.gapm-panel-enter-active,
.gapm-panel-leave-active {
  transition: all 0.25s ease;
}
.gapm-panel-enter-from .gapm-panel,
.gapm-panel-leave-to .gapm-panel {
  transform: translateX(100%);
}
.gapm-panel-enter-from,
.gapm-panel-leave-to {
  opacity: 0;
}
```

**Key considerations:**
- Use `<Teleport to="body">` to escape Drupal's admin theme overflow constraints
- Trap focus inside panel when open (Tab cycling)
- Close on Escape key press
- Close on overlay click (but not panel body click)
- Board remains interactive behind overlay at reduced opacity

### Pattern 2: Inline Title Editing (INTERACT-02)

**What:** Double-click on task title to enter edit mode. Title text is replaced by an input field pre-filled with current value.

**When to use:** TaskCard component, title element.

**Example:**
```vue
<template>
  <!-- Display mode -->
  <h3 v-if="!isEditing" class="gapm-task-card__title"
      @dblclick="startEditing"
      @click="$emit('open-panel', task)">
    {{ task.title }}
  </h3>
  <!-- Edit mode -->
  <input v-else
    ref="editInput"
    v-model="editValue"
    class="gapm-task-card__title-input"
    @keydown.enter="saveEdit"
    @keydown.escape="cancelEdit"
    @blur="saveEdit"
  />
</template>

<script setup>
import { ref, nextTick } from 'vue';

const isEditing = ref(false);
const editValue = ref('');
const editInput = ref(null);

const startEditing = () => {
  editValue.value = props.task.title;
  isEditing.value = true;
  nextTick(() => {
    editInput.value?.focus();
    editInput.value?.select();
  });
};

const saveEdit = () => {
  if (editValue.value.trim() && editValue.value !== props.task.title) {
    emit('update-task', { taskId: props.task.id, updates: { title: editValue.value.trim() } });
  }
  isEditing.value = false;
};

const cancelEdit = () => {
  isEditing.value = false;
};
</script>
```

**Key considerations:**
- Use input-swap pattern (not contenteditable) -- simpler, more predictable behavior
- Double-click for edit (single click opens panel) -- prevents accidental edits
- Auto-select text on edit activation for easy replacement
- Blur saves (not cancels) -- prevents data loss from accidental click-away
- Escape always cancels without saving
- Prevent click event propagation during edit mode (stop `@click` from opening panel)

### Pattern 3: Toast Notification Composable (INTERACT-03)

**What:** Lightweight notification system for success/error feedback on async operations.

**When to use:** After drag-and-drop status changes (error rollback), inline edits, task creation.

**Example:**
```javascript
// composables/useToast.js
import { ref } from 'vue';

const toasts = ref([]);
let idCounter = 0;

export function useToast() {
  const addToast = (message, type = 'info', duration = 4000) => {
    const id = ++idCounter;
    toasts.value.push({ id, message, type });
    if (duration > 0) {
      setTimeout(() => removeToast(id), duration);
    }
  };

  const removeToast = (id) => {
    toasts.value = toasts.value.filter(t => t.id !== id);
  };

  return { toasts, addToast, removeToast };
}
```

**Toast types:**
- `success` -- green, auto-dismiss 3s, for confirmed saves
- `error` -- red, auto-dismiss 5s, for failed operations with rollback
- `info` -- blue, auto-dismiss 4s, for general notifications

### Pattern 4: Context Menu (INTERACT-04)

**What:** Right-click menu positioned at cursor with action items and submenus.

**When to use:** Right-click on TaskCard.

**Example:**
```vue
<!-- ContextMenu.vue -->
<template>
  <Teleport to="body">
    <div v-if="visible" class="gapm-context-menu"
         :style="{ top: position.y + 'px', left: position.x + 'px' }"
         @click.stop>
      <button class="gapm-context-menu__item" @click="emit('action', 'edit')">
        Edit Task
      </button>
      <div class="gapm-context-menu__submenu-trigger"
           @mouseenter="showSubmenu = 'status'"
           @mouseleave="showSubmenu = null">
        Change Status &rsaquo;
        <div v-if="showSubmenu === 'status'" class="gapm-context-menu__submenu">
          <button v-for="(label, key) in statusLabels" :key="key"
                  @click="emit('action', 'status', key)">
            {{ label }}
          </button>
        </div>
      </div>
      <!-- Similar for priority, assign, delete -->
    </div>
  </Teleport>
</template>
```

**Key considerations:**
- Teleport to body to escape overflow: hidden on column containers
- Close on click outside (add document click listener on mount)
- Close on Escape key
- Position adjustment when near viewport edges (prevent menu from going off-screen)
- Submenu opens on hover with slight delay

### Pattern 5: Filter Bar with URL Sync (INTERACT-05)

**What:** Filter controls above the board with dismissible pill indicators and URL query parameter persistence.

**When to use:** Board-level task filtering by assignee and priority.

**Example:**
```javascript
// composables/useFilters.js
import { ref, watch, onMounted } from 'vue';

export function useFilters() {
  const filters = ref({
    assignee: null,
    priority: null,
  });

  // Read from URL on mount
  onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    if (params.has('assignee')) filters.value.assignee = params.get('assignee');
    if (params.has('priority')) filters.value.priority = params.get('priority');
  });

  // Sync to URL on change
  watch(filters, (newFilters) => {
    const params = new URLSearchParams(window.location.search);
    Object.entries(newFilters).forEach(([key, value]) => {
      if (value) {
        params.set(key, value);
      } else {
        params.delete(key);
      }
    });
    const newUrl = `${window.location.pathname}?${params.toString()}`;
    history.replaceState(null, '', newUrl);
  }, { deep: true });

  const clearFilter = (key) => {
    filters.value[key] = null;
  };

  const clearAll = () => {
    filters.value = { assignee: null, priority: null };
  };

  return { filters, clearFilter, clearAll };
}
```

**Filtering is client-side:** All tasks are already loaded via drupalSettings (BOARD-10 from Phase 18). Filters apply `computed` property that narrows `columns` data. No additional API calls needed.

### Pattern 6: Enhanced Drag Animations (INTERACT-06, INTERACT-07)

**What:** Enhanced SortableJS configuration for polished drag-and-drop visual feedback.

**When to use:** KanbanColumn VueDraggable component.

**Example:**
```vue
<VueDraggable
  :model-value="tasks"
  group="kanban-tasks"
  :animation="150"
  easing="cubic-bezier(0.25, 1, 0.5, 1)"
  ghost-class="gapm-task-card--ghost"
  chosen-class="gapm-task-card--chosen"
  drag-class="gapm-task-card--dragging"
  :fallback-on-body="true"
  @change="handleDragChange"
  @start="onDragStart"
  @end="onDragEnd"
>
```

**CSS for drag states:**
```css
/* Ghost: placeholder left behind at source position */
.gapm-task-card--ghost {
  opacity: 0.4;
  background: #e3f2fd;
  border: 2px dashed #90caf9;
  box-shadow: none;
}

/* Chosen: the card under the cursor during drag */
.gapm-task-card--chosen {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
  transform: rotate(1deg) scale(1.02);
  z-index: 100;
}

/* Dragging: transient state while card is moving */
.gapm-task-card--dragging {
  opacity: 0.9;
}

/* Column highlight when a card is being dragged over it */
.gapm-kanban-column--drag-over {
  background: #f0f7ff;
  border-color: #90caf9;
}
```

**Column highlight on drag-over:** Use SortableJS `onMove` event to add/remove a CSS class on the target column. This is handled in the KanbanColumn component by emitting a custom event to the board.

### Anti-Patterns to Avoid

- **Using contenteditable for inline title editing:** Contenteditable has inconsistent behavior across browsers for plain text editing, produces HTML artifacts from paste, and complicates v-model binding. Use input-swap pattern instead.
- **Importing a UI component library for a single component:** Adding vue-toastification (8 KB), headless-ui (20 KB), or @imengyu/vue3-context-menu (15 KB) for components that are 30-80 lines of custom code. These libraries bring transitive dependencies and increase bundle size beyond the 100 KB budget.
- **Using Vue Router for URL parameter sync:** Vue Router fights Drupal routing. This is an embedded island, not a SPA. Use `history.replaceState()` directly for filter URL persistence.
- **Storing filter state in Vuex/Pinia:** The board has no global state management library and does not need one. Filter state is local to the KanbanBoard component and persisted in URL params. Display options persist in localStorage.
- **Using setTimeout for toast dismissal without cleanup:** If the component unmounts before the timeout fires, it causes a Vue warning. Use `onUnmounted()` to clear pending timeouts.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Drag-and-drop | Custom mouse/touch event handlers | SortableJS via vue-draggable-plus (already installed) | Touch support, cross-browser compat, animation, ghost rendering -- 100+ edge cases |
| Avatar color generation | Random colors | Deterministic hash from user ID | Random changes on re-render; hash ensures same user always gets same color |
| Date comparison logic | Custom date parsing | Native `Date` constructor + comparison | Task.dueDate is already ISO format from PHP; no timezone conversion needed for date-only fields |
| Focus trapping in panel | Custom Tab key handler | Computed list of focusable elements + wrap-around | Simpler than trying to handle every edge case; query `[tabindex], button, input, a` on open |

**Key insight:** Phase 19 is primarily a frontend component phase. The existing PHP REST layer and Vue infrastructure handle all data flow. The new components are UI interaction patterns -- slide-over, inline edit, context menu, filter -- that are well-solved problems with lightweight custom implementations. The discipline is keeping each component small and focused rather than importing heavy libraries.

## Common Pitfalls

### Pitfall 1: Click vs Double-Click Conflict on Task Cards

**What goes wrong:** Single click is supposed to open the detail panel (INTERACT-01), but double-click is supposed to activate inline title editing (INTERACT-02). Both fire on a double-click event (two click events + one dblclick event). The panel opens and immediately the title starts editing.

**Why it happens:** DOM fires both `click` and `dblclick` events. On double-click, two click events fire before the dblclick.

**How to avoid:** Use a click delay pattern: on single click, start a 200ms timer. If dblclick fires within that window, cancel the timer and activate editing. If timer completes without dblclick, open the panel. The double-click target is specifically the title element, while the click-to-open target is the entire card body. Alternatively: single-click on the title text opens the panel, double-click on the title text starts editing, and clicking elsewhere on the card does nothing (users use the existing status menu or context menu for other actions).

**Warning signs:** Panel opens when trying to edit title; editing activates when trying to view details.

### Pitfall 2: Context Menu Positioned Off-Screen

**What goes wrong:** Right-clicking a card near the right or bottom edge of the viewport positions the context menu partially or fully off-screen.

**Why it happens:** The menu position is set to `event.clientX, event.clientY` without checking whether the menu fits within the viewport.

**How to avoid:** After rendering the menu, check its `getBoundingClientRect()` against `window.innerWidth/innerHeight`. If it overflows right, shift left by the overflow amount. If it overflows bottom, shift up. Do this in a `nextTick()` callback after the menu becomes visible.

**Warning signs:** Menu items are clipped or invisible when right-clicking cards in the rightmost or bottommost column.

### Pitfall 3: URL Filter Params Lost on Drupal Navigation

**What goes wrong:** User sets filters, URL updates with query params. User clicks a Drupal admin menu link, navigates away, then clicks browser Back. The URL has the params but the Vue app re-initializes without reading them.

**Why it happens:** Drupal performs full page loads on navigation (not SPA). The Vue app re-mounts on each page load. If `onMounted()` does not read URL params, filter state is lost.

**How to avoid:** The useFilters composable MUST read `window.location.search` in `onMounted()` and initialize filter state from URL params. This is already shown in the Pattern 5 example above. Test by: set filters, copy URL, open in new tab -- filters should be applied.

**Warning signs:** Filters reset on page reload despite URL containing filter params.

### Pitfall 4: Toast Notifications Stacking Behind Drupal Admin Toolbar

**What goes wrong:** Toast notifications appear but are hidden behind the Drupal admin toolbar (z-index ~500 for toolbar).

**Why it happens:** Toast container is positioned with `position: fixed; top: 0` but Drupal's toolbar overlay has high z-index.

**How to avoid:** Set toast container z-index to 1100+ (above the context menu at 1000, above Drupal's toolbar). Account for toolbar offset by using `top: var(--drupal-displace-offset-top, 0px)` or checking for `#toolbar-bar` height.

**Warning signs:** Toasts are audible (screen reader) but not visible; or they appear briefly at incorrect position.

### Pitfall 5: Drag Ghost Class Not Applied to Scoped CSS

**What goes wrong:** `ghostClass`, `chosenClass`, and `dragClass` CSS classes from SortableJS are not styled because Vue's `<style scoped>` adds data attributes that SortableJS-injected classes do not have.

**Why it happens:** Vue's scoped CSS generates selectors like `.gapm-task-card--ghost[data-v-abc123]`. SortableJS adds the class directly to the DOM element, but the element may not have the matching data attribute when the scoped selector is evaluated.

**How to avoid:** Define drag state CSS in the **unscoped** `kanban.css` file (already loaded via libraries.yml), not in component `<style scoped>` blocks. The existing Phase 18 code already does this correctly for ghost and dragging classes in `kanban.css`. Continue this pattern for new drag state classes.

**Warning signs:** Drag states have no visual styling despite correct class names in DOM inspector.

### Pitfall 6: localStorage Key Collision Between Projects

**What goes wrong:** Display options (compact mode, show/hide properties) change on Project A's board, and then appear changed on Project B's board too.

**Why it happens:** localStorage key is generic like `gapm-display-options` instead of being scoped to the project.

**How to avoid:** Use project-scoped localStorage keys: `gapm-display-${projectId}`. If project-agnostic defaults are desired, provide a global key with per-project overrides.

**Warning signs:** Changing display options on one project's board affects other projects.

## Code Examples

### Extending serializeTask() for Phase 19 Data (PHP)

```php
// Source: existing KanbanController.php -- extend for Phase 19
protected function serializeTask($task) {
  $assignee = NULL;
  $assignee_id = $task->get('assignee')->value;
  if ($assignee_id) {
    $user = $this->entityTypeManager()->getStorage('user')->load($assignee_id);
    if ($user) {
      $picture_url = NULL;
      $picture = $user->get('user_picture')->entity;
      if ($picture) {
        /** @var \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator */
        $file_url_generator = \Drupal::service('file_url_generator');
        $picture_url = $file_url_generator->generateAbsoluteString($picture->getFileUri());
      }
      $assignee = [
        'id' => (int) $user->id(),
        'name' => $user->getDisplayName(),
        'pictureUrl' => $picture_url,
      ];
    }
  }

  return [
    'id' => (int) $task->id(),
    'title' => $task->getTitle(),
    'description' => $task->get('description')->value,
    'status' => $task->get('status')->value ?? 'todo',
    'priority' => $task->get('priority')->value ?? 'medium',
    'assignee' => $assignee,
    'dueDate' => $task->get('due_date')->value,
    'created' => $task->getCreatedTime(),
    'changed' => $task->getChangedTime(),
    'editUrl' => $task->toUrl('edit-form')->toString(),
  ];
}
```

Note: The `file_url_generator` service should be injected via create()/constructor DI in production. Haiku may use the static `\Drupal::service()` call (known limitation from Phase 18 evals -- Haiku consistently uses ControllerBase lazy methods).

### AssigneeAvatar Component (VISUAL-02)

```vue
<!-- Source: custom component based on standard avatar patterns -->
<template>
  <div class="gapm-avatar" :style="avatarStyle" :title="name">
    <img v-if="pictureUrl" :src="pictureUrl" :alt="name" class="gapm-avatar__img" />
    <span v-else class="gapm-avatar__initials">{{ initials }}</span>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  name: { type: String, required: true },
  pictureUrl: { type: String, default: null },
  userId: { type: Number, required: true },
});

const initials = computed(() => {
  const parts = props.name.split(' ').filter(Boolean);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  }
  return props.name.substring(0, 2).toUpperCase();
});

const colors = ['#1abc9c', '#2ecc71', '#3498db', '#9b59b6', '#e67e22',
  '#e74c3c', '#f39c12', '#27ae60', '#2980b9', '#8e44ad'];

const avatarStyle = computed(() => {
  if (props.pictureUrl) return {};
  const colorIndex = props.userId % colors.length;
  return { backgroundColor: colors[colorIndex], color: 'white' };
});
</script>
```

### Due Date Warning Logic (VISUAL-01)

```javascript
// Source: standard date comparison pattern for Kanban due date warnings
import { computed } from 'vue';

const dueDateClass = computed(() => {
  if (!props.task.dueDate) return '';

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const dueDate = new Date(props.task.dueDate);
  dueDate.setHours(0, 0, 0, 0);

  const diffDays = Math.floor((dueDate - today) / (1000 * 60 * 60 * 24));

  if (diffDays < 0) return 'gapm-task-card--overdue';
  if (diffDays === 0) return 'gapm-task-card--due-today';
  if (diffDays <= 3) return 'gapm-task-card--due-soon';
  return '';
});
```

### KanbanController drupalSettings Enhancement

```php
// Additional data needed for Phase 19 features
'drupalSettings' => [
  'groupAiPm' => [
    'kanban' => [
      // ... existing Phase 18 data ...
      'members' => $this->getProjectMembers($project), // for filter bar assignee dropdown
    ],
  ],
],
```

The `getProjectMembers()` method queries users who are assigned to tasks in this project, providing names and IDs for the filter dropdown. This avoids loading all users (which would be a performance/security concern).

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| contenteditable for inline edit | Input-swap pattern | Ongoing | Avoids HTML paste artifacts, simpler v-model binding |
| Global event bus for cross-component communication | Composable with shared refs | Vue 3.0 (2020) | Module-level `ref()` is naturally shared between components importing the same composable |
| Vuex/Pinia for all state | Composables for local state, Pinia only for complex global state | Vue 3.2+ | This app has no global state management need -- composables suffice |
| jQuery UI drag-and-drop | SortableJS with vue-draggable-plus | 2023+ | Touch support, better animation API, smaller bundle |
| `file_create_url()` for file URLs | `file_url_generator` service | Drupal 9.3+ | Old function deprecated; service is DI-compatible |

**Deprecated/outdated:**
- `file_create_url()`: Use `\Drupal::service('file_url_generator')->generateAbsoluteString()` instead
- vuedraggable@next: Unmaintained; replaced by vue-draggable-plus
- Options API in Vue 3: Still works but Composition API with `<script setup>` is the standard for new code

## Open Questions

1. **DELETE endpoint for context menu**
   - What we know: The context menu (INTERACT-04) includes a "Delete" action, but Phase 18 did not create a DELETE endpoint for tasks. The existing entity routes include `/admin/content/task/{task}/delete` (HTML confirmation form).
   - What's unclear: Should delete go through the REST API (new DELETE endpoint) or redirect to the entity delete confirmation form?
   - Recommendation: Add a DELETE endpoint (`/api/kanban/task/{task}`) to TaskApiController for in-board deletion. Show a confirmation dialog in Vue (not Drupal's entity delete form) before calling the API. This keeps the user on the board.

2. **Assignee Selector in Detail Panel**
   - What we know: The detail panel (INTERACT-01) should allow changing the assignee. The existing PATCH endpoint supports setting assignee by user ID.
   - What's unclear: How to populate the user list for the assignee selector. Loading all users is not appropriate.
   - Recommendation: Pass a `members` list in drupalSettings containing users who have been assigned to tasks in this project. For the detail panel, a simple `<select>` dropdown is sufficient (no autocomplete needed for small team context).

3. **Task Description Editing in Panel**
   - What we know: INTERACT-01 says "full task metadata" in the panel. The task entity has a `description` text_long field.
   - What's unclear: Should description be editable inline in the panel? REQUIREMENTS.md explicitly defers rich text editing to the entity form.
   - Recommendation: Show description as read-only plain text in the panel. Include an "Edit full page" link to the entity edit form for description changes. This matches the "Out of Scope" decision to avoid rich text editors in Vue.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | PHPUnit (Drupal Kernel + Functional tests) + eval pipeline (headless haiku) |
| Config file | phpunit.xml in Drupal root (provided by ddev template) |
| Quick run command | `ddev drush cr && ddev exec vendor/bin/phpunit -c web/core web/modules/custom/group_ai_pm/tests/` |
| Full suite command | Same as quick run + eval pipeline (static + runtime + browser assertions) |

### Phase Requirements -> Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| INTERACT-01 | Slide-over panel opens on card click with task data | browser eval | eval-browser checks for `.gapm-panel` element after click | -- Wave 0 |
| INTERACT-02 | Inline title editing saves on Enter, cancels on Escape | browser eval | eval-browser double-clicks title, types, presses Enter, verifies | -- Wave 0 |
| INTERACT-03 | Drag rollback shows toast notification | browser eval | eval-browser drags card, checks for `.gapm-toast` on error | -- Wave 0 |
| INTERACT-04 | Context menu appears on right-click with correct items | browser eval | eval-browser right-clicks card, checks for `.gapm-context-menu` | -- Wave 0 |
| INTERACT-05 | Filter narrows visible cards, URL updates | browser eval | eval-browser selects filter, checks card count and URL params | -- Wave 0 |
| INTERACT-06 | Drag animation shows card lift and settle | static eval | Check CSS for animation property, ghostClass, chosenClass in VueDraggable config | -- Wave 0 |
| INTERACT-07 | Ghost preview with reduced opacity | static eval | Check CSS for `.gapm-task-card--ghost` with opacity rule | -- Wave 0 |
| VISUAL-01 | Overdue tasks show red border | static eval | Check computed property comparing dueDate, CSS for `--overdue` class | -- Wave 0 |
| VISUAL-02 | Assignee avatars with initials fallback | static eval | Check AssigneeAvatar component exists with pictureUrl/initials logic, PHP serializes pictureUrl | -- Wave 0 |
| VISUAL-03 | Display options persist in localStorage | static eval | Check localStorage read/write in composable, project-scoped key | -- Wave 0 |

### Sampling Rate
- **Per task commit:** `cd /tmp/d10-phase19-{variant}/web/modules/custom/group_ai_pm/js && npx vite build 2>/dev/null` (verify Vue build succeeds)
- **Per wave merge:** Full eval pipeline (static + runtime assertions)
- **Phase gate:** Full eval suite + browser assertions green before `/gsd:verify-work`

### Wave 0 Gaps
- [ ] `eval/v4/phase-19-evals.json` -- static assertion definitions targeting skill-driven patterns
- [ ] `eval/v4/phase-19-runtime-assertions.json` -- drush-based functional checks
- [ ] Browser eval assertions for interactive features (panel, inline edit, context menu, filters)
- [ ] No new test framework infrastructure needed -- Phase 18 pipeline is reusable

## Sources

### Primary (HIGH confidence)
- Existing module source code at `modules/group_ai_pm/` -- all 58 files read and analyzed
- Phase 18 research at `.planning/phases/18-rest-api-vue-infrastructure-basic-board/18-RESEARCH.md` -- architecture decisions carry forward
- v4.0 research at `.planning/research/FEATURES.md` and `.planning/research/PITFALLS.md` -- interaction layer patterns
- [vue-draggable-plus API docs](https://vue-draggable-plus.pages.dev/en/api/) -- SortableJS configuration options, event callbacks
- [SortableJS GitHub](https://github.com/SortableJS/Sortable) -- ghostClass, chosenClass, dragClass, animation options

### Secondary (MEDIUM confidence)
- [Vue.js Composables Documentation](https://vuejs.org/guide/reusability/composables.html) -- composable patterns for useToast, useFilters
- [Drupal user picture programmatic access](https://www.zedangle.com/blog/programmatically-get-user-pictureimage-drupal-8-or-9) -- file_url_generator pattern for user_picture field
- [Custom context menu in Vue 3](https://medium.com/@sj.anyway/custom-right-click-context-menu-in-vue3-b323a3913684) -- @contextmenu.prevent pattern, position calculation
- [URL query parameters with Vue 3](https://serversideup.net/blog/url-query-parameters-with-javascript-vue-2-and-vue-3/) -- history.replaceState for filter URL sync
- [Radix Vue context menu](https://www.radix-vue.com/components/context-menu) -- WAI-ARIA menu pattern reference

### Tertiary (LOW confidence)
- Vue slide-over patterns from Nuxt UI reference (adapted for embedded island, not SPA context)
- Toast notification library comparison (verified we can build lighter custom version)

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH -- no new dependencies, building on verified Phase 18 foundation
- Architecture: HIGH -- all patterns are standard Vue 3 composition API with well-documented SortableJS config
- Pitfalls: HIGH -- pitfalls derived from empirical Phase 18 eval experience and known DOM event interaction patterns
- PHP changes: HIGH -- minor serialization additions to existing controller method, well-documented Drupal APIs

**Research date:** 2026-03-08
**Valid until:** 2026-04-08 (30 days -- stable technologies, no fast-moving APIs)

# Vue.js in Drupal Modules

This reference covers embedding Vue 3 applications within Drupal's admin chrome as interactive "islands" — not decoupled SPAs. The Vue app mounts inside a Drupal page, uses `drupalSettings` for initial data, and communicates with custom REST controllers.

## Architecture: Embedded Vue Island

The Vue app is a single-page component tree that mounts into a DOM element rendered by a Twig template. Drupal handles routing, authentication, permissions, and page chrome. Vue handles the interactive UI within.

```
Drupal page load
  → Controller returns render array with #theme + #attached
  → Twig template renders <div id="app-root">
  → Library loads Vue + your bundle
  → Drupal.behaviors mounts Vue into #app-root
  → Vue fetches/mutates data via custom REST endpoints
```

## Drupal.behaviors Bridge with once()

Mount the Vue app inside a Drupal behavior using `once()` to prevent double-mounting on Ajax page updates.

```javascript
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.myModuleApp = {
    attach: function (context, settings) {
      once('myModuleApp', '#app-root', context).forEach(function (element) {
        var app = Vue.createApp(MyRootComponent, {
          settings: settings.my_module,
        });
        app.mount(element);
      });
    }
  };
})(Drupal, once);
```

> WRONG: Mounting the Vue app with `document.addEventListener('DOMContentLoaded', ...)` or at module scope. This fires once and misses Ajax-loaded content, and can mount before `drupalSettings` is available.
> RIGHT: Always mount inside `Drupal.behaviors` with `once()`. Pass `drupalSettings` data as props to the root component.

## Vite IIFE Build Configuration

Drupal modules use IIFE format (not ES modules) so the bundle works with Drupal's library system. Vue is loaded as a separate global to keep it cacheable.

```javascript
// vite.config.js
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [vue()],
  build: {
    outDir: 'dist',
    lib: {
      entry: 'src/main.js',
      name: 'MyModuleApp',
      formats: ['iife'],
      fileName: () => 'app.js',
    },
    rollupOptions: {
      external: ['vue'],
      output: {
        globals: { vue: 'Vue' },
        assetFileNames: 'style.css',
      },
    },
  },
});
```

The entry point exports the root component for the behavior to mount:

```javascript
// src/main.js
import { createApp } from 'vue';
import App from './components/App.vue';

window.MyModuleApp = {
  createApp(element, settings) {
    const app = createApp(App, { settings });
    app.mount(element);
    return app;
  },
};
```

## Library Configuration for Vue + IIFE Bundle

Vue must load BEFORE the consuming IIFE bundle. Use library dependencies and avoid `defer`.

```yaml
# my_module.libraries.yml
vue:
  js:
    js/vendor/vue.global.prod.js: { minified: true }
  header: true
  weight: -20

app:
  js:
    js/dist/app.js: { minified: true }
  css:
    component:
      js/dist/style.css: {}
  dependencies:
    - my_module/vue
    - core/drupal
    - core/once
    - core/drupalSettings
```

> WRONG: Adding `{ attributes: { defer: true } }` to the Vue library. Deferred scripts execute AFTER non-deferred footer scripts, so the IIFE bundle runs before `Vue` is defined globally — causing "Vue is not defined" errors.
> RIGHT: Omit `defer`. Use `header: true` and `weight: -20` on the Vue library. Drupal's dependency system handles load ordering.

## drupalSettings Data Contract

Pass initial data from PHP to Vue via `drupalSettings`. The controller attaches data to the render array, and the behavior passes it as props.

```php
// In the controller:
$build['#attached']['drupalSettings']['my_module'] = [
  'projectId' => $project->id(),
  'tasks' => $grouped_tasks,
  'permissions' => [
    'createTask' => $user->hasPermission('create task entities'),
  ],
];
```

```javascript
// In the behavior:
once('myModuleApp', '#app-root', context).forEach(function (element) {
  var app = Vue.createApp(RootComponent, {
    settings: settings.my_module,
  });
  app.mount(element);
});
```

The root component receives `settings` as a prop and distributes data to child components via props or composables.

## vue-draggable-plus (v0.1.x) Patterns

vue-draggable-plus wraps SortableJS for Vue 3. Version 0.1.x has a specific API that differs from both newer versions and the older `vuedraggable` library.

### Correct template pattern (v0.1.x)

```vue
<VueDraggable
  v-model="localItems"
  tag="div"
  class="item-list"
  group="shared-group"
  @add="handleDragAdd"
  @start="emit('drag-start')"
  @end="emit('drag-end')"
  ghost-class="item--ghost"
  :animation="200"
  item-key="id"
>
  <ItemCard
    v-for="item in localItems"
    :key="`item-${item.id}`"
    :item="item"
  />
</VueDraggable>
```

> WRONG: Using `<template #item="{ element }">` slot syntax. This slot does NOT exist in vue-draggable-plus v0.1.x. It is a vuedraggable (different library) pattern. Using it causes "[vue-draggable-plus]: Root element not found" and "Sortable: `el` must be an HTMLElement" errors.
> RIGHT: Use `v-model` with `v-for` in the default slot. Required props: `tag` (wrapper element), `item-key` (unique ID field name).

### Event model: SortableJS events, not vuedraggable events

vue-draggable-plus v0.1.x emits raw SortableJS events. It does NOT emit the `{added, removed, moved}` object that the older `vuedraggable` library uses on its `@change` event.

| Event | Fires on | When |
|-------|----------|------|
| `@add` | Destination list | Item dropped in from another list |
| `@remove` | Source list | Item removed to another list |
| `@update` | Same list | Item reordered within the list |
| `@sort` | Both lists | Any sorting operation |
| `@end` | Source list | Drag operation completed |
| `@change` | During drag | SortableJS internal change (index tracking) |

> WRONG: Using `@change` and checking `e.added` or `e.moved`. The `@change` event in vue-draggable-plus is a raw SortableJS event with `{newIndex, newDraggableIndex}` — it has NO `added`/`moved`/`removed` properties. Those are a `vuedraggable` (different library) convention.
> RIGHT: Use `@add` to detect cross-list drops. The event is a SortableJS event with `{item, from, to, oldIndex, newIndex}`. Read task identity from `data-*` attributes on the DOM element: `evt.item.dataset.taskId`.

### Computed model with no-op setter

When the parent manages state (e.g., via a composable), use a computed with a no-op setter. vue-draggable-plus internally manipulates the array for DOM sync, but the actual state update happens through your event handlers.

```javascript
const localItems = computed({
  get: () => props.items,
  set: () => { /* Parent handles state via event handlers */ },
});
```

### Cross-list drag handler pattern

```javascript
const handleDragAdd = (evt) => {
  // @add fires on the DESTINATION list.
  // Read identity from data attribute on the dragged DOM element.
  const itemId = Number(evt.item.dataset.itemId);
  if (itemId) {
    emit('item-status-change', {
      itemId,
      newStatus: props.status, // This list's status = the new status
    });
  }
};
```

## CSRF for REST Endpoints

Drupal requires CSRF tokens for mutating requests from JavaScript. Fetch a session token and send it as a header.

```javascript
async function getCsrfToken() {
  const response = await fetch('/session/token');
  return response.text();
}

async function patchEndpoint(url, data) {
  const token = await getCsrfToken();
  const response = await fetch(url, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': token,
    },
    body: JSON.stringify(data),
  });
  return response.json();
}
```

The route must require `_csrf_request_header_token: 'TRUE'` (not `_csrf_token`, which is for link-based CSRF protection).

## Optimistic Updates with Rollback

For responsive UIs, update local state immediately, then sync with the server. Roll back on error.

```javascript
const updateItemStatus = async (itemId, newStatus) => {
  // Find item in current state.
  let itemData = null;
  let oldColumn = null;
  for (const [col, items] of Object.entries(columns.value)) {
    const found = items.find(i => i.id === itemId);
    if (found) { itemData = found; oldColumn = col; break; }
  }

  // Optimistic: move immediately.
  columns.value[oldColumn] = columns.value[oldColumn].filter(i => i.id !== itemId);
  itemData.status = newStatus;
  columns.value[newStatus].push(itemData);

  try {
    await patchEndpoint(`/api/item/${itemId}/status`, { status: newStatus });
  } catch (err) {
    // Rollback on failure.
    columns.value[newStatus] = columns.value[newStatus].filter(i => i.id !== itemId);
    columns.value[oldColumn].push(itemData);
    itemData.status = oldColumn;
  }
};
```

## Separate Endpoints for Different Concerns

Keep dedicated endpoints for status changes vs. general field updates. This avoids accidentally ignoring fields when the backend only handles a subset.

```
PATCH /api/item/{id}/status   → { status }        (dedicated status handler)
PATCH /api/item/{id}          → { title, priority, assignee }  (general fields)
POST  /api/project/{id}/item  → { title, status }  (creation)
DELETE /api/item/{id}          → (deletion)
```

If a UI (like a detail panel) can change status AND other fields in a single save, the frontend must split the request:

```javascript
const handleSave = async (itemId, updates) => {
  if (updates.status && updates.status !== currentStatus) {
    await updateStatus(itemId, updates.status);
  }
  const { status, ...fieldUpdates } = updates;
  if (Object.keys(fieldUpdates).length > 0) {
    await updateFields(itemId, fieldUpdates);
  }
};
```

## Common Mistakes Reference

| Mistake | Symptom | Fix |
|---------|---------|-----|
| `<template #item>` in vue-draggable-plus v0.1.x | "Root element not found", "el must be HTMLElement" | Use `v-for` in default slot with `tag` and `item-key` props |
| `@change` checking `e.added`/`e.moved` | Handler never fires (properties don't exist) | Use `@add` event with SortableJS event format |
| `defer: true` on Vue global library | "Vue is not defined" in IIFE bundle | Remove defer, use `header: true` + `weight: -20` |
| `document.ready` instead of `Drupal.behaviors` | Vue mounts once, breaks on Ajax | Use `Drupal.behaviors` + `once()` |
| Status in general PATCH endpoint | Status silently ignored on save | Use dedicated status endpoint, or handle `status` field in backend |
| Deep object prop changes not triggering watcher | Panel shows stale data after external mutations | Use `{ deep: true }` on watchers for object props mutated in-place |

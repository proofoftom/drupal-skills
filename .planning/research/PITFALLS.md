# Domain Pitfalls: v4.0 Vue.js Kanban UX Overhaul

**Domain:** Adding Vue.js, AJAX, and rich frontend UX to an existing Drupal 10 module (group_ai_pm)
**Researched:** 2026-03-08
**Confidence:** HIGH for Drupal asset pipeline and CSRF patterns (official docs verified); MEDIUM for Vue.js + Drupal integration (community patterns, no single authoritative source); MEDIUM for Haiku code generation limitations (empirical from v3.0 evals)

**Scope:** Pitfalls specific to ADDING Vue.js Kanban boards, REST endpoints, AJAX interactions, and keyboard-driven UX to the existing group_ai_pm module. v3.0 pitfalls (Group API, AI integration, plugin packaging) are archived in git history.

---

## Critical Pitfalls

Mistakes that cause rewrites, broken UX, or security vulnerabilities.

### Pitfall 1: CSRF Token Not Fetched Before First Mutation -- Silent 403 on Drag-and-Drop

**What goes wrong:**
The Vue.js Kanban board loads, renders tasks correctly via GET, and the user drags a card to a new column. The optimistic UI moves the card. Then the PATCH request to update the task status returns a 403. The card snaps back. This happens on every single mutation because the JavaScript never fetched a CSRF token from `/session/token` before issuing write requests.

**Why it happens:**
Drupal requires an `X-CSRF-Token` header on all POST/PATCH/DELETE requests when using session (cookie) authentication. The token must be retrieved via a separate GET to `/session/token` before any write operation. This is not optional -- missing it produces a 403 with an empty or unhelpful body. The token is per-session, not per-request, so it only needs to be fetched once -- but it MUST be fetched before the first mutation.

Many Vue/React tutorials show REST calls without CSRF because they assume Bearer token auth (which bypasses CSRF checks). Drupal admin pages use session cookies, so CSRF protection is always active for authenticated admin users.

**Consequences:**
- Every drag-and-drop, inline edit, quick-create, and status toggle fails silently or with a confusing 403
- Optimistic UI reverts look like bugs to the user
- If error handling is poor, the UI shows the card in the new column but the server state is unchanged -- data inconsistency

**Prevention:**
- Fetch CSRF token in the Vue app's `onMounted()` lifecycle hook, BEFORE any user interaction is possible
- Store the token in a module-level variable or Pinia store, include it in every mutation request
- Pass the `/session/token` URL via `drupalSettings` (do not hardcode it -- base path may vary)
- Example pattern for the API service layer:
  ```javascript
  let csrfToken = null;
  async function ensureCsrfToken() {
    if (!csrfToken) {
      const response = await fetch(drupalSettings.path.baseUrl + 'session/token');
      csrfToken = await response.text();
    }
    return csrfToken;
  }
  ```
- Add `_csrf_request_header_token: 'TRUE'` to custom REST route requirements (not `_csrf_token: 'TRUE'` -- that is for query-string tokens used in link URLs, not header-based tokens)
- Verify: `_csrf_request_header_token` always succeeds for anonymous users, so ALWAYS pair it with a permission requirement

**Detection:**
- 403 responses on PATCH/POST/DELETE requests in browser DevTools Network tab
- Drupal watchdog showing "X-CSRF-Token request header is missing" or "is invalid"
- Optimistic UI updates that consistently revert

**Phase to address:** API Layer (Phase 1). Must be correct before any mutation endpoint is testable.

---

### Pitfall 2: Vue App Mounts Multiple Times Due to Drupal Behaviors / BigPipe

**What goes wrong:**
The Kanban board Vue app initializes correctly on first page load. Then a Drupal AJAX operation on the same page (dialog close, Views exposed filter update, admin toolbar refresh) triggers `Drupal.attachBehaviors()` again. If the Vue app initialization code is inside a Drupal behavior without a `once()` guard, the app mounts a second time on the same DOM element. This creates duplicate event listeners, double rendering, and eventually a memory leak that causes the tab to slow down and crash.

**Why it happens:**
Drupal's behavior system calls `Drupal.attachBehaviors(context, settings)` on initial page load AND after every AJAX response. BigPipe also triggers it when replacing placeholders. In Drupal 10.1+, behaviors can fire twice on page load (once when the script loads, once from `drupal.init.js`). Without the `once()` utility from `@drupal/once`, any initialization code will re-execute.

Vue 3's `createApp()` does not protect against double mounting. Calling `createApp().mount('#kanban-board')` twice on the same element produces unpredictable behavior.

**Consequences:**
- Duplicate Vue app instances competing for the same DOM
- Event listeners stacking (keyboard shortcuts fire twice per keystroke)
- Memory usage grows with each AJAX operation until the page becomes unresponsive
- Drag-and-drop behaves erratically because two app instances handle the same events

**Prevention:**
- ALWAYS use `once()` to guard Vue app initialization inside Drupal behaviors:
  ```javascript
  Drupal.behaviors.kanbanBoard = {
    attach: function (context, settings) {
      once('kanban-board', '#kanban-board-mount', context).forEach(function (element) {
        const app = createApp(KanbanBoard, {
          projectId: settings.groupAiPm.projectId,
          csrfTokenUrl: settings.path.baseUrl + 'session/token',
        });
        app.mount(element);
        // Store app reference for detach cleanup
        element._vueApp = app;
      });
    },
    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        document.querySelectorAll('[data-once="kanban-board"]').forEach(function (element) {
          if (element._vueApp) {
            element._vueApp.unmount();
            element._vueApp = null;
          }
        });
      }
    }
  };
  ```
- Declare `core/once` as a dependency in `libraries.yml` (it is a separate library, not bundled with `core/drupal`)
- Implement the `detach` handler to unmount Vue when Drupal removes the DOM (page navigation, AJAX replacement)
- Store the Vue app instance on the DOM element so `detach` can find and unmount it

**Detection:**
- Console warnings about mounting on a non-empty DOM node
- Keyboard shortcuts executing twice per keystroke
- Increasing memory usage in browser DevTools Performance tab over time
- Multiple `#kanban-board-mount` elements or duplicate Vue devtools instances

**Phase to address:** Vue Infrastructure (Phase 1). The mounting pattern must be established as part of the initial Vue integration.

---

### Pitfall 3: Custom Controller Returns JsonResponse Without Proper Content Negotiation -- Drupal Error Handler Serves HTML on Errors

**What goes wrong:**
The custom REST controller returns `JsonResponse` for task data. Normal operation works. But when a validation error or access denied occurs, Drupal's default exception handler returns an HTML error page instead of a JSON error response. The Vue app receives HTML where it expects JSON, fails to parse it, and shows a generic "something went wrong" error instead of actionable information.

**Why it happens:**
Drupal dispatches exceptions to different exception subscribers based on the request's `Accept` header and the `?_format` query parameter. Without `?_format=json`, Drupal defaults to HTML error responses. Custom controllers using `JsonResponse` directly (not `ResourceResponse` from the REST module) bypass the content negotiation system. The controller works for happy-path responses but Drupal's error infrastructure does not know the client expects JSON.

**Consequences:**
- Validation errors (missing required field, invalid status value) return HTML instead of JSON
- 403 responses return Drupal's full HTML access denied page
- 404 responses for deleted tasks return HTML
- Vue app's error handling cannot parse the response, so users see unhelpful error messages
- Debugging is difficult because the JSON error body is absent

**Prevention:**
- ALWAYS append `?_format=json` to the route path or require clients to include it:
  ```yaml
  group_ai_pm.api.tasks:
    path: '/api/group-ai-pm/project/{project}/tasks'
    defaults:
      _controller: '\Drupal\group_ai_pm\Controller\TaskApiController::list'
    requirements:
      _permission: 'access group_ai_pm dashboard'
      _format: json
      _csrf_request_header_token: 'TRUE'
  ```
- When `_format: json` is a route requirement, Drupal's JSON exception subscriber handles errors and returns JSON error bodies
- NEVER rely on the Accept header alone -- Drupal's content negotiation requires `_format` in the route definition or query string
- In the Vue API service, always include `?_format=json` in request URLs:
  ```javascript
  const response = await fetch(
    `${baseUrl}api/group-ai-pm/project/${projectId}/tasks?_format=json`
  );
  ```
- Use `try/catch` with response type checking in the API layer to handle the edge case where HTML is returned despite configuration

**Detection:**
- Error responses in browser DevTools Network tab showing `text/html` content type
- Vue app's JSON parse throwing `SyntaxError: Unexpected token '<'`
- Drupal watchdog not logging JSON serialization for error responses

**Phase to address:** API Layer (Phase 1). Route definitions must include `_format: json` from the start.

---

### Pitfall 4: Vue Production Bundle Not Committed -- Module Requires npm Build Step

**What goes wrong:**
The module ships with `package.json`, `vite.config.js`, and Vue source files in `js/src/`. But the compiled production bundle (`js/dist/kanban.js`) is in `.gitignore`. End users install the module via `composer require`, enable it, and get a blank page where the Kanban board should be because the compiled JavaScript does not exist. Users must run `npm install && npm run build` -- but most Drupal admins do not have Node.js in production and should not need it.

**Why it happens:**
Modern JavaScript development workflows use `.gitignore` to exclude `node_modules/` and build artifacts from version control. This is correct for application development but wrong for Drupal contrib modules. Drupal modules must be installable via Composer alone -- no secondary build steps. The pattern used by Drupal core itself is: source files + build tooling exist, and compiled assets are committed alongside them.

**Consequences:**
- Module fails silently (mount point div renders, but no JavaScript executes)
- Users file bug reports about "blank Kanban board"
- Users with Node.js may build locally, creating version drift between their build and the intended bundle
- CI/CD pipelines that do not include Node.js will not produce working deployments

**Prevention:**
- Commit the compiled production bundle to the repository alongside source files
- Module directory structure:
  ```
  modules/group_ai_pm/
    js/
      src/                    # Vue source (for development)
        App.vue
        components/
        stores/
      dist/                   # Compiled bundle (committed)
        kanban.min.js
        kanban.min.css
      package.json            # For developers only
      vite.config.js          # Build configuration
    css/                      # Drupal CSS (non-Vue styles)
  ```
- Reference only `js/dist/kanban.min.js` in `libraries.yml`, never source files
- Add `js/node_modules/` to `.gitignore` but NOT `js/dist/`
- Include a `js/BUILD.md` documenting how developers rebuild the bundle (for contributors, not end users)
- Use Vite in library mode to produce a single UMD bundle that does not require ES module support:
  ```javascript
  // vite.config.js
  export default {
    build: {
      lib: {
        entry: 'src/main.js',
        name: 'GroupAiPmKanban',
        fileName: 'kanban',
        formats: ['umd']
      },
      rollupOptions: {
        // Do NOT externalize Vue -- bundle it in the UMD output
        // Drupal does not provide Vue as a global
      },
      outDir: 'dist'
    }
  };
  ```

**Detection:**
- Blank mount point div with no JavaScript errors (script tag not even present)
- `libraries.yml` referencing files that do not exist in the installed module
- Users reporting the module works in development but not production

**Phase to address:** Vue Infrastructure (Phase 1). Build tooling and committed bundle pattern must be established before any Vue components are built.

---

### Pitfall 5: Using `_csrf_token` (Query String) Instead of `_csrf_request_header_token` (Header) for REST Routes

**What goes wrong:**
The REST route is defined with `_csrf_token: 'TRUE'` (the query-string variant). Drupal automatically appends a CSRF token to URLs generated via `Url::fromRoute()` in PHP. But the Vue app constructs its own URLs in JavaScript and never includes the query-string token. Every PATCH/POST/DELETE request returns 403. The developer adds the `X-CSRF-Token` header (fetched from `/session/token`), but the route validator ignores the header because it is checking for a query string parameter.

**Why it happens:**
Drupal has TWO different CSRF token mechanisms:
1. `_csrf_token: 'TRUE'` -- validates a `token` query parameter in the URL. Used for action links (like "Complete Project" in the existing module). Token is appended automatically by Drupal's URL generation.
2. `_csrf_request_header_token: 'TRUE'` -- validates the `X-CSRF-Token` HTTP header. Used for JavaScript-driven REST calls where URLs are constructed client-side.

These are different route access checkers with different token sources. Using the wrong one for your use case means CSRF protection either blocks legitimate requests or provides no protection.

The existing `group_ai_pm.project.complete` route correctly uses `_csrf_token: 'TRUE'` because it is a link-based action. New REST API routes for Vue.js MUST use `_csrf_request_header_token: 'TRUE'`.

**Consequences:**
- All JavaScript-initiated mutation requests return 403
- Developers add the X-CSRF-Token header but it is still rejected because the route checker is looking for a query parameter
- Falling back to no CSRF protection (`_csrf_token` removed) creates a real CSRF vulnerability

**Prevention:**
- Use `_csrf_request_header_token: 'TRUE'` for ALL routes called by JavaScript fetch/XHR
- Use `_csrf_token: 'TRUE'` ONLY for server-rendered link-based actions (form actions, action links)
- ALWAYS pair `_csrf_request_header_token` with a permission requirement (it auto-passes for anonymous)
- Document the distinction in code comments so future contributors do not mix them up

**Detection:**
- 403 on JavaScript requests despite including X-CSRF-Token header
- Token present in URL query string on PHP-rendered links (correct for `_csrf_token`)
- No token required for GET requests (both variants skip safe methods)

**Phase to address:** API Layer (Phase 1). Route requirement must be correct from the first endpoint definition.

---

### Pitfall 6: Vue Bundle Size Bloats Admin Page Load Time

**What goes wrong:**
The Vue.js Kanban app, including Vue 3 runtime, vue-dnd-kit, and application code, produces a 200-400KB JavaScript bundle. This loads on every admin page where the Kanban board library is attached. Drupal admin pages already load jQuery, Drupal core JS, toolbar JS, and the admin theme's CSS/JS. The additional Vue bundle pushes total page weight over 1MB, causing noticeable load delays on slower connections and in regions with high latency to the server.

**Why it happens:**
Vue 3 production runtime is ~50KB gzipped. vue-dnd-kit adds ~15-30KB. Application code, CSS, and any utility libraries (date formatting, fuzzy search for command palette) add more. Unlike a SPA where this is loaded once, Drupal admin pages are full page loads -- every navigation to the board page re-downloads the bundle unless aggressively cached. If the library is attached globally or to all admin pages (instead of just the board page), it loads unnecessarily everywhere.

**Consequences:**
- Board page takes 2-4 seconds to become interactive on first visit
- Admin users on slow connections experience degraded UX across the admin (if loaded globally)
- Google Lighthouse admin audits show poor performance scores
- Perception of "heavy module" reduces adoption

**Prevention:**
- Attach the Vue library ONLY to the board route controller's render array, never globally:
  ```php
  public function board(ProjectInterface $project): array {
    return [
      '#theme' => 'kanban_board',
      '#project' => $project,
      '#attached' => [
        'library' => ['group_ai_pm/kanban_board'],
        'drupalSettings' => [
          'groupAiPm' => [
            'projectId' => $project->id(),
          ],
        ],
      ],
    ];
  }
  ```
- Use Vite's tree-shaking: import only what is used from vue-dnd-kit, not the entire library
- Externalize Vue 3 ONLY if a shared Vue library module is installed (otherwise bundle it -- double-loading is worse than a larger bundle)
- Enable Drupal's JS aggregation (`/admin/config/development/performance`) for production
- Consider code splitting: load the command palette and detail panel lazily (dynamic import) since they are not needed on initial render
- Target budget: <100KB gzipped for the full Kanban bundle (Vue runtime + DnD + app code)

**Detection:**
- Chrome DevTools Network tab: filter by JS, sort by size, look for the Kanban bundle
- Lighthouse Performance audit on the board page
- Vue library appearing in `drupalSettings.ajaxPageState.libraries` on non-board pages (means it is loading too broadly)

**Phase to address:** Vue Infrastructure (Phase 1) for library scoping; Polish (Phase 3) for optimization.

---

## Moderate Pitfalls

### Pitfall 7: drupalSettings Data Not Available When Vue App Initializes

**What goes wrong:**
The Vue app reads `drupalSettings.groupAiPm.projectId` during initialization, but the value is `undefined`. The app sends API requests without a project ID, gets 404 or returns all tasks across all projects.

**Why it happens:**
In Drupal, `drupalSettings` is populated from render array `#attached` settings and injected into the page as a `<script>` tag. If the Vue library loads BEFORE the settings script (wrong library weight or async loading), `drupalSettings` is not yet defined. Additionally, if the controller forgets to attach settings to the render array, they simply are not present -- no error, just missing data.

This is especially tricky with Drupal's library dependency system. The Vue app library must declare a dependency on `core/drupalSettings`:
```yaml
kanban_board:
  js:
    js/dist/kanban.min.js: { minified: true }
  dependencies:
    - core/drupalSettings
    - core/once
```

Without the `core/drupalSettings` dependency, Drupal may load the kanban JS before the settings script.

**Prevention:**
- Always declare `core/drupalSettings` as a library dependency
- Always attach settings in the controller's render array via `#attached.drupalSettings`
- In JavaScript, validate settings exist before using them:
  ```javascript
  const projectId = drupalSettings?.groupAiPm?.projectId;
  if (!projectId) {
    console.error('GroupAiPm: Missing project ID in drupalSettings');
    return;
  }
  ```
- Pass ALL configuration through drupalSettings: API base URL, project ID, user permissions, CSRF token URL -- never hardcode paths

**Phase to address:** Vue Infrastructure (Phase 1).

---

### Pitfall 8: Local Task Tab Route Does Not Match Entity Base Route -- Tab Disappears

**What goes wrong:**
A "Board" tab is added to the project entity page (alongside View/Edit/Delete), but it does not appear. The route exists and the page loads at the direct URL, but the tab is missing from the project entity page.

**Why it happens:**
Drupal local tasks require a `base_route` property that matches the base route of the tab group. For entity pages, the base route is typically `entity.{entity_type}.canonical`. The "Board" tab must declare:
```yaml
group_ai_pm.project.board:
  route_name: group_ai_pm.project.board
  title: 'Board'
  base_route: entity.project.canonical
  weight: 5
```

Common mistakes:
1. Setting `base_route` to the board route itself (creates a standalone tab group, not part of entity tabs)
2. Using a different route pattern that does not share the same route parameters (the `{project}` parameter must match the entity route's `{project}` parameter exactly)
3. Defining the board route with a path like `/admin/content/project-board/{project}` that does not share the entity route prefix `/admin/content/project/{project}/board` -- the parameter name must match

**Prevention:**
- Board route path MUST follow the entity canonical path pattern:
  ```yaml
  # In routing.yml
  group_ai_pm.project.board:
    path: '/admin/content/project/{project}/board'
    defaults:
      _controller: '\Drupal\group_ai_pm\Controller\BoardController::board'
      _title: 'Board'
    requirements:
      _permission: 'access group_ai_pm dashboard'
    options:
      _admin_route: TRUE
      parameters:
        project:
          type: entity:project
  ```
- The `{project}` parameter name must match what the entity routing uses
- Add `parameters.project.type: entity:project` so Drupal upcasts the parameter to a Project entity
- Declare `_admin_route: TRUE` so the route uses the admin theme (where Claro styles apply)

**Detection:**
- Board page loads at direct URL but tab does not appear on entity page
- Other entity tabs (View/Edit/Delete) appear normally
- `drush router:debug group_ai_pm` shows the route exists but with wrong parameter names

**Phase to address:** Board Route (Phase 1, alongside API layer).

---

### Pitfall 9: Optimistic UI Without Proper Rollback Creates Ghost State

**What goes wrong:**
User drags a task card from "To Do" to "In Progress." The UI updates immediately (optimistic). The server request fails (network error, validation error, concurrent edit conflict). The card stays in "In Progress" in the UI but remains "To Do" in the database. Subsequent page loads show the card back in "To Do," confusing the user about whether their action was saved.

**Why it happens:**
Optimistic updates require three components working together: (1) immediate UI update, (2) server sync, (3) rollback on failure. Most implementations nail the first two but fail on the third. The rollback must:
- Store the previous state before the optimistic update
- Detect failure (network error, non-2xx response, timeout)
- Restore the previous state in the UI
- Show a user-visible error message

Without all three, the UI and server diverge silently.

**Prevention:**
- Implement a proper optimistic update pattern in the Pinia store:
  ```javascript
  async function moveTask(taskId, newStatus) {
    const previousStatus = tasks.value.find(t => t.id === taskId).status;
    // Optimistic update
    tasks.value.find(t => t.id === taskId).status = newStatus;
    try {
      await apiService.patchTask(taskId, { status: newStatus });
    } catch (error) {
      // Rollback
      tasks.value.find(t => t.id === taskId).status = previousStatus;
      showErrorMessage('Failed to update task status. Please try again.');
    }
  }
  ```
- Set a reasonable timeout (5 seconds) on mutation requests -- do not let them hang indefinitely
- On rollback, animate the card moving back to its original column (visual feedback that the action failed)
- Consider a brief "saving..." indicator on the card during the server sync window
- After rollback, log the error to Drupal watchdog via a lightweight error endpoint (helps with debugging)

**Detection:**
- Drag a card, then immediately reload the page -- if the card is in a different column, rollback failed
- Disconnect from the network, drag a card -- if no error appears and the card stays, rollback is missing
- Check browser DevTools Console for unhandled promise rejections on PATCH failures

**Phase to address:** Interactions (Phase 2). Basic drag-and-drop in Phase 1 can use non-optimistic updates; optimistic pattern in Phase 2.

---

### Pitfall 10: Claro Admin Theme CSS Conflicts with Vue Component Styles

**What goes wrong:**
Vue component styles (card layouts, column widths, button styles, typography) render correctly in a standalone development environment but break in Drupal. Cards have wrong padding, buttons inherit Claro's button styles, dropdowns use Claro's select styling, and the overall layout looks off.

**Why it happens:**
Drupal's Claro admin theme applies global CSS rules to common elements: `button`, `select`, `input`, `table`, `a`, `.messages`, etc. These are not namespaced -- they apply to ALL elements under the admin theme. Vue components that use these base HTML elements inherit Claro's styles by default. This causes:
- Buttons getting Claro's blue/white button styling instead of the Kanban design
- Select dropdowns getting Claro's styled select appearance
- Links getting Claro's link colors
- Tables inside task details getting Claro's table borders and padding

**Prevention:**
- Namespace all Kanban CSS under a unique wrapper class:
  ```css
  .kanban-board { /* all styles scoped here */ }
  .kanban-board .task-card { /* specific to kanban */ }
  ```
- Use Vue's `<style scoped>` in single-file components -- this adds data attributes for CSS scoping
- Override specific Claro styles that leak into the Kanban board:
  ```css
  .kanban-board button {
    all: unset; /* Reset Claro button styles */
    /* Then apply kanban-specific styles */
  }
  ```
- Test ALL components inside the Claro admin theme during development, never in a standalone HTML file
- Declare the library CSS with `theme` category (highest weight, loads last, overrides other CSS):
  ```yaml
  kanban_board:
    css:
      theme:
        css/kanban-board.css: {}
  ```
- Do NOT use `!important` to override Claro -- it creates a specificity war that is unmaintainable

**Detection:**
- Visual comparison: component in Storybook/standalone vs inside Drupal admin
- Claro CSS rules appearing in DevTools Element Inspector on Kanban elements
- Buttons, inputs, and links looking different from the intended design

**Phase to address:** Vue Infrastructure (Phase 1, CSS strategy) and Board UI (Phase 1, card/column design).

---

### Pitfall 11: Haiku Code Gen Builds REST Routes But Does Not Wire drupalSettings or CSRF Token Passing

**What goes wrong:**
In the eval pipeline, Haiku generates a controller that returns `JsonResponse`, defines the route in `routing.yml`, and creates a Vue component that calls the endpoint. But the controller does not attach `drupalSettings` with the project ID. The Vue component hardcodes the project ID or omits it entirely. The CSRF token is never fetched -- the Vue app makes mutations without it. The board renders (GET works without CSRF) but every interaction fails.

**Why it happens:**
This is the Haiku "declaration-usage gap" pattern documented in v3.0 eval Phase 16 results. Haiku reliably creates the infrastructure pieces (route, controller, Vue component) but fails to wire them together with data flow. Specifically:
- Creates `libraries.yml` entry but does not add `core/drupalSettings` dependency
- Creates controller but does not attach `drupalSettings` to the render array
- Creates Vue component but does not read from `drupalSettings`
- Implements fetch/axios calls but does not include CSRF token headers

This is the same pattern that caused `#attached` library and `#theme` to never be wired to output in Phase 16 (theming pitfall).

**Consequences:**
- Without-plugin code gen produces REST infrastructure that partially works (GET only)
- With-plugin code gen should perform better IF the skill files explicitly document the wiring pattern (drupalSettings flow, CSRF token flow) as a CRITICAL pattern
- If skills only document individual pieces (how to define routes, how to use drupalSettings), Haiku still will not wire them together

**Prevention:**
- Skills (if created for v4.0 Vue/REST patterns) MUST document the complete data flow as a single connected pattern, not separate pieces
- Include a CRITICAL NEVER callout: "NEVER create a REST route for JavaScript without ALL of: (1) _csrf_request_header_token in route requirements, (2) drupalSettings attached to render array with endpoint URL and config, (3) core/drupalSettings in library dependencies, (4) JavaScript that reads drupalSettings and fetches CSRF token before mutations"
- Eval assertions should test the wiring, not just the existence of files:
  - Static: `libraries.yml` contains `core/drupalSettings` dependency
  - Static: Controller `#attached.drupalSettings` contains project ID
  - Static: JavaScript fetches from `/session/token` before PATCH
  - Runtime: `drush eval` verifies route accepts requests with X-CSRF-Token header

**Detection:**
- JavaScript console shows `drupalSettings.groupAiPm is undefined`
- Network tab shows PATCH requests without X-CSRF-Token header
- Board renders tasks but all interactions fail
- GET requests succeed but POST/PATCH/DELETE return 403

**Phase to address:** ALL phases. This is a cross-cutting concern for eval design. Every eval assertion set must include wiring checks, not just existence checks.

---

## Minor Pitfalls

### Pitfall 12: Keyboard Shortcuts Conflict with Browser and Drupal Defaults

**What goes wrong:**
Custom keyboard shortcuts (Ctrl+K for command palette, S for status change, G+P for go to projects) conflict with browser shortcuts or Drupal admin toolbar shortcuts. Ctrl+K opens the browser's address bar in some browsers. Single-key shortcuts fire while typing in form fields.

**Prevention:**
- NEVER bind single-key shortcuts globally -- only when a board element has focus and no text input is active:
  ```javascript
  function handleKeydown(event) {
    // Skip if user is typing in an input
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(event.target.tagName)) return;
    if (event.target.isContentEditable) return;
    // Now handle board shortcuts
  }
  ```
- Use `event.preventDefault()` for shortcuts that conflict with browser defaults, but only when the Kanban board has focus context
- Test shortcuts in Claro theme specifically (toolbar may have its own keyboard handlers)
- Provide a keyboard shortcut reference (? key to show overlay) so users can discover shortcuts

**Phase to address:** Interactions (Phase 2).

---

### Pitfall 13: Entity Access Checks Missing on REST Endpoints -- Users See Tasks From Other Projects

**What goes wrong:**
The custom REST controller loads tasks by project ID but does not check whether the current user has access to view those tasks or that project. Any authenticated user who guesses a project ID can see its tasks via the REST API, even if they are not a member of the associated group.

**Prevention:**
- Run entity access checks on every loaded entity:
  ```php
  $tasks = $this->entityTypeManager->getStorage('task')->loadMultiple($task_ids);
  $accessible_tasks = array_filter($tasks, function ($task) {
    return $task->access('view');
  });
  ```
- Check project access before returning any tasks:
  ```php
  if (!$project->access('view')) {
    throw new AccessDeniedHttpException();
  }
  ```
- For PATCH operations, check `update` access on the specific entity
- For POST operations, check `create` access on the entity type within the group context
- NEVER rely on route-level permission alone -- it checks "can access the dashboard feature" but not "can access THIS project's tasks"

**Phase to address:** API Layer (Phase 1).

---

### Pitfall 14: Drupal AJAX Commands Used Alongside Vue -- Two Rendering Systems Fight

**What goes wrong:**
The module uses Drupal's AJAX framework (`AjaxResponse`, `ReplaceCommand`, `OpenModalDialogCommand`) for list view enhancements while using Vue.js for the Kanban board. When both are on the same page (or when navigating between them), they interfere. Drupal AJAX replaces HTML that Vue is managing, or Vue's state management conflicts with Drupal's AJAX state tracking.

**Prevention:**
- Draw a clear boundary: Vue owns the Kanban board route, Drupal AJAX owns the entity list/form routes. NEVER mix them on the same page.
- The Kanban board page should have ZERO Drupal AJAX forms or AJAX-enabled elements inside the Vue-managed DOM
- The entity list builder page should use PURE Drupal AJAX for status toggles, without loading Vue
- If a modal is needed from the board (e.g., confirming deletion), use a Vue modal component, NOT `OpenModalDialogCommand`
- Share state via REST API, not DOM manipulation -- if a Drupal AJAX action changes a task status, Vue should re-fetch on focus return

**Phase to address:** Architecture decision (Phase 1). Boundary between Vue and Drupal AJAX must be defined before either is implemented.

---

### Pitfall 15: `_format: json` Missing From Route -- Content Negotiation Defaults to HTML

**What goes wrong:**
Custom REST routes work in manual testing (because the developer includes `?_format=json` in the URL) but fail in the Vue app (because the app constructs URLs without `?_format=json`). The server returns HTML responses that the Vue app cannot parse.

**Prevention:**
- Add `_format: json` as a route requirement (not just a query parameter):
  ```yaml
  group_ai_pm.api.task_update:
    path: '/api/group-ai-pm/task/{task}'
    defaults:
      _controller: '\Drupal\group_ai_pm\Controller\TaskApiController::update'
    requirements:
      _permission: 'access group_ai_pm dashboard'
      _csrf_request_header_token: 'TRUE'
      _format: json
      _method: PATCH
  ```
- When `_format` is a route requirement, Drupal automatically uses the JSON exception subscriber for error responses
- The Vue API service should still include `?_format=json` in URLs as a safety measure
- Set Content-Type headers on JavaScript requests: `'Content-Type': 'application/json'`

**Phase to address:** API Layer (Phase 1).

---

## Phase-Specific Warnings

| Phase Topic | Likely Pitfall | Mitigation |
|-------------|---------------|------------|
| API Layer (REST endpoints) | CSRF token mechanism confusion (#1, #5) | Use `_csrf_request_header_token` for all JS-called routes, `_csrf_token` for link-based actions only |
| API Layer (REST endpoints) | JSON error responses (#3, #15) | Add `_format: json` to ALL API route requirements |
| API Layer (REST endpoints) | Missing entity access checks (#13) | Check `$entity->access()` for every loaded entity, not just route permission |
| Vue Infrastructure | Double initialization (#2) | Use `once()` guard in Drupal behavior, implement `detach` for cleanup |
| Vue Infrastructure | drupalSettings not available (#7) | Declare `core/drupalSettings` dependency, attach settings in controller |
| Vue Infrastructure | Bundle not committed (#4) | Use Vite library mode, commit dist/ folder |
| Vue Infrastructure | Bundle size (#6) | Attach library only on board route, tree-shake imports, target <100KB gzipped |
| Board UI | Claro CSS conflicts (#10) | Namespace all styles, use scoped CSS, test in Claro theme |
| Board UI | Local task tab not appearing (#8) | Match base_route and parameter names exactly with entity canonical route |
| Interactions (DnD) | Optimistic update without rollback (#9) | Implement full rollback pattern with user-visible error messaging |
| Keyboard shortcuts | Browser/Drupal conflicts (#12) | Guard against text input focus, test in Claro with toolbar |
| AJAX list enhancements | Vue and AJAX systems conflict (#14) | Hard boundary: Vue on board page, Drupal AJAX on list pages, never mixed |
| Eval design | Haiku declaration-usage gap (#11) | Eval assertions must test WIRING (settings attached, CSRF fetched), not just file existence |

## "Looks Done But Isn't" Checklist

Things that appear complete but are missing critical pieces.

- [ ] **REST endpoint works:** Check that mutation requests include X-CSRF-Token header AND `?_format=json` -- not just that GET returns data
- [ ] **Vue app mounts:** Check that `once()` guard prevents double mounting after AJAX/BigPipe -- not just that it works on fresh page load
- [ ] **Board tab appears:** Check that the tab appears on the entity page -- not just that the route loads at a direct URL
- [ ] **Drag-and-drop works:** Check that failure rolls back the card AND shows an error -- not just that successful drags update status
- [ ] **Library loads:** Check that the Vue bundle loads ONLY on board pages -- not on every admin page
- [ ] **Settings passed:** Check that drupalSettings contains project ID, base URL, and permissions -- not just that the controller returns JSON
- [ ] **Keyboard shortcuts work:** Check shortcuts while Drupal toolbar is visible and while a text input is focused -- not just in isolation
- [ ] **CSS looks correct:** Check component rendering inside Claro admin theme -- not in a standalone HTML file
- [ ] **Production bundle exists:** Check that `js/dist/` contains compiled files AND is referenced in libraries.yml -- not that source files exist
- [ ] **Access control applies:** Check that REST endpoints respect entity-level access, not just route-level permission

## Recovery Strategies

When pitfalls occur despite prevention, how to recover.

| Pitfall | Recovery Cost | Recovery Steps |
|---------|---------------|----------------|
| CSRF token not fetched (#1) | LOW | Add token fetch to app initialization. Single code change. |
| Vue double mounting (#2) | LOW | Wrap in once() guard. Add detach handler. Quick fix. |
| HTML error responses (#3) | LOW | Add `_format: json` to route requirements. No API changes. |
| Bundle not committed (#4) | LOW | Run build, commit dist/, update .gitignore. No code changes. |
| Wrong CSRF mechanism (#5) | LOW | Change route requirement from `_csrf_token` to `_csrf_request_header_token`. |
| Bundle too large (#6) | MEDIUM | Tree-shaking, code splitting, lazy loading. Requires refactoring imports. |
| drupalSettings missing (#7) | LOW | Add dependency to libraries.yml, attach settings in controller. |
| Local task tab missing (#8) | LOW | Fix route path and base_route parameter matching. |
| Optimistic rollback missing (#9) | MEDIUM | Implement rollback pattern in store. Requires state management refactor. |
| Claro CSS conflicts (#10) | MEDIUM | Add CSS scoping/resets. May require reworking many component styles. |
| Haiku wiring gap (#11) | HIGH | Skill content must be rewritten to show connected patterns. Eval re-run needed. |
| Keyboard conflicts (#12) | LOW | Add input-focus guards. Quick conditional checks. |
| Missing access checks (#13) | MEDIUM | Add access checks to all endpoints. Must audit every controller method. |
| Vue/AJAX conflict (#14) | HIGH | Architectural separation. May require rewriting pages that mix both. |
| JSON format missing (#15) | LOW | Add `_format: json` to routes. No API changes needed. |

## Sources

- [Drupal CSRF Access Checking Documentation](https://www.drupal.org/docs/8/api/routing-system/access-checking-on-routes/csrf-access-checking) -- `_csrf_token` vs `_csrf_request_header_token` distinction (HIGH confidence)
- [Drupal REST Request Fundamentals](https://www.drupal.org/docs/8/core/modules/rest/getting-started-rest-configuration-rest-request-fundamentals) -- X-CSRF-Token header requirement, /session/token endpoint (HIGH confidence)
- [CSRF Token Route Protection Change Record](https://www.drupal.org/node/2772399) -- Token system moved out of REST module to core (HIGH confidence)
- [X-CSRF-Token 403 Error Issue](https://www.drupal.org/project/drupal/issues/3458218) -- Common error patterns (HIGH confidence)
- [REST 403 Responses Unhelpful Issue](https://www.drupal.org/project/drupal/issues/2808233) -- JSON error body missing without `_format` (HIGH confidence)
- [Drupal Asset Libraries Documentation](https://www.drupal.org/docs/develop/creating-modules/adding-assets-css-js-to-a-drupal-module-via-librariesyml) -- libraries.yml, drupalSettings, dependencies (HIGH confidence)
- [Drupal JavaScript API Overview](https://www.drupal.org/docs/drupal-apis/javascript-api/javascript-api-overview) -- Drupal.behaviors, once(), attachBehaviors lifecycle (HIGH confidence)
- [Drupal Behaviors Double-Attachment Issue](https://www.drupal.org/project/drupal/issues/3377788) -- BigPipe and behaviors firing twice (HIGH confidence)
- [Vue.js + Drupal Integration Guide (Five Jars)](https://fivejars.com/blog/how-integrate-vuejs-applications-drupal) -- Library architecture, build targets, mounting patterns (MEDIUM confidence)
- [Building JS for Drupal Contrib Modules (TrueSummit)](https://truesummit.dev/blog/building-js-drupal-contrib-modules) -- Committing compiled bundles, CI/CD pattern (MEDIUM confidence)
- [Vue.js Library Drupal Module](https://www.drupal.org/project/vuejs) -- Vue 3 as Drupal library (MEDIUM confidence)
- [Drupal Local Tasks Documentation](https://www.drupal.org/docs/drupal-apis/menu-api/providing-module-defined-local-tasks) -- base_route, route parameters, tab grouping (HIGH confidence)
- [Vue.js Performance Best Practices](https://vuejs.org/guide/best-practices/performance) -- Tree-shaking, code splitting, bundle optimization (HIGH confidence)
- [Vue.js Production Deployment](https://vuejs.org/guide/best-practices/production-deployment.html) -- Production build configuration (HIGH confidence)
- [Drupal @drupal/once NPM Package](https://www.npmjs.com/package/@drupal/once) -- once() API for preventing double initialization (HIGH confidence)
- [Claro Theme CSS Variables Issue](https://www.drupal.org/project/drupal/issues/3554220) -- Claro library dependency chain (MEDIUM confidence)
- [Drupal Passing Data PHP to JavaScript](https://gorannikolovski.com/snippet/passing-data-from-php-to-javascript-in-drupal) -- drupalSettings patterns (MEDIUM confidence)
- [Drupal HTMX Replacement Proposal](https://www.drupal.org/project/drupal/issues/3404409) -- Context on Drupal AJAX framework complexity (LOW confidence -- proposal, not implemented)
- v3.0 Eval Phase 16 Results -- Haiku "declaration-usage gap" pattern empirically validated (HIGH confidence, project-specific)
- v3.0 Eval Phase 15 Results -- CSRF_token CRITICAL callout effectiveness empirically validated (HIGH confidence, project-specific)
- Existing group_ai_pm module source -- Current route definitions, libraries.yml, entity structure (HIGH confidence, first-party)

---
*Pitfalls research for: v4.0 Vue.js Kanban UX Overhaul -- adding Vue.js, AJAX, and rich frontend UX to existing Drupal 10 module*
*Researched: 2026-03-08*

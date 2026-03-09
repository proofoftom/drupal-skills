---
phase: 18
slug: rest-api-vue-infrastructure-basic-board
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-08
---

# Phase 18 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | PHPUnit (Drupal TestBase classes) |
| **Config file** | `phpunit.xml` in Drupal root (ddev provides) |
| **Quick run command** | `ddev exec phpunit -c /var/www/html/web/core/phpunit.xml --filter KanbanPageTest modules/custom/group_ai_pm/tests/` |
| **Full suite command** | `ddev exec phpunit -c /var/www/html/web/core/phpunit.xml modules/custom/group_ai_pm/tests/` |
| **Estimated runtime** | ~30 seconds |

---

## Sampling Rate

- **After every task commit:** Run static assertions (file existence, grep patterns)
- **After every plan wave:** Run `ddev drush en group_ai_pm -y && ddev drush cr` + runtime assertions
- **Before `/gsd:verify-work`:** Full suite must be green (static + runtime + browser)
- **Max feedback latency:** 30 seconds

---

## Per-Task Verification Map

| Req ID | Behavior | Test Type | Automated Command | File Exists | Status |
|--------|----------|-----------|-------------------|-------------|--------|
| API-01 | GET kanban returns tasks grouped by status | kernel | `ddev drush php-eval "..."` | ❌ W0 | ⬜ pending |
| API-02 | PATCH status requires CSRF, updates entity | kernel | `ddev drush php-eval "..."` | ❌ W0 | ⬜ pending |
| API-03 | POST quick-create returns new task JSON | kernel | `ddev drush php-eval "..."` | ❌ W0 | ⬜ pending |
| API-04 | PATCH reorder updates weights in column | kernel | `ddev drush php-eval "..."` | ❌ W0 | ⬜ pending |
| API-05 | JSON error responses with proper HTTP codes | kernel | `ddev drush php-eval "..."` | ❌ W0 | ⬜ pending |
| API-06 | Entity access enforced on API endpoints | kernel | `ddev drush php-eval "..."` | ❌ W0 | ⬜ pending |
| API-07 | Error responses are JSON with _format:json | kernel | `ddev drush php-eval "..."` | ❌ W0 | ⬜ pending |
| API-08 | CacheableJsonResponse with proper cache tags | kernel | `ddev drush php-eval "..."` | ❌ W0 | ⬜ pending |
| VUE-01 | js/dist/kanban.js exists and is valid IIFE | static | `test -f js/dist/kanban.js` | ❌ W0 | ⬜ pending |
| VUE-02 | js/vendor/vue.global.prod.js exists | static | `test -f js/vendor/vue.global.prod.js` | ❌ W0 | ⬜ pending |
| VUE-03 | once() guard present in main.js | static | `grep -q "once(" js/src/main.js` | ❌ W0 | ⬜ pending |
| VUE-04 | Vite config builds IIFE format | static | `grep -q "iife" vite.config.js` | ❌ W0 | ⬜ pending |
| VUE-05 | core/drupalSettings in library deps | static | `grep -q "core/drupalSettings" group_ai_pm.libraries.yml` | ❌ W0 | ⬜ pending |
| VUE-06 | Drupal.behaviors.groupAiPmKanban defined | static | `grep -q "Drupal.behaviors" js/src/main.js` | ❌ W0 | ⬜ pending |
| VUE-07 | Vue externalized as global in Vite config | static | `grep -q "vue" vite.config.js` | ❌ W0 | ⬜ pending |
| VUE-08 | Library declares js/dist and js/vendor deps | static | `grep -q "js/dist" group_ai_pm.libraries.yml` | ❌ W0 | ⬜ pending |
| BOARD-01 | Board route exists at /admin/content/project/{id}/board | functional | `ddev drush router:match /admin/content/project/1/board` | ❌ W0 | ⬜ pending |
| BOARD-02 | 4 status columns rendered | functional | BrowserTestBase | ❌ W0 | ⬜ pending |
| BOARD-03 | Task cards display in correct columns | functional | BrowserTestBase | ❌ W0 | ⬜ pending |
| BOARD-04 | Drag-and-drop triggers PATCH with CSRF | functional | Browser automation | ❌ W0 | ⬜ pending |
| BOARD-05 | Board tab appears on project entity | functional | `ddev drush php-eval "..."` + BrowserTestBase | ❌ W0 | ⬜ pending |
| BOARD-06 | Quick-create button on column header | functional | BrowserTestBase | ❌ W0 | ⬜ pending |
| BOARD-07 | New task appears without page reload | functional | Browser automation | ❌ W0 | ⬜ pending |
| BOARD-08 | Access denied for unauthorized users | kernel | `ddev drush php-eval "..."` | ❌ W0 | ⬜ pending |
| BOARD-09 | Empty state shown for columns with no tasks | functional | BrowserTestBase | ❌ W0 | ⬜ pending |
| BOARD-10 | drupalSettings.groupAiPm.kanban populated | functional | BrowserTestBase page load check | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/src/Kernel/RestEndpointTest.php` — stubs for API-01, API-02, API-03, API-04, API-05, API-06, API-07, API-08
- [ ] `tests/src/Functional/KanbanPageTest.php` — stubs for BOARD-01, BOARD-02, BOARD-03, BOARD-05, BOARD-06, BOARD-09, BOARD-10
- [ ] No new test framework install needed — existing PHPUnit infrastructure works
- [ ] No new test config needed — existing `$modules` array needs `group_ai_pm` already present

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Drag-and-drop visual UX | BOARD-04 | Requires real browser DnD events | Use eval-browser agent to drag card between columns, verify PATCH fires |
| New task appears without reload | BOARD-07 | Requires JS execution context | Use eval-browser agent to click "+", verify card appended in DOM |
| Loading/error states | BOARD-10 | Requires network interception | Manually simulate slow/failed API responses |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 30s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

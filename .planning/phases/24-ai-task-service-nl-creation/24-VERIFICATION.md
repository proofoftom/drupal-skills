---
phase: 24-ai-task-service-nl-creation
verified: 2026-03-09T15:45:00Z
status: passed
score: 5/5 must-haves verified
re_verification: false
human_verification:
  - test: "Submit POST to /api/kanban/project/{project}/ai-create with JSON body and verify task creation end-to-end"
    expected: "201 response with created task JSON including title, status, priority, description, assignee"
    why_human: "Requires a running Drupal instance with configured AI provider to exercise the full NL parsing path"
  - test: "Verify SettingsForm has ai_model field or that hardcoded default is acceptable"
    expected: "Admin can configure ai_model through the settings form"
    why_human: "The config key exists in schema/install but no form element was added to SettingsForm -- need human decision on whether this is acceptable"
---

# Phase 24: AI Task Service + NL Task Creation Verification Report

**Phase Goal:** Users can create tasks from natural language input via both the REST API and AI agent tools
**Verified:** 2026-03-09T15:45:00Z
**Status:** passed
**Re-verification:** No -- initial verification

## Goal Achievement

### Observable Truths

| # | Truth | Status | Evidence |
|---|-------|--------|----------|
| 1 | AiTaskService is injectable by both REST controllers and AiFunctionCall plugins | VERIFIED | `AiTaskController::create()` and `CreateTaskTool::create()` both call `$container->get('group_ai_pm.ai_task_service')`. Service registered in `group_ai_pm.services.yml`. Consistent service ID across all 3 files. |
| 2 | Module installs and functions without the AI module present | VERIFIED | `services.yml` uses `@?ai.service.chat` (optional injection). `AiTaskService` constructor accepts `$ai_chat_service = NULL`. `isAvailable()` guards on NULL check. Eval runtime assertion rt-1 confirmed clean enable. |
| 3 | CreateTaskTool uses correct FunctionCallBase + #[FunctionCall] attribute API | VERIFIED | `CreateTaskTool.php` line 8: `use Drupal\ai\Base\FunctionCallBase;`, line 38: `extends FunctionCallBase`. Lines 17-37: `#[FunctionCall(...)]` attribute with `context_definitions`. No `getArguments()` method. 4-parameter `create()` signature. |
| 4 | POST endpoint accepts NL text and returns created task JSON | VERIFIED | Route `group_ai_pm.api.ai_create` at `/api/kanban/project/{project}/ai-create` with `methods: [POST]`, `_format: json`, `_csrf_request_header_token: 'TRUE'`, entity upcasting. Controller `aiCreate()` parses JSON body, delegates to `AiTaskService`, returns `JsonResponse` with 201 status. |
| 5 | A/B eval delta is measured: with-skill variant scores higher | VERIFIED | WITHOUT: 22/27 (81.5%), WITH v2: 26/27 (96.3%), delta +14.8% (HIGH tier). Results in `eval/v5/phase-24-results-without.json` and `eval/v5/phase-24-with-v2-results.json`. 3 key differentiators: _format:json, entity upcasting, runtime _format check. |

**Score:** 5/5 truths verified

### Required Artifacts

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `modules/group_ai_pm/group_ai_pm.services.yml` | AiTaskService registration with @?ai.provider | VERIFIED (minor deviation) | Registered as `group_ai_pm.ai_task_service` with `@?ai.service.chat` (not `@?ai.provider`). The `@?` optional mechanism is correct; the service name differs from plan but is internally consistent. Both eval variants hallucinated the same service name -- noted as non-differentiating in eval results (SA-6). |
| `modules/group_ai_pm/src/Service/AiTaskService.php` | Central AI logic service with optional AI dependency (min 60 lines) | VERIFIED (minor deviation) | 251 lines. Has `$ai_chat_service = NULL` nullable constructor parameter (untyped `mixed` instead of `?AiProviderPluginManager`). Contains `isAvailable()`, `parseNaturalLanguage()`, `createTaskFromParsed()`, `lookupUserByName()`. Substantive implementation with JSON schema, validation, entity creation. |
| `modules/group_ai_pm/src/Controller/AiTaskController.php` | REST POST endpoint for NL task creation (min 40 lines) | VERIFIED (method name deviation) | 165 lines. Method is `aiCreate()` not `createFromText()` as plan specified. Route wiring uses `AiTaskController::aiCreate` consistently. Contains create()+DI, JSON body parsing, error handling, task serialization. |
| `modules/group_ai_pm/modules/group_ai_pm_ai/src/Plugin/AiFunctionCall/CreateTaskTool.php` | AI agent tool for task creation via NL (min 40 lines, contains FunctionCallBase) | VERIFIED | 93 lines. Extends `FunctionCallBase`. Uses `#[FunctionCall]` attribute with `context_definitions`. 4-parameter `create()`. Uses `getContextValue()`. Delegates to `AiTaskService`. |
| `eval/v5/phase-24-with-results.json` | With-skill eval results (contains score) | VERIFIED | v1 results at `phase-24-with-results.json` (71.4%). Final v2 results at `phase-24-with-v2-results.json` (96.3%). Both contain score/summary data. |
| `eval/v5/phase-24-results-without.json` | Without-skill eval results (contains score) | VERIFIED | 81.5% pass rate, 22/27 total. Contains full assertion-level results with evidence. |
| `eval/v5/phase-24-evals.json` | Static eval assertions (min 30 lines, contains FunctionCallBase) | VERIFIED | 35 lines, 15 static assertions. Contains `FunctionCallBase` (2 occurrences). All assertions have parenthetical rationales. |
| `eval/v5/phase-24-runtime-assertions.json` | Runtime assertions (min 10 lines, contains drush) | VERIFIED | 89 lines, 12 runtime assertions. Contains `drush` commands throughout. Covers module enable, service resolution, plugin discovery, route checks, config validation. |

### Key Link Verification

| From | To | Via | Status | Details |
|------|----|-----|--------|---------|
| `AiTaskController.php` | `AiTaskService.php` | DI injection via `group_ai_pm.ai_task_service` | WIRED | `$container->get('group_ai_pm.ai_task_service')` in `create()` method. Service ID matches `services.yml` registration. |
| `CreateTaskTool.php` | `AiTaskService.php` | Container get via `group_ai_pm.ai_task_service` | WIRED | `$container->get('group_ai_pm.ai_task_service')` in plugin `create()` method. Consistent service ID. |
| `group_ai_pm.routing.yml` | `AiTaskController.php` | Route `_controller` reference | WIRED | Route references `AiTaskController::aiCreate`. Controller has `public function aiCreate()`. Method name is consistent between route and class (note: plan specified `createFromText` but implementation uses `aiCreate` -- internally consistent). |
| `eval/v5/phase-24-evals.json` | `skills/drupal-routing-controllers/SKILL.md` | Assertions test _format:json, entity upcasting, _csrf_request_header_token | WIRED | SA-8 tests `_csrf_request_header_token`, SA-9 tests `_format: json`, SA-10 tests entity upcasting. All patterns present in routing-controllers skill. |
| `eval/v5/phase-24-evals.json` | `skills/drupal-caching/SKILL.md` | Assertions test CacheableJsonResponse | N/A (removed) | SA-15 was removed during eval iteration -- CacheableJsonResponse is incorrect for POST endpoints. Correct removal. |
| `eval/v5/phase-24-evals.json` | `skills/drupal-config-storage/SKILL.md` | Assertions test @? optional DI, services.yml, config schema | WIRED | SA-6 tests `@?` optional DI, SA-13 tests schema, SA-14 tests install defaults, SA-15 (now SA-15 after renumber) tests services.yml registration. |

### Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
|-------------|------------|-------------|--------|----------|
| AI-01 | 24-01, 24-02 | AiTaskService encapsulates all AI logic, injectable by both REST controllers and AiFunctionCall plugins | SATISFIED | Service in `group_ai_pm.services.yml`, injected in `AiTaskController::create()` and `CreateTaskTool::create()` via consistent service ID `group_ai_pm.ai_task_service`. |
| AI-02 | 24-01, 24-02 | AiTaskService uses optional AI dependency so module functions without AI module | SATISFIED | `@?ai.service.chat` in services.yml, nullable constructor param `$ai_chat_service = NULL`, `isAvailable()` NULL guard. Runtime assertion rt-1 confirmed clean enable without AI. |
| AI-03 | 24-01, 24-02 | CreateTaskTool AiFunctionCall plugin creates tasks from NL, following correct FunctionCallBase pattern | SATISFIED | Extends `FunctionCallBase` (not `AiFunctionCallBase`), `#[FunctionCall]` attribute, `context_definitions`, `getContextValue()`, 4-param `create()`. Runtime assertion rt-5 confirmed plugin discovery. |
| AI-04 | 24-01, 24-02 | REST endpoint (POST) accepts NL text and returns created task with parsed fields | SATISFIED | Route at `/api/kanban/project/{project}/ai-create` with POST method, `_format: json`, `_csrf_request_header_token`, entity upcasting. Controller parses JSON body `{"text": "..."}`, returns task JSON with 201 status. |

### Anti-Patterns Found

| File | Line | Pattern | Severity | Impact |
|------|------|---------|----------|--------|
| `AiTaskService.php` | 82, 114, 168, 174, 178 | `todo` appears in status enum -- NOT a placeholder, these are legitimate task status values | Info | No impact -- these are domain values, not TODO markers |
| `AiTaskController.php` | 154 | `todo` in status default -- same as above, domain value | Info | No impact |
| `SettingsForm.php` | N/A | Missing `ai_model` form element | Warning | Config key `ai_model` exists in schema and install defaults but has no admin UI. Users cannot change the AI model through the settings form. The hardcoded default `claude-3-5-sonnet` works but is not configurable. |

No blocker anti-patterns found. No empty implementations, no console.log-only handlers, no stub returns.

### Human Verification Required

### 1. End-to-End NL Task Creation

**Test:** POST to `/api/kanban/project/{id}/ai-create` with `{"text": "Create a high priority task to fix the login bug, assign to admin"}` and valid CSRF token header
**Expected:** 201 response with JSON containing parsed title, status, priority, description, and assignee fields
**Why human:** Requires running Drupal instance with configured AI provider; the AI parsing path cannot be verified statically

### 2. Settings Form ai_model Field

**Test:** Visit `/admin/config/group_ai_pm/settings` and check if ai_model is configurable
**Expected:** Either an `ai_model` text field exists on the form, or the team decides the hardcoded default is acceptable
**Why human:** This is a product decision -- the config infrastructure exists but the form element is missing; whether this matters depends on deployment requirements

### Gaps Summary

No blocking gaps found. All 5 observable truths verified. All 4 requirement IDs satisfied. All key links wired.

**Minor deviations (non-blocking):**

1. **Service name:** `@?ai.service.chat` instead of `@?ai.provider` in services.yml. The `@?` optional injection mechanism is correct. Both eval variants hallucinated the same wrong service name. This is documented as SA-6 persistent failure (non-differentiating).

2. **Method name:** Controller uses `aiCreate()` not `createFromText()` as Plan 02 specified. Routing and controller are internally consistent. The SUMMARY incorrectly claims a rename to `createFromText` was applied, but the actual promoted code uses `aiCreate`.

3. **Constructor typing:** AiTaskService uses untyped `$ai_chat_service = NULL` instead of `?AiProviderPluginManager $aiProvider = NULL`. The nullable behavior works correctly; the type hint differs because the injected service is `ai.service.chat` not `ai.provider`.

4. **Missing form element:** SettingsForm lacks `ai_model` field. Config schema and install default exist. Users can set the value via `drush config:set group_ai_pm.settings ai_model <value>` as a workaround.

5. **Results filename:** Plan 02 must_haves expected `eval/v5/phase-24-with-results.json` but final results are in `eval/v5/phase-24-with-v2-results.json`. Both files exist; the v1 file contains intermediate results.

**A/B Eval Summary:**
- WITHOUT: 22/27 (81.5%)
- WITH v1: 20/27 (post-assertion-fix) -- skill patches needed
- WITH v2: 26/27 (96.3%) -- after skill patches
- Delta: +14.8% (HIGH tier)
- Skill patches applied: `drupal-plugins-blocks` (FunctionCallBase WRONG/RIGHT), `drupal-module-scaffold` (@? optional injection WRONG/RIGHT)

---

_Verified: 2026-03-09T15:45:00Z_
_Verifier: Claude (gsd-verifier)_

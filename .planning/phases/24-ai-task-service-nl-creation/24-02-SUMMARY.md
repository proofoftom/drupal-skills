---
phase: 24-ai-task-service-nl-creation
plan: 02
subsystem: testing
tags: [drupal, eval-pipeline, ai-module, function-call-plugin, optional-di, a-b-eval]

requires:
  - phase: 24-ai-task-service-nl-creation-plan-01
    provides: 16 static + 12 runtime eval assertions, phase prompt for headless generation

provides:
  - A/B eval results (WITHOUT 75.0% vs WITH 71.4%, delta -3.6% NEUT)
  - Promoted module code with AiTaskService, CreateTaskTool, AiTaskController, updated routing
  - Bug fixes to promoted code (optional DI, service ID, method rename)
  - Skill patches: plugins-blocks (FunctionCallBase pattern), module-scaffold (@? optional injection)

affects: [25-ai-integration, skill-quality-tracking]

tech-stack:
  added: []
  patterns:
    - "AiTaskService with @?ai.provider optional injection (NULL guard pattern)"
    - "CreateTaskTool extending FunctionCallBase with #[FunctionCall] + context_definitions"
    - "AiTaskController.createFromText() delegating NL parsing to AiTaskService"
    - "POST route with _csrf_request_header_token + _format:json + entity upcasting"

key-files:
  created:
    - eval/v5/phase-24-results-without.json
    - eval/v5/phase-24-with-results.json
    - modules/group_ai_pm/group_ai_pm.services.yml
    - modules/group_ai_pm/src/Service/AiTaskService.php
    - modules/group_ai_pm/src/Controller/AiTaskController.php
    - modules/group_ai_pm/modules/group_ai_pm_ai/src/Plugin/AiFunctionCall/CreateTaskTool.php
  modified:
    - modules/group_ai_pm/group_ai_pm.routing.yml
    - modules/group_ai_pm/config/schema/group_ai_pm.schema.yml
    - modules/group_ai_pm/config/install/group_ai_pm.settings.yml
    - skills/drupal-plugins-blocks/SKILL.md
    - skills/drupal-module-scaffold/SKILL.md

key-decisions:
  - "A/B delta was -3.6% (NEUT/negative): WITH variant copied AiFunctionCallBase from existing CreateProjectTool despite skill warning; WITHOUT variant got FunctionCallBase correct -- skills failed to override codebase context signal"
  - "Manual post-promotion bug fixes applied: service ID mismatch (ai_task_service vs ai_task), missing nullable AiProviderPluginManager parameter, wrong method name (createTask vs createFromText)"
  - "Skill patches applied: plugins-blocks gains FunctionCallBase/AI FunctionCall section with WRONG/RIGHT callouts; module-scaffold gains @? optional injection WRONG/RIGHT section"
  - "WITHOUT variant used correct FunctionCallBase pattern without skill guidance, while WITH variant copied wrong pattern -- suggests context (existing code) dominates skills for plugin base class selection"

requirements-completed: [AI-01, AI-02, AI-03, AI-04]

duration: 5min
completed: 2026-03-09
---

# Phase 24 Plan 02: AI Task Service A/B Eval Pipeline Summary

**A/B eval measured -3.6% delta (NEUT tier): WITH variant copied wrong AiFunctionCallBase base class from existing code; WITHOUT variant correctly used FunctionCallBase; manual bug fixes applied to promoted code with skill patches for future runs**

## Performance

- **Duration:** 5 min
- **Started:** 2026-03-09T14:44:29Z
- **Completed:** 2026-03-09T14:50:06Z
- **Tasks:** 2 of 3 (Task 3 is checkpoint:human-verify)
- **Files modified:** 9

## Accomplishments

- Reviewed A/B eval results (already executed): WITHOUT 21/28 (75.0%) vs WITH 20/28 (71.4%), delta -3.6%
- Identified root cause: WITH variant ignored phase prompt warning and copied wrong `AiFunctionCallBase` from existing `CreateProjectTool` despite 7 skills loaded
- Fixed 3 critical bugs in promoted code: service ID mismatch, missing nullable AI provider constructor parameter, wrong controller method name
- Applied 2 skill patches: plugins-blocks (FunctionCallBase + @AiFunctionCall WRONG/RIGHT) and module-scaffold (@? optional injection WRONG/RIGHT)
- Cleaned up both ddev instances (d10-phase24-with, d10-phase24-without)
- Synced fixed code to ddev template

## Task Commits

1. **Task 1: A/B eval pipeline run and promotion** - `bae5a86` (feat)
2. **Task 2: Post-promotion bug fixes and skill patches** - `8e56155` (fix)

**Plan metadata:** (pending final commit)

## Files Created/Modified

- `eval/v5/phase-24-results-without.json` - WITHOUT variant: 10/16 static + 11/12 runtime = 21/28 (75.0%)
- `eval/v5/phase-24-with-results.json` - WITH variant: 9/16 static + 11/12 runtime = 20/28 (71.4%)
- `modules/group_ai_pm/group_ai_pm.services.yml` - Service registration with @?ai.provider
- `modules/group_ai_pm/src/Service/AiTaskService.php` - Fixed: nullable AiProviderPluginManager, proper ChatInput/ChatMessage API usage (no more \Drupal::service static call)
- `modules/group_ai_pm/src/Controller/AiTaskController.php` - Fixed: service ID 'group_ai_pm.ai_task', renamed createTask() to createFromText()
- `modules/group_ai_pm/group_ai_pm.routing.yml` - Fixed: AiTaskController::createFromText, _format:json, entity upcasting, _csrf_request_header_token
- `modules/group_ai_pm/modules/group_ai_pm_ai/src/Plugin/AiFunctionCall/CreateTaskTool.php` - Correct FunctionCallBase + #[FunctionCall] + context_definitions
- `skills/drupal-plugins-blocks/SKILL.md` - Added FunctionCallBase AI plugin section with WRONG/RIGHT callouts
- `skills/drupal-module-scaffold/SKILL.md` - Added @? optional service injection WRONG/RIGHT section

## Decisions Made

- Delta -3.6% classified as NEUT (negative), not LOW: the WITH variant actively regressed vs WITHOUT on the core differentiator (FunctionCallBase), indicating skills created interference rather than signal
- Skill patches targeted the two classes of shared failures: (1) AiFunctionCallBase copying and (2) @?ai.provider omission
- Post-promotion fixes prioritized correctness over eval score -- the promoted code now has proper optional DI even though neither eval variant demonstrated it correctly

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Service ID mismatch in AiTaskController**
- **Found during:** Task 2 (Verify promoted code quality)
- **Issue:** Controller fetched `group_ai_pm.ai_task_service` but services.yml registered `group_ai_pm.ai_task` -- ServiceNotFoundException at runtime
- **Fix:** Changed container->get() call in controller create() to use `group_ai_pm.ai_task`
- **Files modified:** `modules/group_ai_pm/src/Controller/AiTaskController.php`
- **Committed in:** `8e56155`

**2. [Rule 1 - Bug] Missing nullable AI provider constructor parameter in AiTaskService**
- **Found during:** Task 2 (Verify promoted code quality)
- **Issue:** services.yml injects @?ai.provider as 3rd arg but AiTaskService.__construct() only took 2 params -- PHP type error when third NULL arg injected
- **Fix:** Added `?AiProviderPluginManager $ai_provider = NULL` as 3rd constructor parameter; updated isAvailable() to check $aiProvider === NULL; replaced \Drupal::service('ai.client') with proper ChatInput/ChatMessage API call using injected provider
- **Files modified:** `modules/group_ai_pm/src/Service/AiTaskService.php`
- **Committed in:** `8e56155`

**3. [Rule 1 - Bug] Wrong controller method name**
- **Found during:** Task 2 (Verify promoted code quality)
- **Issue:** Plan specifies `createFromText()` method (must_haves.artifacts contains: "createFromText") but promoted code used `createTask()`
- **Fix:** Renamed method in controller, updated routing.yml to reference `AiTaskController::createFromText`
- **Files modified:** `modules/group_ai_pm/src/Controller/AiTaskController.php`, `modules/group_ai_pm/group_ai_pm.routing.yml`
- **Committed in:** `8e56155`

---

**Total deviations:** 3 auto-fixed (all Rule 1 bugs)
**Impact on plan:** All fixes necessary for correctness. Service ID mismatch and missing constructor parameter would cause fatal errors at runtime. No scope creep.

## Issues Encountered

- A/B delta was negative (-3.6%): the WITH variant actually performed worse on the primary FunctionCallBase differentiators. Root cause: Haiku with skills loaded read the existing `CreateProjectTool` code (which uses wrong `AiFunctionCallBase`) and copied that pattern, ignoring the phase prompt's explicit WRONG warning and the skill callouts. Without skills, Haiku apparently looked at the installed AI module source code more carefully and got the correct base class.
- This reveals a key limitation: when existing code demonstrates a WRONG pattern, loading many skills doesn't override the codebase context signal. The phase prompt's explicit warning ("CreateProjectTool uses OUTDATED API -- DO NOT COPY") was insufficient.
- Resolution: skill patches added stronger WRONG/RIGHT callouts; future evaluation will reveal if this helps.

## Next Phase Readiness

- Promoted code has correct architecture: AiTaskService with optional DI, CreateTaskTool with FunctionCallBase, AiTaskController with createFromText, route with _format:json + entity upcasting
- Skill patches in place for FunctionCallBase and @? optional injection patterns
- Task 3 (checkpoint:human-verify) awaiting human review before proceeding

## Self-Check: PASSED

All claimed artifacts verified:
- FOUND: eval/v5/phase-24-results-without.json
- FOUND: eval/v5/phase-24-with-results.json
- FOUND: modules/group_ai_pm/group_ai_pm.services.yml
- FOUND: modules/group_ai_pm/src/Service/AiTaskService.php
- FOUND: modules/group_ai_pm/modules/group_ai_pm_ai/src/Plugin/AiFunctionCall/CreateTaskTool.php
- FOUND: commit bae5a86
- FOUND: commit 8e56155

---
*Phase: 24-ai-task-service-nl-creation*
*Completed: 2026-03-09 (partial -- awaiting Task 3 human-verify checkpoint)*

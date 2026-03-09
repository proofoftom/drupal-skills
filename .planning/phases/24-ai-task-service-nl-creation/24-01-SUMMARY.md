---
phase: 24-ai-task-service-nl-creation
plan: 01
subsystem: testing
tags: [drupal, eval-assertions, ai-module, function-call-plugin, optional-di]

requires:
  - phase: 23-skill-gap-fixes-eval-author-validation
    provides: eval-author agent validated for production use, assertion quality calibration

provides:
  - 16 static eval assertions for Phase 24 AI task service (87% differentiating)
  - 12 runtime assertions for drush-based functional verification
  - Phase prompt for headless Haiku code generation (Plan 02)
  - Calibrated assertion set targeting FunctionCallBase/context_definitions/optional-DI patterns

affects: [24-02-headless-generation, 25-eval-analysis]

tech-stack:
  added: [eval/v5 directory]
  patterns:
    - "eval-author 6-step process (read skills → analyze prompt → read existing code → read gold-standard → design assertions → self-check)"
    - "60/20/20 category distribution enforced: 87% differentiating, 12% wiring, 0% structural"
    - "Flexible runtime assertions checking multiple naming conventions for route/plugin names"

key-files:
  created:
    - eval/v5/phase-24-evals.json
    - eval/v5/phase-24-runtime-assertions.json

key-decisions:
  - "Phase prompt references outdated CreateProjectTool as WRONG pattern, not as template to copy -- Haiku needs explicit signal to use the correct FunctionCallBase API"
  - "16 static assertions with parenthetical rationales, all referencing specific wrong-vs-right patterns from SKILL.md"
  - "Runtime assertions use flexible multi-name matching for route and plugin discovery to accommodate Haiku naming variations"
  - "context_definitions vs getArguments() is the top differentiator: existing codebase uses the WRONG getArguments() pattern, so without skill Haiku copies it"

requirements-completed: [AI-01, AI-02, AI-03, AI-04]

duration: 3min
completed: 2026-03-09
---

# Phase 24 Plan 01: AI Task Service Eval Assertions Summary

**Three-tier eval assertions for AI task service phase: 16 static + 12 runtime assertions targeting FunctionCallBase API, optional @? DI, and #[FunctionCall] attribute patterns**

## Performance

- **Duration:** 3 min
- **Started:** 2026-03-09T14:14:43Z
- **Completed:** 2026-03-09T14:17:37Z
- **Tasks:** 1
- **Files modified:** 2

## Accomplishments

- Executed full eval-author 6-step process manually (no agent available): read 6 skill files, analyzed phase prompt, read existing module code, calibrated against Phase 18 gold-standard
- Created 16 static assertions with 87% differentiating rate (exceeds 60% minimum), all with parenthetical rationales
- Created 12 runtime assertions with flexible multi-name matching for routes/plugins
- Assertion distribution: 87% differentiating (14/16), 12% wiring (2/16), 0% structural

## Task Commits

1. **Task 1: Write phase prompt and invoke eval-author for static + runtime assertions** - `44567bc` (feat)

**Plan metadata:** (pending)

## Files Created/Modified

- `eval/v5/phase-24-evals.json` - 16 static assertions targeting non-obvious AI module patterns
- `eval/v5/phase-24-runtime-assertions.json` - 12 runtime assertions for drush-based verification

## Decisions Made

- Phase prompt explicitly calls out that `CreateProjectTool` uses an OUTDATED pattern, telling Haiku the new tool must follow "the CORRECT current pattern used by the installed AI Agents module" -- this is needed because existing code in the module uses the wrong base class and Haiku would copy it without this signal
- `context_definitions` vs `getArguments()` assertion is the single highest-value differentiator: the existing codebase uses `getArguments()` throughout, so without the plugins-blocks skill, Haiku will copy the wrong pattern into every new tool
- Runtime assertions use try/catch loop over multiple possible route/plugin names to avoid false negatives from naming variations

## Deviations from Plan

None - plan executed exactly as written. Eval-author agent invocation was not available; the 6-step process was executed manually following the eval-author.md protocol.

## Issues Encountered

None.

## Next Phase Readiness

- Phase prompt in `eval/v5/phase-24-evals.json` is ready for use in Plan 02 headless Haiku generation
- Both without-skill and with-skill variants can be launched immediately
- Static and runtime assertions are ready for grading after code generation completes

---
*Phase: 24-ai-task-service-nl-creation*
*Completed: 2026-03-09*

---
phase: 09-eval-prompt-rewrite
plan: 01
subsystem: testing
tags: [evals, drupal-10, prompt-rewrite]

# Dependency graph
requires:
  - phase: 08-eval-infrastructure
    provides: eval pipeline with evals.json schema and grading agents
provides:
  - 8 evals.json files with D10-targeted prompts (no Open Social references)
affects: [09-eval-prompt-rewrite plan 02, 10-eval-runs]

# Tech tracking
tech-stack:
  added: []
  patterns: ["vanilla D10 prompt pattern for eval isolation"]

key-files:
  created: []
  modified:
    - skills/drupal-access-security/evals/evals.json
    - skills/drupal-config-storage/evals/evals.json
    - skills/drupal-forms-api/evals/evals.json
    - skills/drupal-module-scaffold/evals/evals.json
    - skills/drupal-plugins-blocks/evals/evals.json
    - skills/drupal-routing-controllers/evals/evals.json
    - skills/drupal-theming/evals/evals.json
    - skills/drupal-views-dev/evals/evals.json

key-decisions:
  - "Text-only replacement approach: prompt field only, expectations untouched"

patterns-established:
  - "Eval prompts use 'a Drupal 10 site' as environment reference (not Open Social or os-kg)"

requirements-completed: [EVAL-01]

# Metrics
duration: 2min
completed: 2026-03-07
---

# Phase 09 Plan 01: Eval Prompt Rewrite (Straightforward) Summary

**Rewrote 8 eval prompts from Open Social/os-kg references to vanilla "Drupal 10 site" targeting -- prompt-only changes, all expectations preserved byte-identical**

## Performance

- **Duration:** 2 min
- **Started:** 2026-03-07T04:52:05Z
- **Completed:** 2026-03-07T04:54:16Z
- **Tasks:** 2
- **Files modified:** 8

## Accomplishments
- Replaced Open Social / os-knowledge-garden references in all 8 straightforward eval prompts
- Verified zero changes to expectations arrays or expected_output fields (git diff confirms prompt-line-only changes)
- All 13 evals.json files pass the os-kg/Open Social grep check (8 from this plan + 5 already clean from prior commit 86291ba)

## Task Commits

Each task was committed atomically:

1. **Task 1: Rewrite prompts for 8 straightforward skills** - `1fbfa80` (feat)
2. **Task 2: Verify assertion stability across all 8 rewrites** - verification-only, no file changes

**Plan metadata:** `87bda52` (docs: complete plan)

## Files Created/Modified
- `skills/drupal-access-security/evals/evals.json` - Prompt: "our Drupal 10 Open Social site" -> "a Drupal 10 site"
- `skills/drupal-config-storage/evals/evals.json` - Prompt: "our Drupal 10 Open Social site" -> "a Drupal 10 site"
- `skills/drupal-forms-api/evals/evals.json` - Prompt: "on our Drupal 10 Open Social site" -> "on a Drupal 10 site"
- `skills/drupal-module-scaffold/evals/evals.json` - Prompt: "on our Open Social site" -> "on a Drupal 10 site"
- `skills/drupal-plugins-blocks/evals/evals.json` - Prompt: "our Drupal 10 Open Social site" -> "a Drupal 10 site"
- `skills/drupal-routing-controllers/evals/evals.json` - Prompt: "for a Drupal 10 Open Social site (os-knowledge-garden)" -> "for a Drupal 10 site"
- `skills/drupal-theming/evals/evals.json` - Prompt: "our Drupal 10 Open Social site" -> "a Drupal 10 site"
- `skills/drupal-views-dev/evals/evals.json` - Prompt: "our Drupal 10 Open Social site" -> "a Drupal 10 site"

## Decisions Made
- Text-only replacement in prompt fields; no changes to module names, expectations, or expected_output
- Used consistent phrasing "a Drupal 10 site" across all 8 prompts (not "the" or "our")

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered
None

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- 8 of 13 eval prompts now target vanilla Drupal 10
- Plan 02 handles the remaining 5 complex cases (entities-fields, testing, batch-queue-cron, caching, database-api) -- these were already committed in 86291ba prior to this plan
- All 13 evals ready for Phase 10 eval runs

## Self-Check: PASSED

- 09-01-SUMMARY.md: FOUND
- Commit 1fbfa80: FOUND
- All 8 modified evals.json files: FOUND

---
*Phase: 09-eval-prompt-rewrite*
*Completed: 2026-03-07*

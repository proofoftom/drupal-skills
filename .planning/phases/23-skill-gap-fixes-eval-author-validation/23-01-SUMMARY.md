---
phase: 23-skill-gap-fixes-eval-author-validation
plan: 01
subsystem: tooling
tags: [drupal-skills, entities-fields, caching, forms-api, bundle_of, hook_update_N, ajax, CacheableMetadata]

# Dependency graph
requires:
  - phase: 22-drush-skill-eval-author
    provides: skill authoring patterns and line budget conventions
provides:
  - bundled-entities.md reference file with bundle_of + hook_update_N patterns
  - CacheableMetadata bubbling section in caching skill
  - AJAX form elements section in forms-api skill
affects: [eval-author-validation, future-phase-evals]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - reference file pattern for budget-constrained skills
    - CacheableMetadata aggregation loop with addCacheableDependency
    - AJAX callback + wrapper ID matching pattern

key-files:
  created:
    - skills/drupal-entities-fields/references/bundled-entities.md
  modified:
    - skills/drupal-entities-fields/SKILL.md
    - skills/drupal-caching/SKILL.md
    - skills/drupal-forms-api/SKILL.md

key-decisions:
  - "bundle_of content in reference file (not inline) due to 497-line budget constraint"
  - "forms-api AJAX section trimmed to 60 lines to fit exactly at 500-line limit"

patterns-established:
  - "Reference file delegation: when SKILL.md hits budget, create references/*.md and cross-reference"

requirements-completed: [TOOL-06, TOOL-07]

# Metrics
duration: 3min
completed: 2026-03-09
---

# Phase 23 Plan 01: Skill Gap Fixes Summary

**Closed three documented skill gaps: bundle_of + hook_update_N reference file for entities-fields, CacheableMetadata bubbling for caching, and #ajax callback/wrapper/AjaxResponse patterns for forms-api**

## Performance

- **Duration:** 3 min
- **Started:** 2026-03-09T13:26:39Z
- **Completed:** 2026-03-09T13:30:13Z
- **Tasks:** 3
- **Files modified:** 4

## Accomplishments
- Created bundled-entities.md reference file (111 lines) with complete bundle_of pattern in D10 annotation + D11 attribute syntax, plus hook_update_N() for adding base fields
- Added CacheableMetadata bubbling section to caching skill covering render array and JSON controller patterns with addCacheableDependency loop
- Added AJAX form elements section to forms-api skill with callback/wrapper matching, AjaxResponse multi-command, and per-row AJAX in tables
- All three SKILL.md files remain within 500-line budget (497, 409, 500)

## Task Commits

Each task was committed atomically:

1. **Task 1: Create bundled-entities.md reference file and cross-reference** - `b0b50e5` (feat)
2. **Task 2: Add CacheableMetadata bubbling section to caching SKILL.md** - `1bc6239` (feat)
3. **Task 3: Add #ajax section to forms-api SKILL.md** - `bb14fb0` (feat)

## Files Created/Modified
- `skills/drupal-entities-fields/references/bundled-entities.md` - NEW: bundle_of pattern with D10/D11 syntax + hook_update_N() for schema changes + WRONG/RIGHT callouts
- `skills/drupal-entities-fields/SKILL.md` - Added cross-reference to bundled-entities.md at bundle decision tree
- `skills/drupal-caching/SKILL.md` - Added CacheableMetadata bubbling subsection with render array and JSON controller patterns
- `skills/drupal-forms-api/SKILL.md` - Added AJAX form elements section with callback, wrapper matching, AjaxResponse, and per-row AJAX

## Decisions Made
- Used reference file for bundle_of content because entities-fields SKILL.md was at 497 lines (3 lines from budget). Only a cross-reference line was added to the main file.
- Trimmed forms-api AJAX section from initial 73 lines to 60 lines by removing redundant headings and blank lines, landing exactly at 500-line budget.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] forms-api AJAX section exceeded 500-line budget**
- **Found during:** Task 3 (Add #ajax section to forms-api SKILL.md)
- **Issue:** Initial AJAX section was 73 lines, pushing SKILL.md to 513 lines (13 over budget)
- **Fix:** Condensed section by removing separate subsection headings, merging code blocks, removing redundant blank lines. Achieved exactly 500 lines.
- **Files modified:** skills/drupal-forms-api/SKILL.md
- **Verification:** `wc -l` confirms 500 lines, all key patterns (statusCallback, AjaxResponse, ReplaceCommand, wrapper.*task-row) still present
- **Committed in:** bb14fb0 (Task 3 commit)

---

**Total deviations:** 1 auto-fixed (1 blocking)
**Impact on plan:** Line budget constraint required content trimming but all required patterns were preserved. No scope creep.

## Issues Encountered
None beyond the line budget deviation documented above.

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- All three skill gaps are closed, ready for eval-author validation (plan 23-02)
- entities-fields skill now has complete bundled entity documentation via reference file
- Caching skill now covers the CacheableMetadata bubbling pattern that was missing in v3.0/v4.0 evals
- forms-api skill now covers the #ajax patterns that appeared in Phase 20 evals

## Self-Check: PASSED

All files verified present. All 3 task commits verified in git log.

---
*Phase: 23-skill-gap-fixes-eval-author-validation*
*Completed: 2026-03-09*

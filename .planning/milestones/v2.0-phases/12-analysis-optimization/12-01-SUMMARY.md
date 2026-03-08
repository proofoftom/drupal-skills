---
phase: 12-analysis-optimization
plan: 01
subsystem: eval-pipeline
tags: [phpcs, coding-standards, skill-content, eval-prompt]

requires:
  - phase: 11-batch-execution
    provides: "6 WITH-skill failure root causes from eval results"
provides:
  - "New drupal-coding-standards skill for phpcs compliance (fixes failures #2, #4, #6)"
  - "Patched batch-queue-cron SKILL.md with try/catch in processItem (fixes failure #3)"
  - "Patched routing-controllers SKILL.md with CRITICAL DI callout (fixes failure #1)"
  - "Patched database-api eval prompt with addTag motivation (fixes failure #5)"
affects: [12-03, 12-04]

tech-stack:
  added: []
  patterns:
    - "Coding-standards skill loaded as baseline for all eval runs (both with/without domain skill)"

key-files:
  created:
    - "skills/drupal-coding-standards/SKILL.md"
    - "skills/drupal-coding-standards/evals/evals.json"
  modified:
    - "skills/drupal-batch-queue-cron/SKILL.md"
    - "skills/drupal-routing-controllers/SKILL.md"
    - "skills/drupal-database-api/evals/evals.json"

key-decisions:
  - "Coding-standards skill kept to exactly 150 lines -- focused on 4 phpcs failure patterns, not comprehensive style guide"
  - "CRITICAL NEVER callout added before DI flow explanation, not after -- ensures Haiku sees it early"

patterns-established:
  - "Baseline quality skills (coding-standards) loaded for BOTH with/without variants in eval pipeline"

requirements-completed: [ANLZ-03, CARRY-01]

duration: 3min
completed: 2026-03-08
---

# Phase 12 Plan 01: Fix WITH-skill Failures Summary

**New coding-standards skill (150 lines, 4 phpcs patterns) plus 2 SKILL.md patches and 1 eval prompt fix addressing all 6 Phase 11 WITH-skill failures**

## Performance

- **Duration:** 3 min
- **Started:** 2026-03-08T01:35:39Z
- **Completed:** 2026-03-08T01:38:46Z
- **Tasks:** 2
- **Files modified:** 5

## Accomplishments
- Created drupal-coding-standards skill covering cuddled brace style, docblock requirements, nullable parameter types, and general formatting -- all with WRONG/RIGHT examples
- Patched batch-queue-cron SKILL.md to show try/catch with SuspendQueueException in processItem() (root cause of failure #3)
- Added CRITICAL NEVER callout to routing-controllers SKILL.md immediately after controller example (root cause of failure #1)
- Updated database-api eval prompt to motivate addTag() via "alterable by other modules" phrasing (root cause of failure #5)

## Task Commits

Each task was committed atomically:

1. **Task 1: Create drupal-coding-standards skill and eval** - `47abbd7` (feat)
2. **Task 2: Patch SKILL.md files and database-api eval prompt** - `f60ec22` (fix)

## Files Created/Modified
- `skills/drupal-coding-standards/SKILL.md` - New coding standards skill (150 lines) covering phpcs failure patterns
- `skills/drupal-coding-standards/evals/evals.json` - Eval testing service, controller, hook_cron, nullable params for phpcs compliance
- `skills/drupal-batch-queue-cron/SKILL.md` - processItem() now has try/catch with SuspendQueueException
- `skills/drupal-routing-controllers/SKILL.md` - Added CRITICAL NEVER callout for static \Drupal:: in controllers
- `skills/drupal-database-api/evals/evals.json` - Prompt now includes "alterable by other modules using appropriate query tags"

## Decisions Made
- Kept coding-standards skill to exactly 150 lines by consolidating brace style examples and tightening docblock section -- avoids Pitfall 2 (too comprehensive)
- Placed CRITICAL NEVER callout BEFORE the DI flow explanation (not after) so Haiku encounters it immediately after the code example
- Eval for coding-standards skill asks for a module with service, controller, hook_cron, and nullable params -- exercises all 4 failure patterns in one prompt

## Deviations from Plan
None - plan executed exactly as written.

## Issues Encountered
None

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- All 6 WITH-skill failure root causes addressed
- Ready for Plan 02 (harder evals for neutral-delta skills)
- Coding-standards skill ready to be loaded as baseline in re-run pipeline (Plan 03)

## Self-Check: PASSED

All 6 files verified present. Both task commits (47abbd7, f60ec22) verified in git log.

---
*Phase: 12-analysis-optimization*
*Completed: 2026-03-08*

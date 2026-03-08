---
phase: 12-analysis-optimization
plan: 03
subsystem: eval-pipeline
tags: [eval-rerun, coding-standards, skill-patches, harder-evals, delta-validation]

# Dependency graph
requires:
  - phase: 12-analysis-optimization
    provides: "Plan 01 fixes (coding-standards skill, SKILL.md patches, eval prompt fix) and Plan 02 harder evals"
provides:
  - "v3 grade files for 7 re-run skills (14 grade JSONs)"
  - "v3 summary files for 7 re-run skills with delta comparisons"
  - "Empirical validation that 3 negative-delta skills flipped to positive"
  - "Confirmation that 4 neutral-delta skills remain neutral despite harder evals"
affects: [12-04]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Coding-standards skill as baseline for both eval variants eliminates phpcs noise"
    - "Browser step dropped from eval pipeline (zero discriminatory value confirmed)"

key-files:
  created:
    - "eval/results/routing-controllers/summary-v3.json"
    - "eval/results/batch-queue-cron/summary-v3.json"
    - "eval/results/views-dev/summary-v3.json"
    - "eval/results/database-api/summary-v3.json"
    - "eval/results/forms-api/summary-v3.json"
    - "eval/results/theming/summary-v3.json"
    - "eval/results/entities-fields/summary-v3.json"
  modified: []

key-decisions:
  - "Browser step dropped entirely -- zero discriminatory value across all skills, graders verify via drush/curl instead"
  - "Neutral-delta skills confirmed as baseline Haiku knowledge -- no further iteration needed on forms-api, theming"
  - "entities-fields bundle_of gap identified in BOTH variants -- SKILL.md needs explicit coverage (deferred to Plan 04)"

patterns-established:
  - "v3 eval pipeline: coding-standards baseline + domain skill delta measurement"

requirements-completed: [ANLZ-02, ANLZ-03, CARRY-01]

# Metrics
duration: 2min
completed: 2026-03-08
---

# Phase 12 Plan 03: Re-run 7 Affected Skills Summary

**All 3 negative-delta skills flipped to positive (+33.3%, +12.5%, +11.1%); 4 neutral-delta skills confirmed at 0% despite harder evals and coding-standards baseline**

## Performance

- **Duration:** 2 min (Task 2 only; Task 1 ran externally via orchestrator)
- **Started:** 2026-03-08T02:11:50Z
- **Completed:** 2026-03-08T02:13:22Z
- **Tasks:** 1 (Task 2; Task 1 completed by orchestrator)
- **Files modified:** 21

## Accomplishments
- Compiled summary-v3.json for all 7 re-run skills with delta comparisons against Phase 11 baselines
- Documented that SKILL.md patches (routing-controllers DI callout, batch-queue-cron SuspendQueueException) produced large positive swings
- Confirmed coding-standards skill eliminated phpcs noise from WITH variants
- Identified entities-fields bundle_of as a gap in SKILL.md content (neither variant produces it)

## V3 Results Table

| Skill | WITH v3 | WITHOUT v3 | Delta v3 | Delta v2 | Change |
|-------|---------|------------|----------|----------|--------|
| routing-controllers | 9/9 | 6/9 | **+33.3%** | -11.1% | +44.4% |
| batch-queue-cron | 8/8 | 7/8 | **+12.5%** | -12.5% | +25.0% |
| views-dev | 9/9 | 8/9 | **+11.1%** | -11.1% | +22.2% |
| database-api | 7/9 | 7/9 | 0% | 0% | 0% |
| forms-api | 8/9 | 8/9 | 0% | 0% | 0% |
| theming | 9/9 | 9/9 | 0% | 0% | 0% |
| entities-fields | 7/9 | 7/9 | 0% | 0% | 0% |

## Updated Full Results Table (13 skills, v3 where available)

| Tier | Skill | WITH | WITHOUT | Delta |
|------|-------|------|---------|-------|
| HIGH | caching | 8/8 | 5/8 | +37.5% |
| HIGH | routing-controllers | 9/9 | 6/9 | +33.3% |
| HIGH | scaffold | 6/6 | 4/6 | +33.3% |
| HIGH | testing | 9/9 | 7/9 | +22.2% |
| MOD | config-storage | 8/8 | 7/8 | +12.5% |
| MOD | batch-queue-cron | 8/8 | 7/8 | +12.5% |
| MOD | plugins-blocks | 8/8 | 7/8 | +12.5% |
| MOD | views-dev | 9/9 | 8/9 | +11.1% |
| MOD | access-security | 9/10 | 8/10 | +10.0% |
| NEUT | forms-api | 8/9 | 8/9 | 0% |
| NEUT | database-api | 7/9 | 7/9 | 0% |
| NEUT | theming | 9/9 | 9/9 | 0% |
| NEUT | entities-fields | 7/9 | 7/9 | 0% |

**Portfolio: 4 HIGH (>15%), 5 MOD (5-15%), 4 NEUT (0%), 0 NEG**

## Task Commits

Each task was committed atomically:

1. **Task 1: Re-run 7 affected skills through eval pipeline** - (completed by orchestrator, no single commit)
2. **Task 2: Compile re-run summary files** - `f361b3b` (feat)

## Files Created/Modified
- `eval/results/routing-controllers/summary-v3.json` - Delta +33.3%, up from -11.1%
- `eval/results/batch-queue-cron/summary-v3.json` - Delta +12.5%, up from -12.5%
- `eval/results/views-dev/summary-v3.json` - Delta +11.1%, up from -11.1%
- `eval/results/database-api/summary-v3.json` - Delta 0%, unchanged
- `eval/results/forms-api/summary-v3.json` - Delta 0%, unchanged
- `eval/results/theming/summary-v3.json` - Delta 0%, unchanged
- `eval/results/entities-fields/summary-v3.json` - Delta 0%, unchanged
- 14 grade-v3-*.json files (7 with + 7 without)

## Decisions Made
- Browser step dropped from eval pipeline entirely -- graders use drush/curl verification instead (zero discriminatory value confirmed across all skills)
- Neutral-delta skills (forms-api, theming) confirmed as baseline Haiku knowledge -- ConfirmFormBase and template_preprocess_HOOK are within Haiku's training data
- entities-fields bundle_of gap identified: neither WITH nor WITHOUT variant produces the bundle_of key on config entity annotation, suggesting SKILL.md needs explicit coverage
- database-api 0% delta shows different failure modes (WITH: raw query + DrupalPractice warning; WITHOUT: broken page + unused import) -- skill teaches value but on different axes that cancel out

## Deviations from Plan

None - plan executed exactly as written. Task 1 was completed by the orchestrator before this executor was spawned.

## Issues Encountered
None

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- All re-runs complete with stabilized v3 results
- Zero negative-delta skills remaining (was 3 at end of Phase 11)
- Ready for Plan 04: final report compilation with tier classifications and overall verdict
- entities-fields bundle_of gap should be noted in final report as a potential improvement for v3.0

## Self-Check: PASSED

All 22 files verified present (7 summary-v3.json, 14 grade-v3-*.json, 1 SUMMARY.md). Task commit f361b3b verified in git log.

---
*Phase: 12-analysis-optimization*
*Completed: 2026-03-08*

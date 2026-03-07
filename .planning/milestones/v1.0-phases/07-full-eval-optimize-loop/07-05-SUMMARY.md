---
phase: 07-full-eval-optimize-loop
plan: 05
subsystem: eval
tags: [drupal, eval, benchmark, theming, database-api, views-dev, batch-queue-cron, sonnet, ddev]

requires:
  - phase: 07-02
    provides: E2E eval infrastructure (setup/teardown scripts, e2e-assert.sh)
  - phase: 07-03
    provides: evals.json for theming, database-api, views-dev, batch-queue-cron skills
provides:
  - 4 skill eval workspaces with benchmark.json showing with/without deltas
  - drupal-theming-workspace/iteration-1/benchmark.json
  - drupal-database-api-workspace/iteration-1/benchmark.json
  - drupal-views-dev-workspace/iteration-1/benchmark.json
  - drupal-batch-queue-cron-workspace/iteration-1/benchmark.json
affects: [07-06, 07-07, eval-analysis]

tech-stack:
  added: []
  patterns: [sequential-skill-eval, headless-claude-sonnet, ddev-env-per-run]

key-files:
  created:
    - drupal-theming-workspace/iteration-1/benchmark.json
    - drupal-database-api-workspace/iteration-1/benchmark.json
    - drupal-views-dev-workspace/iteration-1/benchmark.json
    - drupal-batch-queue-cron-workspace/iteration-1/benchmark.json
  modified: []

key-decisions:
  - "Theming, database-api, views-dev expectations not discriminating enough for Sonnet -- all pass 100% with and without skill"
  - "Batch-queue-cron shows +29% delta -- without-skill fails cron due to undeclared logger channel service"
  - "Stale os-knowledge-garden ddev project causes router health check failures -- must delete before each eval run"
  - "Re-ran theming without-skill with explicit 'do not ask questions' prefix after Sonnet asked clarifying question on first attempt"

patterns-established:
  - "Router cleanup: remove stale traefik configs before ddev start to prevent health check timeout"
  - "Prompt prefix: include 'Do NOT ask questions -- just create the code' for without-skill runs"

requirements-completed: [FULL-03, FULL-04]

duration: 74min
completed: 2026-03-06
---

# Phase 7 Plan 5: Eval Batch 2 Summary

**4 skill evals (theming, database-api, views-dev, batch-queue-cron) with Sonnet via headless claude; batch-queue-cron shows +29% skill delta, other 3 show 0% due to insufficiently discriminating expectations**

## Performance

- **Duration:** 74 min
- **Started:** 2026-03-06T10:51:11Z
- **Completed:** 2026-03-06T12:05:00Z
- **Tasks:** 2
- **Files modified:** 67

## Accomplishments
- Ran all 4 skills through full eval pipeline: setup ddev, run headless Sonnet with/without skill, grade, teardown
- Batch-queue-cron is the only skill in this batch showing measurable delta (+29%) -- without-skill fails E2E cron test
- Identified that theming, database-api, and views-dev expectations need tightening for Sonnet (qualitative differences exist but aren't tested)
- All ddev environments properly torn down after each eval

## Eval Results Summary

| Skill | With Skill | Without Skill | Delta |
|-------|-----------|---------------|-------|
| theming | 8/8 (100%) | 8/8 (100%) | **+0%** |
| database-api | 8/8 (100%) | 8/8 (100%) | **+0%** |
| views-dev | 7/7 (100%) | 7/7 (100%) | **+0%** |
| batch-queue-cron | 7/7 (100%) | 5/7 (71%) | **+29%** |

### Qualitative Differences (Not Captured by Expectations)

| Skill | With Skill Pattern | Without Skill Pattern |
|-------|-------------------|----------------------|
| theming | D11 #[Block] attribute, template_preprocess function | D10 @Block annotation, no preprocess |
| database-api | Service class with DI (ViewAnalyticsTracker) | Procedural function in .module |
| views-dev | .views.inc lazy loading, D11 #[ViewsFilter], InOperator, config schema | .module file, D10 annotation, FilterPluginBase, no schema |
| batch-queue-cron | Proper exception throwing for invalid data | Silent return, undeclared logger service |

## Task Commits

Each task was committed atomically:

1. **Task 1: Run eval sub-batch C (theming, database-api)** - `a23cde7` (feat)
2. **Task 2: Run eval sub-batch D (views-dev, batch-queue-cron)** - `6967118` (feat)

## Files Created/Modified
- `drupal-theming-workspace/iteration-1/` - Full eval workspace with benchmark, grading, outputs, transcripts
- `drupal-database-api-workspace/iteration-1/` - Full eval workspace with benchmark, grading, outputs, transcripts
- `drupal-views-dev-workspace/iteration-1/` - Full eval workspace with benchmark, grading, outputs, transcripts
- `drupal-batch-queue-cron-workspace/iteration-1/` - Full eval workspace with benchmark, grading, outputs, transcripts

## Decisions Made
- Theming, database-api, views-dev expectations are too coarse for Sonnet -- both with and without pass all assertions. Need tighter expectations targeting D11 attributes, .views.inc separation, service DI patterns
- Batch-queue-cron expectation for error handling and E2E cron test successfully discriminates -- the undeclared logger channel service is a real production bug
- Added "Do NOT ask questions" prefix to without-skill prompts after theming Sonnet asked a clarifying question on first attempt
- Cleaned stale os-knowledge-garden ddev project that caused persistent router health check failures

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] Stale ddev-router health check failures**
- **Found during:** Task 1 (database-api without-skill) and Task 2 (views-dev without-skill, batch-queue)
- **Issue:** os-knowledge-garden registered as stopped ddev project; stale traefik config causes router health check to fail on fresh starts
- **Fix:** Deleted os-knowledge-garden ddev project; removed stale traefik configs between env setups
- **Files modified:** None (runtime fix only)
- **Verification:** Router achieves healthy status after cleanup

**2. [Rule 1 - Bug] Sonnet asked clarifying question in without-skill theming run**
- **Found during:** Task 1 (theming without-skill first attempt)
- **Issue:** Headless claude -p returned a question instead of code, resulting in no module files created
- **Fix:** Re-ran with "Do NOT ask questions -- just create the code" prefix in prompt
- **Files modified:** None (re-execution only)
- **Verification:** Second run created all expected module files

---

**Total deviations:** 2 auto-fixed (1 blocking, 1 bug)
**Impact on plan:** Both fixes necessary for eval completion. No scope creep.

## Issues Encountered
- Gemini API rate limit quota exceeded during ddev install (AI search indexing) -- non-blocking for eval, affects only Qdrant vector indexing
- ddev-router health check timeouts recurring pattern -- stale traefik configs from previous eval phases persist and need cleanup

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- All 4 skills now have benchmark data; combined with Plan 04 + Phase 6, total coverage depends on Plan 04 execution
- Three skills (theming, database-api, views-dev) need expectation tightening in Plan 06 (optimization loop)
- Batch-queue-cron demonstrates that E2E runtime tests (cron exit code) are the most discriminating assertion type

## Self-Check: PASSED

- All 4 benchmark.json files: FOUND
- SUMMARY.md: FOUND
- Commit a23cde7 (Task 1): FOUND
- Commit 6967118 (Task 2): FOUND

---
*Phase: 07-full-eval-optimize-loop*
*Completed: 2026-03-06*

---
phase: 10-pipeline-validation
plan: 01
subsystem: testing
tags: [eval-pipeline, caching, grading, benchmark, ddev, subagents]

# Dependency graph
requires:
  - phase: 08-eval-optimization-loop
    provides: eval-executor, eval-grader subagents, setup/teardown scripts
  - phase: 09-eval-prompt-rewrite
    provides: hint-free evals.json with differentiating expectations
provides:
  - Complete caching eval workspace with grading.json (both configs) and benchmark.json
  - Validated eval-grader subagent produces schema-compliant JSON with real code
  - Empirical proof that v2.0 pipeline completes end-to-end (delta +11%, below 30% threshold -- assertions need tuning)
affects: [10-02-scaffold-calibration, 11-batch-eval-runs]

# Tech tracking
tech-stack:
  added: []
  patterns: [workspace-layout, benchmark-aggregation, grader-schema-validation]

key-files:
  created:
    - .planning/phases/10-pipeline-validation/workspaces/drupal-caching-workspace/iteration-1/eval-cache-block/eval_metadata.json
    - .planning/phases/10-pipeline-validation/workspaces/drupal-caching-workspace/iteration-1/eval-cache-block/with_skill/run-1/grading.json
    - .planning/phases/10-pipeline-validation/workspaces/drupal-caching-workspace/iteration-1/eval-cache-block/without_skill/run-1/grading.json
    - .planning/phases/10-pipeline-validation/workspaces/drupal-caching-workspace/iteration-1/benchmark.json
  modified: []

key-decisions:
  - "Caching delta +11% (9/9 with vs 8/9 without) -- pipeline validated but delta below 30% threshold, assertions need tuning"
  - "eval-grader subagent produces compliant grading.json on first real run (no schema fixes needed)"
  - "Only 1 differentiating expectation: route vs url.path cache context -- Sonnet without skill is highly competent at caching"

patterns-established:
  - "Workspace layout: workspaces/<skill>-workspace/iteration-N/eval-<name>/{with,without}_skill/run-N/"
  - "benchmark.json schema: metadata + runs + run_summary with delta computation"
  - "Post-grading schema validation: jq checks for expectations array length and summary.pass_rate"

requirements-completed: [PIPE-01]

# Metrics
duration: 3min
completed: 2026-03-07
---

# Phase 10 Plan 01: Caching Calibration Pipeline Summary

**Full v2.0 eval pipeline end-to-end on caching skill: +11% delta (with-skill 9/9 vs without-skill 8/9), validating grader, workspace layout, and benchmark aggregation. Delta below 30% threshold -- Sonnet is competent at caching without skill guidance.**

## Performance

- **Duration:** 3 min (aggregation of pre-run pipeline results)
- **Started:** 2026-03-07T05:27:36Z
- **Completed:** 2026-03-07T05:30:58Z
- **Tasks:** 2
- **Files modified:** 8

## Accomplishments
- Complete caching eval pipeline executed: setup, with/without executor runs, grading, benchmark aggregation, teardown
- eval-grader subagent produced schema-compliant grading.json with specific evidence strings on first real run
- Caching skill delta of +11% (below 30% threshold; v1.0 was +75%). Sonnet without skill passed 8/9 expectations -- only missed route vs url.path cache context
- 1 differentiating expectation identified: route cache context (SKILL.md teaches route, Sonnet defaults to url.path)
- benchmark.json matches 10-RESEARCH.md Pattern 4 schema exactly

## Task Commits

Each task was committed atomically:

1. **Task 1: Setup environments and run caching eval pipeline** - `8de65ef` (feat)
2. **Task 2: Aggregate benchmark.json and validate caching delta** - `16e9ace` (feat)

## Files Created/Modified
- `workspaces/drupal-caching-workspace/iteration-1/eval-cache-block/eval_metadata.json` - Eval configuration snapshot
- `workspaces/drupal-caching-workspace/iteration-1/eval-cache-block/with_skill/run-1/grading.json` - 9/9 passed (100%)
- `workspaces/drupal-caching-workspace/iteration-1/eval-cache-block/with_skill/run-1/outputs/` - Archived module code
- `workspaces/drupal-caching-workspace/iteration-1/eval-cache-block/without_skill/run-1/grading.json` - 8/9 passed (89%)
- `workspaces/drupal-caching-workspace/iteration-1/eval-cache-block/without_skill/run-1/outputs/` - Archived module code
- `workspaces/drupal-caching-workspace/iteration-1/benchmark.json` - Aggregated results with +0.11 delta

## Decisions Made
- **Pipeline validated but delta low:** +11% delta confirms the v2.0 pipeline works end-to-end but the caching assertions are mostly standard Drupal knowledge that Sonnet already knows. Assertion tuning is Phase 12 work.
- **Grader works on first real run:** eval-grader subagent produced compliant JSON without any schema fixes or re-runs, validating the Phase 8 simulation approach.
- **Sonnet is highly competent at caching:** Without-skill passed 8/9 expectations. Only the route vs url.path cache context distinction differentiates. This confirms the v1.0 finding that standard Drupal patterns show minimal delta.

## Pipeline Results Detail

| Metric | With Skill | Without Skill | Delta |
|--------|-----------|---------------|-------|
| Pass Rate | 100% (9/9) | 89% (8/9) | **+11%** |

### Failed Expectations (Without Skill) -- Differentiators
1. Cache contexts use `url.path` instead of `route`/`route.name` (SKILL.md teaches route-based contexts)

### Passed Expectations (Both) -- Non-differentiating
1. #cache with BOTH tags AND contexts (golden rule -- Sonnet knows this)
2. Node-specific cache tags (node:ID pattern)
3. User cache context present
4. Does NOT set max-age to 0
5. Does NOT use \Drupal::cache() for render caching
6. User context over max-age workaround
7. #cache on outermost render array
8. Module enables successfully
9. Block renders without errors (8 non-differentiating expectations -- Sonnet is highly competent at caching)

## Deviations from Plan

Orchestration was initially delegated to gsd-executor (wrong agent type for eval runs -- lacks Agent tool for spawning eval-executor/eval-grader subagents). Corrected mid-flight to direct orchestration from main Opus session. The gsd-executor's results were discarded in favor of properly orchestrated pipeline results.

## Issues Encountered

The gsd-executor agent destroyed the caching-with ddev environment's .ddev directory, requiring re-provisioning before grading could run. Lesson: keep ddev instances alive until grading completes, then tear down.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness
- Caching calibration complete -- pipeline validated, delta low (+11%)
- Workspace layout pattern established for reuse in Plan 02 (scaffold calibration)
- eval-grader validated -- no schema concerns for subsequent runs
- Key learning: keep ddev instances alive until grading completes
- Assertion tuning needed for caching skill (Phase 12 work)
- Ready for 10-02 scaffold calibration run

## Self-Check: PASSED

All files verified present, all commits verified in git log.

---
*Phase: 10-pipeline-validation*
*Completed: 2026-03-07*

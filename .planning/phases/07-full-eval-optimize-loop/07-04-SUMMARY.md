---
phase: 07-full-eval-optimize-loop
plan: 04
subsystem: eval
tags: [eval, benchmark, sonnet, ddev, e2e, agent-browser]

# Dependency graph
requires:
  - phase: 07-02
    provides: E2E eval infrastructure (e2e-assert.sh, agent-browser patterns)
  - phase: 07-03
    provides: New evals.json files for all 5 skills evaluated here
provides:
  - Graded benchmarks for routing-controllers, forms-api, plugins-blocks, config-storage, access-security
  - With-skill vs without-skill delta data for 5 skills
  - E2E-verified eval runs with transcripts and output files
affects: [07-05, 07-06, 07-07, analysis, optimization]

# Tech tracking
tech-stack:
  added: []
  patterns: [eval-pipeline-sequential, single-env-dual-module, e2e-assert-with-port-detection]

key-files:
  created:
    - drupal-routing-controllers-workspace/iteration-1/benchmark.json
    - drupal-forms-api-workspace/iteration-1/benchmark.json
    - drupal-plugins-blocks-workspace/iteration-1/benchmark.json
    - drupal-config-storage-workspace/iteration-1/benchmark.json
    - drupal-access-security-workspace/iteration-1/benchmark.json
  modified: []

key-decisions:
  - "All 5 skills show 0% delta on Sonnet -- standard Drupal patterns well-covered in training data"
  - "Used single ddev env per skill with both with/without modules installed simultaneously"
  - "ddev-router health check failures recoverable with docker restart + retry"
  - "Port-based access needed for E2E: ddev assigns dynamic ports per instance"
  - "Config eval langcode expectation fails for both with/without -- Drupal auto-adds it on install"

patterns-established:
  - "Single-env dual-module: install both with/without modules in same ddev instance to save RAM"
  - "Port detection: use docker port to find actual mapped ports for E2E assertions"
  - "Drush uli with --uri flag: must match actual ddev port for agent-browser login"

requirements-completed: [FULL-03, FULL-04]

# Metrics
duration: 49min
completed: 2026-03-06
---

# Phase 7 Plan 4: Eval Batch 1 Summary

**5 skill evals (routing, forms, blocks, config, access) all show 0% delta on Sonnet -- standard Drupal patterns are well-known baseline knowledge**

## Performance

- **Duration:** 49 min
- **Started:** 2026-03-06T10:51:07Z
- **Completed:** 2026-03-06T11:40:07Z
- **Tasks:** 2
- **Files modified:** 70

## Accomplishments
- Ran 5 skills through full eval pipeline: setup ddev, run headless Sonnet with/without skill, grade against expectations, teardown
- E2E assertions verified for all 5 skills: JSON endpoint, form rendering, block placement, config persistence, access control (403/200)
- All 5 benchmarks show 0% delta -- Sonnet baseline is already competent at routing, forms, blocks, config, and access patterns
- Key insight: Skills add most value for patterns absent from training data (caching golden rule, D11 compat quirks), not standard Drupal API patterns

## Eval Results

| Skill | With | Without | Delta | Notes |
|-------|------|---------|-------|-------|
| routing-controllers | 8/8 (100%) | 8/8 (100%) | +0% | DI patterns well-known |
| forms-api | 8/8 (100%) | 8/8 (100%) | +0% | ConfigFormBase well-known |
| plugins-blocks | 8/8 (100%) | 8/8 (100%) | +0% | Plugin DI well-known |
| config-storage | 8/9 (89%) | 8/9 (89%) | +0% | Both miss langcode |
| access-security | 7/7 (100%) | 7/7 (100%) | +0% | AccessResult well-known |

## Task Commits

Each task was committed atomically:

1. **Task 1: Run eval sub-batch A (routing, forms, blocks)** - `a5f2684` (feat)
2. **Task 2: Run eval sub-batch B (config, access)** - `a9d4326` (feat)

## Files Created/Modified
- `drupal-routing-controllers-workspace/iteration-1/` - Full eval workspace with benchmark, grading, transcripts, outputs
- `drupal-forms-api-workspace/iteration-1/` - Full eval workspace
- `drupal-plugins-blocks-workspace/iteration-1/` - Full eval workspace
- `drupal-config-storage-workspace/iteration-1/` - Full eval workspace
- `drupal-access-security-workspace/iteration-1/` - Full eval workspace

## Decisions Made
- All 5 skills show 0% delta on Sonnet -- confirms phase 6 finding that skills add most value for training-data-absent patterns
- Used single ddev env per skill (with both modules) instead of 2 separate envs to save RAM and time
- ddev-router health check failures are transient -- recoverable with docker restart + retry
- E2E assertions need port-aware URLs: ddev assigns dynamic ports when router has issues
- Config eval's langcode expectation reveals a discrimination gap -- both versions fail it equally

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] ddev-router health check failures**
- **Found during:** Task 1 (plugins-blocks) and Task 2 (access-security)
- **Issue:** ddev-router failed health check on start, blocking env setup
- **Fix:** docker restart ddev-router, wait for healthy, then teardown/retry setup
- **Verification:** Subsequent ddev start succeeds after router recovery
- **Impact:** Added ~2 min delay per occurrence

**2. [Rule 3 - Blocking] Dynamic port mapping for E2E assertions**
- **Found during:** Task 2 (config-storage)
- **Issue:** e2e-assert.sh uses default HTTPS but ddev assigns non-standard ports when router is in recovery mode
- **Fix:** Used docker port command to detect actual mapped ports, then accessed via correct port in agent-browser
- **Verification:** All E2E assertions pass with port-aware URLs

---

**Total deviations:** 2 auto-fixed (both blocking issues)
**Impact on plan:** Infrastructure quirks added ~5 min total. No scope creep. All planned work completed.

## Issues Encountered
- Stale ddev instances from prior work consumed RAM -- cleaned 6 instances before starting
- ddev-router intermittently fails health checks when creating new ddev instances rapidly
- agent-browser --ignore-https-errors flag only works on first session creation, not subsequent opens

## Next Phase Readiness
- 5 more skills need evals (07-05: theming, database, testing, batch-queue-cron, views)
- 0% delta pattern suggests evals need more discriminating expectations targeting skill-specific wrong-way callouts
- Phase 7 optimization plans (07-06, 07-07) should focus on improving eval discrimination for these standard-pattern skills

---
*Phase: 07-full-eval-optimize-loop*
*Completed: 2026-03-06*

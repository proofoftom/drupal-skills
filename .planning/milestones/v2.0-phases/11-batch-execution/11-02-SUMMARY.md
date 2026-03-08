---
plan: "11-02"
status: complete
started: 2026-03-07
completed: 2026-03-07
---

## Summary

Ran the eval pipeline for drupal-access-security across 2 iterations (4 total runs). Iteration 1 revealed a biased prompt; iteration 2 tested the fix.

## Results

### Run 1 (biased prompt — included "Use AccessResult objects")

| Config | Pass Rate | Passed | Failed | Total |
|--------|-----------|--------|--------|-------|
| with_skill | 0.89 | 8 | 1 | 9 |
| without_skill | 0.89 | 8 | 1 | 9 |
| **Delta** | **+0.00** | | | |

Both failed expectation 2 (`_permission` route requirement) — the prompt's "Use AccessResult objects" hint steered both toward `_custom_access`.

### Run 2 (debiased prompt — AccessResult hint removed)

| Config | Pass Rate | Passed | Failed | Total |
|--------|-----------|--------|--------|-------|
| with_skill | 1.00 | 9 | 0 | 9 |
| without_skill | 1.00 | 9 | 0 | 9 |
| **Delta** | **+0.00** | | | |

Both correctly chose `_permission`. Delta remains 0 — Sonnet knows this pattern without skill guidance.

### Aggregate (2 runs per config)

| Config | Mean | StdDev | Min | Max |
|--------|------|--------|-----|-----|
| with_skill | 0.945 | 0.055 | 0.89 | 1.00 |
| without_skill | 0.945 | 0.055 | 0.89 | 1.00 |
| **Delta** | **+0.00** | | | |

## Analysis

This eval scenario (simple permission + route) tests patterns Sonnet already knows well. The skill's value for access-security lies in non-obvious knowledge:
- Orphaned permission strings silently cause 403 (no error/warning)
- Cache-aware AccessResult patterns (bare `allowed()` without cache contexts caches for ALL users)
- Ownership-based access with proper cache metadata
- Entity access handler patterns

A more complex eval scenario testing these traps would better differentiate.

## Prompt Change

Removed "Use AccessResult objects for access checking, not bare booleans." from evals.json — it biased toward `_custom_access` over the correct `_permission` pattern. Updated eval_metadata.json accordingly.

## Key Files

### Created
- `.planning/phases/11-batch-execution/workspaces/drupal-access-security-workspace/iteration-1/benchmark.json` (4 runs)
- `eval-restricted-reports/with_skill/run-{1,2}/grading.json`
- `eval-restricted-reports/without_skill/run-{1,2}/grading.json`

### Modified
- `skills/drupal-access-security/evals/evals.json` (prompt debiased)

## Self-Check: PASSED
- [x] benchmark.json exists with valid schema (4 runs)
- [x] 4 grading.json files (2 configs x 2 runs)
- [x] Each grading.json has 9 expectations
- [x] ddev instances torn down

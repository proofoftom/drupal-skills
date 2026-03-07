# Requirements: v2.0 Eval & Optimization Loop

**Defined:** 2026-03-07
**Core Value:** Empirically prove all 13 Drupal skills produce measurably better code than baseline Sonnet, with clean data from a robust autonomous eval pipeline.

## Infrastructure

- [x] **INFRA-01**: eval-executor subagent with `model: sonnet` in frontmatter for controlled A/B execution
- [x] **INFRA-02**: eval-grader subagent following skill-creator grader.md, producing compliant grading.json
- [x] **INFRA-03**: Fresh Drupal 10 ddev setup script (replaces os-kg-based setup) with auto-retry for ddev-router failures
- [x] **INFRA-04**: eval-browser subagent using agent-browser + drush uli for E2E/UAT verification

## Eval Content

- [x] **EVAL-01**: All 13 eval prompts rewritten for fresh Drupal 10 instances (not os-kg tasks)
- [x] **EVAL-02**: All 13 evals.json have differentiating assertions targeting SKILL.md non-obvious patterns (carried from v1.0 07-06, may need adjustment for new prompts)

## Pipeline

- [x] **PIPE-01**: Pipeline validated end-to-end on 2-3 calibration skills (caching, scaffold) before batch run
- [ ] **PIPE-02**: Batch orchestrator runs all 13 skills through eval with minimal manual intervention (3-4 skills per session)
- [ ] **PIPE-03**: Each skill produces grading.json, benchmark.json in correct workspace layout

## Analysis

- [ ] **ANLZ-01**: All 13 skills have graded benchmarks showing with-skill vs without-skill delta
- [ ] **ANLZ-02**: Skills classified into tiers: High Delta (>15%), Moderate (5-15%), Low (<5%)
- [ ] **ANLZ-03**: Skills with weak deltas analyzed -- assertions tightened or skill content improved where feasible
- [ ] **ANLZ-04**: Final report with stabilized results, tier classifications, and overall verdict

## Carried from v1.0

- [ ] **CARRY-01**: FULL-05 -- Skills with weak deltas iterated on (carried from v1.0 Phase 7)
- [ ] **CARRY-02**: FULL-06 -- Final analysis with stabilized results (carried from v1.0 Phase 7)

## Out of Scope

| Feature | Reason |
|---------|--------|
| Description/trigger optimization | Deferred until content evals prove value |
| SKILL.md content changes | Locked unless eval data demands it |
| Multi-run variance analysis (3+ runs) | Start with 1 run per config; add if signal is unclear |
| Custom HTML viewer | Use skill-creator's generate_review.py |
| os-knowledge-garden environments | Replaced by fresh D10 |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| INFRA-01 | Phase 8 | Complete |
| INFRA-02 | Phase 8 | Complete |
| INFRA-03 | Phase 8 | Complete |
| INFRA-04 | Phase 8 | Complete |
| EVAL-01 | Phase 9 | Planned |
| EVAL-02 | Phase 9 | Planned |
| PIPE-01 | Phase 10 | Planned |
| PIPE-02 | Phase 11 | Planned |
| PIPE-03 | Phase 11 | Planned |
| ANLZ-01 | Phase 11 | Planned |
| ANLZ-02 | Phase 12 | Planned |
| ANLZ-03 | Phase 12 | Planned |
| ANLZ-04 | Phase 12 | Planned |
| CARRY-01 | Phase 12 | Planned |
| CARRY-02 | Phase 12 | Planned |

**Coverage:**
- v2.0 requirements: 15 total
- Mapped to phases: 15
- Unmapped: 0

---
*Requirements defined: 2026-03-07*

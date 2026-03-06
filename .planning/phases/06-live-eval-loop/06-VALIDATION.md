---
phase: 6
slug: live-eval-loop
status: draft
nyquist_compliant: true
wave_0_complete: true
created: 2026-03-05
---

# Phase 6 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Shell scripts + skill-creator grader/aggregator |
| **Config file** | eval/setup-drupal-env.sh, eval/teardown-drupal-env.sh |
| **Quick run command** | `bash eval/setup-drupal-env.sh test && bash eval/teardown-drupal-env.sh test` |
| **Full suite command** | Run all 8 eval subagents + graders + aggregation |
| **Estimated runtime** | ~30 min (ddev setup + agent execution + grading) |

---

## Sampling Rate

- **After every task commit:** Verify file creation (evals.json valid JSON, scripts executable)
- **After every plan wave:** Run setup/teardown smoke test
- **Before `/gsd:verify-work`:** All 8 grading.json files exist with valid scores
- **Max feedback latency:** ~5 seconds for file checks, ~5 min for ddev smoke test

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 06-01-01 | 01 | 1 | evals.json | file check | `python -m json.tool < skills/drupal-module-scaffold/evals/evals.json` | N/A | pending |
| 06-01-02 | 01 | 1 | setup script | smoke test | `bash eval/setup-drupal-env.sh test && bash eval/teardown-drupal-env.sh test` | N/A | pending |
| 06-02-01 | 02 | 1 | eval runs | output check | `ls *-workspace/iteration-1/*/with_skill/run-1/grading.json` | N/A | pending |
| 06-02-02 | 02 | 1 | benchmarks | file check | `ls *-workspace/iteration-1/benchmark.json` | N/A | pending |
| 06-03-01 | 03 | 1 | analysis | file check | `test -f eval/analysis-iteration-1.md` | N/A | pending |

*Status: pending / green / red / flaky*

---

## Wave 0 Requirements

*Existing infrastructure covers all phase requirements:*
- ddev already installed and working
- os-knowledge-garden already cloned with ddev config
- skill-creator grader/aggregator/viewer scripts already exist

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Eval output quality | Graded code correctness | Human judgment needed for edge cases | Review HTML viewer for each skill |
| Benchmark interpretation | Meaningful skill improvement | Statistical significance requires human review | Compare with/without pass rates in analysis |

---

## Validation Sign-Off

- [x] All tasks have automated verify or Wave 0 dependencies
- [x] Sampling continuity: no 3 consecutive tasks without automated verify
- [x] Wave 0 covers all MISSING references
- [x] No watch-mode flags
- [x] Feedback latency < 300s
- [x] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

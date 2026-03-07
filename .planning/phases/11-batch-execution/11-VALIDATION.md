---
phase: 11
slug: batch-execution
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-07
---

# Phase 11 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Shell-based validation (jq + bash assertions) |
| **Config file** | None — validation is inline during pipeline execution |
| **Quick run command** | `jq '.summary.pass_rate' <path>/grading.json` |
| **Full suite command** | `for f in $(find .planning/phases/11-batch-execution/workspaces/ -name "benchmark.json"); do echo "$f: delta=$(jq '.run_summary.delta.pass_rate' "$f")"; done` |
| **Estimated runtime** | ~5 seconds |

---

## Sampling Rate

- **After every task commit:** Run `jq schema check` on latest grading.json
- **After every plan wave:** Validate all benchmark.json files in the batch have valid schema and deltas
- **Before `/gsd:verify-work`:** `find workspaces/ -name "benchmark.json" | wc -l` returns 13; all have valid schema
- **Max feedback latency:** 5 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 11-01-01 | 01 | 1 | PIPE-02 | integration | `mkdir -p workspaces/ && ls` | ❌ W0 | ⬜ pending |
| 11-01-02 | 01 | 1 | ANLZ-01 | integration | `test -f workspaces/drupal-caching-workspace/iteration-1/benchmark.json` | ❌ W0 | ⬜ pending |
| 11-01-03 | 01 | 1 | ANLZ-01 | integration | `test -f workspaces/drupal-module-scaffold-workspace/iteration-1/benchmark.json` | ❌ W0 | ⬜ pending |
| 11-02-01 | 02 | 1 | PIPE-02, PIPE-03 | unit | `jq 'has("expectations") and has("summary")' grading.json` | ❌ W0 | ⬜ pending |
| 11-02-02 | 02 | 1 | ANLZ-01 | integration | `jq 'has("metadata") and has("runs") and has("run_summary")' benchmark.json` | ❌ W0 | ⬜ pending |
| 11-03-01 | 03 | 2 | PIPE-02, PIPE-03 | unit | `jq 'has("expectations") and has("summary")' grading.json` | ❌ W0 | ⬜ pending |
| 11-03-02 | 03 | 2 | ANLZ-01 | integration | `jq 'has("metadata") and has("runs") and has("run_summary")' benchmark.json` | ❌ W0 | ⬜ pending |
| 11-04-01 | 04 | 3 | PIPE-02, PIPE-03 | unit | `jq 'has("expectations") and has("summary")' grading.json` | ❌ W0 | ⬜ pending |
| 11-04-02 | 04 | 3 | ANLZ-01 | integration | `jq 'has("metadata") and has("runs") and has("run_summary")' benchmark.json` | ❌ W0 | ⬜ pending |
| 11-04-03 | 04 | 3 | ANLZ-01 | integration | `find workspaces/ -name "benchmark.json" \| wc -l` = 13 | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] Phase 11 workspace directory tree for all 13 skills
- [ ] Phase 10 benchmark.json files copied for caching and scaffold
- [ ] eval_metadata.json populated per skill from evals.json
- [ ] Validation commands embedded in plan tasks

*Covered by Plan 01 (setup + calibration copy).*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Executor generates correct Drupal module | PIPE-03 | AI code generation is non-deterministic | Review grader evidence in grading.json |
| Grader accurately assesses expectations | PIPE-03 | Grading requires code comprehension | Spot-check grading.json evidence fields |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 5s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

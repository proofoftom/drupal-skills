---
phase: 8
slug: eval-optimization-loop
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-06
---

# Phase 8 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Shell scripts (bash) + Agent tool integration tests |
| **Config file** | none — Wave 0 creates scripts |
| **Quick run command** | `bash eval/setup-fresh-drupal10.sh smoke-test && ddev delete -O -y d10-smoke-test` |
| **Full suite command** | Run all 5 validation scenarios sequentially |
| **Estimated runtime** | ~120 seconds |

---

## Sampling Rate

- **After every task commit:** Run `bash eval/setup-fresh-drupal10.sh smoke-test` (fastest validation)
- **After every plan wave:** Run all 5 validation scenarios
- **Before `/gsd:verify-work`:** Full suite must be green
- **Max feedback latency:** 120 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 08-01-01 | 01 | 1 | INFRA-03 | smoke | `bash eval/setup-fresh-drupal10.sh smoke-test` | ❌ W0 | ⬜ pending |
| 08-01-02 | 01 | 1 | INFRA-01 | integration | Spawn eval-executor, verify .info.yml created | ❌ W0 | ⬜ pending |
| 08-01-03 | 01 | 1 | INFRA-02 | integration | Spawn eval-grader, verify grading.json schema | ❌ W0 | ⬜ pending |
| 08-01-04 | 01 | 1 | INFRA-04 | integration | Spawn eval-browser, verify drush uli + snapshot | ❌ W0 | ⬜ pending |
| 08-01-05 | 01 | 1 | INFRA-01 | integration | Run with/without skill, verify knowledge isolation | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `eval/setup-fresh-drupal10.sh` — fresh D10 ddev provisioning script
- [ ] `.claude/agents/eval-executor.md` — executor subagent definition
- [ ] `.claude/agents/eval-grader.md` — grader subagent definition
- [ ] `.claude/agents/eval-browser.md` — browser E2E subagent definition
- [ ] `.claude/agents/` directory — does not exist yet

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Knowledge isolation A/B | INFRA-01 | Requires spawning two agents and comparing output | Run caching eval with/without skill, compare generated code for cache-specific patterns |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 120s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

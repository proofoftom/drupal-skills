---
phase: 8
slug: eval-optimization-loop
status: draft
nyquist_compliant: true
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

Task IDs follow the format `{plan}-{task}` matching actual plan structure.

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 01-T1 | 01 | 1 | INFRA-01,02,04 | static | `test -f .claude/agents/eval-executor.md && grep -q "model: sonnet" ...` | Wave 0 | pending |
| 01-T2 | 01 | 1 | INFRA-03 | static | `test -x eval/setup-fresh-drupal10.sh && bash -n eval/setup-fresh-drupal10.sh` | Wave 0 | pending |
| 01-T3 | 01 | 1 | INFRA-03 | static | `bash -n eval/teardown-drupal-env.sh && grep -q "d10-" ...` | Wave 0 | pending |
| 02-T1 | 02 | 2 | INFRA-01,03 | integration | `test -f /tmp/.../test_smoke.info.yml && ddev drush pm:list --filter=test_smoke` | Depends 01 | pending |
| 02-T2 | 02 | 2 | INFRA-02,04 | integration | `jq -e '.expectations \| length > 0' /tmp/.../grading.json` + skills: test | Depends 01 | pending |

*Status: pending / green / red / flaky*

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
| skills: frontmatter resolution | INFRA-01 | Empirical test of Claude Code feature with non-standard skill paths | Plan 02 Task 2 Step 3 tests this and documents the result |

---

## Validation Sign-Off

- [x] All tasks have `<automated>` verify or Wave 0 dependencies
- [x] Sampling continuity: no 3 consecutive tasks without automated verify
- [x] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [x] Feedback latency < 120s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

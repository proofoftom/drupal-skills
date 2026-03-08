---
phase: 12
slug: analysis-optimization
status: draft
nyquist_compliant: true
wave_0_complete: true
created: 2026-03-07
---

# Phase 12 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Custom eval pipeline (headless `claude -p` + eval-grader + eval-browser agents) |
| **Config file** | `.claude/agents/eval-grader.md`, `.claude/agents/eval-browser.md` |
| **Quick run command** | Single skill eval: setup + code gen + grade |
| **Full suite command** | All 7 affected skills through pipeline |
| **Estimated runtime** | ~15 min per skill (setup + 2 code gen + browser + grade) |

---

## Sampling Rate

- **After every task commit:** Verify deliverable exists (skill file, patched content, eval prompt, grade JSON)
- **After every plan wave:** Compare re-run results against Phase 11 baselines
- **Before `/gsd:verify-work`:** Final report exists with all 13 skills classified, all re-runs complete
- **Max feedback latency:** ~15 min (single skill eval cycle)

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| Content fixes | 01 | 1 | ANLZ-03, CARRY-01 | manual | Verify SKILL.md patches, new skill, eval prompt fix | N/A | pending |
| Harder evals | 02 | 1 | ANLZ-03, CARRY-01 | manual | Verify evals.json rewritten for obscure patterns | N/A | pending |
| Re-runs | 03 | 2 | ANLZ-02, ANLZ-03 | pipeline | Eval pipeline produces grade JSONs | N/A | pending |
| Final report | 04 | 3 | ANLZ-04, CARRY-02 | manual | Verify report with tiers, verdict, all 13 skills | N/A | pending |

*Status: pending / green / red / flaky*

---

## Wave 0 Requirements

*Existing infrastructure covers all phase requirements.* No new test framework or config needed — eval pipeline from Phases 8-11 is fully operational.

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Tier classification correct | ANLZ-02 | Document deliverable, not code | Review re-run results, verify tier thresholds applied correctly |
| Iteration attempted | ANLZ-03, CARRY-01 | Process verification | Confirm SKILL.md patches applied, harder evals written, re-runs completed |
| Final report complete | ANLZ-04, CARRY-02 | Document deliverable | Verify report has all 13 skills, stabilized tiers, overall verdict |
| HTML viewers available | ANLZ-04 | Tool output | Run generate_review.py or verify markdown report serves as human-readable alternative |

**Justification:** Phase 12 requirements are analysis and reporting tasks, not code functionality. The eval pipeline IS the automated test framework for skill quality; the phase deliverables are documents verified by review.

---

## Validation Sign-Off

- [x] All tasks have automated verify or Wave 0 dependencies
- [x] Sampling continuity: no 3 consecutive tasks without automated verify
- [x] Wave 0 covers all MISSING references
- [x] No watch-mode flags
- [x] Feedback latency < 15 min
- [x] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

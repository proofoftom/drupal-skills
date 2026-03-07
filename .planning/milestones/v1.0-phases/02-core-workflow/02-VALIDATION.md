---
phase: 2
slug: core-workflow
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-05
---

# Phase 2 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | skill-creator eval loop (anthropics/skills) |
| **Config file** | evals/evals.json per skill directory |
| **Quick run command** | `Skill(skill="skill-creator", args="eval <skill-dir>")` |
| **Full suite command** | `python -m scripts.aggregate_benchmark` |
| **Estimated runtime** | ~120 seconds per eval |

---

## Sampling Rate

- **After every task commit:** Manual review of skill structure + single eval run
- **After every plan wave:** Full eval suite for all four skills
- **Before `/gsd:verify-work`:** Full suite must be green
- **Max feedback latency:** 120 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 02-01-01 | 01 | 1 | CORE-01 | eval | Eval: "Create a settings form for module X" | Wave 0 | pending |
| 02-02-01 | 02 | 1 | CORE-03 | eval | Eval: "Store a setting with config schema" | Wave 0 | pending |
| 02-03-01 | 03 | 2 | CORE-02 | eval | Eval: "Create a custom block plugin" | Wave 0 | pending |
| 02-04-01 | 04 | 2 | CORE-04 | eval | Eval: "Add permission-based access to a route" | Wave 0 | pending |
| 02-XX-XX | all | all | SKIL-01..07 | manual | Visual inspection of skill anatomy | N/A | pending |

*Status: pending · green · red · flaky*

---

## Wave 0 Requirements

- [ ] `evals/evals.json` for drupal-forms-api — eval prompts for form creation and altering
- [ ] `evals/evals.json` for drupal-config-storage — eval prompts for config/state/tempstore usage
- [ ] `evals/evals.json` for drupal-plugins-blocks — eval prompts for block and custom plugin type creation
- [ ] `evals/evals.json` for drupal-access-security — eval prompts for permission and access control

*If none: "Existing infrastructure covers all phase requirements."*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Skill follows SKILL.md anatomy | SKIL-01 | Structural review | Check frontmatter, <500 lines, references/ dir |
| Decision-guide format | SKIL-02 | Pattern review | Verify decision trees present |
| Wrong-way callouts present | SKIL-03 | Content review | Grep for "WRONG:" markers |
| D10/D11 dual syntax | SKIL-05 | Content review | Inspect code examples for both annotation and attribute syntax |
| Self-contained directory | SKIL-06 | Structural review | Verify no external file dependencies |
| Cross-references degrade gracefully | SKIL-07 | Content review | Check "if installed" phrasing |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 120s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

---
phase: 1
slug: foundations
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-05
---

# Phase 1 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | skill-creator eval loop (anthropics/skills) |
| **Config file** | evals/evals.json per skill directory |
| **Quick run command** | `Skill(skill="skill-creator", args="eval <skill-dir>")` |
| **Full suite command** | `python -m scripts.aggregate_benchmark` |
| **Estimated runtime** | ~60 seconds per eval prompt |

---

## Sampling Rate

- **After every task commit:** Manual review of skill structure + single eval run
- **After every plan wave:** Full eval suite for all three skills
- **Before `/gsd:verify-work`:** Full suite must show improvement over baseline
- **Max feedback latency:** 60 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 1-01-01 | 01 | 1 | SKIL-01 | manual-only | Visual inspection of SKILL.md anatomy | N/A | pending |
| 1-01-02 | 01 | 1 | SKIL-02 | manual-only | Review for decision-guide format | N/A | pending |
| 1-01-03 | 01 | 1 | SKIL-03 | manual-only | Grep for "WRONG:" callouts | N/A | pending |
| 1-01-04 | 01 | 1 | SKIL-04 | eval | Eval: check paired PHP+YAML in output | Wave 0 | pending |
| 1-01-05 | 01 | 1 | SKIL-05 | manual-only | Inspect D10/D11 dual syntax examples | N/A | pending |
| 1-01-06 | 01 | 1 | SKIL-06 | manual-only | Verify no external file dependencies | N/A | pending |
| 1-01-07 | 01 | 1 | SKIL-07 | manual-only | Check cross-reference language | N/A | pending |
| 1-02-01 | 01 | 1 | FOUN-01 | eval | Eval: "Create a Drupal module called X" | Wave 0 | pending |
| 1-02-02 | 02 | 2 | FOUN-02 | eval | Eval: "Add a page at /foo to module X" | Wave 0 | pending |
| 1-02-03 | 03 | 3 | FOUN-03 | eval | Eval: "Create a custom content entity" | Wave 0 | pending |

*Status: pending / green / red / flaky*

---

## Wave 0 Requirements

- [ ] `evals/evals.json` for drupal-module-scaffold — eval prompts grounded in os-knowledge-garden
- [ ] `evals/evals.json` for drupal-routing-controllers — eval prompts for route/controller/service creation
- [ ] `evals/evals.json` for drupal-entities-fields — eval prompts for content and config entity creation
- [ ] Skill-creator scripts available in workspace for running evals

*Note: Eval infrastructure setup is part of Wave 0 planning.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Skill anatomy (<500 lines, frontmatter, references/) | SKIL-01 | Structural review of markdown files | Count lines in SKILL.md body, verify YAML frontmatter, check references/ dir exists |
| Decision-guide format | SKIL-02 | Qualitative review of content style | Verify decision trees present, not API reference style |
| Wrong-way callouts | SKIL-03 | Content quality check | Grep for "WRONG:" or equivalent markers, verify correct alternatives shown |
| D10/D11 dual syntax | SKIL-05 | Code example review | Verify annotation and attribute examples shown side-by-side for entity types |
| Self-contained directory | SKIL-06 | Directory structure check | Verify no imports/references to files outside skill directory |
| Cross-references degrade gracefully | SKIL-07 | Language review | Check "if available" or "if installed" phrasing on cross-references |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 60s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

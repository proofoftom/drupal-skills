---
phase: 5
slug: eval-optimization-packaging
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-05
---

# Phase 5 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Manual eval loop (skill-creator methodology) + bash smoke tests |
| **Config file** | None — manual process with smoke scripts |
| **Quick run command** | `ls skills/drupal-* | wc -l` (should be 13) |
| **Full suite command** | `bash install.sh && ls ~/.claude/skills/drupal-* | wc -l` |
| **Estimated runtime** | ~5 seconds (smoke); manual eval varies |

---

## Sampling Rate

- **After every task commit:** Run `ls skills/drupal-* | wc -l` for packaging tasks
- **After every plan wave:** Run representative eval prompts and verify install.sh
- **Before `/gsd:verify-work`:** Full suite must be green — all 13 skills verified
- **Max feedback latency:** 5 seconds (smoke tests)

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 05-01-01 | 01 | 1 | EVAL-01 | manual | Compare Claude output with/without skills | N/A | pending |
| 05-01-02 | 01 | 1 | EVAL-02 | manual | Verify prompts reference real module patterns | N/A | pending |
| 05-01-03 | 01 | 1 | EVAL-03 | manual | Test activation with diverse prompts | N/A | pending |
| 05-01-04 | 01 | 1 | EVAL-04 | manual | Test cross-domain prompts | N/A | pending |
| 05-02-01 | 02 | 2 | PACK-01 | smoke | `ls skills/drupal-* \| wc -l` | Wave 0 | pending |
| 05-02-02 | 02 | 2 | PACK-02 | smoke | `bash install.sh && ls ~/.claude/skills/drupal-*` | Wave 0 | pending |
| 05-02-03 | 02 | 2 | PACK-03 | manual | Review README content | N/A | pending |

*Status: pending · green · red · flaky*

---

## Wave 0 Requirements

- [ ] `install.sh` — does not exist yet, needed for PACK-02
- [ ] `README.md` — does not exist yet, needed for PACK-03

*Existing skills/ directory structure covers PACK-01 baseline.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Skill improvement vs baseline | EVAL-01 | Requires LLM output comparison | Prompt Claude with/without skills, compare quality |
| Eval prompts grounded in real tasks | EVAL-02 | Requires human judgment of relevance | Verify prompts use os-knowledge-garden patterns |
| Trigger description optimization | EVAL-03 | Requires testing skill activation patterns | Test diverse prompts, verify correct skill(s) activate |
| Multi-skill coherent output | EVAL-04 | Requires LLM output quality judgment | Test cross-domain prompts, verify coherent integration |
| README completeness | PACK-03 | Requires human review | Verify inventory, install instructions, usage examples |

---

## Validation Sign-Off

- [ ] All tasks have automated verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 5s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

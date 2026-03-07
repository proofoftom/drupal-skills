---
phase: 3
slug: presentation-and-quality
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-05
---

# Phase 3 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Manual validation via automated shell checks |
| **Config file** | None — skills are markdown files, not executable code |
| **Quick run command** | `wc -l < skills/drupal-*/SKILL.md` |
| **Full suite command** | `for f in skills/drupal-*/SKILL.md; do echo "$f:"; wc -l < "$f"; grep -c "WRONG:" "$f"; grep -c "if installed\|if available" "$f"; done` |
| **Estimated runtime** | ~2 seconds |

---

## Sampling Rate

- **After every task commit:** Run `wc -l < skills/drupal-*/SKILL.md`
- **After every plan wave:** Run full suite command
- **Before `/gsd:verify-work`:** Full suite must be green
- **Max feedback latency:** 2 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 03-01-01 | 01 | 1 | PRES-01 | file check | `test -f skills/drupal-theming/SKILL.md && wc -l < skills/drupal-theming/SKILL.md` | W0 | pending |
| 03-01-02 | 01 | 1 | PRES-01 | file check | `test -f skills/drupal-theming/references/js-ajax.md` | W0 | pending |
| 03-02-01 | 02 | 1 | PRES-02 | file check | `test -f skills/drupal-caching/SKILL.md && wc -l < skills/drupal-caching/SKILL.md` | W0 | pending |
| 03-03-01 | 03 | 2 | PRES-04 | file check | `test -f skills/drupal-database-api/SKILL.md && wc -l < skills/drupal-database-api/SKILL.md` | W0 | pending |
| 03-04-01 | 04 | 2 | PRES-03 | file check | `test -f skills/drupal-testing/SKILL.md && wc -l < skills/drupal-testing/SKILL.md` | W0 | pending |

*Status: pending / green / red / flaky*

---

## Wave 0 Requirements

- [ ] `skills/drupal-theming/` directory and SKILL.md stub
- [ ] `skills/drupal-theming/references/js-ajax.md` stub
- [ ] `skills/drupal-caching/` directory and SKILL.md stub
- [ ] `skills/drupal-testing/` directory and SKILL.md stub
- [ ] `skills/drupal-database-api/` directory and SKILL.md stub

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Skill produces correct Drupal code when triggered | PRES-01 through PRES-04 | Requires Claude interaction to test skill triggering | Use skill-creator eval to verify each skill triggers correctly and produces valid patterns |
| Wrong-way callouts minimum 3+ per skill | SKIL-04 | Content quality check | `grep -c "WRONG:" skills/drupal-*/SKILL.md` — verify each >= 3 |
| Cross-references degrade gracefully | SKIL-06 | Behavioral check | Verify `if installed`/`if available` guards on cross-references |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 2s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

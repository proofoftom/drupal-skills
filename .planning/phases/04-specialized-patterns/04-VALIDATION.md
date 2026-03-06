---
phase: 4
slug: specialized-patterns
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-05
---

# Phase 4 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Manual validation via automated shell checks |
| **Config file** | None — skills are markdown files, not executable code |
| **Quick run command** | `wc -l < skills/drupal-*/SKILL.md` |
| **Full suite command** | `for f in skills/drupal-views-dev/SKILL.md skills/drupal-batch-queue-cron/SKILL.md; do echo "$f:"; wc -l < "$f"; grep -c "WRONG:" "$f"; grep -c "if installed\|if available" "$f"; done` |
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
| 04-01-01 | 01 | 1 | SPEC-01 | automated check | `test -f skills/drupal-views-dev/SKILL.md && wc -l < skills/drupal-views-dev/SKILL.md` | ❌ W0 | ⬜ pending |
| 04-02-01 | 02 | 1 | SPEC-02 | automated check | `test -f skills/drupal-batch-queue-cron/SKILL.md && wc -l < skills/drupal-batch-queue-cron/SKILL.md` | ❌ W0 | ⬜ pending |
| 04-02-02 | 02 | 1 | SPEC-02 | automated check | `test -f skills/drupal-batch-queue-cron/references/logging-mail-tokens.md` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `skills/drupal-views-dev/` directory and SKILL.md
- [ ] `skills/drupal-batch-queue-cron/` directory and SKILL.md
- [ ] `skills/drupal-batch-queue-cron/references/logging-mail-tokens.md`

*All files created during execution — no pre-existing infrastructure needed.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Skill produces correct hook_views_data | SPEC-01 | Requires Claude interaction | Ask Claude to "expose a custom table to Views" and verify output |
| Skill produces correct QueueWorker | SPEC-02 | Requires Claude interaction | Ask Claude to "create a queue worker for processing imports" and verify output |
| SKIL-01 through SKIL-07 compliance | SPEC-01, SPEC-02 | Quality standards require review | Run skill-creator eval on both skills |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 2s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

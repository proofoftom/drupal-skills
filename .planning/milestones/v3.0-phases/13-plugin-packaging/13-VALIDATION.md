---
phase: 13
slug: plugin-packaging
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-07
---

# Phase 13 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Manual validation (bash scripts + claude CLI) |
| **Config file** | none — Wave 0 creates test script |
| **Quick run command** | `claude --plugin-dir . -p "What Drupal skills are available?"` |
| **Full suite command** | `bash eval/v3/test-auto-trigger.sh` |
| **Estimated runtime** | ~120 seconds (12 prompts x ~10s each) |

---

## Sampling Rate

- **After every task commit:** Run `claude --plugin-dir . -p "What Drupal skills are available?"`
- **After every plan wave:** Run `bash eval/v3/test-auto-trigger.sh`
- **Before `/gsd:verify-work`:** Full suite must be green
- **Max feedback latency:** 15 seconds (per quick run)

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| TBD | 01 | 0 | EVAL-01 | setup | Create `eval/v3/test-auto-trigger.sh` | ❌ W0 | ⬜ pending |
| TBD | 01 | 1 | PLUG-01 | smoke | `claude --plugin-dir . -p "List all available skills"` | N/A | ⬜ pending |
| TBD | 01 | 1 | PLUG-03 | manual | Code review: no duplication with SKILL.md | N/A | ⬜ pending |
| TBD | 01 | 1 | PLUG-04 | smoke | `./install.sh --help` shows deprecation | N/A | ⬜ pending |
| TBD | 01 | 2 | PLUG-02 | integration | Run 12 test prompts, count activations | ❌ W0 | ⬜ pending |
| TBD | 01 | 2 | EVAL-01 | integration | Same as PLUG-02 with structured grading | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `eval/v3/test-auto-trigger.sh` — script to run 12+ test prompts and measure activation rate
- [ ] Empirical test of `--plugin-dir` + `-p` compatibility — determines eval methodology

*If `--plugin-dir` + `-p` is incompatible, eval methodology must switch to interactive sessions.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| CLAUDE.md is minimal and non-redundant | PLUG-03 | Requires human judgement on "minimal" and "non-obvious" | Review each line against all 14 SKILL.md files; verify no duplication |
| Deprecation messaging is clear | PLUG-04 | UX quality assessment | Run `./install.sh --help` and `./install.sh --uninstall`; verify messaging |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 15s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

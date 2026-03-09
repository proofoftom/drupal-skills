---
phase: 19
slug: interactions-detail-panel-visual-polish
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-08
---

# Phase 19 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | PHPUnit (Drupal Kernel + Functional) + eval pipeline (headless haiku) |
| **Config file** | phpunit.xml in Drupal root (provided by ddev template) |
| **Quick run command** | `cd /tmp/d10-phase19-{variant}/web/modules/custom/group_ai_pm/js && npx vite build 2>/dev/null` |
| **Full suite command** | Full eval pipeline (static + runtime + browser assertions) |
| **Estimated runtime** | ~120 seconds |

---

## Sampling Rate

- **After every task commit:** Run `cd /tmp/d10-phase19-{variant}/web/modules/custom/group_ai_pm/js && npx vite build 2>/dev/null`
- **After every plan wave:** Run full eval pipeline (static + runtime assertions)
- **Before `/gsd:verify-work`:** Full suite must be green
- **Max feedback latency:** 120 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 19-01-01 | 01 | 1 | INTERACT-01 | browser eval | eval-browser checks `.gapm-panel` after click | ❌ W0 | ⬜ pending |
| 19-01-02 | 01 | 1 | INTERACT-02 | browser eval | eval-browser double-clicks title, types, presses Enter | ❌ W0 | ⬜ pending |
| 19-01-03 | 01 | 1 | INTERACT-03 | browser eval | eval-browser drags card, checks `.gapm-toast` on error | ❌ W0 | ⬜ pending |
| 19-01-04 | 01 | 1 | INTERACT-04 | browser eval | eval-browser right-clicks card, checks `.gapm-context-menu` | ❌ W0 | ⬜ pending |
| 19-01-05 | 01 | 1 | INTERACT-05 | browser eval | eval-browser selects filter, checks card count + URL | ❌ W0 | ⬜ pending |
| 19-01-06 | 01 | 1 | INTERACT-06 | static eval | Check CSS animation, ghostClass, chosenClass in config | ❌ W0 | ⬜ pending |
| 19-01-07 | 01 | 1 | INTERACT-07 | static eval | Check `.gapm-task-card--ghost` with opacity rule | ❌ W0 | ⬜ pending |
| 19-02-01 | 02 | 1 | VISUAL-01 | static eval | Check computed dueDate comparison + `--overdue` CSS | ❌ W0 | ⬜ pending |
| 19-02-02 | 02 | 1 | VISUAL-02 | static eval | Check AssigneeAvatar component + PHP pictureUrl serialization | ❌ W0 | ⬜ pending |
| 19-02-03 | 02 | 1 | VISUAL-03 | static eval | Check localStorage read/write in composable | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `eval/v4/phase-19-evals.json` — static assertion definitions targeting skill-driven patterns
- [ ] `eval/v4/phase-19-runtime-assertions.json` — drush-based functional checks
- [ ] Browser eval assertions for interactive features (panel, inline edit, context menu, filters)
- [ ] No new test framework infrastructure needed — Phase 18 pipeline is reusable

*Existing eval infrastructure covers framework requirements.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Drag feel (animation smoothness) | INTERACT-06 | Subjective visual quality | Drag a card, verify smooth lift/settle animation without jank |
| Context menu positioning | INTERACT-04 | Edge cases near viewport boundary | Right-click cards near bottom/right edge, verify menu stays on-screen |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 120s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

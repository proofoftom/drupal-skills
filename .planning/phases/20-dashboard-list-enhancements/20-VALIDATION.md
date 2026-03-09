---
phase: 20
slug: dashboard-list-enhancements
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-08
---

# Phase 20 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | PHPUnit (Drupal core test base) + eval pipeline (headless claude -p) |
| **Config file** | `phpunit.xml` (ddev instance) |
| **Quick run command** | `ddev exec phpunit --filter DashboardTest` |
| **Full suite command** | `ddev exec phpunit --group group_ai_pm` |
| **Estimated runtime** | ~30 seconds |

---

## Sampling Rate

- **After every task commit:** Run static assertions (grep, file existence)
- **After every plan wave:** Run runtime assertions (drush-based checks)
- **Before `/gsd:verify-work`:** Full suite must be green
- **Max feedback latency:** 30 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 20-01-01 | 01 | 1 | DASH-01 | static | `grep '#theme.*group_ai_pm_dashboard' src/Controller/DashboardController.php` | ❌ W0 | ⬜ pending |
| 20-01-02 | 01 | 1 | DASH-01 | static | `test -f templates/group-ai-pm-dashboard.html.twig` | ❌ W0 | ⬜ pending |
| 20-01-03 | 01 | 1 | DASH-01 | runtime | `ddev drush php-eval "..."` (dashboard renders with project cards) | ❌ W0 | ⬜ pending |
| 20-01-04 | 01 | 1 | DASH-02 | static | `grep 'entity.project.add_form' src/Controller/DashboardController.php` | ❌ W0 | ⬜ pending |
| 20-01-05 | 01 | 1 | DASH-02 | runtime | `ddev drush php-eval "..."` (quick action URLs resolve) | ❌ W0 | ⬜ pending |
| 20-02-01 | 02 | 1 | DASH-03 | static | `grep '#ajax' src/Form/TaskStatusForm.php` | ❌ W0 | ⬜ pending |
| 20-02-02 | 02 | 1 | DASH-03 | static | `grep 'FormBase' src/Form/TaskStatusForm.php` | ❌ W0 | ⬜ pending |
| 20-02-03 | 02 | 1 | DASH-03 | runtime | `ddev drush php-eval "..."` (form renders with select elements) | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `eval/v4/phase-20-evals.json` — static assertions for dashboard + AJAX patterns
- [ ] `eval/v4/phase-20-runtime-assertions.json` — drush-based functional checks

*Existing test infrastructure covers PHPUnit needs.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| AJAX status select updates task without page reload | DASH-03 | AJAX interaction requires browser context | 1. Navigate to task overview, 2. Change status dropdown, 3. Verify task updated in DB without page reload |
| Progress bar visual rendering | DASH-01 | CSS visual correctness | 1. Navigate to dashboard, 2. Verify progress bars show correct widths |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 30s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

---
phase: 14
slug: module-foundation
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-07
---

# Phase 14 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | PHPUnit (Drupal integrated, via drupal/core-dev) |
| **Config file** | `phpunit.xml` or `phpunit.xml.dist` at Drupal root (not in module) |
| **Quick run command** | `ddev drush en group_ai_pm -y && ddev drush cr` |
| **Full suite command** | `ddev exec phpunit --group group_ai_pm` |
| **Estimated runtime** | ~30 seconds |

---

## Sampling Rate

- **After every task commit:** Run `ddev drush en group_ai_pm -y && ddev drush cr` (module installs without error)
- **After every plan wave:** Full smoke test: install module + create entities + check list pages + submit settings form
- **Before `/gsd:verify-work`:** Full suite must be green + `phpcs --standard=Drupal,DrupalPractice modules/group_ai_pm/`
- **Max feedback latency:** 30 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 14-01-01 | 01 | 1 | SCAF-01 | smoke | `ddev drush en group_ai_pm -y && ddev drush cr` | Wave 0 | ⬜ pending |
| 14-01-02 | 01 | 1 | SCAF-02 | manual | Inspect `modules/group_ai_pm/composer.json` | N/A | ⬜ pending |
| 14-01-03 | 01 | 1 | SCAF-03 | manual | `ls -R modules/group_ai_pm/src/` | N/A | ⬜ pending |
| 14-02-01 | 02 | 1 | ENTY-01 | smoke | `ddev drush entity:create project --title="Test"` | Wave 0 | ⬜ pending |
| 14-02-02 | 02 | 1 | ENTY-02 | smoke | `ddev drush entity:create task --title="Test"` | Wave 0 | ⬜ pending |
| 14-02-03 | 02 | 1 | ENTY-03 | smoke | Manual browser check or `curl` admin form pages | Wave 0 | ⬜ pending |
| 14-02-04 | 02 | 1 | ENTY-04 | smoke | `curl -s /admin/content/project` \| grep "sortable" | Wave 0 | ⬜ pending |
| 14-03-01 | 03 | 2 | ROUTE-01 | smoke | `ddev drush route:list --path=/admin/content/project` | Wave 0 | ⬜ pending |
| 14-03-02 | 03 | 2 | ROUTE-02 | smoke | `curl -s /admin/content/project-dashboard` returns 200 | Wave 0 | ⬜ pending |
| 14-03-03 | 03 | 2 | ROUTE-03 | smoke | Submit form + `ddev drush config:get group_ai_pm.settings` | Wave 0 | ⬜ pending |
| 14-03-04 | 03 | 2 | ROUTE-04 | smoke | Submit form with invalid data, verify error | Wave 0 | ⬜ pending |
| 14-04-01 | 04 | 2 | EVAL-02 | manual | Run headless baseline script | N/A | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `modules/group_ai_pm/` — entire module directory (does not exist yet)
- [ ] Drupal test environment with Group + AI modules — need ddev setup with contrib modules
- [ ] phpcs configuration — need `drupal/coder` installed in test environment
- [ ] Baseline eval script — need `eval/v3/baseline-phase14.sh` for EVAL-02

*Wave 0 sets up the module scaffold and test environment before entity/route plans execute.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| composer.json dependencies correct | SCAF-02 | Static file inspection | Verify `drupal/group: ^3.3`, `drupal/ai: ^1.2.11`, `drupal/ai_agents: ^1.2.3` in require section |
| PSR-4 structure correct | SCAF-03 | Directory layout check | Verify `src/Entity/`, `src/Form/`, `src/Controller/` exist |
| Baseline code generated | EVAL-02 | Headless pipeline execution | Run `eval/v3/baseline-phase14.sh` and verify output |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 30s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

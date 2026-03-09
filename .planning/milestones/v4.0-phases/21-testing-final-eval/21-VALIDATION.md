---
phase: 21
slug: testing-final-eval
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-08
---

# Phase 21 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | PHPUnit 9 (Drupal 10) |
| **Config file** | `web/core/phpunit.xml` (copy from phpunit.xml.dist, configure SIMPLETEST_DB) |
| **Quick run command** | `ddev exec bash -c 'cd /var/www/html/web && SIMPLETEST_DB="sqlite://localhost//var/www/html/web/sites/default/files/.ht.sqlite" ../vendor/bin/phpunit --group group_ai_pm modules/custom/group_ai_pm/tests/src/Kernel/ 2>&1 \| tail -10'` |
| **Full suite command** | `ddev exec bash -c 'cd /var/www/html/web && SIMPLETEST_DB="sqlite://localhost//var/www/html/web/sites/default/files/.ht.sqlite" SIMPLETEST_BASE_URL="http://localhost" ../vendor/bin/phpunit --group group_ai_pm modules/custom/group_ai_pm/tests/ 2>&1'` |
| **Estimated runtime** | ~120 seconds (Kernel ~30s, Functional ~90s) |

---

## Sampling Rate

- **After every task commit:** Run quick kernel tests (~30 seconds)
- **After every plan wave:** Run full suite (kernel + functional, ~2 minutes)
- **Before `/gsd:verify-work`:** Full suite must be green + phpcs green
- **Max feedback latency:** 120 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 21-01-01 | 01 | 1 | EVAL-01 | static | eval-grader reads code against evals.json | ❌ W0 | ⬜ pending |
| 21-01-02 | 01 | 1 | EVAL-02 | runtime | ddev drush + phpunit commands | ❌ W0 | ⬜ pending |
| 21-01-03 | 01 | 1 | EVAL-03 | browser | agent-browser navigates board page | ❌ W0 | ⬜ pending |
| 21-01-04 | 01 | 1 | EVAL-04 | delta | Compare with/without grading results | ❌ W0 | ⬜ pending |
| 21-02-01 | 02 | 1 | TEST-01 | kernel | phpunit tests/src/Kernel/RestApiTest.php | ❌ W0 | ⬜ pending |
| 21-02-02 | 02 | 1 | TEST-02 | functional | phpunit tests/src/Functional/BoardPageTest.php | ❌ W0 | ⬜ pending |
| 21-02-03 | 02 | 1 | TEST-03 | cli | phpcs --standard=Drupal,DrupalPractice | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `eval/v4/phase-21-evals.json` — static expectations (14-16 assertions targeting testing skill)
- [ ] `eval/v4/phase-21-runtime-assertions.json` — runtime assertions (7-8 including PHPUnit execution)
- [ ] PHPUnit + core-dev installed in ddev instances: `ddev composer require --dev drupal/core-dev phpunit/phpunit`
- [ ] phpunit.xml configured with SIMPLETEST_DB and SIMPLETEST_BASE_URL
- [ ] phpcs + drupal/coder installed: `ddev composer require --dev drupal/coder squizlabs/php_codesniffer`

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Drag-and-drop changes task status in browser | EVAL-03 | Requires real browser interaction with SortableJS | Use agent-browser to drag task between columns, verify status persists |
| AJAX list toggle functions on dashboard | EVAL-03 | Requires real browser AJAX interaction | Use agent-browser to click status toggle, verify UI updates |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 120s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

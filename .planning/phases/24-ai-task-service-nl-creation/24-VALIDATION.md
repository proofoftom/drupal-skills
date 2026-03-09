---
phase: 24
slug: ai-task-service-nl-creation
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-09
---

# Phase 24 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | PHPUnit (Drupal's BrowserTestBase / KernelTestBase) |
| **Config file** | `phpunit.xml` in Drupal root |
| **Quick run command** | `ddev drush cr && ddev drush php-eval "\Drupal::service('group_ai_pm.ai_task');"` |
| **Full suite command** | `ddev exec phpunit modules/custom/group_ai_pm/tests/ --no-coverage` |
| **Estimated runtime** | ~30 seconds |

---

## Sampling Rate

- **After every task commit:** Run `ddev drush cr && ddev drush php-eval "\Drupal::service('group_ai_pm.ai_task');"`
- **After every plan wave:** Run `ddev exec phpunit modules/custom/group_ai_pm/tests/ --no-coverage`
- **Before `/gsd:verify-work`:** Full suite must be green
- **Max feedback latency:** 30 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 24-01-01 | 01 | 1 | AI-01 | kernel | `ddev drush php-eval "\Drupal::service('group_ai_pm.ai_task');"` | ❌ W0 | ⬜ pending |
| 24-01-02 | 01 | 1 | AI-02 | kernel | `ddev drush en group_ai_pm -y` (without ai module) | ❌ W0 | ⬜ pending |
| 24-01-03 | 01 | 1 | AI-03 | kernel | `ddev drush php-eval "print_r(array_keys(\Drupal::service('plugin.manager.ai.function_calls')->getDefinitions()));"` | ❌ W0 | ⬜ pending |
| 24-01-04 | 01 | 1 | AI-04 | functional | `curl -X POST /api/kanban/project/1/ai-create -d '{"text":"..."}'` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] Runtime assertion: `group_ai_pm.ai_task` service resolves from container
- [ ] Runtime assertion: module enables without AI module (`ddev drush en group_ai_pm -y` on clean install without ai)
- [ ] Runtime assertion: CreateTaskTool plugin is discovered by `plugin.manager.ai.function_calls`
- [ ] Runtime assertion: POST to ai-create endpoint returns 201 with task JSON
- [ ] Static assertion: `AiTaskService` constructor accepts nullable `AiProviderPluginManager`
- [ ] Static assertion: `CreateTaskTool` extends `FunctionCallBase` (NOT `AiFunctionCallBase`)
- [ ] Static assertion: `CreateTaskTool` uses `#[FunctionCall]` attribute (NOT `@AiFunctionCall` annotation)
- [ ] Static assertion: Route `group_ai_pm.api.ai_create` has `_format: json` requirement

*If none: "Existing infrastructure covers all phase requirements."*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| NL parsing quality | AI-04 | Depends on AI provider response quality | Send varied NL inputs, verify parsed fields |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 30s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

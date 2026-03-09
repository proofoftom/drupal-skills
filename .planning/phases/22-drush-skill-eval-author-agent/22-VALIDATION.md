---
phase: 22
slug: drush-skill-eval-author-agent
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-09
---

# Phase 22 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Eval pipeline (headless claude -p + eval-grader agent) |
| **Config file** | `skills/drupal-drush/evals/evals.json` (new — Wave 0 creates) |
| **Quick run command** | `drush route --name=my_module.*` (verifies route-level self-verification) |
| **Full suite command** | Full A/B eval pipeline (headless with/without runs + grading) |
| **Estimated runtime** | ~300 seconds (headless runs + grading) |

---

## Sampling Rate

- **After every task commit:** Verify skill file structure, assertion count, reference file presence
- **After every plan wave:** Review SKILL.md content for usage focus (not command authoring)
- **Before `/gsd:verify-work`:** Full suite must be green
- **Max feedback latency:** 30 seconds (quick checks), 300 seconds (full eval)

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 22-01-01 | 01 | 1 | TOOL-01 | manual + static | Review SKILL.md for usage focus; verify WRONG/RIGHT callouts cover php-eval vs drush route, sql:query vs entity:save, manual vs drush generate | -- W0 | pending |
| 22-01-02 | 01 | 1 | TOOL-02 | static | `cat skills/drupal-drush/evals/evals.json \| jq '.evals[0].expectations'` | -- W0 | pending |
| 22-02-01 | 02 | 2 | TOOL-03 | manual | Run eval-author agent with test input; verify three-tier output | -- W0 | pending |
| 22-02-02 | 02 | 2 | TOOL-04 | manual | Count assertion categories in eval-author output; verify 60/20/20 | -- W0 | pending |

---

## Wave 0 Requirements

- [ ] `skills/drupal-drush/SKILL.md` — usage-focused Drush skill
- [ ] `skills/drupal-drush/evals/evals.json` — Drush usage eval assertions
- [ ] `skills/drupal-drush/references/command-authoring.md` — command authoring reference
- [ ] `.claude/agents/eval-author.md` — Opus subagent definition

*All are deliverables, not test infrastructure.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| SKILL.md teaches Drush usage for development | TOOL-01 | Semantic quality review | Read SKILL.md; verify self-verification recipes, drush generate, Drupal-first principle, watchdog debugging |
| Eval-author produces three-tier output | TOOL-03 | Requires agent execution | Run eval-author with Drush skill + sample module; check static + runtime + browser tiers |
| Assertion distribution enforced | TOOL-04 | Requires category counting | Count differentiating/wiring/structural in output; verify >=60/~20/<=20 split |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 300s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

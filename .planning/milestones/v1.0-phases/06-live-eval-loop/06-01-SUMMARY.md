---
phase: 06-live-eval-loop
plan: "01"
subsystem: eval-infrastructure
tags: [evals, ddev, drupal, shell-scripts, functional-eval]
dependency_graph:
  requires: []
  provides: [evals-json-4-skills, drupal-env-setup-teardown]
  affects: [06-02-run-eval-agents]
tech_stack:
  added: []
  patterns: [evals.json functional assertions, ddev isolated environments, sed INSERT pattern]
key_files:
  created:
    - skills/drupal-module-scaffold/evals/evals.json
    - skills/drupal-entities-fields/evals/evals.json
    - skills/drupal-caching/evals/evals.json
    - skills/drupal-testing/evals/evals.json
    - eval/setup-drupal-env.sh
    - eval/teardown-drupal-env.sh
  modified: []
decisions:
  - id: SED-INSERT
    description: "Used sed 1a (INSERT) not sed s/// (substitute) because os-knowledge-garden/.ddev/config.yaml has no name: field"
    rationale: "Substitution would silently fail; INSERT places the name: field as line 2"
metrics:
  duration: 2min
  completed: "2026-03-06"
  tasks_completed: 2
  files_created: 6
  files_modified: 0
---

# Phase 6 Plan 1: Eval Infrastructure Summary

**One-liner:** evals.json with 7-9 functional assertions for 4 skills plus ddev setup/teardown scripts using sed INSERT for unique project names.

## What Was Built

Created the eval infrastructure needed before spawning eval subagents in the next plan:

1. **4 evals.json files** — each contains 1 eval with functional shell-verifiable assertions:
   - `drupal-module-scaffold`: 7 assertions testing event_analytics module (info.yml format, drush en, PHP syntax)
   - `drupal-entities-fields`: 9 assertions testing EventEnrollment content entity (class, annotation/attribute, base fields, handlers, install, entity create)
   - `drupal-caching`: 8 assertions testing related_content_block (cache tags, contexts, no max-age 0, drush en)
   - `drupal-testing`: 8 assertions testing KernelTestBase usage (base class, $modules, setUp, @group, assertions)

2. **eval/setup-drupal-env.sh** — takes a `<unique-name>`, copies os-knowledge-garden to `/tmp/os-kg-{name}`, inserts a unique `name:` field into .ddev/config.yaml, then runs `ddev start` + `scripts/install.sh --demo=cascadia`.

3. **eval/teardown-drupal-env.sh** — takes a `<unique-name>`, runs `ddev delete -O -y` in the target directory, then removes `/tmp/os-kg-{name}`. Idempotent.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Corrected sed command in setup script**

- **Found during:** Task 2 — pre-execution verification of os-knowledge-garden
- **Issue:** The original PRD (docs/plans/2026-03-05-skill-eval-loop.md Task 5) had `sed -i "s/^name:.*/name: os-kg-${NAME}/"` (substitution). The 06-RESEARCH.md confirmed that os-knowledge-garden/.ddev/config.yaml has no `name:` field, so a substitution would silently produce an unnamed ddev project, causing a name collision on subsequent runs.
- **Fix:** Used `sed -i "1a name: os-kg-${NAME}"` (INSERT after line 1) as specified in the 06-01-PLAN.md must_haves section — which already had the corrected pattern from research. Applied the PLAN.md version (correct) rather than the PRD version (buggy).
- **Files modified:** eval/setup-drupal-env.sh
- **Commit:** 879a1d3

## Decisions Made

| Decision | Choice | Rationale |
|----------|--------|-----------|
| sed INSERT vs substitute | `sed -i "1a name: os-kg-${NAME}"` | .ddev/config.yaml has no existing `name:` field; substitute would silently fail |
| Script safety | `set -euo pipefail` | Ensures setup script fails fast on any error during ddev operations |
| Teardown idempotency | `exit 0` when directory missing | Allows cleanup to be called safely even if setup failed partway through |

## Commits

| Hash | Message |
|------|---------|
| 1da7625 | feat(06-01): add evals.json for all 4 skills under test |
| 879a1d3 | feat(06-01): add shared Drupal environment setup and teardown scripts |

## Self-Check: PASSED

- [x] skills/drupal-module-scaffold/evals/evals.json — exists, 7 assertions
- [x] skills/drupal-entities-fields/evals/evals.json — exists, 9 assertions
- [x] skills/drupal-caching/evals/evals.json — exists, 8 assertions
- [x] skills/drupal-testing/evals/evals.json — exists, 8 assertions
- [x] eval/setup-drupal-env.sh — exists, executable, uses sed INSERT, uses cascadia demo
- [x] eval/teardown-drupal-env.sh — exists, executable, uses ddev delete, idempotent
- [x] Commits 1da7625 and 879a1d3 verified in git log

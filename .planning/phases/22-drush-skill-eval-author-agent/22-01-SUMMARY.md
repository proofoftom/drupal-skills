---
phase: 22-drush-skill-eval-author-agent
plan: 01
subsystem: skills
tags: [drush, self-verification, scaffolding, debugging, entity-api, eval-assertions]

# Dependency graph
requires:
  - phase: none
    provides: standalone skill creation (uses existing 14 skills as format reference)
provides:
  - drupal-drush SKILL.md teaching Drush usage for development
  - drupal-drush evals/evals.json with 10 assertions targeting usage patterns
  - drupal-drush references/command-authoring.md preserving command creation patterns
affects: [22-02 eval-author-agent, future eval runs, all phases using Drush for verification]

# Tech tracking
tech-stack:
  added: []
  patterns: [drush-generate-scaffolding, self-verification-recipes, drupal-first-principle, php-script-over-php-eval]

key-files:
  created:
    - skills/drupal-drush/SKILL.md
    - skills/drupal-drush/evals/evals.json
    - skills/drupal-drush/references/command-authoring.md

key-decisions:
  - "Drush skill teaches USAGE (self-verification, scaffolding, debugging) not command authoring"
  - "Command-authoring patterns preserved as reference file, cross-referenced from main skill"
  - "10 eval assertions target non-obvious Drush usage patterns with parenthetical rationales"
  - "Commands shown without ddev prefix for portability, with ddev note in intro"

patterns-established:
  - "Self-verification recipes: check routes, watchdog, services, permissions, config, state, queues after every operation"
  - "Drupal-first principle: entity API over sql:query, with clear exceptions for when SQL is appropriate"
  - "php:script for multi-step tests, php:eval for one-liners"
  - "Reference file pattern: skill cross-references deeper content in references/ subdirectory"

requirements-completed: [TOOL-01, TOOL-02]

# Metrics
duration: 6min
completed: 2026-03-09
---

# Phase 22 Plan 01: Drush Skill + Eval Assertions Summary

**15th Drupal skill teaching Drush usage for self-verification (routes, watchdog, services), scaffolding via drush generate, Drupal-first entity operations, and debugging -- with 10 eval assertions and preserved command-authoring reference**

## Performance

- **Duration:** 6 min
- **Started:** 2026-03-09T12:40:32Z
- **Completed:** 2026-03-09T12:46:47Z
- **Tasks:** 2
- **Files created:** 3

## Accomplishments
- Created SKILL.md (404 lines) with 6 WRONG/RIGHT callouts covering the 3 most common anti-patterns: php-eval for everything, sql:query for entity ops, no self-verification
- Designed 10 eval assertions following Phase 18 gold-standard format with parenthetical rationales, targeting differentiating Drush usage patterns
- Preserved all command-authoring content (536 lines) as a reference file with proper cross-referencing

## Task Commits

Each task was committed atomically:

1. **Task 1: Author Drush usage skill SKILL.md** - `4c8acef` (feat)
2. **Task 2: Create command-authoring reference and eval assertions** - `ec2a7b4` (feat)

## Files Created/Modified
- `skills/drupal-drush/SKILL.md` - Main skill teaching Drush usage for development (404 lines)
- `skills/drupal-drush/evals/evals.json` - 10 eval assertions for Drush usage patterns
- `skills/drupal-drush/references/command-authoring.md` - Drush 12/13.7+ custom command creation reference (536 lines)

## Decisions Made
- Taught drush generate as a powerful option, not mandatory -- some scaffolding needs custom patterns not covered by generators
- Focused on 7 highest-impact self-verification recipes to avoid overwhelming the skill signal
- Commands shown without `ddev` prefix for portability, with note about ddev environments in the intro
- Eval prompt designed as a realistic module creation task that naturally triggers self-verification without explicitly asking for Drush commands

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness
- Drush skill ready for eval pipeline testing
- Command-authoring reference preserves original content for agents that need to create custom commands
- Plan 22-02 (eval-author agent) can reference this skill as input for assertion generation patterns

## Self-Check: PASSED

All 3 created files verified present on disk. Both task commits (4c8acef, ec2a7b4) verified in git log.

---
*Phase: 22-drush-skill-eval-author-agent*
*Completed: 2026-03-09*

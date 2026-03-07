---
phase: 09-eval-prompt-rewrite
plan: 02
subsystem: testing
tags: [evals, prompts, hint-removal, d10-migration, testing-redesign]

# Dependency graph
requires:
  - phase: 08-eval-infrastructure
    provides: eval pipeline with subagent executors and grader
provides:
  - 5 rewritten eval prompts with hint removal and platform-neutral framing
  - Self-contained calculator testing eval (no external module dependencies)
  - Cross-plan validation confirming all 13 evals clean
affects: [10-eval-runs, 11-skill-optimization]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Requirement-focused prompts (describe WHAT, not HOW) to maximize assertion differentiation"
    - "Self-contained eval scenarios that work on vanilla Drupal 10 without external modules"

key-files:
  created: []
  modified:
    - skills/drupal-caching/evals/evals.json
    - skills/drupal-batch-queue-cron/evals/evals.json
    - skills/drupal-database-api/evals/evals.json
    - skills/drupal-testing/evals/evals.json
    - skills/drupal-entities-fields/evals/evals.json

key-decisions:
  - "Removed 3 high-impact hints that directly taught assertions (max-age:0, queue pattern, Entity API ban)"
  - "Redesigned testing eval with calculator module instead of patching social_ai_indexing references"
  - "Replaced entity-schema expectations with service-container ones for calculator scenario"

patterns-established:
  - "Eval prompts describe functional requirements without implementation hints"
  - "Self-contained eval modules (calculator) that need no external dependencies"

requirements-completed: [EVAL-01, EVAL-02]

# Metrics
duration: 3min
completed: 2026-03-07
---

# Phase 9 Plan 02: Complex Prompt Rewrites Summary

**Removed 3 high-impact implementation hints from caching/batch-queue-cron/database-api, redesigned testing eval with self-contained calculator module, and recontextualized entities-fields for vanilla D10**

## Performance

- **Duration:** 3 min
- **Started:** 2026-03-07T04:52:10Z
- **Completed:** 2026-03-07T04:54:40Z
- **Tasks:** 3
- **Files modified:** 5

## Accomplishments
- Removed 3 hint-leaking instructions that directly neutralized differentiating assertions (max-age:0 ban, queue pattern instruction, Entity API ban)
- Fully redesigned testing eval from social_ai_indexing dependency to self-contained calculator module with updated expectations
- Recontextualized entities-fields from os-kg knowledge curation to generic D10 while preserving entity/module names
- Cross-plan validation confirmed all 13 evals pass: valid JSON, zero forbidden references, all prompts coherent with expectations

## Task Commits

Each task was committed atomically:

1. **Task 1: Rewrite 3 hint-leaking prompts** - `86291ba` (feat)
2. **Task 2: Redesign testing eval and recontextualize entities-fields** - `df73418` (feat)
3. **Task 3: Final cross-plan validation** - No commit (validation-only, no file changes)

## Files Created/Modified
- `skills/drupal-caching/evals/evals.json` - Removed "Do NOT use max-age: 0" hint, D10 prompt
- `skills/drupal-batch-queue-cron/evals/evals.json` - Removed queue pattern and cron.time hints, D10 prompt
- `skills/drupal-database-api/evals/evals.json` - Removed Entity API ban and raw SQL hint, D10 prompt
- `skills/drupal-testing/evals/evals.json` - Full redesign: calculator module, updated 3 expectations
- `skills/drupal-entities-fields/evals/evals.json` - Removed os-kg framing, kept entity/module names

## Decisions Made
- **Removed 3 high-impact hints rather than softening them**: "Do NOT use max-age: 0", "Do NOT process items directly in hook_cron", and "do NOT use the Entity API" were direct instruction of what assertions test. Removing them entirely (rather than rephrasing) maximizes differentiation potential.
- **Calculator module for testing instead of patching**: Rather than trying to make social_ai_indexing references work on vanilla D10, created a completely self-contained calculator scenario. This required updating 3 of 8 expectations to match (installEntitySchema -> parent::setUp(), installSchema -> container->get(), modules list -> just calculator).
- **Kept "Use Drupal's database abstraction layer" in database-api prompt**: This is a legitimate functional requirement (tells agent what API to use) -- different from "do NOT use Entity API" which teaches the architectural choice the assertion tests for.

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered
None

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- All 13 evals.json now have platform-neutral, hint-free prompts targeting vanilla Drupal 10
- Ready for Phase 10 eval runs to measure whether hint removal produces meaningful assertion deltas
- Cross-plan validation (Task 3) confirmed Plan 01 and Plan 02 changes are integrated and consistent

## Self-Check: PASSED

All 5 modified eval files exist. Both task commits (86291ba, df73418) verified in git log.

---
*Phase: 09-eval-prompt-rewrite*
*Completed: 2026-03-07*

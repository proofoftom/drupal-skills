---
phase: 07-full-eval-optimize-loop
plan: 06
subsystem: testing
tags: [evals, assertions, differentiating-knowledge, sonnet, drupal]

# Dependency graph
requires:
  - phase: 07-full-eval-optimize-loop (plans 02-03)
    provides: "Initial evals.json files with standard assertions (0% delta for 8/13 skills)"
  - phase: 07-full-eval-optimize-loop (plans 04-05)
    provides: "Eval run results proving standard assertions are vacuous"
provides:
  - "13 rewritten evals.json files with source-material-driven differentiating assertions"
  - "Each eval targets SKILL.md wrong-way callouts and Sipos book knowledge"
  - "Assertion design follows high-delta model: negative checks, specific format, runtime"
affects: [07-full-eval-optimize-loop plans 07-08]

# Tech tracking
tech-stack:
  added: []
  patterns: ["differentiating assertion design: negative checks + specific format + runtime verification"]

key-files:
  modified:
    - skills/drupal-routing-controllers/evals/evals.json
    - skills/drupal-forms-api/evals/evals.json
    - skills/drupal-plugins-blocks/evals/evals.json
    - skills/drupal-config-storage/evals/evals.json
    - skills/drupal-access-security/evals/evals.json
    - skills/drupal-theming/evals/evals.json
    - skills/drupal-database-api/evals/evals.json
    - skills/drupal-views-dev/evals/evals.json
    - skills/drupal-batch-queue-cron/evals/evals.json
    - skills/drupal-caching/evals/evals.json
    - skills/drupal-module-scaffold/evals/evals.json
    - skills/drupal-entities-fields/evals/evals.json
    - skills/drupal-testing/evals/evals.json

key-decisions:
  - "Preserved all original prompts and module names for delta comparison consistency"
  - "Each eval targets 7-10 assertions per skill, with at least 2 negative anti-pattern checks"
  - "Assertion design based on .continue-here.md differentiating patterns research"
  - "High-delta skills (caching, scaffold, batch-queue-cron) assertions enhanced, not replaced"

patterns-established:
  - "Differentiating assertion categories: NEGATIVE (does NOT), SPECIFIC FORMAT (exact syntax), RUNTIME (enables), E2E (browser)"
  - "Anti-pattern assertions test what Sonnet defaults to WITHOUT the skill, not what it already knows"

requirements-completed: [FULL-01, FULL-04]

# Metrics
duration: 4min
completed: 2026-03-06
---

# Phase 7 Plan 6: Rewrite All 13 Evals with Differentiating Assertions Summary

**Rewrote all 13 evals.json files with source-material-driven assertions targeting non-obvious SKILL.md patterns that Sonnet misses without the skill**

## Performance

- **Duration:** 4 min
- **Started:** 2026-03-06T14:48:45Z
- **Completed:** 2026-03-06T14:53:06Z
- **Tasks:** 2
- **Files modified:** 13

## Accomplishments
- Replaced vacuous standard-pattern assertions with differentiating ones across all 13 skills
- Each eval now targets specific wrong-way callouts from SKILL.md (e.g., 4-param create() for plugins, label vs string schema types for config, empty query() for virtual Views fields)
- Total of 111 assertions across 13 skills (avg 8.5 per skill), each with at least 2 negative anti-pattern checks
- Prompts preserved exactly from iteration 1 so delta differences come from assertion quality only

## Task Commits

Each task was committed atomically:

1. **Task 1: Rewrite evals.json for 7 skills** - `c80b98d` (feat)
2. **Task 2: Rewrite evals.json for 6 skills** - `d34e52f` (feat)

## Files Modified
- `skills/drupal-routing-controllers/evals/evals.json` - DI anti-patterns, static \Drupal::service() absence
- `skills/drupal-forms-api/evals/evals.json` - ConfigFormBase parent calls, setErrorByName, schema types
- `skills/drupal-plugins-blocks/evals/evals.json` - 4-param create(), parent::__construct, blockForm/blockSubmit
- `skills/drupal-config-storage/evals/evals.json` - label vs string schema, no State API, no variable_get
- `skills/drupal-access-security/evals/evals.json` - AccessResult cache contexts, no hook_permission
- `skills/drupal-theming/evals/evals.json` - hyphen template naming, #attached library, no raw HTML
- `skills/drupal-database-api/evals/evals.json` - addTag(), addExpression(), no Entity API
- `skills/drupal-views-dev/evals/evals.json` - group key, empty query() for virtual fields, PSR-4
- `skills/drupal-batch-queue-cron/evals/evals.json` - queue name matching, cron time, RequeueException
- `skills/drupal-caching/evals/evals.json` - golden rule (tags+contexts), no max-age:0, no \Drupal::cache()
- `skills/drupal-module-scaffold/evals/evals.json` - ^10||^11 format, drupal: prefix, strict_types
- `skills/drupal-entities-fields/evals/evals.json` - parent::baseFieldDefinitions merge, setDisplayOptions, no config_export
- `skills/drupal-testing/evals/evals.json` - KernelTestBase choice, installEntitySchema, @group

## Decisions Made
- Preserved all original prompts and module names to ensure delta differences come from assertion quality, not prompt changes
- Each skill's assertions are directly derived from the .continue-here.md differentiating patterns research
- High-delta skills (caching +75%, scaffold +43%, batch-queue-cron +29%) had assertions enhanced rather than replaced
- Low-delta skills (routing, forms, plugins, config, access, theming, database, views, entities, testing) had assertions entirely rewritten

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered
None

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- All 13 evals.json files ready for re-running through eval pipeline
- Plans 07 and 08 can proceed with running these improved evals against Sonnet with/without skills
- Expect significantly higher delta for previously 0% skills due to anti-pattern targeting

## Self-Check: PASSED

All 13 evals.json files verified present. Both task commits (c80b98d, d34e52f) confirmed in git log. SUMMARY.md exists.

---
*Phase: 07-full-eval-optimize-loop*
*Completed: 2026-03-06*

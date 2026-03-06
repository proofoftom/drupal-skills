---
phase: 04-specialized-patterns
plan: 01
subsystem: skills
tags: [drupal, views, hook-views-data, entity-views-data, views-field, views-filter, views-argument]

# Dependency graph
requires: []
provides:
  - drupal-views-dev skill with Views data exposure decision guide
  - Custom ViewsField/ViewsFilter/ViewsArgument plugin patterns with D10/D11 syntax
  - Views relationship (JOIN) patterns
  - Views plugin configuration schema patterns
affects: [04-02, eval-phase]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Views data exposure decision tree (entity vs custom table vs alter)"
    - "Virtual field pattern with empty query() override"
    - "InOperator filter extension pattern for select-list filters"
    - "Views plugin configuration schema using dynamic types (views.field.PLUGIN_ID)"

key-files:
  created:
    - skills/drupal-views-dev/SKILL.md
    - skills/drupal-views-dev/references/.gitkeep
  modified: []

key-decisions:
  - "Included 5 wrong-way callouts covering entity hook_views_data misuse, missing table group, virtual field query(), missing config schema, and hook_views_data vs hook_views_data_alter confusion"
  - "Added drupal-database-api as third cross-reference beyond entities-fields and plugins-blocks for hook_schema() connection"
  - "Used D11 PHP attribute classes (Drupal\\views\\Attribute\\ViewsField etc.) following established plugin attribute pattern from drupal-plugins-blocks skill"
  - "Included ViewsArgument plugin type with D10/D11 syntax for complete Views plugin coverage"

patterns-established:
  - "Views plugin namespace convention: Plugin\\views\\{plugin_type} with matching attribute/annotation"
  - "Summary table of all Views plugin types with base classes and discovery syntax"

requirements-completed: [SPEC-01]

# Metrics
duration: 2min
completed: 2026-03-06
---

# Phase 4 Plan 01: Views Development Skill Summary

**Decision-guide skill for Drupal Views data integration with hook_views_data(), EntityViewsData, custom ViewsField/ViewsFilter/ViewsArgument plugins, and 5 wrong-way callouts for common Views mistakes**

## Performance

- **Duration:** 2 min
- **Started:** 2026-03-06T03:15:28Z
- **Completed:** 2026-03-06T03:17:28Z
- **Tasks:** 2
- **Files modified:** 2

## Accomplishments
- Created drupal-views-dev skill with decision tree for Views data exposure (entity vs custom table vs alter)
- Complete ViewsField plugin pattern including virtual field empty query() override
- Complete ViewsFilter plugin pattern extending InOperator with select-list options
- D10 annotation and D11 attribute syntax for all four Views plugin types (field, filter, sort, argument)
- Views plugin configuration schema patterns using dynamic types
- Validated against all 7 SKIL quality standards (SKIL-01 through SKIL-07)

## Task Commits

Each task was committed atomically:

1. **Task 1: Create drupal-views-dev SKILL.md** - `569e9c3` (feat)
2. **Task 2: Validate skill against SKIL-01 through SKIL-07 quality standards** - no changes needed (validation passed without edits)

## Files Created/Modified
- `skills/drupal-views-dev/SKILL.md` - Decision-guide skill for Views data integration (476 lines)
- `skills/drupal-views-dev/references/.gitkeep` - Empty references directory placeholder

## Decisions Made
- Included 5 wrong-way callouts (entity hook_views_data, missing table group, virtual field query(), missing config schema, hook_views_data vs alter) exceeding the minimum 4 required
- Added drupal-database-api as third cross-reference for hook_schema() connection to custom tables
- Used D11 attribute classes from Drupal\views\Attribute namespace following established pattern
- Included ViewsArgument plugin type for complete coverage of all Views plugin types

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness
- Views integration skill complete, ready for 04-02 (drupal-batch-queue-cron)
- All four Views plugin types documented with D10/D11 dual syntax
- Cross-references to entities-fields, plugins-blocks, and database-api established

## Self-Check: PASSED

- FOUND: skills/drupal-views-dev/SKILL.md
- FOUND: skills/drupal-views-dev/references/.gitkeep
- FOUND: commit 569e9c3

---
*Phase: 04-specialized-patterns*
*Completed: 2026-03-06*

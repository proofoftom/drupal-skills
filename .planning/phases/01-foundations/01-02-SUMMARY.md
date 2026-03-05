---
phase: 01-foundations
plan: 02
subsystem: skills
tags: [drupal, routing, controllers, services, dependency-injection, menus, local-tasks]

# Dependency graph
requires:
  - phase: 01-01
    provides: Skill template pattern (directory layout, frontmatter, wrong-way callouts, cross-references)
provides:
  - drupal-routing-controllers skill with decision-guide format
  - Routing, controller, service, and DI patterns for Claude
  - Menu system reference (menu links, local tasks, local actions, contextual links)
affects: [01-03, all-subsequent-skills]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Route type decision tree (simple page, form, entity, admin, parameterized)"
    - "Controller with DI pattern (create() + constructor)"
    - "Complete route + controller + service file ecosystem"
    - "Menu link YAML file types (.links.menu.yml, .links.task.yml, .links.action.yml, .links.contextual.yml)"

key-files:
  created:
    - skills/drupal-routing-controllers/SKILL.md
    - skills/drupal-routing-controllers/references/menus.md
  modified: []

key-decisions:
  - "Included 5 wrong-way callouts (hook_menu, hardcoded access, plain strings, static DI, container injection) exceeding minimum of 4"
  - "Used a distinct greeting module example (not hello_world) to avoid duplication with scaffold skill"
  - "Kept controller DI example with full docblocks in DI section, trimmed docblocks in complete example to stay under 500 lines"

patterns-established:
  - "Route decision tree format for guiding Claude to correct route type"
  - "Separate references/ file for menu system to keep SKILL.md focused"

requirements-completed: [SKIL-01, SKIL-02, SKIL-03, SKIL-04, SKIL-05, SKIL-06, SKIL-07, FOUN-02]

# Metrics
duration: 4min
completed: 2026-03-05
---

# Phase 1 Plan 02: Routing and Controllers Skill Summary

**Decision-guide skill for Drupal routing, controllers, services, and DI with 5 wrong-way callouts and a menus reference covering local tasks, actions, and contextual links**

## Performance

- **Duration:** 4 min
- **Started:** 2026-03-05T23:38:12Z
- **Completed:** 2026-03-05T23:42:38Z
- **Tasks:** 2
- **Files modified:** 2

## Accomplishments
- Created drupal-routing-controllers skill following the template pattern from Plan 01
- SKILL.md uses decision-guide format with route type decision tree driving structure
- 5 wrong-way callouts covering the most common routing/DI mistakes Claude makes
- Complete greeting module example with paired .routing.yml + Controller + .services.yml + Service
- references/menus.md covers all four menu link YAML file types with examples
- Cross-references to 3 related skills plus internal menus.md reference, all with graceful degradation
- Validated against all 7 SKIL quality standards (SKIL-01 through SKIL-07)

## Task Commits

Each task was committed atomically:

1. **Task 1: Create drupal-routing-controllers SKILL.md and menus reference** - `614b088` (feat)
2. **Task 2: Validate skill against SKIL-01 through SKIL-07 and template consistency** - no changes needed (validation passed without edits)

## Files Created/Modified
- `skills/drupal-routing-controllers/SKILL.md` - Decision-guide skill for routing, controllers, services, DI (498 lines)
- `skills/drupal-routing-controllers/references/menus.md` - Menu links, local tasks, local actions, contextual links reference

## Decisions Made
- Included 5 wrong-way callouts exceeding the minimum 4 required by SKIL-03
- Used a greeting module (not hello_world) as the complete example to avoid duplicating the scaffold skill's example
- Trimmed docblocks in complete example section to keep SKILL.md under 500 lines while keeping full docblocks in the DI teaching section

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness
- Two of three foundational skills complete (scaffold + routing-controllers)
- Template pattern well-established for 01-03 (entities-fields), the highest-complexity skill
- Cross-reference network growing: scaffold references routing-controllers, routing-controllers references scaffold and entities-fields

## Self-Check: PASSED

- FOUND: skills/drupal-routing-controllers/SKILL.md
- FOUND: skills/drupal-routing-controllers/references/menus.md
- FOUND: commit 614b088

---
*Phase: 01-foundations*
*Completed: 2026-03-05*

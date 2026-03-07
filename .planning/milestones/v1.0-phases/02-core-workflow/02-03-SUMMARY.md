---
phase: 02-core-workflow
plan: 03
subsystem: skills
tags: [drupal, plugins, blocks, di, annotations, attributes, custom-plugin-types]

# Dependency graph
requires:
  - phase: 01-foundations
    provides: Skill anatomy pattern (frontmatter, decision-guide, wrong-way callouts, cross-references)
  - phase: 02-core-workflow plan 01
    provides: drupal-forms-api skill (cross-referenced for block config forms)
  - phase: 02-core-workflow plan 02
    provides: drupal-config-storage skill (cross-referenced for config vs block config distinction)
provides:
  - drupal-plugins-blocks skill covering block plugins, config forms, custom plugin types, plugin discovery
  - Plugin DI pattern (4-param create()) clearly distinguished from controller DI (1-param)
  - D10 annotation and D11 attribute syntax for blocks and custom plugin types
affects: [drupal-access-security, eval-packaging]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Plugin DI: 4-param create($container, $configuration, $plugin_id, $plugin_definition)"
    - "Custom plugin type boilerplate: manager + annotation/attribute class + interface + services.yml"
    - "Block config via $this->configuration, NOT Config API"

key-files:
  created:
    - skills/drupal-plugins-blocks/SKILL.md
    - skills/drupal-plugins-blocks/references/.gitkeep
  modified: []

key-decisions:
  - "Used sandwich plugin as custom plugin type example to demonstrate the pattern clearly without domain-specific complexity"
  - "Included parent: default_plugin_manager shorthand in services.yml for plugin manager registration"
  - "Showed D10 and D11 custom plugin type boilerplate separately (annotation class vs attribute class) rather than a bridge pattern"

patterns-established:
  - "Plugin DI 4-param signature as the primary wrong-way callout -- most confused pattern in Drupal"
  - "Block config forms use blockForm/blockSubmit, NOT Config API"

requirements-completed: [CORE-02]

# Metrics
duration: 3min
completed: 2026-03-06
---

# Phase 2 Plan 3: Plugins and Blocks Summary

**Block plugin and custom plugin type skill with 4-param DI pattern, D10/D11 dual annotation/attribute syntax, and block config forms**

## Performance

- **Duration:** 3 min
- **Started:** 2026-03-06T00:23:48Z
- **Completed:** 2026-03-06T00:26:41Z
- **Tasks:** 2
- **Files modified:** 2

## Accomplishments
- Created drupal-plugins-blocks skill at 490 lines with decision-guide format
- Covered block plugins, plugin DI (4-param create), block config forms, custom plugin types, and plugin discovery
- 5 wrong-way callouts targeting the most common Claude mistakes with Drupal plugins
- D10 annotation and D11 attribute syntax shown side-by-side for blocks and custom plugin types
- 6 cross-references with graceful degradation to forms-api, config-storage, access-security, routing-controllers, entities-fields

## Task Commits

Each task was committed atomically:

1. **Task 1: Create drupal-plugins-blocks SKILL.md** - `918d708` (feat)
2. **Task 2: Validate skill against SKIL-01 through SKIL-07** - no changes needed (all standards met on first pass)

## Files Created/Modified
- `skills/drupal-plugins-blocks/SKILL.md` - Block plugin and custom plugin type decision guide (490 lines)
- `skills/drupal-plugins-blocks/references/.gitkeep` - Placeholder for future reference files

## Decisions Made
- Used sandwich plugin as custom plugin type example for clarity
- Included `parent: default_plugin_manager` shorthand in services.yml for cleaner manager registration
- Showed D10 and D11 custom plugin type boilerplate as separate sections rather than using AttributeBridgeDecorator bridge pattern

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered
None

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- All three completed Phase 2 skills (forms-api, config-storage, plugins-blocks) cross-reference each other
- drupal-access-security (Plan 04) is the final Phase 2 skill, which cross-cuts all others
- Plugin DI pattern established here will be cross-referenced by access-security for entity access handlers

---
*Phase: 02-core-workflow*
*Completed: 2026-03-06*

---
phase: 02-core-workflow
plan: 04
subsystem: skills
tags: [drupal, access-control, permissions, csrf, xss, entity-access, security]

# Dependency graph
requires:
  - phase: 01-foundations
    provides: Skill template pattern (SKILL.md anatomy, wrong-way callouts, cross-references, file ecosystems)
  - phase: 02-core-workflow
    provides: Routing and entity patterns for cross-referencing (02-01, 02-02)
provides:
  - drupal-access-security skill covering permissions, route access, entity access, custom access checkers, CSRF, XSS
  - Access control decision tree for choosing the right access pattern
  - AccessResult with cache metadata patterns
affects: [03-specialized, drupal-plugins-blocks, drupal-forms-api, drupal-routing-controllers]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Permissions.yml paired with routing.yml _permission requirement"
    - "Custom access checker with AccessResult cache contexts and tags"
    - "Entity access control handler with per-operation checks"
    - "CSRF protection via _csrf_token route requirement (not manual tokens)"
    - "XSS prevention layers: Twig auto-escape, #plain_text, Html::escape, Xss::filter"

key-files:
  created:
    - skills/drupal-access-security/SKILL.md
    - skills/drupal-access-security/references/.gitkeep
  modified: []

key-decisions:
  - "Included 7 wrong-way callouts (exceeding minimum of 5) covering orphaned permissions, hook_permission, bare AccessResult, manual CSRF, unsafe markup, controller access checks, and string concatenation in t()"
  - "Added D10 annotation and D11 attribute examples for entity access handler reference"
  - "Used AccessResult::orIf() pattern in entity handler to show composable access results"
  - "Included dynamic permissions via permission_callbacks as an advanced pattern"

patterns-established:
  - "Permission strings in routes MUST match definitions in permissions.yml"
  - "AccessResult always needs cache contexts (or use allowedIfHasPermission shortcut)"
  - "CSRF route requirement for non-form state-changing links only"
  - "Entity access via handler, not controller-level permission checks"

requirements-completed: [CORE-04]

# Metrics
duration: 3min
completed: 2026-03-06
---

# Phase 2 Plan 04: Access Security Skill Summary

**Access control decision guide with permissions, custom access checkers with cache metadata, entity access handlers, CSRF protection, and XSS prevention patterns with 7 wrong-way callouts**

## Performance

- **Duration:** 3 min
- **Started:** 2026-03-06T00:23:53Z
- **Completed:** 2026-03-06T00:26:25Z
- **Tasks:** 2
- **Files modified:** 2

## Accomplishments
- Created drupal-access-security skill with decision tree for choosing the right access pattern
- 7 wrong-way callouts covering the most common Claude access/security mistakes
- Complete custom access checker example with cache contexts and tags for correct caching
- Entity access control handler with per-operation checks (view/update/delete/create)
- CSRF protection pattern for non-form state-changing links with automatic token handling
- XSS prevention covering Twig auto-escaping, render arrays, PHP sanitization, and translation placeholders
- Cross-references to routing-controllers, entities-fields, plugins-blocks, and forms-api with graceful degradation
- Validated against all SKIL-01 through SKIL-07 quality standards -- all passed without edits

## Task Commits

Each task was committed atomically:

1. **Task 1: Create drupal-access-security SKILL.md** - `b0a3cbc` (feat)
2. **Task 2: Validate skill against SKIL-01 through SKIL-07 quality standards** - no changes needed (all standards passed without edits)

## Files Created/Modified
- `skills/drupal-access-security/SKILL.md` - Access control and security decision guide (453 lines)
- `skills/drupal-access-security/references/.gitkeep` - Empty references directory placeholder

## Decisions Made
- Included 7 wrong-way callouts (exceeding the plan's minimum of 5) to cover additional security pitfalls
- Added D10/D11 entity handler reference examples (annotation and attribute syntax)
- Used AccessResult::orIf() composition pattern in entity handler for owner-or-admin logic
- Included dynamic permissions via permission_callbacks as advanced pattern beyond basic static permissions
- Added string concatenation in t() as wrong-way callout for XSS via translation bypass

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness
- Access security skill complete, Phase 2 fully finished (all 4 plans complete)
- All Phase 2 skills (forms, config-storage, plugins-blocks pending, access-security) ready for Phase 3 specialized skills
- Cross-references established between access-security and all other skills for composable security guidance

## Self-Check: PASSED

- FOUND: skills/drupal-access-security/SKILL.md
- FOUND: skills/drupal-access-security/references/.gitkeep
- FOUND: commit b0a3cbc

---
*Phase: 02-core-workflow*
*Completed: 2026-03-06*

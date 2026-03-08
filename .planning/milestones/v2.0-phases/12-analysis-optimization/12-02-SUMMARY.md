---
phase: 12-analysis-optimization
plan: 02
subsystem: testing
tags: [evals, forms-api, theming, entities-fields, ConfirmFormBase, preprocess, bundle-entity]

# Dependency graph
requires:
  - phase: 11-batch-execution
    provides: "Baseline eval results showing 0% delta for forms-api, theming, entities-fields"
provides:
  - "Harder forms-api eval targeting ConfirmFormBase patterns"
  - "Harder theming eval targeting template_preprocess_HOOK and hook_theme_suggestions_HOOK"
  - "Harder entities-fields eval targeting bundle entity wiring with bundle_entity_type/bundle_of"
affects: [12-04-PLAN, eval-rerun, skill-optimization]

# Tech tracking
tech-stack:
  added: []
  patterns: ["Eval design targeting obscure SKILL.md-taught patterns that baseline Haiku unlikely produces"]

key-files:
  created: []
  modified:
    - skills/drupal-forms-api/evals/evals.json
    - skills/drupal-theming/evals/evals.json
    - skills/drupal-entities-fields/evals/evals.json

key-decisions:
  - "ConfirmFormBase selected over FormBase for forms-api eval -- tests getCancelUrl Url object, getQuestion method, redirect pattern"
  - "template_preprocess_HOOK naming chosen as primary theming differentiator -- Haiku commonly uses MODULE_preprocess_HOOK instead"
  - "Bundle entity wiring (bundle_entity_type + bundle_of + entity_keys bundle) chosen as entities-fields differentiator -- complex annotation pair Haiku frequently gets wrong"

patterns-established:
  - "Harder eval pattern: prompt asks for module using obscure pattern, expectations verify specific method signatures and return types"

requirements-completed: [ANLZ-03, CARRY-01]

# Metrics
duration: 2min
completed: 2026-03-08
---

# Phase 12 Plan 02: Harder Evals for Neutral-Delta Skills Summary

**3 harder evals targeting ConfirmFormBase (forms-api), template_preprocess_HOOK + theme suggestions (theming), and bundle entity wiring (entities-fields)**

## Performance

- **Duration:** 2 min
- **Started:** 2026-03-08T01:35:52Z
- **Completed:** 2026-03-08T01:37:47Z
- **Tasks:** 2
- **Files modified:** 3

## Accomplishments
- Replaced forms-api eval: now tests ConfirmFormBase with getCancelUrl() returning Url object, getQuestion() method, and submitForm redirect -- patterns Haiku confuses with ConfigFormBase
- Replaced theming eval: now tests template_preprocess_HOOKNAME naming convention and hook_theme_suggestions_HOOK() with double-underscore suggestion pattern -- Haiku commonly uses wrong naming
- Replaced entities-fields eval: now tests bundle entity wiring with bundle_entity_type/bundle_of annotation pair, EntityChangedTrait+changed field pair, and parent::baseFieldDefinitions() call

## Task Commits

Each task was committed atomically:

1. **Task 1: Write harder forms-api eval targeting ConfirmFormBase** - `4742fb2` (feat)
2. **Task 2: Write harder theming and entities-fields evals** - `4465ed0` (feat)

## Files Created/Modified
- `skills/drupal-forms-api/evals/evals.json` - Replaced ConfigFormBase eval with ConfirmFormBase eval targeting getCancelUrl Url object, getQuestion, and redirect patterns
- `skills/drupal-theming/evals/evals.json` - Replaced basic theming eval with template_preprocess_HOOK naming and hook_theme_suggestions_HOOK double-underscore pattern
- `skills/drupal-entities-fields/evals/evals.json` - Replaced simple content entity eval with bundle entity wiring eval targeting bundle_entity_type/bundle_of and EntityChangedTrait

## Decisions Made
- ConfirmFormBase chosen as forms-api differentiator: getCancelUrl() must return Url object (Haiku returns string), getQuestion() is required (Haiku skips it), ConfirmFormBase is less common than ConfigFormBase
- template_preprocess_HOOK naming chosen as theming differentiator: SKILL.md line 187 teaches it but Haiku commonly uses MODULE_preprocess_HOOK instead
- Bundle entity wiring chosen as entities-fields differentiator: bundle_entity_type + bundle_of + entity_keys bundle is a complex pattern Haiku frequently gets wrong

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered
None

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- All 3 harder evals ready for re-run in plan 12-04
- Each eval targets patterns taught in the corresponding SKILL.md that baseline Haiku is unlikely to produce correctly
- Previous eval patterns (ConfigFormBase, basic hook_theme, simple content entity) were too easy for Haiku without the skill

## Self-Check: PASSED

All 3 eval files exist and are valid JSON. Both task commits verified (4742fb2, 4465ed0). SUMMARY.md created.

---
*Phase: 12-analysis-optimization*
*Completed: 2026-03-08*

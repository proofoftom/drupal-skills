---
gsd_state_version: 1.0
milestone: v5.0
milestone_name: AI Integration & Eval Tooling
status: completed
stopped_at: Completed 24-02-PLAN.md
last_updated: "2026-03-09T15:27:14.312Z"
last_activity: 2026-03-09 -- completed A/B eval pipeline (plan 24-02), +14.8% delta (HIGH)
progress:
  total_phases: 6
  completed_phases: 3
  total_plans: 6
  completed_plans: 6
  percent: 100
---

# Project State

## Project Reference

See: .planning/PROJECT.md (updated 2026-03-09)

**Core value:** Claude can generate correct, production-ready Drupal module code across all major development domains when guided by these skills.
**Current focus:** v5.0 Phase 24 complete -- ready for Phase 25

## Current Position

Phase: 24 of 27 (AI Task Service + NL Task Creation) -- Plan 2 of 2 COMPLETE
Plan: 2 of 2 complete
Status: Complete
Last activity: 2026-03-09 -- completed A/B eval pipeline (plan 24-02), +14.8% delta (HIGH)

Progress: [██████████] 100% (v5.0 Phase 24)

## Accumulated Context

### Decisions

- [v4.0]: Aggregate +7.6% delta (WITHOUT 84.7% -> WITH 92.3%)
- [v4.0-UAT]: Manual testing found bugs automated pipeline missed -- browser assertions must be first-class
- [v5.0]: Tooling before features (Drush skill + eval-author agent before AI module features)
- [v5.0]: Custom table via hook_schema() for history (not content entity) -- append-only log data
- [v5.0]: eval-author enforces 60/20/20 assertion distribution to prevent tautological assertions
- [22-01]: Drush skill teaches USAGE not command authoring; command-authoring preserved as reference file
- [22-01]: Commands shown without ddev prefix for portability, with ddev note in intro
- [22-02]: eval-author uses Opus model for deep reasoning about skill impact on code quality
- [22-02]: 60/20/20 category distribution enforced with counting + rebalancing instructions
- [22-02]: 6 specific tautological assertion anti-patterns explicitly rejected
- [Phase 23-01]: bundle_of content in reference file (not inline) due to 497-line budget constraint
- [Phase 23-01]: forms-api AJAX section trimmed to 60 lines to fit exactly at 500-line limit
- [23-02]: All 17 static assertions made 100% differentiating (exceeds 60% minimum) -- matches gold-standard distribution
- [23-02]: Eval-author validated against Phase 18 gold-standard: 17 assertions, 5/5 differentiators, 0 tautological -- approved for production use
- [Phase 24]: Phase prompt references outdated CreateProjectTool as WRONG pattern; context_definitions vs getArguments() is the top differentiator; runtime assertions use flexible multi-name matching
- [Phase 24]: A/B delta was -3.6% (NEUT/negative): WITH variant copied AiFunctionCallBase from existing CreateProjectTool despite skill warning; skills failed to override codebase context signal for plugin base class selection
- [Phase 24]: Skill patches applied for FunctionCallBase (@AiFunctionCall WRONG) and @? optional injection (both in relevant skills); manual post-promotion bug fixes: service ID mismatch, nullable AI provider param, method rename
- [Phase 24-02]: v1 delta -3.6% (NEUT) -> skill patches -> v2 delta +14.8% (HIGH); WRONG/RIGHT callouts in skills essential to override codebase context when existing code shows wrong patterns
- [Phase 24-02]: SA-15 removed (CacheableJsonResponse wrong for POST endpoints); SA-16 relaxed (service ID naming flexibility); SA-6 kept as non-differentiating shared failure

### Pending Todos

- ~~entities-fields bundle_of gap: SKILL.md needs explicit coverage~~ DONE (23-01)
- Test vue-drupal.md reference impact on eval scores

### Blockers/Concerns

- None

## Session Continuity

Last session: 2026-03-09T15:20:31Z
Stopped at: Completed 24-02-PLAN.md
Resume file: None
Resume action: Run `/gsd:plan-phase` for Phase 25

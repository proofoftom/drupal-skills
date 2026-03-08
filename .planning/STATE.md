---
gsd_state_version: 1.0
milestone: v2.0
milestone_name: Eval & Optimization Loop
status: unknown
stopped_at: Completed 12-01-PLAN.md
last_updated: "2026-03-08T01:40:26.080Z"
last_activity: "2026-03-08 -- Session 21: Plan 12-01 complete (coding-standards skill, SKILL.md patches, eval prompt fix)."
progress:
  total_phases: 5
  completed_phases: 3
  total_plans: 23
  completed_plans: 10
  percent: 61
---

# Project State

## Project Reference

See: .planning/PROJECT.md (updated 2026-03-07)

**Core value:** Claude can generate correct, production-ready Drupal module code across all major development domains when guided by these skills.
**Current focus:** v2.0 -- Eval & Optimization Loop (Phase 12: Analysis & Optimization)

## Current Position

Phase: 12 of 12 -- Analysis & Optimization
Plans 01 and 02 complete. Plans 03, 04 remaining.
Last activity: 2026-03-08 -- Session 21: Plan 12-01 complete (coding-standards skill, SKILL.md patches, eval prompt fix).

Progress: [██████░░░░] 61%

### Phase 11 Final Results (13/13)
| Tier | Skill | WITH | WITHOUT | Delta |
|------|-------|------|---------|-------|
| HIGH | caching | 8/8 | 5/8 | +37.5% |
| HIGH | scaffold | 6/6 | 4/6 | +33.3% |
| HIGH | testing | 9/9 | 7/9 | +22.2% |
| MOD | config-storage | 8/8 | 7/8 | +12.5% |
| MOD | plugins-blocks | 8/8 | 7/8 | +12.5% |
| MOD | access-security | 9/10 | 8/10 | +10.0% |
| NEUT | forms-api | 9/9 | 9/9 | 0% |
| NEUT | database-api | 8/9 | 8/9 | 0% |
| NEUT | theming | 9/9 | 9/9 | 0% |
| NEUT | entities-fields | 9/9 | 9/9 | 0% |
| NEG | routing-controllers | 7/9 | 8/9 | -11.1% |
| NEG | views-dev | 8/9 | 9/9 | -11.1% |
| NEG | batch-queue-cron | 6/8 | 7/8 | -12.5% |

## Accumulated Context

### Decisions

- 08-01: Used Read-based skill loading instead of skills: frontmatter (deferred validation to Plan 02)
- 08-01: eval-grader uses model: inherit for flexible Opus grading
- 08-01: Single teardown script auto-detects both d10- and os-kg- prefixes
- 08-02: Read-based loading confirmed over skills: frontmatter (empirically validated)
- 08-02: Grader validated via bash/jq simulation; real subagent grading deferred to Phase 10
- 08-02: Production eval runs orchestrated directly from Opus session (no gsd-executor wrapper)
- [Phase 09]: 09-01: Text-only prompt replacement, expectations byte-identical, consistent 'a Drupal 10 site' phrasing
- [Phase 09]: 09-02: Removed 3 high-impact hints (max-age:0, queue pattern, Entity API ban) to maximize differentiation
- [Phase 09]: 09-02: Redesigned testing eval with calculator module (self-contained, no external deps)
- [Phase 10]: Caching delta +11% was ARTIFICIAL — flawed expectation required 'route' when SKILL.md teaches 'url.path'
- [Phase 10]: Scaffold delta +13% (7/8 with vs 6/8 without) -- .module discipline is the differentiator
- [Phase 10]: Pipeline validated but deltas were confounded by agent scaffold
- [Phase 11]: eval-executor model changed: sonnet → haiku (applied to .claude/agents/eval-executor.md)
- [Phase 11]: eval-grader model changed: inherit → sonnet (applied to .claude/agents/eval-grader.md)
- [Phase 11]: Agent scaffold confirmed as confound — "You are a Drupal 10 module developer" helps without-skill model
- [Phase 11]: Expectations test obvious patterns — need rewrite for ALL 13 skills
- [Phase 11]: Headless `claude -p` produces different (worse) baseline than agent subagent
- [Phase 11]: ddev naming: pass `routing-with` not `d10-routing-with` to setup script (it prepends d10-)
- [Phase 11]: Fixed caching evals.json expectation 3 (url.path accepted alongside route)
- [Phase 11]: ALWAYS use eval-browser for E2E, NEVER curl — user preference
- [Phase 11]: evals.json schema only supports `expectations` — no custom fields like browser_checks/static_checks
- [Phase 11]: Only keep E2E checks that exercise actual code paths (Behat mindset) — drop "exists in admin" checks
- [Phase 11]: phpcs --standard=Drupal,DrupalPractice as static analysis expectation for all skills
- [Phase 11]: phpunit execution is the correct E2E for testing skill
- [Phase 11]: Theming prompt updated to require page route for browser verification
- [Phase 11]: Mega-module integrated eval approach deferred to next milestone
- [Phase 11]: Agent harness confound CONFIRMED: 0% delta (agent) vs 37.5% delta (headless) on caching
- [Phase 11]: Pipeline retooled to headless `claude -p` for code generation — agents only for grading/browser
- [Phase 11]: eval-executor.md deprecated — headless templates documented inside it
- [Phase 11]: eval-browser receives expectations from orchestrator, outputs structured JSON with expectation text
- [Phase 11]: eval-grader uses browser report as evidence for `(via eval-browser)` expectations
- [Phase 12]: 12-02: ConfirmFormBase selected as forms-api eval differentiator (getCancelUrl Url object, getQuestion method)
- [Phase 12]: 12-02: template_preprocess_HOOK naming and hook_theme_suggestions_HOOK chosen as theming differentiators
- [Phase 12]: 12-02: Bundle entity wiring (bundle_entity_type + bundle_of + entity_keys bundle) chosen as entities-fields differentiator
- [Phase 12]: 12-01: Coding-standards skill kept to 150 lines -- focused on 4 phpcs failure patterns only
- [Phase 12]: 12-01: CRITICAL NEVER callout placed before DI flow explanation for maximum Haiku visibility

### Carried from v1.0

- Standard Drupal patterns show 0% delta on Sonnet AND Haiku; only non-obvious patterns show value
- Agent subagents inherit parent model; must use frontmatter `model: haiku` for controlled runs

### Pending Todos

- Consider Group contrib module for harder eval scenarios
- Consider harder caching scenarios: lazy_builder for per-user uncacheable content, CacheableMetadata bubbling
- Next milestone: integrated mega-module eval with full browser UAT

### Blockers/Concerns

- No content scaffolding for list-rendering evals (caching, theming, views) — deferred

## Session Continuity

Last session: 2026-03-08T01:40:26.078Z
Stopped at: Completed 12-01-PLAN.md
Resume file: None

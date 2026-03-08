---
gsd_state_version: 1.0
milestone: v2.0
milestone_name: Eval & Optimization Loop
status: complete
stopped_at: Completed 12-04-PLAN.md -- v2.0 milestone shipped
last_updated: "2026-03-08T02:23:30.814Z"
last_activity: 2026-03-08 -- Phase 12 complete. FINAL-REPORT.md compiled. v2.0 shipped.
progress:
  total_phases: 5
  completed_phases: 5
  total_plans: 23
  completed_plans: 23
  percent: 100
---

# Project State

## Project Reference

See: .planning/PROJECT.md (updated 2026-03-07)

**Core value:** Claude can generate correct, production-ready Drupal module code across all major development domains when guided by these skills.
**Current focus:** v2.0 -- Eval & Optimization Loop (Phase 12: Analysis & Optimization)

## Current Position

Phase: 12 of 12 -- Analysis & Optimization -- COMPLETE
All plans complete. v2.0 Eval & Optimization Loop milestone shipped.
Last activity: 2026-03-08 -- Phase 12 complete. FINAL-REPORT.md compiled. v2.0 shipped.

Progress: [██████████] 100%

### Final Results (Stabilized)
| Tier | Skill | WITH | WITHOUT | Delta | Note |
|------|-------|------|---------|-------|------|
| HIGH | caching | 8/8 | 5/8 | +37.5% | v2 |
| HIGH | routing-controllers | 9/9 | 6/9 | +33.3% | v3, was -11.1% |
| HIGH | scaffold | 6/6 | 4/6 | +33.3% | v2 |
| HIGH | testing | 9/9 | 7/9 | +22.2% | v2 |
| MOD | config-storage | 8/8 | 7/8 | +12.5% | v2 |
| MOD | batch-queue-cron | 8/8 | 7/8 | +12.5% | v3, was -12.5% |
| MOD | plugins-blocks | 8/8 | 7/8 | +12.5% | v2 |
| MOD | views-dev | 9/9 | 8/9 | +11.1% | v3, was -11.1% |
| MOD | access-security | 9/10 | 8/10 | +10.0% | v2 |
| NEUT | forms-api | 8/9 | 8/9 | 0% | v3, harder eval |
| NEUT | database-api | 7/9 | 7/9 | 0% | v3, prompt fix |
| NEUT | theming | 9/9 | 9/9 | 0% | v3, harder eval |
| NEUT | entities-fields | 7/9 | 7/9 | 0% | v3, harder eval |

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
- [Phase 12]: 12-03: Browser step dropped from eval pipeline -- zero discriminatory value, graders use drush/curl
- [Phase 12]: 12-03: Neutral-delta skills (forms-api, theming) confirmed as baseline Haiku knowledge -- no further iteration
- [Phase 12]: 12-03: entities-fields bundle_of gap: neither variant produces it, SKILL.md needs explicit coverage
- [Phase 12]: 12-03: Coding-standards baseline for both variants eliminates phpcs noise, isolates domain skill delta
- [Phase 12]: 12-04: Final portfolio: 4 HIGH (+31.6% avg), 5 MOD (+11.7% avg), 4 NEUT (0%), 0 NEG
- [Phase 12]: 12-04: Skill content placement matters more than presence -- CRITICAL NEVER callout placement produced +44.4% swing
- [Phase 12]: 12-04: Neutral skills confirmed as baseline Haiku knowledge -- forms-api, theming, database-api, entities-fields
- [Phase 12]: 12-04: v2.0 milestone shipped with FINAL-REPORT.md

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

Last session: 2026-03-08T02:23:30.812Z
Stopped at: Completed 12-04-PLAN.md -- v2.0 milestone shipped
Resume file: None

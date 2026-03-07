---
gsd_state_version: 1.0
milestone: v2.0
milestone_name: Eval & Optimization Loop
status: in_progress
stopped_at: Phase 11 paused — evals.json needs schema cleanup before eval runs
last_updated: "2026-03-07T12:25:30Z"
last_activity: 2026-03-07 -- Session 14 reworked E2E strategy. Added browser_checks to evals.json (needs cleanup — non-schema field). Established eval verification approach for this milestone.
progress:
  total_phases: 5
  completed_phases: 3
  total_plans: 19
  completed_plans: 7
  percent: 37
---

# Project State

## Project Reference

See: .planning/PROJECT.md (updated 2026-03-07)

**Core value:** Claude can generate correct, production-ready Drupal module code across all major development domains when guided by these skills.
**Current focus:** v2.0 -- Eval & Optimization Loop (Phase 11: evals.json schema cleanup, then validation run)

## Current Position

Phase: 11 of 12 -- Batch Execution
Plan: 3 of 13 complete (11-01 setup, 11-02 access-security, 11-03 routing-controllers)
Status: IN PROGRESS — evals.json has non-schema fields to clean up, then ready to validate
Last activity: 2026-03-07 -- Reworked eval verification: eval-browser for meaningful E2E (not curl), phpcs for static analysis, phpunit for testing skill

Progress: [████░░░░░░] 37%

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

### Carried from v1.0

- Standard Drupal patterns show 0% delta on Sonnet AND Haiku; only non-obvious patterns show value
- Agent subagents inherit parent model; must use frontmatter `model: haiku` for controlled runs

### Pending Todos

- Clean up evals.json: remove browser_checks/cli_checks, promote valuable E2E to expectations, add phpcs
- Consider Group contrib module for harder eval scenarios
- Next milestone: integrated mega-module eval with full browser UAT

### Blockers/Concerns

- evals.json files have non-schema fields — must clean up before eval runs
- No content scaffolding for list-rendering evals (caching, theming, views) — deferred

## Session Continuity

Last session: 2026-03-07T12:25:30Z
Stopped at: evals.json needs schema cleanup (remove browser_checks/cli_checks, promote valuable E2E to expectations, add phpcs), then validate with caching re-run
Resume file: .planning/phases/11-batch-execution/.continue-here.md

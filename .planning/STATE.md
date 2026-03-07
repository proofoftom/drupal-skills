---
gsd_state_version: 1.0
milestone: v2.0
milestone_name: Eval & Optimization Loop
status: blocked
stopped_at: Phase 11 paused — eval infrastructure redesign needed
last_updated: "2026-03-07T12:00:00Z"
last_activity: 2026-03-07 -- Discovered eval scaffold confound + expectations test obvious patterns. Headless comparison proved skills DO teach valuable patterns but agent scaffold compresses delta to 0%.
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
**Current focus:** v2.0 -- Eval & Optimization Loop (Phase 11 PAUSED for pipeline redesign)

## Current Position

Phase: 11 of 12 -- Batch Execution
Plan: 3 of 13 complete (11-01 setup, 11-02 access-security, 11-03 routing-controllers)
Status: PAUSED — eval infrastructure has two confounds that produce 0% delta on everything
Last activity: 2026-03-07 -- Headless vs subagent comparison confirmed scaffold confound

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

### Carried from v1.0

- Standard Drupal patterns show 0% delta on Sonnet AND Haiku; only non-obvious patterns show value
- Agent subagents inherit parent model; must use frontmatter `model: haiku` for controlled runs

### Pending Todos

- Audit and rewrite ALL 13 evals.json for non-obvious patterns
- Fix eval-executor scaffold (strip for without-skill or use headless)
- Consider Group contrib module for harder eval scenarios

### Blockers/Concerns

- **BLOCKED**: Cannot run remaining evals until scaffold confound and expectations are fixed
- Running with current setup will produce 0% delta on everything

## Session Continuity

Last session: 2026-03-07T12:00:00Z
Stopped at: Phase 11 paused — eval infrastructure redesign needed (scaffold confound + expectations audit)
Resume file: .planning/phases/11-batch-execution/.continue-here.md

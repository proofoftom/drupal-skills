# Project Retrospective

*A living document updated after each milestone. Lessons feed forward into future planning.*

## Milestone: v1.0 -- Skill Authoring

**Shipped:** 2026-03-07
**Phases:** 7 | **Plans:** 28 | **Sessions:** ~8

### What Was Built
- 13 Drupal skills covering all 18 chapters of Sipos book (~6,990 lines)
- Eval infrastructure: setup/teardown scripts, evals.json for all 13 skills, E2E assertion helpers
- Live eval results for 4 skills proving concept (caching +75%, scaffold +43%)
- Expanded eval to all 13 skills with differentiating assertions
- Packaging: install.sh, README, MIT license

### What Worked
- Wave-based build order gave clean dependency chain for cross-references
- Decision-guide format with wrong-way callouts proved highly effective for skill quality
- Parallel ddev instances for eval runs worked well (no memory issues)
- Phase 6 live eval loop validated the approach quickly with 4 representative skills
- Source-material-driven assertions (Phase 7 rewrite) identified what actually differentiates skills

### What Was Inefficient
- Phase 5 eval methodology was too simplistic (expected-behavior docs, not real A/B runs)
- Phase 7 initial assertions tested standard patterns Sonnet already knows (0% delta for 9/13 skills)
- Nested claude session management (CLAUDECODE env var) was discovered late, required infrastructure fix
- Agent subagent model control was trial-and-error (/model switching, not frontmatter -- corrected for v2.0)
- Phase 7 ran 2 iterations of eval runs before realizing assertions needed fundamental rewrite

### Patterns Established
- Skill-creator anatomy: frontmatter + <500 line body + references/ subdirectory
- Wrong-way callouts: "Do NOT" patterns targeting common Claude mistakes per domain
- Differentiating assertions: test non-obvious patterns from SKILL.md, not standard Drupal
- ddev-based eval: setup script creates isolated Drupal 10 instances, teardown cleans up
- Dual-agent eval: with-skill reads SKILL.md, without-skill never hears about it

### Key Lessons
1. Standard Drupal patterns (routing, forms, blocks) show 0% delta on Sonnet -- skills only add value for knowledge gaps
2. Eval assertions must target what the SKILL teaches that the model doesn't already know
3. Agent subagents inherit parent model -- must use frontmatter `model: sonnet` for controlled runs
4. ddev-router health checks fail ~50% of first starts -- `docker restart ddev-router` is reliable recovery
5. Must include "Do NOT ask questions" in without-skill eval prompts to prevent Sonnet from asking clarification

### Cost Observations
- Model mix: ~70% Opus (skill authoring, grading), ~30% Sonnet (eval execution)
- Sessions: ~8 sessions over 2 days
- Notable: Skill authoring phases (1-4) were very fast (~3min/plan); eval phases (6-7) were much slower (~30-50min/plan)

---

## Milestone: v2.0 -- Eval & Optimization Loop

**Shipped:** 2026-03-08
**Phases:** 5 | **Plans:** 23 | **Sessions:** ~11

### What Was Built
- Headless eval pipeline (`claude -p`) eliminating agent harness confound
- 13 rewritten eval prompts for fresh Drupal 10 with differentiating assertions
- Coding-standards skill for cross-cutting phpcs compliance
- Full 13-skill benchmark suite with tier classifications
- FINAL-REPORT.md with empirically-grounded portfolio analysis

### What Worked
- Discovering agent harness confound early in Phase 11 saved the entire eval from invalid data
- Headless `claude -p` pipeline produced clean, reproducible signals
- Coding-standards baseline skill elegantly separated phpcs noise from domain skill value
- Phase 12 iteration cycle: fix SKILL.md -> re-run -> validate -- turned 3 negative-delta skills positive
- CRITICAL NEVER callout placement experiment showed +44.4% swing -- proved content placement matters

### What Was Inefficient
- Phase 10 pipeline validation was confounded by agent scaffold -- had to redo with headless pipeline in Phase 11
- Phase 11 batch execution was manual (1 skill at a time) despite plans for 3-4 per session batching
- Multiple rounds of evals.json schema discovery -- browser_checks field wasn't supported, wasted time adding it
- E2E browser verification (eval-browser) proved zero discriminatory value -- dropped in v3 runs
- Phase 11 had 13 plans but most were identical batch runs -- over-planned for repetitive work

### Patterns Established
- Headless `claude -p --model haiku` for all code generation (never agent subagents)
- `unset CLAUDECODE` before headless sessions to prevent environment leakage
- Coding-standards skill loaded as baseline for both variants (isolates domain delta)
- Single-run design sufficient for tier classification (HIGH/MOD/NEUT clear in 1 run)
- Eval grading with sonnet agent reading code + structured expectations

### Key Lessons
1. Agent harness provides implicit knowledge -- NEVER use agent subagents for controlled A/B code generation
2. Skill content placement matters more than presence -- CRITICAL NEVER callouts near the relevant code flow produce the biggest swings
3. 4 neutral-delta skills (forms-api, theming, database-api, entities-fields) represent domains where Haiku baseline is sufficient -- accept this honestly
4. phpcs compliance is cross-cutting -- dedicated skill is better than per-domain coverage
5. E2E browser testing adds no discriminatory value for eval grading -- drush/curl is sufficient evidence
6. Expectations must test what the skill teaches that the model doesn't know -- "obvious" assertions produce 0% delta

### Cost Observations
- Model mix: ~50% Opus (orchestration, grading), ~40% Haiku (code gen, browser), ~10% Sonnet (grading)
- Sessions: ~11 sessions over 3 days
- Notable: Code gen moved from Sonnet to Haiku in Phase 11 (cheaper + more accurate baseline measurement)

---

## Cross-Milestone Trends

### Process Evolution

| Milestone | Sessions | Phases | Key Change |
|-----------|----------|--------|------------|
| v1.0 | ~8 | 7 | Established skill authoring + eval pipeline |
| v2.0 | ~11 | 5 | Headless pipeline, coding-standards baseline, tier classifications |

### Top Lessons (Verified Across Milestones)

1. Eval methodology matters more than skill content -- wrong assertions produce meaningless data (v1.0 Phase 7, v2.0 Phase 10-11)
2. Skills add value only where model training data has gaps; standard patterns are already known (v1.0 0% delta on 9/13, v2.0 confirmed 4 neutral)
3. Agent scaffolding confounds eval results -- implicit knowledge in system prompts inflates baselines (v1.0 discovered, v2.0 confirmed and fixed)
4. Content placement within skill files matters as much as content presence (v2.0 CRITICAL NEVER callout experiment)

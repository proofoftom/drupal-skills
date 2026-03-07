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

## Cross-Milestone Trends

### Process Evolution

| Milestone | Sessions | Phases | Key Change |
|-----------|----------|--------|------------|
| v1.0 | ~8 | 7 | Established skill authoring + eval pipeline |

### Top Lessons (Verified Across Milestones)

1. Eval methodology matters more than skill content -- wrong assertions produce meaningless data
2. Skills add value only where model training data has gaps; standard patterns are already known

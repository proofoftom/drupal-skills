# Phase 6: Live Eval Loop - Context

**Gathered:** 2026-03-05
**Status:** Ready for planning
**Source:** PRD Express Path (docs/plans/2026-03-05-skill-eval-loop.md)

<domain>
## Phase Boundary

Run 4 representative Drupal skills through real functional evaluation: spawn Sonnet 4.6 subagents that stand up live Drupal instances via ddev, generate code from eval prompts with and without the skill loaded, grade outputs against functional assertions, and produce aggregated benchmarks with HTML viewer for human review.

This is **functional evaluation** (does the skill produce correct Drupal code?) not trigger evaluation (does the description make Claude invoke the skill?). The skill-creator's trigger eval infrastructure (`run_eval.py`, `run_loop.py`) handles the latter separately.

</domain>

<decisions>
## Implementation Decisions

### Skills Under Test
- drupal-module-scaffold (scaffold a module with .info.yml, .module, PSR-4)
- drupal-entities-fields (content entity type with base fields and handlers)
- drupal-caching (block plugin with cache tags, contexts, no max-age 0)
- drupal-testing (kernel test with correct base class and setup patterns)

### Eval Architecture
- Each skill gets an evals.json with functional assertions
- For each skill, spawn 2 Sonnet 4.6 subagents: with-skill and without-skill (baseline)
- Each subagent clones os-knowledge-garden, starts ddev, installs Drupal via `scripts/install.sh --demo=cascadia`
- Subagents work in `/tmp/os-kg-{name}/html/modules/custom/`
- After task completion, outputs copied to workspace directory and transcript written
- ddev environments torn down after each run

### Environment Management
- Shared setup script (`eval/setup-drupal-env.sh`) handles clone + ddev start + install
- Shared teardown script (`eval/teardown-drupal-env.sh`) handles ddev delete + cleanup
- Unique ddev project names via sed on .ddev/config.yaml
- Each instance uses ~512MB RAM; max 4 simultaneous

### Grading and Analysis
- Use skill-creator's grader agent (`agents/grader.md`) for grading
- Use skill-creator's `aggregate_benchmark.py` for aggregation
- Use skill-creator's `generate_review.py` for HTML viewer
- Grade all 8 runs (4 skills x 2 configs) and compare pass rates

### Parallelism Strategy
- evals.json creation: sequential (fast file creation)
- Eval runs: 2 skills at a time (4 agents per batch) to manage ddev resources
  - Batch 1: scaffold + entities
  - Batch 2: caching + testing
- Grading: all 8 graders in parallel (no ddev needed)
- Aggregation and analysis: sequential

### Constants
- Skill-creator path: `/home/proofoftom/.claude/plugins/cache/claude-plugins-official/skill-creator/205b6e0b3036/skills/skill-creator`
- os-knowledge-garden source: `/home/proofoftom/Code/drupal-skills/os-knowledge-garden`
- Gemini API key: already configured in os-knowledge-garden/.ddev/.env
- Skills base: `/home/proofoftom/Code/drupal-skills/skills`

### Claude's Discretion
- Exact workspace directory naming and structure
- How to handle ddev startup failures or timeouts
- Whether to use Task tool or Agent tool for subagent spawning
- Plan granularity (how many GSD plans to split into)

</decisions>

<specifics>
## Specific Ideas

- Eval prompts are already defined in detail in the PRD (docs/plans/2026-03-05-skill-eval-loop.md Tasks 1-4)
- Each eval has specific functional assertions (e.g., "ddev drush en event_analytics returns exit code 0")
- Timeout: install.sh --demo=cascadia takes 3-5 minutes; agents need 10+ minute timeouts
- Analysis should identify: non-discriminating assertions (pass both ways), always-fail assertions (too strict), high-variance assertions (skill making real difference)

</specifics>

<deferred>
## Deferred Ideas

- Scaling to all 13 skills (this phase covers 4 representative ones)
- Multiple iterations per skill (this phase does 1 iteration each)
- Trigger evaluation integration (separate from functional eval)
- Skill revision based on eval results

</deferred>

---

*Phase: 06-live-eval-loop*
*Context gathered: 2026-03-05 via PRD Express Path*

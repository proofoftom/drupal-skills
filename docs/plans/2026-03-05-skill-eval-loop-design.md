# Skill-Creator Eval Loop for Drupal Skills

## Summary

Run 4 representative Drupal skills through the skill-creator's actual eval infrastructure — real subagent runs with/without skills on Sonnet 4.6, functional assertions against live Drupal instances, graded benchmarks, and HTML eval viewer for human review.

## Skills Under Test

1. `drupal-module-scaffold` — simple file generation
2. `drupal-entities-fields` — complex entity API, D10/D11 transition
3. `drupal-caching` — performance pitfalls (max-age:0 antipattern)
4. `drupal-testing` — test methodology and base class selection

## Approach

### Per skill:
1. Create `evals/evals.json` with concrete, functional assertions
2. Spawn 2 Sonnet 4.6 subagents in parallel: with-skill and without-skill (baseline)
3. Each agent clones os-knowledge-garden to a unique directory
4. Each agent runs `ddev start` + `scripts/install.sh --demo=cascadia`
5. Agent generates code from eval prompt and places it in the Drupal instance
6. Functional verification: `drush en`, route checks, test runs
7. Agent tears down with `ddev delete -O`
8. Grade outputs against assertions
9. Aggregate into `benchmark.json`
10. Launch eval viewer (`generate_review.py`) for human review

### Key constraints:
- **Executor model:** Sonnet 4.6 (`claude-sonnet-4-6`) — matches what most users run
- **Drupal instance:** os-knowledge-garden clone with `--demo=cascadia`
- **Unique ddev names:** Each clone gets a unique directory to avoid ddev conflicts
- **Description optimization:** Deferred until after eval results reviewed

## Workspace Structure

```
skills/drupal-module-scaffold/
  evals/evals.json
drupal-module-scaffold-workspace/
  iteration-1/
    eval-scaffold-module/
      with_skill/outputs/
      without_skill/outputs/
      eval_metadata.json
      grading.json
      timing.json
    benchmark.json
```

## What changes from Phase 5's hypothetical approach
- **Delete:** `eval/eval-results.md` (hypothetical, replaced by real data)
- **Keep:** `eval/eval-prompts.md` (source material for evals.json)
- **Add:** Per-skill `evals/evals.json`, workspace dirs with real benchmark data

## Out of scope (this pass)
- Iterative skill rewriting (only if eval results show problems)
- Multi-skill eval prompts (after single-skill evals validated)
- Remaining 9 skills (scale up after reviewing these 4)
- Description optimization via `run_loop.py` (after evals pass)

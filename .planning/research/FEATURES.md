# Feature Landscape: v2.0 Eval & Optimization Pipeline

**Domain:** Automated skill evaluation pipeline for 13 Drupal skills
**Researched:** 2026-03-06
**Supersedes:** v1.0 FEATURES.md (which covered SKILL.md content features -- those are LOCKED)

## Table Stakes

Features the eval pipeline MUST have to produce valid, actionable benchmark data. Missing any of these means eval results are unreliable or require heavy manual intervention.

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| **With-skill / without-skill subagent pairs** | Knowledge isolation is the entire methodology. A single agent that reads SKILL.md then runs "without" is contaminated. Must be 2 separate subagents per eval. | Med | Skill-creator SKILL.md Step 1: "spawn two subagents in the same turn -- one with the skill, one without" |
| **Model control via agent frontmatter** | Evals must run on Sonnet (the target user model), not Opus. Agent `.md` files support `model: sonnet` in YAML frontmatter. Eliminates fragile `/model` switching. | Low | Discovered from .claude/agents/*.md pattern. Phase 6 proved Sonnet is the right executor model. |
| **Grader agent following skill-creator grader.md** | Grading must produce `grading.json` with exact schema: `expectations[].text/passed/evidence`, `summary.pass_rate`, `claims[]`, `eval_feedback`. Viewer depends on these exact field names. | Med | Read grader.md: fields must be `text`, `passed`, `evidence` -- NOT `name`/`met`/`details` |
| **aggregate_benchmark.py integration** | Script reads `eval-*/with_skill/run-N/grading.json` directory tree. Produces `benchmark.json` with `run_summary.with_skill/without_skill` stats (mean/stddev/min/max) and delta. | Low | Script already exists at skill-creator path. Supports workspace layout natively. |
| **generate_review.py viewer** | HTML viewer with Outputs tab (per-case review + feedback textbox) and Benchmark tab (quantitative comparison). Use `--static` flag for headless/cowork environments. | Low | Script exists. Use `--static` for file output. `--previous-workspace` for iteration 2+. |
| **eval_metadata.json per eval directory** | Each eval dir needs `eval_id`, `eval_name`, `prompt`, `assertions[]`. The aggregate script reads `eval_metadata.json` for eval IDs. | Low | Must create fresh per iteration -- does not carry over automatically. |
| **timing.json capture from subagent notifications** | When subagent completes, notification includes `total_tokens` and `duration_ms`. Must save immediately -- data is NOT persisted elsewhere. | Low | Skill-creator SKILL.md Step 3: "This is the only opportunity to capture this data" |
| **Workspace directory structure** | `<skill>-workspace/iteration-N/eval-<name>/{with_skill,without_skill}/run-1/{outputs/,grading.json,timing.json}` | Low | Must match what aggregate_benchmark.py expects. Script auto-discovers eval-* dirs. |
| **Differentiating assertions** | Assertions must test skill-specific knowledge, not standard Drupal patterns. Phase 6 lesson: standard assertions yield 0% delta because Sonnet already knows basics. | High | Critical insight from iteration 1. All 13 evals.json already rewritten with differentiating assertions (phase 07-06). |
| **ddev environment per eval** | Each eval run needs a working Drupal instance. setup-drupal-env.sh creates ddev project, installs Drupal, enables test module. | Med | Existing scripts. Max 2 ddev instances at once (1 skill = 2 runs). |
| **Clean teardown between skills** | Must `drush pm:uninstall` before `rm -rf` files, or tear down entire ddev env. Stale state corrupts subsequent evals. | Med | Eval rule #9. Delete stale ddev projects: `ddev list | grep -o 'os-kg-[^ ]*' | xargs -I{} ddev delete -O {}` |

## Differentiators

Features that elevate this pipeline beyond manual eval runs. Not strictly required but dramatically reduce friction for 13-skill batch evaluation.

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| **Batch orchestration script** | Run all 13 skills through eval with a single command. Loop: setup ddev, spawn with/without agents, grade, aggregate, teardown, next skill. Currently requires ~30 manual steps per skill. | High | The single highest-ROI feature. Transforms 6-hour manual process into autonomous batch run. |
| **eval-executor subagent (.claude/agents/eval-executor.md)** | Custom agent with `model: sonnet` frontmatter. Receives prompt + optional skill path. Saves outputs to specified directory. Standardized across all 13 skills. Eliminates per-skill prompt crafting. | Med | Replaces fragile `/model sonnet` dance. Agent file is ~20 lines of YAML+markdown. |
| **eval-grader subagent (.claude/agents/eval-grader.md)** | Custom agent that reads skill-creator's grader.md instructions. Receives expectations + outputs_dir + transcript_path. Produces grading.json. Opus-level model for grading quality. | Med | Currently grading is inline. Dedicated agent makes it reproducible and parallelizable. |
| **Programmatic assertion checking** | Write scripts for assertions that can be verified programmatically (file exists, grep for pattern, drush status check, curl endpoint). Faster, more reliable, reusable across iterations. | Med | Grader.md: "For assertions that can be checked programmatically, write and run a script rather than eyeballing it" |
| **eval-browser subagent (agent-browser)** | Automated UAT via browser: `drush uli` generates one-time login URL, agent-browser navigates Drupal admin, verifies themed output, tests access control (anonymous vs authenticated). | High | Required for theming, access-security, forms-api evals where output is visual/interactive. Needs agent-browser MCP. |
| **Iteration comparison** | Pass `--previous-workspace` to generate_review.py for side-by-side comparison of iterations. Shows what changed, whether feedback was addressed. | Low | Already built into generate_review.py. |
| **Analyst pass after aggregation** | Read benchmark data and surface patterns: non-discriminating assertions (always pass both configs), high-variance evals (flaky), time/token tradeoffs. | Med | Skill-creator Step 4.3. Use agents/analyzer.md patterns. |
| **Multi-run variance analysis** | Run each eval 3 times per configuration to get stddev. High variance = flaky eval, not meaningful delta. | Med | benchmark.json schema supports `runs_per_configuration: 3`. aggregate_benchmark.py calculates stddev. |
| **Skill tier classification** | After all 13 skills have benchmark data, classify into tiers: High Delta (>15%), Moderate (5-15%), Low (<5%). Drives optimization priority. | Low | Post-batch analysis. Phase 6 established tier thresholds. |

## Anti-Features

Features to explicitly NOT build. Scope traps that would delay the pipeline without improving eval quality.

| Anti-Feature | Why Avoid | What to Do Instead |
|--------------|-----------|-------------------|
| **Custom HTML viewer** | generate_review.py already handles both server and static HTML modes. Writing custom HTML duplicates effort and misses viewer features (feedback capture, benchmark tab). | Use generate_review.py with `--static` flag. Skill-creator SKILL.md is emphatic: "use generate_review.py to create the viewer; there's no need to write custom HTML" |
| **Description/trigger optimization** | PROJECT.md explicitly defers this. Content evals must prove value before optimizing triggers. Premature trigger optimization wastes cycles. | Run content evals first. Description optimization is a separate milestone after deltas are proven. |
| **SKILL.md content changes** | Skills are LOCKED. Changing skill content based on eval results should only happen if eval findings specifically demand it. | Optimize assertions and eval methodology, not skill content. |
| **os-knowledge-garden as eval environment** | os-kg has OpenSocial + custom modules that add complexity/flakiness. Fresh Drupal 10 ddev instances are faster, more controlled. | Use `ddev config --project-type=drupal --php-version=8.3` with standard `--demo=cascadia` or clean install. |
| **Parallel multi-skill eval runs** | Running 2+ skills simultaneously means 4+ ddev instances. Docker resource contention causes flaky failures. | Run 1 skill at a time (2 ddev instances max). Sequential is slower but reliable. |
| **claude -p for eval runs** | Black box, hangs silently, no observability. Memory rule #4: "No headless `claude -p`". | Use Agent subagents for full observability of eval runs. |
| **Interactive git rebase or manual commits during eval** | Eval runs should not create git state. Commits happen after batch analysis, not per-skill. | Orchestrator commits after all evals complete. |
| **Blind comparison (comparator.md)** | Skill-creator's blind A/B comparison is for comparing two VERSIONS of a skill. We're comparing skill-vs-no-skill, which is simpler -- just compare pass rates. | Blind comparison adds complexity with no additional signal for our use case. |

## Feature Dependencies

```
eval-executor subagent ──> model: sonnet frontmatter (controls executor model)
eval-executor subagent ──> ddev environment (needs running Drupal to execute against)

eval-grader subagent ──> grader.md instructions (knows grading protocol)
eval-grader subagent ──> grading.json schema (produces correct format)

aggregate_benchmark.py ──> grading.json files (reads from workspace tree)
aggregate_benchmark.py ──> eval_metadata.json (reads eval IDs)

generate_review.py ──> benchmark.json (displays benchmark tab)
generate_review.py ──> workspace directory tree (displays outputs tab)

eval-browser subagent ──> agent-browser MCP (navigates Drupal UI)
eval-browser subagent ──> drush uli (generates login URLs)
eval-browser subagent ──> ddev environment (needs running Drupal)

batch orchestrator ──> eval-executor subagent (spawns per skill)
batch orchestrator ──> eval-grader subagent (grades after runs)
batch orchestrator ──> aggregate_benchmark.py (aggregates per skill)
batch orchestrator ──> ddev setup/teardown scripts (manages environments)

tier classification ──> all 13 benchmark.json files (needs complete data)
```

## MVP Recommendation

### Phase 1: Subagent Infrastructure (foundation -- everything depends on this)

1. **eval-executor.md agent file** -- `model: sonnet` frontmatter, standardized prompt template with skill path injection for with-skill runs and omission for without-skill runs. This is the #1 blocker.
2. **eval-grader.md agent file** -- Reads grader.md instructions, produces compliant grading.json. Can run on Opus for quality.
3. **Workspace directory conventions** -- Document and enforce the exact tree structure that aggregate_benchmark.py expects.

### Phase 2: Single-Skill Pipeline (prove the loop works end-to-end)

4. **Run 1 skill through full pipeline** -- Setup ddev, spawn executor pairs, capture timing, grade, aggregate, generate viewer. Validate all schemas match.
5. **Programmatic assertion scripts** -- Write reusable assertion checkers (file-exists, grep-pattern, curl-endpoint, drush-check).

### Phase 3: Batch Execution (the ROI multiplier)

6. **Batch orchestration** -- Script or orchestrator prompt that loops through all 13 skills: setup, run, grade, aggregate, teardown, next.
7. **Tier classification and final analysis** -- After all 13 complete, classify tiers and produce summary report.

### Phase 4: Browser-Based Evals (stretch -- for visual/interactive skills)

8. **eval-browser subagent** -- agent-browser integration for theming, access-security, forms-api evals where assertions require UI verification.

**Defer:** Description/trigger optimization (separate milestone), SKILL.md content changes (only if eval data demands it).

## Key Dependencies on Skill-Creator Tooling

| Skill-Creator Asset | Location | How We Use It | Modification Needed |
|---------------------|----------|---------------|---------------------|
| `grader.md` | `~/.claude/plugins/.../agents/grader.md` | Read by eval-grader subagent for grading protocol | None -- use as-is |
| `schemas.md` | `~/.claude/plugins/.../references/schemas.md` | Reference for grading.json, benchmark.json, timing.json formats | None -- use as-is |
| `aggregate_benchmark.py` | `~/.claude/plugins/.../scripts/aggregate_benchmark.py` | Run after grading to produce benchmark.json + benchmark.md | None -- supports workspace layout natively |
| `generate_review.py` | `~/.claude/plugins/.../eval-viewer/generate_review.py` | Generate HTML viewer with `--static` flag for review | None -- use `--static` for headless |
| `analyzer.md` | `~/.claude/plugins/.../agents/analyzer.md` | Analyst pass patterns (non-discriminating assertions, variance) | None -- reference patterns |

## Complexity Notes

| Feature | Estimated Effort | Risk | Notes |
|---------|-----------------|------|-------|
| eval-executor subagent | 1 hour | Low | Simple agent file. Main work is prompt template. |
| eval-grader subagent | 1 hour | Low | Wrapper around existing grader.md. |
| Single-skill pipeline validation | 2-3 hours | Med | Integration testing -- schemas must match exactly or viewer shows empty. |
| Batch orchestrator | 3-4 hours | Med | 13 skills x (setup + 2 runs + grade + aggregate + teardown). Error recovery is the hard part. |
| eval-browser (agent-browser) | 4-6 hours | High | New dependency. Browser automation against Drupal is flaky. Login state, AJAX waits, viewport issues. |
| Programmatic assertion scripts | 2-3 hours | Low | Many assertions can reuse patterns from existing e2e-assert.sh. |
| Tier classification | 1 hour | Low | Simple analysis once all benchmark.json files exist. |

## Sources

- Skill-creator SKILL.md (`~/.claude/plugins/.../skill-creator/SKILL.md`) -- eval workflow methodology, Steps 1-5
- Skill-creator grader.md (`~/.claude/plugins/.../agents/grader.md`) -- grading protocol, output schema
- Skill-creator schemas.md (`~/.claude/plugins/.../references/schemas.md`) -- JSON schemas for all eval artifacts
- Skill-creator aggregate_benchmark.py (`~/.claude/plugins/.../scripts/aggregate_benchmark.py`) -- aggregation logic, directory layout support
- Project MEMORY.md -- phase 6/7 eval results, iteration 1 lessons, execution rules
- PROJECT.md -- v2.0 milestone definition, target features, constraints

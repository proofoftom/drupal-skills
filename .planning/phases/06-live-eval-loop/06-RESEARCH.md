# Phase 6: Live Eval Loop - Research

**Researched:** 2026-03-05
**Domain:** Functional evaluation of Drupal skills using subagents, ddev, and skill-creator tooling
**Confidence:** HIGH

## Summary

This phase runs 4 representative Drupal skills through functional evaluation: spawning Sonnet 4.6 subagents that build Drupal modules against live ddev instances, then grading the outputs using the skill-creator's grader agent and aggregation tooling. The skill-creator provides general-purpose grading, aggregation, and HTML review tools, but has no built-in "functional eval" executor -- that part is custom to this phase.

The key technical challenges are: (1) managing multiple simultaneous ddev instances on a 16GB RAM machine, (2) structuring workspace directories to match what aggregate_benchmark.py and generate_review.py expect, and (3) correctly prompting executor subagents to produce transcripts and outputs the grader can consume.

**Primary recommendation:** Structure workspace directories as `{skill}-workspace/iteration-1/eval-{name}/{with,without}_skill/run-1/{outputs/,grading.json,transcript.md}` to match aggregate_benchmark.py's expected layout exactly. Limit to 2 concurrent ddev instances (not 4) given 6.2GB available RAM.

<user_constraints>

## User Constraints (from CONTEXT.md)

### Locked Decisions
- **Skills Under Test:** drupal-module-scaffold, drupal-entities-fields, drupal-caching, drupal-testing
- **Eval Architecture:** Each skill gets evals.json; 2 Sonnet 4.6 subagents per skill (with-skill, without-skill); each clones os-knowledge-garden, starts ddev, installs via `scripts/install.sh --demo=cascadia`; subagents work in `/tmp/os-kg-{name}/html/modules/custom/`; outputs copied to workspace; ddev torn down after each run
- **Environment Management:** Shared setup script (`eval/setup-drupal-env.sh`), shared teardown (`eval/teardown-drupal-env.sh`), unique ddev project names via sed, max 4 simultaneous instances (~512MB each)
- **Grading and Analysis:** Use skill-creator's grader agent, aggregate_benchmark.py, generate_review.py; grade all 8 runs; compare pass rates
- **Parallelism Strategy:** evals.json creation sequential; eval runs 2 skills at a time (4 agents per batch); grading all 8 in parallel; aggregation sequential
- **Constants:** Skill-creator at `/home/proofoftom/.claude/plugins/cache/claude-plugins-official/skill-creator/205b6e0b3036/skills/skill-creator`; os-knowledge-garden at `/home/proofoftom/Code/drupal-skills/os-knowledge-garden`; skills at `/home/proofoftom/Code/drupal-skills/skills`

### Claude's Discretion
- Exact workspace directory naming and structure
- How to handle ddev startup failures or timeouts
- Whether to use Task tool or Agent tool for subagent spawning
- Plan granularity (how many GSD plans to split into)

### Deferred Ideas (OUT OF SCOPE)
- Scaling to all 13 skills
- Multiple iterations per skill
- Trigger evaluation integration
- Skill revision based on eval results

</user_constraints>

## Standard Stack

### Core
| Tool | Version | Purpose | Why Standard |
|------|---------|---------|--------------|
| ddev | v1.24.8 | Drupal environment management | Already installed, known working with os-knowledge-garden |
| skill-creator grader.md | N/A | Agent protocol for grading eval outputs | Standard skill-creator tool, produces grading.json |
| aggregate_benchmark.py | N/A | Aggregates grading.json into benchmark.json | Standard skill-creator tool, generates stats + markdown |
| generate_review.py | N/A | HTML viewer for eval results | Standard skill-creator tool, supports --static flag |
| Sonnet 4.6 | N/A | Executor model for subagents | Matches typical user experience per project memory |

### Supporting
| Tool | Purpose | When to Use |
|------|---------|-------------|
| os-knowledge-garden/scripts/install.sh | Drupal site install with demo content | During env setup, with --demo=cascadia flag |
| eval/setup-drupal-env.sh | Shared env setup (clone + ddev start + install) | Before each eval run |
| eval/teardown-drupal-env.sh | Shared env teardown (ddev delete + rm) | After each eval run |

## Architecture Patterns

### Required Workspace Directory Structure

The aggregate_benchmark.py script expects this specific layout. The `eval-*` directories under the workspace root must contain `with_skill/` and `without_skill/` subdirectories, each containing `run-*` directories with `grading.json` files.

```
{skill}-workspace/
└── iteration-1/
    └── eval-{name}/
        ├── eval_metadata.json          # prompt + assertions
        ├── with_skill/
        │   └── run-1/
        │       ├── outputs/            # copied module files
        │       ├── transcript.md       # agent execution log
        │       └── grading.json        # grader output (written by grader agent)
        └── without_skill/
            └── run-1/
                ├── outputs/
                ├── transcript.md
                └── grading.json
```

**Critical detail:** `aggregate_benchmark.py` discovers config directories dynamically by looking for `run-*` subdirectories. It globs for `eval-*` directories, then discovers `with_skill/` and `without_skill/` by checking which subdirectories contain `run-*` children. The `grading.json` must be inside `run-1/`, not at the config level.

**Critical detail:** `generate_review.py` discovers runs by recursively finding directories that contain an `outputs/` subdirectory. It reads `eval_metadata.json` from the run directory or its parent. It reads `grading.json` from the run directory or its parent.

### evals.json Format

The evals.json files in each skill's `evals/` directory define the eval prompts and assertions. This is a custom format for this project (not a skill-creator standard):

```json
{
  "skill_name": "drupal-module-scaffold",
  "evals": [
    {
      "id": 1,
      "prompt": "...",
      "expected_output": "...",
      "files": [],
      "expectations": [
        "event_analytics.info.yml contains 'type: module'",
        "The module enables successfully: ddev drush en event_analytics returns exit code 0"
      ]
    }
  ]
}
```

### Grader Agent Protocol

The grader agent (from `agents/grader.md`) expects:
- **Inputs:** expectations (list of strings), transcript_path (path to markdown), outputs_dir (directory of output files)
- **Process:** Read transcript, examine output files, evaluate each assertion, extract implicit claims, check user_notes.md, critique eval quality
- **Output:** Write `grading.json` to `{outputs_dir}/../grading.json` (sibling to outputs_dir)

The grading.json output format includes:
- `expectations[]` with `text`, `passed`, `evidence`
- `summary` with `passed`, `failed`, `total`, `pass_rate`
- `execution_metrics` (if available)
- `timing` (if available)
- `claims[]` with extracted/verified claims
- `eval_feedback` with improvement suggestions

### Pattern: Executor Subagent Prompt

Each executor subagent needs a structured prompt:
1. Set up Drupal environment (run setup script)
2. (With-skill only) Read the SKILL.md and references
3. Complete the eval task in the ddev instance
4. Verify work with ddev drush commands
5. Copy generated files to outputs directory
6. Write transcript of all actions
7. Tear down environment

### Pattern: Batch Execution

Run 2 skills concurrently (4 agents total per batch):
- Batch 1: scaffold (with + without) + entities (with + without)
- Batch 2: caching (with + without) + testing (with + without)

### Anti-Patterns to Avoid
- **Running 4 skills simultaneously (8 agents):** Machine has only ~6GB available RAM. Each ddev instance needs ~512MB. Running 8 ddev instances simultaneously would consume ~4GB on ddev alone plus agent memory.
- **Placing grading.json at wrong level:** Must be inside `run-1/`, not at `with_skill/` level, for aggregate_benchmark.py to find it.
- **Omitting transcript.md:** Grader agent requires a transcript to grade against. Without it, grading will be unreliable.
- **Using run_eval.py for functional eval:** run_eval.py is for trigger evaluation only (does the description make Claude invoke the skill?). It is NOT suitable for functional evaluation.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Grading eval outputs | Custom grading logic | skill-creator grader.md agent | Handles evidence citation, claim extraction, eval critique |
| Aggregating results | Custom statistics | aggregate_benchmark.py | Handles multi-config comparison, delta calculation, markdown generation |
| HTML eval viewer | Custom HTML/JS | generate_review.py --static | Self-contained HTML with embedded data, supports benchmark tab |
| Drupal env setup | Inline bash in agent prompts | eval/setup-drupal-env.sh | Reusable, tested, handles edge cases |

**Key insight:** The skill-creator provides the post-execution pipeline (grading, aggregation, viewing) but NOT the executor. The executor (spawning agents against live Drupal) is custom and the main complexity of this phase.

## Common Pitfalls

### Pitfall 1: ddev Name Collision
**What goes wrong:** Multiple ddev instances with the same project name cause conflicts.
**Why it happens:** os-knowledge-garden's config.yaml has no `name:` field, so ddev uses the directory name. If two instances share a directory name pattern, they collide.
**How to avoid:** The setup script uses unique names via `sed -i "s/^name:.*/name: os-kg-${NAME}/"` -- but since there's no existing `name:` field, this sed command will NOT match. Instead, ADD the name field: `sed -i '1a name: os-kg-${NAME}' .ddev/config.yaml`.
**Warning signs:** ddev start fails with "project already running" or port conflicts.

### Pitfall 2: install.sh Timeout
**What goes wrong:** scripts/install.sh --demo=cascadia takes 3-5 minutes. Agent timeouts may kill the process prematurely.
**Why it happens:** Composer install + site install + demo content + search indexing is slow.
**How to avoid:** Set agent/task timeouts to 10+ minutes (600000ms). Use `run_in_background` for setup where possible.
**Warning signs:** Partial Drupal installs, missing demo content, Solr indexing incomplete.

### Pitfall 3: RAM Exhaustion
**What goes wrong:** System becomes unresponsive or OOM killer terminates processes.
**Why it happens:** Machine has 16GB total, ~6.2GB available. Each ddev instance uses ~512MB. Running 4 instances = ~2GB on ddev alone, plus Claude agents consume memory.
**How to avoid:** Run max 2 skills (4 ddev instances) simultaneously, not 4 skills (8 instances). The CONTEXT.md says max 4 simultaneous instances, which means 2 skills at a time (each skill = 2 instances).
**Warning signs:** System slowdown, swap usage increase, agent failures.

### Pitfall 4: Grading.json Location Mismatch
**What goes wrong:** aggregate_benchmark.py can't find grading results.
**Why it happens:** The grader writes to `{outputs_dir}/../grading.json` which places it as a sibling to `outputs/`. But aggregate_benchmark.py expects it inside `run-N/grading.json`. These are the same location only if the run directory structure is: `run-1/outputs/` and grader writes to `run-1/grading.json`.
**How to avoid:** Ensure the outputs directory is at `run-1/outputs/` so the grader's `{outputs_dir}/../grading.json` resolves to `run-1/grading.json`.
**Warning signs:** "grading.json not found" warnings from aggregate_benchmark.py.

### Pitfall 5: ddev Port Conflicts
**What goes wrong:** Multiple ddev instances try to bind the same host ports.
**Why it happens:** ddev uses dynamic port allocation by default, but the router (which is shared) can have issues with many simultaneous projects.
**How to avoid:** ddev handles port allocation automatically via the router. Unique project names are sufficient. Tear down instances after use.
**Warning signs:** "port already in use" errors during ddev start.

### Pitfall 6: sed on Missing Field
**What goes wrong:** The setup script's `sed -i "s/^name:.*/name: os-kg-${NAME}/"` silently does nothing because the source config.yaml has no `name:` line.
**Why it happens:** The os-knowledge-garden config.yaml omits the `name:` field (it's commented out in the DDEV defaults). The sed substitution pattern won't match.
**How to avoid:** Use `sed -i "1a name: os-kg-${NAME}" .ddev/config.yaml` to INSERT the name on line 2 (after the first line), or use: `echo "name: os-kg-${NAME}" >> .ddev/config.yaml`.
**Warning signs:** All ddev instances get the same name (the directory name), causing collisions.

## Code Examples

### Setup Script Fix (Critical)

The PRD plan's sed command will silently fail. Corrected version:

```bash
# WRONG (field doesn't exist in source config):
# sed -i "s/^name:.*/name: os-kg-${NAME}/" .ddev/config.yaml

# CORRECT (insert name field at top of file):
sed -i "1a name: os-kg-${NAME}" .ddev/config.yaml
```

### aggregate_benchmark.py CLI

```bash
SKILL_CREATOR="/home/proofoftom/.claude/plugins/cache/claude-plugins-official/skill-creator/205b6e0b3036/skills/skill-creator"

python "$SKILL_CREATOR/scripts/aggregate_benchmark.py" \
  drupal-module-scaffold-workspace/iteration-1 \
  --skill-name drupal-module-scaffold \
  --skill-path /home/proofoftom/Code/drupal-skills/skills/drupal-module-scaffold

# Outputs: iteration-1/benchmark.json and iteration-1/benchmark.md
```

### generate_review.py CLI (Static Mode)

```bash
python "$SKILL_CREATOR/eval-viewer/generate_review.py" \
  drupal-module-scaffold-workspace/iteration-1 \
  --skill-name drupal-module-scaffold \
  --benchmark drupal-module-scaffold-workspace/iteration-1/benchmark.json \
  --static drupal-module-scaffold-workspace/iteration-1/review.html

# Outputs: standalone HTML file at review.html
```

### Grader Agent Prompt Template

```
Grade this eval run.

**Expectations:**
- event_analytics.info.yml contains 'type: module'
- The module enables successfully: ddev drush en event_analytics returns exit code 0
[... more expectations]

**Transcript path:** /home/proofoftom/Code/drupal-skills/drupal-module-scaffold-workspace/iteration-1/eval-scaffold-module/with_skill/run-1/transcript.md
**Outputs dir:** /home/proofoftom/Code/drupal-skills/drupal-module-scaffold-workspace/iteration-1/eval-scaffold-module/with_skill/run-1/outputs/

Follow the grading protocol at:
/home/proofoftom/.claude/plugins/cache/claude-plugins-official/skill-creator/205b6e0b3036/skills/skill-creator/agents/grader.md

Write grading.json to: /home/proofoftom/Code/drupal-skills/drupal-module-scaffold-workspace/iteration-1/eval-scaffold-module/with_skill/run-1/grading.json
```

## State of the Art

| Aspect | Current State | Impact |
|--------|---------------|--------|
| Eval prompts | Already defined in `eval/eval-prompts.md` | PRD plan has 4 prompts ready with assertions |
| Eval results | `eval/eval-results.md` exists but contains hypothetical analysis | Must be replaced with real data |
| evals.json files | Do NOT exist yet in skills directories | Must be created as first step |
| Setup/teardown scripts | Do NOT exist yet | Must be created before running evals |
| Skill-creator functional eval | Does NOT exist as built-in | run_eval.py is trigger-only; executor is custom |

## Open Questions

1. **Task vs Agent tool for subagent spawning**
   - What we know: Both Task and Agent tools can spawn subagents. Task tool is simpler, Agent tool has more features.
   - What's unclear: Which tool supports model selection (claude-sonnet-4-6) and timeout configuration better in the current environment.
   - Recommendation: Use Task tool with explicit model specification if supported; fall back to Agent tool if Task doesn't support model selection.

2. **ddev simultaneous instance limit**
   - What we know: Machine has 16GB RAM, ~6.2GB available. Each instance ~512MB. CONTEXT says max 4 simultaneous.
   - What's unclear: Whether 4 simultaneous ddev instances plus 4 Claude agents will cause memory pressure.
   - Recommendation: Start with 2 skills (4 instances) per batch as specified. If memory is tight, drop to 1 skill (2 instances) per batch.

3. **Drupal install time variability**
   - What we know: install.sh --demo=cascadia takes 3-5 minutes per the PRD.
   - What's unclear: Whether running 4 installs simultaneously degrades performance significantly (disk I/O, network).
   - Recommendation: Set generous timeouts (15 minutes per agent). Monitor first batch closely.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Manual verification + skill-creator grading pipeline |
| Config file | N/A (grading is agent-based, aggregation is Python script) |
| Quick run command | `python aggregate_benchmark.py {workspace}/iteration-1 --skill-name {name}` |
| Full suite command | Run all 8 graders + aggregate all 4 skills |

### Phase Requirements to Test Map
| Behavior | Test Type | Automated Command | Notes |
|----------|-----------|-------------------|-------|
| evals.json files created for 4 skills | file check | `ls skills/drupal-{module-scaffold,entities-fields,caching,testing}/evals/evals.json` | Wave 0 |
| Setup/teardown scripts work | smoke | `bash eval/setup-drupal-env.sh test-smoke && bash eval/teardown-drupal-env.sh test-smoke` | Manual |
| Executor agents produce outputs + transcripts | manual | Review workspace directories for outputs/ and transcript.md | Per-run |
| Grading produces valid JSON | file check | `python -m json.tool {workspace}/.../grading.json` | Per-run |
| Benchmark aggregation succeeds | smoke | `python aggregate_benchmark.py {workspace}/iteration-1` | Per-skill |
| HTML viewer generates | file check | `ls {workspace}/iteration-1/review.html` | Per-skill |
| With-skill pass rate > without-skill | analysis | Compare benchmark.json delta values | Final check |

### Sampling Rate
- **Per task commit:** Verify file existence and JSON validity
- **Per wave merge:** Run aggregate_benchmark.py to verify data flows through pipeline
- **Phase gate:** All 4 skills have benchmark.json with positive delta

### Wave 0 Gaps
- [ ] `eval/setup-drupal-env.sh` -- Drupal env setup script (must be created)
- [ ] `eval/teardown-drupal-env.sh` -- Drupal env teardown script (must be created)
- [ ] `skills/drupal-module-scaffold/evals/evals.json` -- eval definitions
- [ ] `skills/drupal-entities-fields/evals/evals.json` -- eval definitions
- [ ] `skills/drupal-caching/evals/evals.json` -- eval definitions
- [ ] `skills/drupal-testing/evals/evals.json` -- eval definitions

## Sources

### Primary (HIGH confidence)
- skill-creator grader.md -- read in full, grading protocol and output format verified
- skill-creator aggregate_benchmark.py -- read in full, CLI args and directory layout verified
- skill-creator generate_review.py -- read in full, --static flag and workspace discovery verified
- skill-creator run_eval.py -- read in full, confirmed it is trigger-only (not functional eval)
- os-knowledge-garden .ddev/config.yaml -- read in full, confirmed no `name:` field exists
- eval/eval-prompts.md -- read in full, all 13 single-skill + 6 multi-skill prompts available
- docs/plans/2026-03-05-skill-eval-loop.md -- read in full, PRD with 13 tasks

### Secondary (MEDIUM confidence)
- System RAM check: 16GB total, ~6.2GB available (varies with system load)
- ddev v1.24.8 installed and working

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - all tools read and verified directly from source
- Architecture: HIGH - directory layout requirements verified from aggregate_benchmark.py source code
- Pitfalls: HIGH - sed bug identified by reading actual config.yaml (no name field); RAM constraints measured

**Research date:** 2026-03-05
**Valid until:** 2026-04-05 (stable tooling, no expected changes)

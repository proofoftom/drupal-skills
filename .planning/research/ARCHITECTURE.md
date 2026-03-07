# Architecture Patterns

**Domain:** Automated eval pipeline for Drupal Claude skills (v2.0 integration)
**Researched:** 2026-03-06
**Confidence:** HIGH -- derived from existing working code, confirmed Phase 7 findings, and skill-creator official schemas

## Recommended Architecture

The eval pipeline is an orchestrator-driven system where an Opus main session spawns Sonnet subagents for controlled A/B skill evaluation, then grades and aggregates results. It layers onto existing infrastructure (ddev environments, skill-creator JSON schemas, workspace directories).

### System Overview

```
Opus Main Session (Orchestrator)
  |
  |-- 1. Setup: ddev environments (2 per skill: with/without)
  |
  |-- 2. Execute: Spawn eval-executor subagents (Sonnet)
  |     |-- with-skill agent: reads SKILL.md, generates Drupal module
  |     |-- without-skill agent: no SKILL.md access, generates baseline
  |
  |-- 3. Grade: Opus reads generated files + evals.json, writes grading.json
  |
  |-- 4. Aggregate: Opus writes benchmark.json from grading results
  |
  |-- 5. Teardown: ddev delete + rm -rf per environment
  |
  |-- 6. Iterate: if delta < threshold, optimize SKILL.md and re-run
```

### Component Boundaries

| Component | Responsibility | Communicates With | Location | Status |
|-----------|---------------|-------------------|----------|--------|
| Orchestrator (Opus) | Lifecycle, model switching, grading, aggregation | All components | Interactive Claude session | EXISTING pattern |
| eval-executor subagent | Code generation within ddev Drupal env | ddev CLI, drush, filesystem | `.claude/agents/eval-executor.md` | **NEW** |
| eval-browser subagent | Authenticated browser E2E verification | agent-browser, drush uli | `.claude/agents/eval-browser.md` | **NEW** |
| setup-drupal-env.sh | Provision isolated ddev Drupal instance | ddev, docker, os-kg source | `eval/setup-drupal-env.sh` | EXISTING |
| teardown-drupal-env.sh | Clean up ddev instance + filesystem | ddev, docker | `eval/teardown-drupal-env.sh` | EXISTING |
| e2e-assert.sh | Browser-based assertion checks | agent-browser | `eval/e2e-assert.sh` | EXISTING (curl preferred) |
| evals.json (x13) | Eval prompts + expectations per skill | Read by orchestrator | `skills/drupal-*/evals/evals.json` | EXISTING |
| grading.json | Per-run grading results | Written by orchestrator | workspace dirs | EXISTING schema |
| benchmark.json | Aggregated with/without comparison | Written by orchestrator | workspace dirs | EXISTING schema |

## New Components to Build

### 1. `.claude/agents/eval-executor.md` -- Core New Component

Custom subagent definition for controlled eval execution.

**Frontmatter:**

```yaml
---
name: eval-executor
description: Executes a Drupal module development task in an isolated ddev environment. Generates code without access to external skills unless explicitly provided in the prompt.
tools: Read, Write, Edit, Bash, Glob, Grep
---
```

**Architectural decisions (all confirmed from Phase 7 execution):**

| Decision | Rationale |
|----------|-----------|
| `model: sonnet` in frontmatter | Per Claude Code sub-agents docs, the `model` field in subagent .md frontmatter controls the model. Agent tool has no model param, but frontmatter does. No `/model` switching needed. |
| No `skills` field | Deliberate omission. Must NOT auto-load skills from `~/.claude/skills/`. Knowledge isolation is the point. |
| Tools: file + bash only | No WebSearch, WebFetch, Agent. Executor writes code from training data (without-skill) or SKILL.md (with-skill). |
| No `color` field | Optional cosmetic; not functionally relevant. |

**System prompt structure:**

```markdown
You are a Drupal module developer. You will be given a task to create a Drupal module
in an existing ddev environment.

## Environment
- Working directory: {ddev_project_dir}
- Drupal root: {ddev_project_dir}/html (os-kg) or {ddev_project_dir}/web (fresh D10)
- Custom modules go in: {docroot}/modules/custom/{module_name}/
- Use `ddev drush` for all drush commands
- Use `ddev exec` for PHP commands

## Rules
- Do NOT ask questions -- just create the code
- Do NOT search the web for documentation
- Create all required files for a working Drupal module
- Enable the module with `ddev drush en {module_name}` after creating files
- Verify the module works (drush status, route check, etc.)

## Task
{eval_prompt_from_evals_json}
```

**With-skill variant adds** (before Task):
```markdown
## Skill Reference
Read the skill file at {absolute_path_to_SKILL.md} before starting.
Follow its patterns and conventions.
```

**Without-skill variant:** No skill reference section. Agent works from training data alone.

### 2. `.claude/agents/eval-browser.md` -- MUST-HAVE

Browser-based E2E verification for evals needing authenticated UI testing (theming, forms, access).

```yaml
---
name: eval-browser
description: Performs authenticated browser-based verification of Drupal module functionality using drush uli for login and agent-browser for page inspection.
tools: Read, Bash
---
```

**drush uli authentication flow:**
1. `ddev drush uli --uid=1` returns one-time login URL
2. `agent-browser open {login_url}` authenticates as admin (sets session cookie)
3. Navigate to target URL for verification
4. `agent-browser snapshot` captures page content for assertion

**When to use eval-browser vs curl:**
| Method | Use For | Skills |
|--------|---------|--------|
| `curl -sk` (preferred) | JSON endpoints, drush commands, status codes, module enable | 10 of 13 skills |
| eval-browser | CSS/layout, multi-step forms, role-based access pages | theming, forms-api, access-security |

### 3. `eval/setup-drupal-env.sh` -- Potential Modification

Current script copies os-knowledge-garden as base. PROJECT.md v2.0 specifies "fresh Drupal 10 ddev instances (no os-kg, faster/controlled)."

**Decision:** Use fresh Drupal 10 instances (not os-kg). Faster installs, controlled environment, no traefik router collisions from stale os-kg projects. Eval prompts should reference "Drupal 10 site" (not Open Social). Setup script needs modification or replacement.

## Data Flow

### Per-Skill Eval Run (Complete Sequence)

```
1. INPUT
   skills/drupal-{name}/evals/evals.json     -- prompts + expectations
   skills/drupal-{name}/SKILL.md              -- with-skill only

2. ENVIRONMENT SETUP (orchestrator runs bash)
   eval/setup-drupal-env.sh {name}-with    --> /tmp/os-kg-{name}-with/
   eval/setup-drupal-env.sh {name}-without --> /tmp/os-kg-{name}-without/

3. MODEL SWITCH
   /model sonnet                              -- parent session switches

4. EXECUTION (2 subagents, can be parallel or sequential)
   Agent("eval-executor", with-skill prompt)     --> module files in ddev env
   Agent("eval-executor", without-skill prompt)  --> module files in ddev env

5. MODEL SWITCH BACK
   /model opus                                -- for grading quality

6. GRADING (orchestrator reads files, runs drush/curl checks)
   Read generated files from each ddev env
   Compare against evals.json expectations[]
   Write grading.json per config/run

7. AGGREGATION
   Combine grading into benchmark.json with delta calculation

8. OUTPUT (written to workspace)
   drupal-{name}-workspace/iteration-{N}/
     eval-{module-name}/
       with_skill/run-1/grading.json
       without_skill/run-1/grading.json
     benchmark.json

9. TEARDOWN
   eval/teardown-drupal-env.sh {name}-with
   eval/teardown-drupal-env.sh {name}-without

10. DECISION
    If delta >= 15%: skill is validated, move to next
    If delta < 15%: optimize SKILL.md, re-run (new iteration)
```

### JSON Schema Mapping (Actual vs skill-creator)

The project uses simplified schemas compared to skill-creator's full specification. This is deliberate -- grading is done by the orchestrator reading files directly, not a separate grader agent examining transcripts.

**grading.json (actual, from existing runs):**
```json
{
  "skill": "drupal-routing-controllers",
  "eval_id": 1,
  "run": 1,
  "config": "with_skill",
  "model": "claude-sonnet-4-6",
  "timestamp": "2026-03-06T16:03:00Z",
  "expectations": [
    {
      "id": 1,
      "description": "expectation text from evals.json",
      "result": "PASS",
      "evidence": "specific evidence from file inspection"
    }
  ],
  "score": "8/8",
  "percentage": 100
}
```

**Differences from skill-creator grading.json schema:**
- No `claims[]` (no transcript to extract claims from)
- No `user_notes_summary` (no executor notes file)
- No `eval_feedback` (orchestrator handles this inline)
- No `execution_metrics` (tokens/duration captured in benchmark.json instead)
- Uses `result: "PASS"/"FAIL"` instead of `passed: true/false`
- Uses `description` instead of `text` for expectation content

**benchmark.json (actual, from existing runs):**
```json
{
  "skill": "drupal-routing-controllers",
  "iteration": 2,
  "model": "claude-sonnet-4-6",
  "timestamp": "2026-03-06T16:03:00Z",
  "eval_id": 1,
  "prompt": "eval prompt text",
  "results": {
    "with_skill": {
      "score": "8/8", "percentage": 100,
      "tokens": 18175, "tool_uses": 9, "duration_ms": 48986
    },
    "without_skill": {
      "score": "8/8", "percentage": 100,
      "tokens": 11248, "tool_uses": 7, "duration_ms": 42754
    }
  },
  "delta": 0,
  "notes": "analysis text"
}
```

**Differences from skill-creator benchmark.json schema:**
- Flat structure (no `metadata`, `runs[]`, `run_summary` nesting)
- Single eval per benchmark (not aggregated across multiple evals)
- `delta` is a number (percentage difference), not a string
- No statistical aggregation (mean/stddev) -- single run per config

## Workspace Directory Structure

```
drupal-skills/                              # Project root
  .claude/
    agents/
      eval-executor.md                      # NEW: Eval execution subagent
      eval-browser.md                       # NEW: Browser verification subagent

  skills/
    drupal-{name}/
      SKILL.md                              # Skill content (read by with-skill agent)
      evals/
        evals.json                          # Eval definitions (read by orchestrator)
      references/
        *.md                                # Reference files

  eval/
    setup-drupal-env.sh                     # Environment provisioning
    teardown-drupal-env.sh                  # Environment cleanup
    e2e-assert.sh                           # Browser assertion helper

  drupal-{name}-workspace/                  # Per-skill workspace (13 total)
    iteration-{N}/                          # Iteration number (sequential)
      eval-{module-name}/                   # Named from eval prompt module
        with_skill/
          run-{N}/
            grading.json                    # Grading results
        without_skill/
          run-{N}/
            grading.json
      benchmark.json                        # Aggregated comparison
```

**Iteration numbering:**
- Skills with only iteration-1 results: next is iteration-2
- caching + module-scaffold (already have iteration-2): next is iteration-3

## Patterns to Follow

### Pattern 1: Knowledge Isolation via Separate Agents

**What:** With-skill and without-skill runs MUST use separate Agent instances.
**Why:** Once an agent reads SKILL.md, knowledge contaminates all subsequent outputs. Cannot "unknow" content.
**How:**
```
Agent("eval-executor", prompt_with_skill_path)    # reads SKILL.md
Agent("eval-executor", prompt_without_skill_path) # never sees SKILL.md
```

### Pattern 2: Model Control via Subagent Frontmatter

**What:** Set `model: sonnet` in eval-executor.md frontmatter. Opus parent session spawns Sonnet subagents automatically.
**Why:** Claude Code sub-agents docs confirm `model` field in .md frontmatter controls the subagent model. No `/model` switching needed.
**How:** Add `model: sonnet` to eval-executor.md and eval-browser.md frontmatter.

### Pattern 3: Serial ddev Lifecycle with flock

**What:** Serialize `ddev start` via flock. Max 2 simultaneous ddev instances per skill.
**Why:** ddev-router health check fails ~50% when `ddev start` calls race.
**Recovery:** `docker restart ddev-router && sleep 20`, then retry.

### Pattern 4: Full Environment Teardown (Not Surgical)

**What:** Tear down entire ddev project after eval, not surgical module uninstall.
**Why:** Removing module files without `drush pm:uninstall` corrupts Drupal registry. Full teardown avoids this entirely since environments are disposable.

### Pattern 5: curl over e2e-assert.sh for E2E

**What:** Use `curl -sk` with full URL (including port) instead of e2e-assert.sh.
**Why:** ddev assigns non-standard HTTPS ports. e2e-assert.sh constructs URLs without port.
**How:** `ddev describe` to get URL, or `curl -sk https://os-kg-{name}.ddev.site:{port}/path`

## Anti-Patterns to Avoid

### Anti-Pattern 1: Headless `claude -p` for Eval Execution
**Why bad:** Black box, hangs silently, no observability, output lost on process death.
**Instead:** Agent tool from within main session -- full visibility of progress and errors.

### Anti-Pattern 2: Skills in ~/.claude/skills/ During Evals
**Why bad:** Auto-discovery contaminates without-skill baseline.
**Instead:** Skills only in `skills/` repo dir. With-skill agent gets explicit path in prompt.

### Anti-Pattern 3: GSD Executor for Eval Runs
**Why bad:** Cannot spawn sub-agents (tools restricted). Adds irrelevant overhead (STATE.md, commits).
**Instead:** Opus main session orchestrates directly via Agent tool.

### Anti-Pattern 4: Single Agent for Both Configs
**Why bad:** SKILL.md knowledge persists in agent context, contaminating without-skill run.
**Instead:** Always 2 separate Agent instances per skill eval.

## Build Order (Dependency Chain)

```
Phase 1: Subagent Definitions (no dependencies, blocks everything else)
  1. .claude/agents/eval-executor.md
  2. .claude/agents/eval-browser.md

Phase 2: Environment Setup (uses existing scripts, may need modification)
  3. Verify/modify eval/setup-drupal-env.sh if --fresh flag needed
     (can proceed with existing os-kg approach initially)

Phase 3: Execution Loop (depends on Phase 1)
  4. Orchestration runbook documenting:
     - Model switching protocol
     - Agent prompt templates (with/without skill)
     - Grading procedure
     - benchmark.json writing
     - Teardown sequence

Phase 4: Browser Integration (depends on Phase 1, MUST-HAVE)
  5. eval-browser + drush uli flow for E2E/UAT verification
     (Most Drupal bugs surface in UI testing -- required for all skills with UI output)

Phase 5: Aggregation & Analysis
  6. Cross-skill analysis template
  7. Tier classification and optimization recommendations
```

**Critical path:** Subagent definitions (Phase 1) -> Execution loop (Phase 3). Everything else is parallel or deferrable.

## Integration Points Summary

| Existing Component | Integration | Changes Needed |
|-------------------|-------------|----------------|
| `skills/drupal-*/evals/evals.json` | Read by orchestrator for prompts + expectations | None |
| `skills/drupal-*/SKILL.md` | Path passed to with-skill agent prompt | None |
| `eval/setup-drupal-env.sh` | Invoked by orchestrator via Bash | Optional `--fresh` flag |
| `eval/teardown-drupal-env.sh` | Invoked by orchestrator via Bash | None |
| `drupal-*-workspace/` | Write target for grading + benchmark JSON | None (follows existing convention) |
| `.planning/` GSD infrastructure | Separate concern entirely | Not used by eval pipeline |
| `os-knowledge-garden/` | Base image for ddev environments | No changes |
| `.claude/agents/` directory | **NEW directory** to create | Create dir + 2 agent files |

## Sources

- **Phase 7 continue-here.md** (HIGH): Confirmed model control, knowledge isolation, ddev patterns
- **Existing grading.json** (HIGH): `drupal-routing-controllers-workspace/iteration-2/eval-api-status-endpoint/with_skill/run-1/grading.json` -- actual schema
- **Existing benchmark.json** (HIGH): `drupal-routing-controllers-workspace/iteration-2/benchmark.json` -- actual schema
- **skill-creator grader.md** (HIGH): Official grader agent spec with full grading.json schema
- **skill-creator schemas.md** (HIGH): Official JSON schemas for all eval artifacts
- **gsd-executor.md** (HIGH): Example subagent frontmatter (name, description, tools, skills)
- **PROJECT.md** (HIGH): v2.0 milestone requirements
- **MEMORY.md** (HIGH): Eval execution rules confirmed across 7 sessions

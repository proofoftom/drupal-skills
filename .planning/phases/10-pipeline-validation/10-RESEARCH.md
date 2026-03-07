# Phase 10: Pipeline Validation - Research

**Researched:** 2026-03-06
**Domain:** End-to-end eval pipeline orchestration, calibration runs, schema validation
**Confidence:** HIGH

## Summary

Phase 10 validates that the v2.0 eval pipeline (built in Phase 8, with prompts rewritten in Phase 9) produces valid, meaningful data by running two calibration skills -- caching (known +75% delta in v1.0) and scaffold (known +43% delta in v1.0) -- through the full pipeline cycle. The phase is primarily an execution and validation phase, not an infrastructure-building phase. All components exist: eval-executor, eval-grader, eval-browser subagents, setup/teardown scripts, and rewritten evals.json files.

The core challenge is orchestrating the full pipeline from the main Opus session: for each calibration skill, set up two fresh D10 environments (with-skill and without-skill), spawn eval-executor subagents with/without SKILL.md access, spawn eval-grader to grade outputs, aggregate results into benchmark.json, and tear down environments. The pipeline must complete without manual intervention, and the resulting deltas must exceed minimum thresholds (caching >30%, scaffold >15%) to prove the new pipeline works.

Key risks: (1) ddev-router flakiness requiring retry logic already built into setup script, (2) eval-grader producing non-compliant JSON since it was only validated via bash/jq simulation in Phase 8 (real subagent grading happens for the first time here), (3) the updated caching prompt (hint removal: "Do NOT use max-age: 0" removed) may change delta magnitude vs v1.0, and (4) workspace layout must be established for the first time in v2.0.

**Primary recommendation:** Run each calibration skill through the complete pipeline cycle sequentially (setup -> execute with-skill -> execute without-skill -> grade both -> aggregate -> teardown), validate schemas after each run, and compare deltas against v1.0 baselines. If deltas are significantly lower than expected, debug assertions before proceeding.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| PIPE-01 | Pipeline validated end-to-end on 2-3 calibration skills (caching, scaffold) before batch run | All infrastructure exists from Phase 8; evals.json rewritten in Phase 9; grading.json and benchmark.json schemas documented from v1.0; workspace layout pattern established in v1.0; orchestration is direct from main Opus session |
</phase_requirements>

## Standard Stack

### Core

| Tool | Version | Purpose | Why Standard |
|------|---------|---------|--------------|
| Claude Code Agent tool | Current | Spawn eval-executor, eval-grader, eval-browser subagents | Official orchestration mechanism; main session controls A/B isolation |
| eval-executor subagent | model: sonnet | Generate Drupal modules in ddev environments | Defined in Phase 8; Read-based SKILL.md loading for A/B |
| eval-grader subagent | model: inherit (Opus) | Grade generated code against expectations | Defined in Phase 8; produces grading.json |
| eval-browser subagent | model: haiku | E2E browser verification via agent-browser | Defined in Phase 8; for E2E assertions only |
| setup-fresh-drupal10.sh | eval/ | Provision fresh D10 ddev instances | Created in Phase 8; auto-retry, Traefik cleanup |
| teardown-drupal-env.sh | eval/ | Clean up ddev instances | Updated in Phase 8; d10- and os-kg- dual prefix |
| ddev | v1.24.8 | Container-based Drupal environments | Already installed; proven reliable with retry logic |

### Supporting

| Tool | Version | Purpose | When to Use |
|------|---------|---------|-------------|
| jq | System | Validate grading.json and benchmark.json schemas | After grader produces output; schema compliance checks |
| curl | System | HTTP-level E2E assertions | For simple status code checks (prefer over agent-browser per MEMORY) |
| bash/shell | System | Pipeline orchestration scripts, JSON aggregation | benchmark.json construction from grading.json data |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Sequential skill runs | Parallel runs (2 skills at once) | Parallel is faster but risks ddev-router conflicts; sequential is safer for calibration |
| Manual benchmark.json creation | Python aggregate script | No Python scripts exist in project; bash/jq is simpler for 2 skills; Python can be added in Phase 11 for 13 skills |
| eval-browser for all E2E | curl -sk for HTTP checks | curl is simpler/faster for status code checks; eval-browser needed only for complex page content verification |

## Architecture Patterns

### v2.0 Workspace Layout

The workspace layout follows v1.0 patterns but lives under `.planning/phases/10-pipeline-validation/workspaces/` (not the archived v1.0 milestones path):

```
.planning/phases/10-pipeline-validation/
  workspaces/
    drupal-caching-workspace/
      iteration-1/
        eval-cache-block/                    # eval name from evals.json
          eval_metadata.json                 # Eval config snapshot
          with_skill/
            run-1/
              grading.json                   # Grader output
              outputs/                       # Generated module files
              transcript.md                  # Executor output log
          without_skill/
            run-1/
              grading.json
              outputs/
              transcript.md
        benchmark.json                       # Aggregated with/without comparison
    drupal-module-scaffold-workspace/
      iteration-1/
        eval-scaffold-module/
          ...same structure...
        benchmark.json
```

### Pattern 1: Full Pipeline Cycle (Per Skill)

**What:** The complete sequence of steps to evaluate one skill end-to-end.

**When to use:** Every calibration skill evaluation.

**Sequence:**

```
1. SETUP PHASE
   a. Run setup-fresh-drupal10.sh <skill>-with     -> /tmp/d10-<skill>-with
   b. Run setup-fresh-drupal10.sh <skill>-without   -> /tmp/d10-<skill>-without

2. EXECUTION PHASE
   a. Spawn eval-executor (with-skill):
      - Prompt includes: "Read SKILL.md at skills/drupal-<skill>/SKILL.md FIRST"
      - Prompt includes: task from evals.json
      - Prompt includes: "Work in /tmp/d10-<skill>-with/web/modules/custom/"
   b. Spawn eval-executor (without-skill):
      - Prompt includes: task from evals.json ONLY (NO SKILL.md reference)
      - Prompt includes: "Do NOT ask questions -- just create the code"
      - Prompt includes: "Work in /tmp/d10-<skill>-without/web/modules/custom/"

3. GRADING PHASE
   a. Spawn eval-grader for with-skill run:
      - Pass expectations list from evals.json
      - Pass module path and ddev project name
      - Output: grading.json to workspace
   b. Spawn eval-grader for without-skill run:
      - Same expectations, different module path/project
      - Output: grading.json to workspace

4. AGGREGATION PHASE
   a. Read both grading.json files
   b. Compute pass rates, delta
   c. Write benchmark.json with proper schema

5. TEARDOWN PHASE
   a. Run teardown-drupal-env.sh <skill>-with
   b. Run teardown-drupal-env.sh <skill>-without
```

### Pattern 2: Knowledge Isolation via Read-Based Loading

**What:** The A/B mechanism for with-skill vs without-skill runs.

**How it works (empirically validated in Phase 8-02):**

```
WITH-SKILL prompt to eval-executor:
  "You are working in ddev project d10-caching-with at /tmp/d10-caching-with.
   FIRST: Read the skill file at /home/proofoftom/Code/drupal-skills/skills/drupal-caching/SKILL.md
   THEN: [task from evals.json prompt field]"

WITHOUT-SKILL prompt to eval-executor:
  "You are working in ddev project d10-scaffold-without at /tmp/d10-scaffold-without.
   Do NOT ask questions -- just create the code.
   [task from evals.json prompt field]"
```

The with-skill prompt includes an explicit Read instruction; the without-skill prompt omits it entirely. The eval-executor subagent's system prompt says "If a SKILL.md path is provided, Read it FIRST before starting any work." This ensures deterministic knowledge isolation.

### Pattern 3: Grader Prompt Construction

**What:** How to instruct eval-grader to grade a specific run.

**Prompt to eval-grader:**

```
Grade the Drupal module code against these expectations:

Expectations:
1. "build() returns render array with #cache key..."
2. "Cache tags include node-specific tags..."
[...all expectations from evals.json]

Module path: /tmp/d10-caching-with/web/modules/custom/related_content_block/
ddev project name: d10-caching-with

Write the grading JSON to:
/home/proofoftom/Code/drupal-skills/.planning/phases/10-pipeline-validation/workspaces/drupal-caching-workspace/iteration-1/eval-cache-block/with_skill/run-1/grading.json
```

### Pattern 4: benchmark.json Aggregation

**What:** Constructing benchmark.json from two grading.json files.

**Schema (from v1.0, proven):**

```json
{
  "metadata": {
    "skill_name": "drupal-caching",
    "skill_path": "/home/proofoftom/Code/drupal-skills/skills/drupal-caching",
    "executor_model": "sonnet",
    "analyzer_model": "opus",
    "timestamp": "2026-03-07T00:00:00Z",
    "evals_run": [1],
    "runs_per_configuration": 1
  },
  "runs": [
    {
      "eval_id": 1,
      "configuration": "with_skill",
      "run_number": 1,
      "result": {
        "pass_rate": 1.0,
        "passed": 9,
        "failed": 0,
        "total": 9,
        "time_seconds": 0,
        "tokens": 0,
        "tool_calls": 0,
        "errors": 0
      },
      "expectations": [ /* copied from grading.json */ ],
      "notes": []
    },
    {
      "eval_id": 1,
      "configuration": "without_skill",
      "run_number": 1,
      "result": { /* ... */ },
      "expectations": [ /* ... */ ],
      "notes": []
    }
  ],
  "run_summary": {
    "with_skill": {
      "pass_rate": { "mean": 1.0, "stddev": 0.0, "min": 1.0, "max": 1.0 },
      "time_seconds": { "mean": 0, "stddev": 0, "min": 0, "max": 0 },
      "tokens": { "mean": 0, "stddev": 0, "min": 0, "max": 0 }
    },
    "without_skill": {
      "pass_rate": { "mean": 0.25, "stddev": 0.0, "min": 0.25, "max": 0.25 },
      "time_seconds": { "mean": 0, "stddev": 0, "min": 0, "max": 0 },
      "tokens": { "mean": 0, "stddev": 0, "min": 0, "max": 0 }
    },
    "delta": {
      "pass_rate": "+0.75",
      "time_seconds": "+0.0",
      "tokens": "+0"
    }
  },
  "notes": []
}
```

### Anti-Patterns to Avoid

- **Running both calibration skills in parallel:** Risks ddev-router conflicts. Run sequentially for calibration runs.
- **Skipping teardown between skills:** Stale environments pollute subsequent runs. Always teardown before next skill.
- **Trusting grader output without schema validation:** Phase 8 validated grader via simulation only. Phase 10 is the first real subagent grading -- validate JSON schema after each grading run.
- **Comparing v2.0 deltas directly to v1.0 exact numbers:** v2.0 has different prompts (hints removed, D10 context), different environments (fresh D10 vs os-kg), and updated expectations. Expect directionally similar deltas but not identical percentages.
- **Using e2e-assert.sh for v2.0 runs:** The script still uses `os-kg-` prefix in BASE_URL (line 24). Use curl or eval-browser directly instead.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Eval orchestration | Custom eval runner binary/script | Main Opus session + Agent tool | Phase 8 decision: direct orchestration for full observability |
| Drupal environment provisioning | Docker compose scripts | setup-fresh-drupal10.sh | Already handles retry, Traefik cleanup, bootstrap verification |
| Code grading | Pattern matching scripts | eval-grader subagent | AI grader reads actual code, verifies evidence, handles nuance |
| E2E verification | Custom browser scripts | eval-browser or curl -sk | eval-browser handles authentication via drush uli; curl for simple checks |
| JSON schema validation | Custom validators | jq queries | `jq '.summary.pass_rate' grading.json` validates structure simply |
| benchmark.json construction | Python aggregation script | Manual/bash construction for 2 skills | Only 2 calibration skills; Python aggregation more useful for 13 skills in Phase 11 |

**Key insight:** Phase 10 is an EXECUTION phase, not a BUILD phase. All infrastructure exists. The work is orchestrating the pipeline, observing results, and validating output quality.

## Common Pitfalls

### Pitfall 1: First Real Subagent Grading May Produce Non-Compliant JSON

**What goes wrong:** eval-grader was only validated via bash/jq simulation in Phase 8. The actual subagent may produce JSON with extra fields, missing fields, or non-standard formatting.
**Why it happens:** AI agents can deviate from specified output formats, especially on first real use.
**How to avoid:** After each grading run, immediately validate the grading.json with jq: `jq '.expectations | length' grading.json` and `jq '.summary.pass_rate' grading.json`. If invalid, re-run grader with more explicit schema instructions.
**Warning signs:** jq parse errors, missing summary section, expectations array with different field names.

### Pitfall 2: Updated Prompts May Shift Deltas Significantly

**What goes wrong:** v1.0 caching prompt included "Do NOT use max-age: 0" which was a hint. v2.0 removes this hint. The without-skill run may now use max-age: 0, which would PASS the assertion about not using max-age: 0 (vacuous pass since that assertion was tightened in v2.0 to require BOTH tags AND contexts).
**Why it happens:** The v2.0 caching prompt is designed to be hint-free, which changes baseline behavior.
**How to avoid:** Review the updated expectations carefully before grading. The v2.0 caching expectations already account for this: expectation 4 now says "Does NOT set max-age to 0 anywhere" combined with expectation 1 requiring "#cache key containing BOTH 'tags' AND 'contexts'". The delta should still be strong because the fundamental gap (no #cache at all) persists.
**Warning signs:** Delta significantly different from expected range (e.g., caching <30% or >90%).

### Pitfall 3: Module Name Mismatch Between Prompt and Expectations

**What goes wrong:** The eval prompt asks to create module X, but expectations reference module Y.
**Why it happens:** Prompts were rewritten in Phase 9 but expectations were mostly preserved from v1.0.
**How to avoid:** Cross-reference: caching prompt says "module called `related_content_block`" and expectations reference "ddev drush en related_content_block" -- matches. Scaffold prompt says "module called event_analytics" and expectations reference "ddev drush en event_analytics" -- matches.
**Warning signs:** Executor creates module but grader can't find the expected files.

### Pitfall 4: ddev Environment Naming Collisions

**What goes wrong:** Two environments with similar names conflict in ddev's routing table.
**Why it happens:** ddev project names must be unique. If a stale environment exists, the setup script's cleanup may fail.
**How to avoid:** Use distinct names: `caching-with`, `caching-without`, `scaffold-with`, `scaffold-without`. The setup script already cleans stale environments with matching names.
**Warning signs:** "Project already exists" errors during setup, Traefik routing to wrong instance.

### Pitfall 5: Workspace Directory Creation Timing

**What goes wrong:** Grader tries to write grading.json to a directory that doesn't exist yet.
**Why it happens:** The workspace directory tree must be pre-created before grader runs.
**How to avoid:** Create the full workspace directory tree (including outputs/ and run-1/) BEFORE spawning the grader. Use `mkdir -p` for the entire path.
**Warning signs:** "No such file or directory" errors from grader.

### Pitfall 6: eval-executor Outputs Not Captured to Workspace

**What goes wrong:** The executor creates module files in the ddev project but they aren't copied to the workspace for archival.
**Why it happens:** v1.0 used a manual copy step after execution. v2.0 needs the same.
**How to avoid:** After executor completes, copy the generated module files from `/tmp/d10-<name>/web/modules/custom/<module>/` to the workspace `outputs/` directory. This preserves the code for offline review and grader re-runs.
**Warning signs:** Empty `outputs/` directory in workspace; no way to review what the executor produced after teardown.

## Code Examples

### Orchestration Prompt for With-Skill Executor

```
Use the eval-executor agent to create a Drupal module.

Work in ddev project d10-caching-with at /tmp/d10-caching-with

FIRST: Read the skill file at /home/proofoftom/Code/drupal-skills/skills/drupal-caching/SKILL.md

THEN: Create a block plugin module called `related_content_block` for a Drupal 10 site. The block should display a list of related content nodes, showing their titles as links. It should load specific node entities based on the current page and current user. The block is causing performance issues because it has no caching configured. Add proper cache metadata to the block so it invalidates when the displayed content changes, varies correctly based on which page it appears on, and produces different output per user. We want D11 compatibility.
```

### Orchestration Prompt for Without-Skill Executor

```
Use the eval-executor agent to create a Drupal module.

Work in ddev project d10-caching-without at /tmp/d10-caching-without

Do NOT ask questions -- just create the code.

Create a block plugin module called `related_content_block` for a Drupal 10 site. The block should display a list of related content nodes, showing their titles as links. It should load specific node entities based on the current page and current user. The block is causing performance issues because it has no caching configured. Add proper cache metadata to the block so it invalidates when the displayed content changes, varies correctly based on which page it appears on, and produces different output per user. We want D11 compatibility.
```

### Schema Validation Commands

```bash
# Validate grading.json schema
jq 'has("expectations") and has("summary") and (.summary | has("pass_rate"))' grading.json
# Should output: true

# Extract pass rate
jq '.summary.pass_rate' grading.json

# Validate benchmark.json schema
jq 'has("metadata") and has("runs") and has("run_summary") and (.run_summary | has("delta"))' benchmark.json
# Should output: true

# Extract delta
jq '.run_summary.delta.pass_rate' benchmark.json
```

### eval_metadata.json Template

```json
{
  "skill_name": "drupal-caching",
  "eval_id": 1,
  "iteration": 1,
  "eval_name": "eval-cache-block",
  "prompt": "[from evals.json]",
  "expected_output": "[from evals.json]",
  "assertions": ["[from evals.json expectations array]"],
  "configs": ["with_skill", "without_skill"],
  "runs_per_config": 1,
  "model": "claude-sonnet-4-6"
}
```

## State of the Art

| Old Approach (v1.0) | Current Approach (v2.0) | When Changed | Impact |
|---------------------|-------------------------|--------------|--------|
| `claude -p` headless execution | Agent tool subagents from main session | Phase 8 | Full observability, no silent hangs |
| os-knowledge-garden environments | Fresh Drupal 10 ddev instances | Phase 8 | No Open Social collisions, cleaner baseline |
| Hints in prompts ("Do NOT use max-age: 0") | Hint-free prompts | Phase 9 | Stronger differentiation, without-skill runs reflect true baseline |
| grading.json with 8 old expectations | 9 updated expectations (caching) / 8 (scaffold) | Phase 9 (evals.json rewrite) | Assertions target SKILL.md non-obvious patterns |
| Manual model switching | model: sonnet in subagent frontmatter | Phase 8 | Deterministic, automatable |
| Grader validated via bash/jq simulation | First real subagent grading in Phase 10 | Phase 10 (this phase) | Must validate grader produces compliant output |

**Key changes from v1.0 that affect expected deltas:**

1. **Caching prompt no longer says "Do NOT use max-age: 0"** -- without-skill may or may not use max-age: 0, but the key discriminator (missing #cache entirely) should persist. Expected delta: still strong (>30%), but magnitude may differ from +75%.

2. **Scaffold expectations now include 8 assertions** (previously 7 in eval data). Updated assertions include ".module file only created if hooks are actually needed" and "E2E: Module appears in /admin/modules list". Expected delta: should remain strong on D11 compat and strict_types gaps.

3. **Fresh D10 vs os-kg environments** -- no Open Social modules to confuse the executor. Results should be cleaner.

## Open Questions

1. **eval-grader real subagent behavior**
   - What we know: grading.json schema validated via bash/jq simulation in Phase 8
   - What's unclear: Whether the actual subagent will consistently produce compliant JSON with proper evidence strings
   - Recommendation: Run grader for first skill, validate output, adjust grader prompt if needed before continuing

2. **Transcript capture**
   - What we know: v1.0 had transcript.md files in workspace
   - What's unclear: How to capture executor subagent output as a transcript in v2.0 (Agent tool doesn't produce a transcript file automatically)
   - Recommendation: The main session can observe executor output directly and write a summary to transcript.md. This is a nice-to-have, not blocking.

3. **Optimal eval naming convention**
   - What we know: v1.0 used descriptive names like "eval-cache-block", "eval-scaffold-module"
   - What's unclear: Whether to keep v1.0 names or derive from v2.0 evals.json
   - Recommendation: Keep consistent naming. Derive from skill name: drupal-caching -> eval-cache-block (descriptive), drupal-module-scaffold -> eval-scaffold-module.

## Validation Architecture

### Test Framework

| Property | Value |
|----------|-------|
| Framework | Shell-based validation (jq + bash assertions) |
| Config file | None -- validation is inline during pipeline execution |
| Quick run command | `jq '.summary.pass_rate' <path>/grading.json` |
| Full suite command | Validate both grading.json files + benchmark.json for each skill |

### Phase Requirements -> Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| PIPE-01a | Caching skill produces >30% delta | integration | `jq '.run_summary.delta.pass_rate' workspaces/drupal-caching-workspace/iteration-1/benchmark.json` | Wave 0 |
| PIPE-01b | Scaffold skill produces >15% delta | integration | `jq '.run_summary.delta.pass_rate' workspaces/drupal-module-scaffold-workspace/iteration-1/benchmark.json` | Wave 0 |
| PIPE-01c | grading.json matches expected schema | unit | `jq 'has("expectations") and has("summary")' grading.json` | Wave 0 |
| PIPE-01d | benchmark.json matches expected schema | unit | `jq 'has("metadata") and has("runs") and has("run_summary")' benchmark.json` | Wave 0 |
| PIPE-01e | Full pipeline completes without manual intervention | smoke | Pipeline runs end-to-end with no user prompts | Wave 0 |

### Sampling Rate

- **Per task commit:** Validate grading.json schema after each grader run
- **Per wave merge:** Validate benchmark.json and delta thresholds after each skill completes
- **Phase gate:** Both calibration skills have benchmark.json with deltas exceeding thresholds

### Wave 0 Gaps

- [ ] Workspace directories need to be created before first run
- [ ] eval_metadata.json template needs to be populated per skill
- [ ] No automated pipeline validation script exists -- validation is manual/inline during orchestration

## Sources

### Primary (HIGH confidence)

- Phase 8 RESEARCH.md and SUMMARY files -- subagent architecture, grading.json/benchmark.json schemas, setup/teardown scripts
- Phase 9 VERIFICATION.md -- all 13 evals.json rewritten, expectations verified, hint removal documented
- v1.0 workspace files -- grading.json and benchmark.json actual examples (drupal-caching, drupal-module-scaffold)
- Actual subagent definitions -- `.claude/agents/eval-executor.md`, `.claude/agents/eval-grader.md`, `.claude/agents/eval-browser.md`
- Actual scripts -- `eval/setup-fresh-drupal10.sh`, `eval/teardown-drupal-env.sh`
- Current evals.json files -- `skills/drupal-caching/evals/evals.json`, `skills/drupal-module-scaffold/evals/evals.json`

### Secondary (MEDIUM confidence)

- v1.0 analysis-iteration-1.md -- delta values (+75% caching, +43% scaffold) as baseline expectations
- MEMORY.md eval execution rules -- orchestration patterns, known pitfalls

### Tertiary (LOW confidence)

- None -- all findings verified against actual project files

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - all tools exist and were validated in Phase 8
- Architecture: HIGH - workspace layout and pipeline pattern proven in v1.0; subagent orchestration pattern documented in Phase 8
- Pitfalls: HIGH - known issues well-documented from v1.0 experience and Phase 8 validation
- Delta expectations: MEDIUM - v2.0 prompt changes (hint removal, D10 context) may shift magnitudes vs v1.0 baselines

**Research date:** 2026-03-06
**Valid until:** 2026-04-06 (stable -- infrastructure is settled)

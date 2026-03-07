# Phase 11: Batch Execution - Research

**Researched:** 2026-03-07
**Domain:** Batch eval pipeline orchestration for 13 Drupal skills
**Confidence:** HIGH

## Summary

Phase 11 scales the validated v2.0 eval pipeline from 2 calibration skills to all 13 skills. The pipeline was proven end-to-end in Phase 10 (caching +11%, scaffold +13%). All infrastructure exists: eval-executor, eval-grader, eval-browser subagents, setup/teardown scripts, and rewritten evals.json files for all 13 skills. The phase is pure execution -- no new infrastructure needs to be built.

The primary challenge is operational: running 13 skills x 2 configurations (with/without) = 26 ddev environments and 26 executor runs + 26 grader runs, organized into batches of 3-4 skills per session. Each skill requires sequential ddev provisioning (ddev-router serialization), but within a batch, the two configurations (with/without) for the SAME skill can share session context. Resource constraints are manageable: 16GB RAM, 566GB free disk, and ddev instances are lightweight.

Two skills (caching and scaffold) already have benchmark.json from Phase 10 calibration. These results live in Phase 10's workspace directory and must be COPIED into Phase 11's workspace -- NOT re-run -- since the pipeline was already validated on them. The remaining 11 skills need fresh pipeline runs.

**Primary recommendation:** Run 11 remaining skills in 3 batches (4, 4, 3) plus copy the 2 calibration results. Each batch follows the exact Phase 10 pipeline pattern. Workspaces go under `.planning/phases/11-batch-execution/workspaces/`. After all 13 skills have benchmark.json, produce a consolidated summary table showing all deltas.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| PIPE-02 | Batch orchestrator runs all 13 skills through eval with minimal manual intervention (3-4 skills per session) | Pipeline validated in Phase 10; all 13 evals.json rewritten in Phase 9; batch size of 3-4 based on session context limits and ddev-router serialization; orchestration is direct from main Opus session |
| PIPE-03 | Each skill produces grading.json, benchmark.json in correct workspace layout | Workspace layout proven in Phase 10 (workspaces/<skill>-workspace/iteration-1/<eval-name>/{with,without}_skill/run-1/); grading.json and benchmark.json schemas validated |
| ANLZ-01 | All 13 skills have graded benchmarks showing with-skill vs without-skill delta | 2 skills already have benchmarks from Phase 10; 11 remaining skills need pipeline runs; complete skill inventory with module names and expectation counts documented below |
</phase_requirements>

## Standard Stack

### Core

| Tool | Version | Purpose | Why Standard |
|------|---------|---------|--------------|
| Claude Code Agent tool | Current | Spawn eval-executor, eval-grader subagents | Phase 8/10 validated; main session controls A/B isolation |
| eval-executor subagent | model: sonnet | Generate Drupal modules in ddev environments | `.claude/agents/eval-executor.md`; Read-based SKILL.md loading |
| eval-grader subagent | model: inherit (Opus) | Grade generated code against expectations | `.claude/agents/eval-grader.md`; validated in Phase 10 |
| setup-fresh-drupal10.sh | eval/ | Provision fresh D10 ddev instances | Auto-retry, Traefik cleanup, bootstrap verification |
| teardown-drupal-env.sh | eval/ | Clean up ddev instances | d10- and os-kg- dual prefix support |
| ddev | v1.24.8 | Container-based Drupal environments | Proven in Phase 10; flock-serialized starts |
| jq | System | JSON schema validation and data extraction | Inline validation of grading.json and benchmark.json |

### Supporting

| Tool | Version | Purpose | When to Use |
|------|---------|---------|-------------|
| eval-browser subagent | model: haiku | E2E browser verification | NOT needed for batch runs -- eval-grader handles E2E via curl/drush |
| curl | System | HTTP-level E2E assertions | Grader uses curl -sk for E2E expectations |
| bash/shell | System | benchmark.json construction, directory setup | Workspace creation, file copying, aggregation |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Sequential batch runs | Parallel within batch (2 skills at once) | Parallel risks ddev-router conflicts; not worth the risk for a one-time execution |
| Manual benchmark.json construction | Python aggregate script | No Python scripts exist; bash/jq is proven from Phase 10; Python aggregation mentioned in ROADMAP but is Phase 12 work |
| Re-running caching+scaffold | Copying Phase 10 results | Copy is correct -- pipeline was already validated on these; re-running wastes time and may produce different results |

## Architecture Patterns

### Phase 11 Workspace Layout

Workspaces live under Phase 11's directory, not Phase 10's. The 2 calibration skills' benchmark.json files are COPIED from Phase 10.

```
.planning/phases/11-batch-execution/
  workspaces/
    drupal-access-security-workspace/
      iteration-1/
        eval-restricted-reports/
          eval_metadata.json
          with_skill/run-1/{grading.json, outputs/}
          without_skill/run-1/{grading.json, outputs/}
        benchmark.json
    drupal-batch-queue-cron-workspace/
      iteration-1/
        eval-content-indexer/
          ...same structure...
        benchmark.json
    drupal-caching-workspace/
      iteration-1/
        benchmark.json            # COPIED from Phase 10
    drupal-config-storage-workspace/
      ...
    [... all 13 skills ...]
```

### Complete Skill Inventory (All 13)

| # | Skill | Module Name | Eval Name | Expectations | ddev Names | Status |
|---|-------|-------------|-----------|-------------|------------|--------|
| 1 | drupal-access-security | restricted_reports | eval-restricted-reports | 9 | d10-access-with/without | NEEDS RUN |
| 2 | drupal-batch-queue-cron | content_indexer | eval-content-indexer | 8 | d10-batch-with/without | NEEDS RUN |
| 3 | drupal-caching | related_content_block | eval-cache-block | 9 | -- | COPY FROM PHASE 10 |
| 4 | drupal-config-storage | site_announcements | eval-site-announcements | 9 | d10-config-with/without | NEEDS RUN |
| 5 | drupal-database-api | view_analytics | eval-view-analytics | 9 | d10-database-with/without | NEEDS RUN |
| 6 | drupal-entities-fields | knowledge_resource | eval-knowledge-resource | 9 | d10-entities-with/without | NEEDS RUN |
| 7 | drupal-forms-api | search_settings | eval-search-settings | 10 | d10-forms-with/without | NEEDS RUN |
| 8 | drupal-module-scaffold | event_analytics | eval-scaffold-module | 8 | -- | COPY FROM PHASE 10 |
| 9 | drupal-plugins-blocks | content_recommendations | eval-content-recommendations | 9 | d10-plugins-with/without | NEEDS RUN |
| 10 | drupal-routing-controllers | api_status_endpoint | eval-api-status-endpoint | 8 | d10-routing-with/without | NEEDS RUN |
| 11 | drupal-testing | calculator | eval-calculator | 8 | d10-testing-with/without | NEEDS RUN |
| 12 | drupal-theming | featured_resources | eval-featured-resources | 8 | d10-theming-with/without | NEEDS RUN |
| 13 | drupal-views-dev | resource_directory | eval-resource-directory | 7 | d10-views-with/without | NEEDS RUN |

**Total: 11 skills need pipeline runs; 2 skills copy from Phase 10**

### Pattern 1: Batch Organization

**What:** How to group the 11 remaining skills into 3-4 per session.

**Recommended batches (based on expectation count and complexity):**

```
Batch 1 (4 skills, medium complexity):
  - drupal-access-security (9 exp, 2 E2E)
  - drupal-routing-controllers (8 exp, 1 E2E)
  - drupal-plugins-blocks (9 exp, 1 E2E)
  - drupal-forms-api (10 exp, 1 E2E)

Batch 2 (4 skills, mixed complexity):
  - drupal-entities-fields (9 exp, complex entity type)
  - drupal-config-storage (9 exp, 0 E2E)
  - drupal-database-api (9 exp, 0 E2E)
  - drupal-batch-queue-cron (8 exp, 1 E2E)

Batch 3 (3 skills, lighter):
  - drupal-testing (8 exp, no runtime checks)
  - drupal-theming (8 exp, 0 E2E)
  - drupal-views-dev (7 exp, 0 E2E)
```

**Batch execution order rationale:** Batch 1 includes the skills with the most E2E expectations (access-security has 2: anonymous 403, admin 200), providing early validation that the grader handles E2E checks correctly across different skill domains. Batch 3 is lightest since testing/theming/views have fewer or no runtime requirements.

### Pattern 2: Per-Skill Pipeline Cycle (Identical to Phase 10)

**Steps for each skill in a batch:**

```
1. SETUP: mkdir -p workspace tree + write eval_metadata.json
2. CLEANUP: ddev list | grep stale | xargs ddev delete
3. PROVISION: setup-fresh-drupal10.sh <skill>-with (then <skill>-without) SEQUENTIAL
4. EXECUTE WITH: Spawn eval-executor with SKILL.md Read instruction
5. COPY OUTPUTS WITH: cp -r /tmp/d10-<skill>-with/web/modules/custom/<module>/* workspace/with_skill/run-1/outputs/
6. EXECUTE WITHOUT: Spawn eval-executor without SKILL.md reference
7. COPY OUTPUTS WITHOUT: cp -r /tmp/d10-<skill>-without/web/modules/custom/<module>/* workspace/without_skill/run-1/outputs/
8. GRADE WITH: Spawn eval-grader for with_skill run
9. VALIDATE WITH: jq schema check on grading.json
10. GRADE WITHOUT: Spawn eval-grader for without_skill run
11. VALIDATE WITHOUT: jq schema check on grading.json
12. AGGREGATE: Construct benchmark.json from both grading.json files
13. TEARDOWN: teardown-drupal-env.sh <skill>-with (then <skill>-without)
```

**CRITICAL: Keep ddev instances alive until BOTH grading runs complete.** Phase 10 lesson: gsd-executor destroyed .ddev directory, preventing grading. Teardown ONLY after all grading is done.

### Pattern 3: Calibration Result Copying

**What:** Copy Phase 10 benchmark.json files into Phase 11 workspace.

```bash
# Caching
mkdir -p .planning/phases/11-batch-execution/workspaces/drupal-caching-workspace/iteration-1/
cp .planning/phases/10-pipeline-validation/workspaces/drupal-caching-workspace/iteration-1/benchmark.json \
   .planning/phases/11-batch-execution/workspaces/drupal-caching-workspace/iteration-1/benchmark.json

# Scaffold
mkdir -p .planning/phases/11-batch-execution/workspaces/drupal-module-scaffold-workspace/iteration-1/
cp .planning/phases/10-pipeline-validation/workspaces/drupal-module-scaffold-workspace/iteration-1/benchmark.json \
   .planning/phases/11-batch-execution/workspaces/drupal-module-scaffold-workspace/iteration-1/benchmark.json
```

### Pattern 4: ddev Naming Convention

Use shortened skill identifiers to keep ddev project names reasonable:

| Skill | ddev Prefix |
|-------|-------------|
| drupal-access-security | access |
| drupal-batch-queue-cron | batch |
| drupal-config-storage | config |
| drupal-database-api | database |
| drupal-entities-fields | entities |
| drupal-forms-api | forms |
| drupal-plugins-blocks | plugins |
| drupal-routing-controllers | routing |
| drupal-testing | testing |
| drupal-theming | theming |
| drupal-views-dev | views |

So ddev projects are: `d10-access-with`, `d10-access-without`, `d10-batch-with`, etc.

### Anti-Patterns to Avoid

- **Re-running caching or scaffold:** These already have Phase 10 benchmark data. Re-running wastes time and may produce different stochastic results. Copy the existing benchmark.json.
- **Running more than 4 skills per batch/session:** Context window limits in a long Opus session make this risky. 4 skills = 8 executor runs + 8 grader runs + 8 setup/teardown cycles -- already substantial.
- **Provisioning all environments upfront:** Provision and teardown per-skill, not per-batch. Having 8 simultaneous ddev instances wastes RAM and risks port conflicts.
- **Skipping output archival before teardown:** Always cp module files to workspace BEFORE teardown. After teardown, the /tmp directory is gone.
- **Using eval-browser for batch runs:** eval-browser was created for complex page content verification. The eval-grader handles all E2E expectations via curl -sk and ddev drush commands. eval-browser adds complexity for no benefit in batch runs.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Batch orchestration script | Custom Python/bash batch runner | Direct Opus session orchestration with Agent tool | Phase 8 decision: observability trumps automation; failures need real-time diagnosis |
| Environment provisioning | Docker compose automation | setup-fresh-drupal10.sh (existing) | Handles retry, Traefik cleanup, bootstrap verification |
| Code grading | grep/jq pattern matching | eval-grader subagent (existing) | AI reads actual code, verifies evidence, handles E2E checks |
| benchmark.json construction | aggregate_benchmark.py | Manual bash/jq construction per skill | aggregate_benchmark.py does not exist; bash/jq is proven from Phase 10; Python aggregation is Phase 12 work |
| Workspace layout | Custom directory structure | Phase 10 workspace pattern (proven) | Exact directory structure validated and schema-checked |
| Cross-batch aggregation | Custom reporting tool | Simple summary table in plan output | 13 rows x 4 columns; no tool needed |

**Key insight:** Phase 11 is an EXECUTION phase at scale. The pipeline works. The only new work is applying it 11 more times and assembling the complete dataset.

## Common Pitfalls

### Pitfall 1: Session Context Overflow

**What goes wrong:** Running too many skills in a single session exhausts the context window, causing late-batch skills to lose early context.
**Why it happens:** Each skill produces ~2KB of workspace paths, grading evidence, and benchmark data. 13 skills would be ~26KB of accumulated context plus all the Agent tool subagent interactions.
**How to avoid:** Batch into 3-4 skills per session (plan). Between sessions (plans), commit results and start fresh. Each plan should commit its batch's workspaces and summaries.
**Warning signs:** Agent tool responses becoming less detailed, missing file paths, or schema validation failures in late-batch skills.

### Pitfall 2: ddev-Router Exhaustion Across Batch

**What goes wrong:** After tearing down several skills, stale Traefik configs accumulate and block new ddev starts.
**Why it happens:** teardown-drupal-env.sh removes the ddev project but may leave Traefik config remnants in the ddev-global-cache Docker volume.
**How to avoid:** Run stale cleanup BEFORE each skill's setup, not just at batch start. The setup-fresh-drupal10.sh already handles this, but additionally run `ddev list 2>/dev/null | grep -o 'd10-[^ ]*' | xargs -I{} ddev delete -O -y {} 2>/dev/null || true` before each batch.
**Warning signs:** "ddev start failed after 3 attempts" errors, Traefik health check failures.

### Pitfall 3: Module Name Mismatch Between Prompt and ddev Project

**What goes wrong:** Executor creates module in wrong directory or grader looks at wrong path.
**Why it happens:** Each skill has a different module name. If the prompt or grader path uses the wrong module name, the pipeline produces empty or incorrect grading.
**How to avoid:** Use the complete skill inventory table above. Cross-reference module name from evals.json prompt with the cp and grader paths.
**Warning signs:** Empty outputs/ directories, "module not found" in grader evidence.

### Pitfall 4: Inconsistent Workspace Paths Across Skills

**What goes wrong:** Some skills' workspaces use different naming conventions, breaking aggregate analysis.
**Why it happens:** Manual workspace creation without a consistent template.
**How to avoid:** Use consistent naming: `workspaces/<skill-name>-workspace/iteration-1/<eval-name>/`. Eval names derived from module name (e.g., `eval-restricted-reports` for `restricted_reports` module).
**Warning signs:** aggregate analysis scripts can't find benchmark.json at expected paths.

### Pitfall 5: Testing Skill Has No Runtime Dependencies

**What goes wrong:** drupal-testing eval asks to create a calculator module with tests, but PHPUnit/Kernel test runs may fail if not configured.
**Why it happens:** Fresh D10 ddev instances don't have PHPUnit configured out of the box. The testing eval expectations check for test file existence and structure, NOT test execution results.
**How to avoid:** The testing expectations (8 total, 0 E2E) check code structure, not runtime. The grader should evaluate code patterns, not run tests. This is already how the grader works.
**Warning signs:** Grader trying to run phpunit and failing -- this would be wrong behavior.

### Pitfall 6: Access-Security E2E Needs Careful Auth Handling

**What goes wrong:** The access-security skill has 2 E2E expectations: "Anonymous gets 403" and "Admin gets 200". The grader must test BOTH unauthenticated and authenticated requests.
**Why it happens:** Most other E2E expectations just check if a page loads. Access-security requires testing permission boundaries.
**How to avoid:** The grader handles this via `curl -sk` (unauthenticated for 403) and `ddev drush uli` + curl (authenticated for 200). This is within the grader's documented capabilities.
**Warning signs:** Both E2E checks pass when they shouldn't (e.g., anonymous getting 200 instead of 403).

## Code Examples

### Workspace Setup for a Skill (Template)

```bash
SKILL="drupal-access-security"
EVAL_NAME="eval-restricted-reports"
PHASE_DIR=".planning/phases/11-batch-execution"
WORKSPACE="$PHASE_DIR/workspaces/${SKILL}-workspace/iteration-1/${EVAL_NAME}"

mkdir -p "$WORKSPACE/with_skill/run-1/outputs"
mkdir -p "$WORKSPACE/without_skill/run-1/outputs"
```

### eval_metadata.json Template

```json
{
  "skill_name": "<skill-name>",
  "eval_id": 1,
  "iteration": 1,
  "eval_name": "<eval-name>",
  "prompt": "<from evals.json>",
  "expected_output": "<from evals.json>",
  "expectations": ["<all expectations from evals.json>"],
  "configs": ["with_skill", "without_skill"],
  "runs_per_config": 1,
  "model": "claude-sonnet-4-6"
}
```

### With-Skill Executor Prompt Template

```
You are working in ddev project d10-<SHORT_NAME>-with at /tmp/d10-<SHORT_NAME>-with

FIRST: Read the skill file at /home/proofoftom/Code/drupal-skills/skills/<SKILL_NAME>/SKILL.md

THEN: <prompt from evals.json>
```

### Without-Skill Executor Prompt Template

```
You are working in ddev project d10-<SHORT_NAME>-without at /tmp/d10-<SHORT_NAME>-without

Do NOT ask questions -- just create the code.

<prompt from evals.json>
```

### Grader Prompt Template

```
Grade the Drupal module code against these expectations:

Expectations:
1. "<expectation 1 from evals.json>"
2. "<expectation 2>"
...

Module path: /tmp/d10-<SHORT_NAME>-<config>/web/modules/custom/<MODULE_NAME>/
ddev project name: d10-<SHORT_NAME>-<config>

Write the grading JSON to:
/home/proofoftom/Code/drupal-skills/<WORKSPACE_PATH>/<config>/run-1/grading.json
```

### benchmark.json Construction (bash/jq from Phase 10)

```bash
# Read both grading.json files
WITH_RATE=$(jq '.summary.pass_rate' "$WORKSPACE/with_skill/run-1/grading.json")
WITHOUT_RATE=$(jq '.summary.pass_rate' "$WORKSPACE/without_skill/run-1/grading.json")
DELTA=$(echo "$WITH_RATE - $WITHOUT_RATE" | bc)

# Construct benchmark.json following Phase 10 schema
# (see Phase 10 RESEARCH.md Pattern 4 for full schema)
```

### Schema Validation

```bash
# Validate grading.json
jq 'has("expectations") and has("summary") and (.summary | has("pass_rate"))' grading.json
# Must output: true

# Validate expectations count matches evals.json
jq '.expectations | length' grading.json
# Must match expected count for skill

# Validate benchmark.json
jq 'has("metadata") and has("runs") and has("run_summary") and (.run_summary | has("delta"))' benchmark.json
# Must output: true
```

### Consolidated Summary Table Template

After all 13 skills have benchmark.json:

```
=== PHASE 11: BATCH EXECUTION RESULTS ===

| # | Skill | With | Without | Delta | Source |
|---|-------|------|---------|-------|--------|
| 1 | drupal-access-security | X/9 (XX%) | Y/9 (YY%) | +ZZ% | Batch 1 |
| 2 | drupal-batch-queue-cron | X/8 | Y/8 | +ZZ% | Batch 2 |
| 3 | drupal-caching | 9/9 (100%) | 8/9 (89%) | +11% | Phase 10 |
| ... | ... | ... | ... | ... | ... |
| 13 | drupal-views-dev | X/7 | Y/7 | +ZZ% | Batch 3 |

Skills with benchmark: 13/13
```

## State of the Art

| Old Approach (Phase 10) | Current Approach (Phase 11) | Impact |
|-------------------------|------------------------------|--------|
| 2 calibration skills, sequential | 11 new skills in 3 batches of 3-4 | Scale from validation to production |
| Workspace under Phase 10 dir | Workspace under Phase 11 dir (Phase 10 results copied) | Clean separation of calibration vs batch data |
| Manual benchmark.json per skill | Same manual approach but 11x | No new tooling needed; Python aggregation deferred to Phase 12 |
| Delta thresholds enforced (>30%/15%) | No delta thresholds -- record all results | Phase 11 collects data; Phase 12 classifies into tiers |

## Open Questions

1. **Whether to re-run caching/scaffold or copy Phase 10 results**
   - What we know: Phase 10 produced valid benchmark.json for both skills. Deltas were low (+11%, +13%) but pipeline was validated.
   - What's unclear: None -- copying is the correct approach.
   - Recommendation: Copy Phase 10 results. Do NOT re-run.

2. **Whether 3 batches of 3-4 or 4 batches of 2-3 is better**
   - What we know: Phase 10 ran 1 skill per plan without context issues. 4 skills per session is the ROADMAP recommendation.
   - What's unclear: Whether 4 skills in one session will hit context limits.
   - Recommendation: Use 3 batches (4+4+3). If the first batch of 4 shows context issues, the planner can split Batch 2 into 2+2.

3. **How to handle skills that produce 0% delta**
   - What we know: Phase 7 iteration 1 showed 9 of 13 skills at 0% delta with old (standard) assertions. Phase 9 rewrote ALL assertions to target differentiating patterns.
   - What's unclear: Whether the rewritten assertions will produce non-zero deltas for all skills.
   - Recommendation: Record all results regardless of delta. 0% delta is valid data for Phase 12 analysis.

## Validation Architecture

### Test Framework

| Property | Value |
|----------|-------|
| Framework | Shell-based validation (jq + bash assertions) |
| Config file | None -- validation is inline during pipeline execution |
| Quick run command | `jq '.summary.pass_rate' <path>/grading.json` |
| Full suite command | `for f in $(find .planning/phases/11-batch-execution/workspaces/ -name "benchmark.json"); do echo "$f: delta=$(jq '.run_summary.delta.pass_rate' "$f")"; done` |

### Phase Requirements -> Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| PIPE-02 | All 13 skills processed in batches of 3-4 | integration | `find workspaces/ -name "benchmark.json" \| wc -l` (must be 13) | Wave 0 |
| PIPE-03a | Each skill has grading.json (with_skill) | unit | `jq 'has("expectations") and has("summary")' grading.json` per skill | Wave 0 |
| PIPE-03b | Each skill has grading.json (without_skill) | unit | Same as above for without_skill path | Wave 0 |
| PIPE-03c | Each skill has benchmark.json | unit | `jq 'has("metadata") and has("runs") and has("run_summary")' benchmark.json` per skill | Wave 0 |
| ANLZ-01a | All 13 skills have with-skill pass rates | integration | `jq '.run_summary.with_skill.pass_rate.mean' benchmark.json` for all 13 | Wave 0 |
| ANLZ-01b | All 13 skills have without-skill pass rates | integration | Same for without_skill | Wave 0 |
| ANLZ-01c | All 13 skills have delta computed | integration | `jq '.run_summary.delta.pass_rate' benchmark.json` for all 13 | Wave 0 |

### Sampling Rate

- **Per skill commit:** Validate grading.json schema after each grader run (`jq schema check`)
- **Per batch (plan) merge:** Validate all benchmark.json files in the batch have valid schema and deltas
- **Phase gate:** `find workspaces/ -name "benchmark.json" | wc -l` returns 13; all have valid schema

### Wave 0 Gaps

- [ ] Phase 11 workspace directory tree needs creation for all 13 skills
- [ ] Phase 10 benchmark.json files need copying for caching and scaffold
- [ ] eval_metadata.json needs populating per skill from evals.json
- [ ] No automated "run all validations" script -- each batch validates inline

## Sources

### Primary (HIGH confidence)

- Phase 10 RESEARCH.md -- pipeline architecture, workspace layout, schemas (all verified empirically)
- Phase 10 01-SUMMARY.md and 02-SUMMARY.md -- actual pipeline results, lessons learned (ddev destruction, grader compliance)
- Phase 10 benchmark.json files (caching, scaffold) -- actual grading data and deltas
- `.claude/agents/eval-executor.md` -- subagent definition with model: sonnet
- `.claude/agents/eval-grader.md` -- grader definition with model: inherit
- `eval/setup-fresh-drupal10.sh` -- environment provisioning with retry logic
- `eval/teardown-drupal-env.sh` -- cleanup script
- All 13 `skills/drupal-*/evals/evals.json` -- rewritten prompts and expectations

### Secondary (MEDIUM confidence)

- MEMORY.md eval execution rules -- 11 rules for orchestration (empirically derived from v1.0)
- Phase 7 iteration 1 results -- 9 of 13 skills showed 0% delta (old assertions), motivating Phase 9 rewrite

### Tertiary (LOW confidence)

- None -- all findings verified against actual project files and Phase 10 empirical results

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH -- all tools exist and were validated in Phase 10
- Architecture: HIGH -- workspace layout and pipeline pattern proven; complete skill inventory documented with verified module names
- Pitfalls: HIGH -- Phase 10 lessons (ddev destruction, grader schema compliance) directly applicable; session context limits understood
- Batch organization: MEDIUM -- 3-4 per batch is recommended but untested at that scale; first batch will validate

**Research date:** 2026-03-07
**Valid until:** 2026-04-07 (stable -- no infrastructure changes expected)

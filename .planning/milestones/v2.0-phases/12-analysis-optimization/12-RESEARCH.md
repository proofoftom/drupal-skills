# Phase 12: Analysis & Optimization - Research

**Researched:** 2026-03-07
**Domain:** Eval pipeline optimization, skill content improvement, coding standards, harder eval design
**Confidence:** HIGH

## Summary

Phase 12 takes the raw eval data from Phase 11 (13 skills evaluated, 6 WITH-skill failures identified) and turns it into actionable improvements. The work breaks into four streams: (1) fix known failures via a new coding-standards skill and SKILL.md patches, (2) write harder evals for the 4 neutral-delta skills, (3) re-run affected skills to measure improvement, and (4) compile a final report with stabilized tier classifications.

The 6 WITH-skill failures have clear root causes. Three are phpcs violations (brace style, missing docblocks, nullable types) solvable by a single `drupal-coding-standards` skill. Two are SKILL.md content gaps (batch-queue-cron missing try/catch in processItem example, routing-controllers weak DI guidance). One is an eval prompt gap (database-api doesn't motivate addTag()). All fixes are surgical and low-risk.

The harder evals for neutral skills require targeting patterns that baseline Haiku does NOT know. Analysis of the Sipos book and current evals shows: forms-api evals test ConfigFormBase (well-known), but `#states` conditional visibility, `#ajax` callbacks, and `ConfirmFormBase` with cancel URL are obscure. Theming evals test basic hook_theme/template patterns (well-known), but `template_preprocess_HOOK()` naming conventions and `hook_theme_suggestions_HOOK()` for dynamic template selection are obscure. Entities-fields evals test basic content entities (well-known), but `EntityChangedTrait` usage, proper `preSave()` validation, and bundle entity patterns are obscure. Database-api needs addTag() motivation in the prompt (already identified). The pipeline is headless `claude -p` with haiku -- no changes needed to infrastructure.

**Primary recommendation:** Execute the 7-step optimization plan from .continue-here.md in order, re-running only the affected skills (not all 13), then compile the final report.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| ANLZ-02 | Skills classified into tiers: High (>15%), Moderate (5-15%), Low (<5%) | Tier classification already done in Phase 11 handoff. Phase 12 stabilizes classifications after re-runs. |
| ANLZ-03 | Skills with weak deltas analyzed -- assertions tightened or skill content improved | 6 failures identified with root causes. Optimization plan: new coding-standards skill, 2 SKILL.md patches, 1 eval prompt fix, harder evals for 4 neutral skills. |
| ANLZ-04 | Final report with stabilized results and overall verdict | Report compiles re-run data, tier reclassifications, and overall skill value verdict. |
| CARRY-01 | FULL-05 -- Skills with weak deltas iterated on | Same as ANLZ-03. Harder evals for neutral skills + fixes for negative skills constitute the iteration. |
| CARRY-02 | FULL-06 -- Final analysis with stabilized results | Same as ANLZ-04. Final report with generate_review.py HTML viewers. |
</phase_requirements>

## Standard Stack

### Core
| Tool | Version | Purpose | Why Standard |
|------|---------|---------|--------------|
| `claude -p` (headless) | claude-haiku-4-5-20251001 | Code generation for eval runs | Confirmed in Phase 11 -- eliminates agent harness confound |
| eval-grader agent | model: sonnet | Grade generated code against expectations | Standard grading agent from Phase 8 |
| eval-browser agent | model: haiku | E2E browser verification via agent-browser | Standard browser agent from Phase 8 |
| `eval/setup-fresh-drupal10.sh` | N/A | Fresh Drupal 10 ddev instances | Standard setup from Phase 8, includes auto-retry |
| phpcs | Drupal,DrupalPractice | Static analysis for coding standards | Standard Drupal tooling, already in eval pipeline |

### Supporting
| Tool | Version | Purpose | When to Use |
|------|---------|---------|-------------|
| generate_review.py | skill-creator plugin | HTML viewer for human eval review | After final report, for visual review |
| ddev | System | Drupal development environment | All eval runs |

### Paths
| Item | Path |
|------|------|
| Skills | `skills/drupal-*/SKILL.md` |
| Evals | `skills/drupal-*/evals/evals.json` |
| Eval results | `eval/results/<skill>/` |
| Setup script | `eval/setup-fresh-drupal10.sh` |
| Eval agents | `.claude/agents/eval-{grader,browser}.md` |
| Sipos book | `Sipos D. Drupal 10 Module Development...4ed 2023.md` |
| generate_review.py | `~/.claude/plugins/cache/claude-plugins-official/skill-creator/205b6e0b3036/skills/skill-creator/eval-viewer/generate_review.py` |

## Architecture Patterns

### Work Stream Organization

The optimization work has natural dependencies:

```
Stream 1: Fix known failures (can run in parallel)
  1a. Create drupal-coding-standards skill (NEW)
  1b. Patch batch-queue-cron SKILL.md (try/catch in processItem)
  1c. Patch routing-controllers SKILL.md (strengthen DI WRONG callout)
  1d. Patch database-api evals.json (add addTag motivation to prompt)

Stream 2: Write harder evals for neutral skills (after Stream 1)
  2a. forms-api -- target #states, ConfirmFormBase, or #ajax
  2b. theming -- target template_preprocess_HOOK naming, theme suggestions
  2c. entities-fields -- target EntityChangedTrait, preSave validation, bundle patterns
  2d. database-api -- re-evaluate after prompt fix (may not need harder eval)

Stream 3: Re-run affected skills (after Streams 1-2)
  Re-run: routing-controllers, batch-queue-cron, views-dev (fixes #1-6)
  Re-run: forms-api, theming, entities-fields, database-api (harder evals)
  Total: ~7 skills to re-run (not all 13)

Stream 4: Final report (after Stream 3)
  Compile stabilized results, tier classifications, verdict
  Generate HTML viewers via generate_review.py
```

### Coding Standards Skill Content

The new `drupal-coding-standards` skill must cover these phpcs failure patterns observed in Phase 11:

| Failure | Skill Must Teach | Source |
|---------|-----------------|--------|
| Missing `} else {` brace style (routing-controllers) | Cuddled elses: `} else {` on same line, `} catch (...) {` on same line | Drupal coding standards |
| Missing docblocks (batch-queue-cron, 4 errors) | All classes, methods, and functions need docblocks. `@param`, `@return`, `@throws`. Hook implementations use `/** * Implements hook_name(). */` | Sipos Ch.1 p.869, Drupal standards |
| Nullable parameter `$options = NULL` (views-dev) | D11 method signatures: use `?type` for nullable parameters when overriding parent methods. `public function init(..., ?array $options = NULL)` not `$options = NULL` | PHP 8.4 deprecation, Drupal D11 compat |
| General | `@file` docblocks only for procedural files (.module, .install), NOT for class files. One class per file. Trailing commas in multi-line arrays. | Drupal coding standards |

**Skill structure recommendation:**
```
skills/drupal-coding-standards/
  SKILL.md
  evals/
    evals.json
```

The skill should be SHORT and focused -- not a comprehensive style guide but a targeted "patterns that phpcs catches" reference. Include WRONG/RIGHT examples for each pattern.

### SKILL.md Patches

**batch-queue-cron SKILL.md (line ~228, processItem method):**
Current: `processItem()` example just throws `\Exception('Missing team ID')` -- no try/catch, no SuspendQueueException handling in the processItem body itself. The try/catch with SuspendQueueException is only shown in the "Programmatic queue processing" section (line ~293), which is a different context (manual loop, not cron worker).

Fix: Add try/catch inside processItem() example showing:
```php
public function processItem($data) {
  try {
    // ... process item ...
  }
  catch (SuspendQueueException $e) {
    // Systemic failure -- rethrow to suspend queue processing.
    throw $e;
  }
  catch (\Exception $e) {
    // Log and skip bad items (don't rethrow = item deleted from queue).
    \Drupal::logger('my_module')->error('Failed: @msg', ['@msg' => $e->getMessage()]);
  }
}
```

**routing-controllers SKILL.md (line ~278, DI WRONG callout):**
Current: The WRONG callout about `\Drupal::service()` is buried at line 278 in a paragraph. Haiku may not internalize it.

Fix: Move the WRONG/RIGHT callout HIGHER (after the controller class example around line 268) and strengthen:
```
> NEVER use \Drupal::service() or \Drupal::entityTypeManager() inside controllers.
> This is the #1 DI violation. Always inject via create() + constructor.
```

### Eval Prompt Patch

**database-api evals.json prompt:**
Current: "Use Drupal's database abstraction layer for all queries."
Add: "Ensure queries are alterable by other modules using appropriate query tags."

This directly motivates the addTag() expectation without being too prescriptive about HOW.

### Harder Eval Strategies for Neutral Skills

Analysis of why each skill shows 0% delta and what would differentiate:

**forms-api (9/9 vs 9/9):**
- Current eval tests ConfigFormBase -- a pattern Haiku knows perfectly
- Harder eval options:
  - **ConfirmFormBase with entity delete** -- requires `getQuestion()`, `getCancelUrl()`, `getConfirmText()`, and `$form_state->setRedirectUrl($this->getCancelUrl())` in submitForm. Haiku often forgets getCancelUrl() or hardcodes a string instead of using Url::fromRoute().
  - **Form with #states conditional visibility** -- requires `#states` array with correct selector syntax (`:input[name="field_name"]`). Haiku frequently gets the selector format wrong.
  - **Multi-step form with setRebuild()** -- obscure pattern where `$form_state->setRebuild()` preserves form state across steps. Haiku typically rebuilds manually.
  - **Recommendation:** ConfirmFormBase eval. It's the most testable and has clear pass/fail differentiators.

**theming (9/9 vs 9/9):**
- Current eval tests hook_theme + template + library -- patterns Haiku knows
- Harder eval options:
  - **template_preprocess_HOOK naming convention** -- the preprocessor function MUST be named `template_preprocess_HOOKNAME()` (with `template_` prefix for default preprocessor). Haiku may use wrong prefix like `my_module_preprocess_` or skip it entirely.
  - **hook_theme_suggestions_HOOK()** -- dynamic template suggestions based on variables. Haiku may not know the double-underscore pattern (`base_hook__context`) or the suggestion hook naming.
  - **Render element vs variables** -- `'render element' => 'element'` in hook_theme for form elements. Haiku likely only knows the `'variables'` pattern.
  - **Recommendation:** An eval requiring template_preprocess_HOOK with Attribute object handling AND hook_theme_suggestions_HOOK for context-based template selection. Both are Sipos Ch.4 patterns that baseline Haiku is unlikely to know.

**entities-fields (9/9 vs 9/9):**
- Current eval tests basic content entity with base fields -- Haiku knows this well
- Harder eval options:
  - **Entity with bundles** -- requires companion ConfigEntityType as bundle entity, `bundle_entity_type` and `bundle_of` in annotations. Complex wiring Haiku often gets wrong.
  - **preSave() validation** -- entity-level validation in `preSave()` with `ConstraintViolation` or field constraints. Obscure pattern.
  - **EntityChangedTrait with changed field** -- the trait provides `getChangedTime()`/`setChangedTime()` and requires a `changed` base field. Haiku sometimes adds the trait but forgets the field or vice versa.
  - **Revisionable entity** -- requires `revision` entity key, revision table, `setRevisionable(TRUE)` on fields.
  - **Recommendation:** Bundle entity eval. It's the most complex pattern and directly tests whether the skill teaches the bundle_entity_type/bundle_of wiring correctly.

**database-api (8/9 vs 8/9):**
- Both failed only on addTag() -- prompt didn't motivate it
- After prompt fix, WITH should pass addTag and WITHOUT should not (skill teaches it clearly at line 358)
- May not need harder eval if prompt fix creates the delta
- **Recommendation:** Fix prompt first, re-run. Only write harder eval if delta remains 0%.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| HTML eval viewer | Custom HTML/JS dashboard | generate_review.py --static | Skill-creator tool handles workspace discovery, benchmark tabs, side-by-side comparison |
| Custom eval runner script | Bash orchestration script | Manual orchestrator commands from main session | Pipeline is well-understood; script adds debugging complexity |
| Coding standards checker | Manual code review | phpcs --standard=Drupal,DrupalPractice | Automated, reproducible, already in pipeline |
| Aggregate benchmark | Custom summary script | Manual summary table (like in STATE.md) | v2.0 pipeline doesn't use aggregate_benchmark.py workspace format |

**Key insight:** The v2.0 eval pipeline diverged from skill-creator's workspace structure. Results are stored in `eval/results/<skill>/` with custom JSON formats, not the workspace layout generate_review.py expects. The final report should be a manually compiled markdown document, with generate_review.py used only if workspace structure is retrofitted.

## Common Pitfalls

### Pitfall 1: Re-running all 13 skills instead of just affected ones
**What goes wrong:** Wasting 6+ hours on re-runs that won't change results.
**Why it happens:** Desire for "clean" data across all skills.
**How to avoid:** Only re-run skills where content or evals changed. The 6 HIGH/MOD tier skills (caching, scaffold, testing, config-storage, plugins-blocks, access-security) have stable, clean results. Do not re-run them.
**Warning signs:** Planning documents that include "re-run all 13 skills."

### Pitfall 2: Coding standards skill that's too comprehensive
**What goes wrong:** A 500-line skill covering every phpcs rule, most of which Haiku already follows.
**Why it happens:** Trying to be thorough instead of targeted.
**How to avoid:** Focus ONLY on the 4 phpcs patterns that caused actual failures in Phase 11 evals. Include WRONG/RIGHT examples, not exhaustive rule lists.
**Warning signs:** Skill file > 150 lines.

### Pitfall 3: Harder evals that test well-known patterns
**What goes wrong:** 0% delta again because the "harder" pattern is still something Haiku knows.
**Why it happens:** Insufficient analysis of what baseline Haiku does/doesn't know.
**How to avoid:** Each harder eval must target a specific pattern that the SKILL.md teaches AND that is obscure enough that Haiku without the skill won't produce it. The differentiation analysis above identifies specific patterns per skill.
**Warning signs:** New eval expectations that overlap with existing expectations (same knowledge tested differently).

### Pitfall 4: Haiku model noise in single-run eval
**What goes wrong:** A single re-run shows different results due to model randomness, not skill improvement.
**Why it happens:** LLM outputs are non-deterministic. A single run may pass or fail randomly.
**How to avoid:** Accept single-run variance as inherent limitation. Focus on structural improvements (does the fix address the root cause?) rather than chasing exact percentages. If a re-run shows unexpected results, consider the root cause analysis over the raw numbers.
**Warning signs:** Spending time re-running the same skill multiple times to get "better" numbers.

### Pitfall 5: generate_review.py workspace format mismatch
**What goes wrong:** generate_review.py expects a specific workspace directory structure (iteration-N/outputs/, eval_metadata.json, grading.json) that the v2.0 pipeline doesn't produce.
**Why it happens:** v2.0 pipeline stores results in `eval/results/<skill>/` with custom JSON format.
**How to avoid:** Either (a) retrofit results into workspace format for generate_review.py, or (b) skip generate_review.py and produce the final report as markdown. Option (b) is more pragmatic. The success criteria says "available for human review" -- a well-formatted markdown report satisfies this.
**Warning signs:** Spending time restructuring result files to match workspace layout.

### Pitfall 6: Coding standards skill interactions with existing skills
**What goes wrong:** With-skill runs load coding-standards PLUS the domain skill, but without-skill runs load neither. This changes what's being tested.
**Why it happens:** The coding-standards skill should be loaded for ALL skill evals (both with and without domain skill), since it's a baseline quality skill not a domain differentiator.
**How to avoid:** Two options: (a) Include coding-standards as a "read first" instruction for ALL eval runs (both variants), so it's not a differentiator. (b) Only load coding-standards for with-skill runs, but then phpcs expectations measure coding-standards skill value, not domain skill value. Option (a) is cleaner -- it makes phpcs a baseline expectation that both variants should pass.
**Warning signs:** Coding-standards loading only in with-skill variant, inflating domain skill deltas.

## Code Examples

### Headless eval code generation template (from eval-executor.md)
```bash
# WITHOUT skill (baseline):
unset CLAUDECODE
cat <<'PROMPT' | claude -p --model claude-haiku-4-5-20251001 --allowedTools 'Read,Write,Edit,Bash'
[task prompt from evals.json]

Create all files in /tmp/d10-<skill>-without/web/modules/custom/<module_name>/
After creating all files, enable the module by running: cd /tmp/d10-<skill>-without && ddev drush en <module_name> -y && ddev drush cr
Do NOT ask questions -- just create the code.
PROMPT

# WITH skill:
unset CLAUDECODE
cat <<'PROMPT' | claude -p --model claude-haiku-4-5-20251001 --allowedTools 'Read,Write,Edit,Bash'
First, read the skill file at /path/to/SKILL.md and apply its patterns to the following task.

[task prompt from evals.json]

Create all files in /tmp/d10-<skill>-with/web/modules/custom/<module_name>/
After creating all files, enable the module by running: cd /tmp/d10-<skill>-with && ddev drush en <module_name> -y && ddev drush cr
Do NOT ask questions -- just create the code.
PROMPT
```

### phpcs installation in ddev (from MEMORY.md)
```bash
ddev composer require --dev drupal/coder squizlabs/php_codesniffer
# Then set installed_paths:
ddev exec vendor/bin/phpcs --config-set installed_paths \
  vendor/drupal/coder/coder_sniffer,vendor/sirbrillig/phpcs-variable-analysis,vendor/slevomat/coding-standard
```

### Eval orchestration sequence per skill
```bash
# 1. Setup (parallel for with/without)
eval/setup-fresh-drupal10.sh <skill>-with &
eval/setup-fresh-drupal10.sh <skill>-without &
wait

# 2. Code generation (parallel, headless)
# [headless claude -p for with-skill]
# [headless claude -p for without-skill]

# 3. Browser checks (if skill has (via eval-browser) expectations)
# [eval-browser agent for with variant]
# [eval-browser agent for without variant]

# 4. Grading (eval-grader agent for each variant)
# [eval-grader for with variant -- receives browser report]
# [eval-grader for without variant -- receives browser report]

# 5. Teardown AFTER grading completes
ddev delete -O -y d10-<skill>-with
ddev delete -O -y d10-<skill>-without
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Agent subagent code gen | Headless `claude -p` | Phase 11 (session 15) | Eliminated agent harness confound, 37.5% vs 0% delta on caching |
| eval-executor agent | Deprecated | Phase 11 | Agent implicit knowledge eliminated from pipeline |
| model: inherit for grader | model: sonnet | Phase 11 | Consistent grading quality |
| model: sonnet for executor | model: haiku | Phase 11 | Cost-effective, appropriate for code generation eval |
| All 13 expectations obvious patterns | Rewritten for non-obvious patterns | Phase 9 (session 13) | Better differentiation between with/without skill |

## Open Questions

1. **Should coding-standards skill load for BOTH variants or only with-skill?**
   - What we know: phpcs is an expectation in ALL evals. Coding-standards skill would help all variants.
   - What's unclear: Loading it for both variants eliminates phpcs as a differentiator but keeps domain skill deltas clean. Loading only for with-skill inflates domain deltas.
   - Recommendation: Load coding-standards for BOTH variants. phpcs compliance should be a baseline, not a differentiator. This is the cleanest experimental design. If phpcs still fails for without-skill variants even with the coding-standards skill, that's interesting signal about Haiku's ability to follow instructions.

2. **Are harder evals worth the effort for all 4 neutral skills?**
   - What we know: forms-api, theming, entities-fields all scored 9/9 or 8/9 for both variants.
   - What's unclear: Whether harder evals will show skill value or whether Haiku simply knows these domains well enough that skills don't help.
   - Recommendation: Write harder evals for forms-api and theming first (best differentiation potential). If those show delta, do entities-fields. Database-api only needs the prompt fix.

3. **generate_review.py workspace format compatibility**
   - What we know: v2.0 results are in `eval/results/<skill>/` with custom JSON formats.
   - What's unclear: How much work to retrofit into generate_review.py workspace format.
   - Recommendation: Skip generate_review.py for now. A markdown final report with the tier table satisfies the "human review" requirement. Revisit if user specifically requests HTML viewers.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Custom eval pipeline (headless claude -p + eval-grader agent + eval-browser agent) |
| Config file | `.claude/agents/eval-grader.md`, `.claude/agents/eval-browser.md` |
| Quick run command | Run single skill eval (setup + code gen + grade) |
| Full suite command | Run all affected skills (~7) through pipeline |

### Phase Requirements to Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| ANLZ-02 | Tier classification correct after re-runs | manual-only | Review re-run results, classify into tiers | N/A |
| ANLZ-03 | At least 1 iteration attempted | manual-only | Verify SKILL.md patches, new evals, re-runs completed | N/A |
| ANLZ-04 | Final report with stabilized results | manual-only | Verify report file exists with all 13 skills, tiers, verdict | N/A |
| CARRY-01 | Weak deltas iterated on | manual-only | Same as ANLZ-03 | N/A |
| CARRY-02 | Stabilized results | manual-only | Same as ANLZ-04 | N/A |

**Justification for manual-only:** Phase 12 requirements are analysis and reporting tasks, not code functionality. "Tier classification" and "final report" are document deliverables verified by review, not automated tests. The eval pipeline itself IS the test framework for individual skill quality.

### Sampling Rate
- **Per task:** Verify each re-run produces grade JSON with expected structure
- **Per wave:** Compare re-run results against Phase 11 baselines
- **Phase gate:** All re-runs complete, final report exists with all 13 skills classified

### Wave 0 Gaps
None -- existing eval infrastructure covers all phase requirements. No new test framework or config needed.

## Sources

### Primary (HIGH confidence)
- Phase 11 .continue-here.md -- 6 failure root causes, optimization plan, tier classifications
- Phase 11 eval results in `eval/results/` -- grade JSONs for all 13 skills
- SKILL.md files for batch-queue-cron, routing-controllers -- verified content gaps
- evals.json files for all 7 affected skills -- verified expectation content
- eval-grader.md, eval-browser.md, eval-executor.md -- pipeline configuration
- MEMORY.md -- pipeline rules, model specifications, confirmed confounds

### Secondary (MEDIUM confidence)
- Sipos D. "Drupal 10 Module Development" 4ed 2023 -- source for harder eval patterns (forms Ch.3, theming Ch.4, entities Ch.6-7, database Ch.9)
- Drupal coding standards at drupal.org/docs/develop/standards/coding-standards -- source for coding-standards skill content

### Tertiary (LOW confidence)
- generate_review.py workspace format compatibility -- unclear whether v2.0 results can be retrofitted

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - pipeline is well-established from Phases 8-11, no changes needed
- Architecture: HIGH - optimization plan directly derives from empirical failure analysis
- Pitfalls: HIGH - based on direct experience from Phase 11 execution
- Harder eval design: MEDIUM - patterns identified from Sipos book and SKILL.md analysis, but actual differentiation depends on Haiku's training data coverage

**Research date:** 2026-03-07
**Valid until:** 2026-04-07 (stable -- no fast-moving dependencies)

# Phase 9: Eval Prompt Rewrite - Research

**Researched:** 2026-03-06
**Domain:** Eval prompt authoring, Drupal module development tasks, A/B eval methodology
**Confidence:** HIGH

## Summary

Phase 9 requires rewriting all 13 eval prompts to work against a vanilla Drupal 10 site (provisioned by `eval/setup-fresh-drupal10.sh`) instead of os-knowledge-garden/Open Social. Every single current prompt references "Open Social" or "os-knowledge-garden" -- these must be replaced with generic Drupal 10 site context. The testing eval additionally references a non-existent module (`social_ai_indexing`) that won't exist on fresh D10, requiring a completely different prompt scenario.

The existing differentiating assertions (expectations) are already well-crafted from the Phase 7 plan 07-06 rewrite. Most target non-obvious SKILL.md patterns (cache golden rule, `^10 || ^11` format, 4-param plugin create(), etc.) and should remain largely intact. However, some may need adjustment if the new prompt scenario changes what code the executor produces (e.g., a different module name means different drush commands in E2E assertions).

**Primary recommendation:** Replace "Open Social" / "os-knowledge-garden" references with "Drupal 10 site" across all 13 prompts, redesign the testing prompt entirely (new module scenario), audit each prompt for implementation hints that leak to the without-skill agent, and update E2E assertions to match new module names if any change.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| EVAL-01 | All 13 eval prompts rewritten for fresh Drupal 10 instances (not os-kg tasks) | Full audit of all 13 evals.json shows every prompt references Open Social/os-kg. Detailed prompt-by-prompt analysis below identifies exact changes needed. |
| EVAL-02 | All 13 evals.json have differentiating assertions targeting SKILL.md non-obvious patterns | Existing assertions from v1.0 07-06 are already differentiating. Research identifies which need adjustment for new prompts and which are stable. |
</phase_requirements>

## Audit: All 13 Current Prompts

### Classification of Changes Needed

**Category A: Simple text replacement** (11 skills) -- Replace "Open Social site" / "os-knowledge-garden" with "Drupal 10 site", keep everything else the same. Module names, task descriptions, and assertions remain valid.

| Skill | Current Reference | Module Name | Change Scope |
|-------|------------------|-------------|--------------|
| drupal-caching | "Open Social Drupal 10 site" | related_content_block | Text swap only |
| drupal-module-scaffold | "Open Social site" | event_analytics | Text swap only |
| drupal-routing-controllers | "Drupal 10 Open Social site (os-knowledge-garden)" | api_status_endpoint | Text swap only |
| drupal-forms-api | "Drupal 10 Open Social site" | search_settings | Text swap only |
| drupal-plugins-blocks | "Drupal 10 Open Social site" | content_recommendations | Text swap only |
| drupal-config-storage | "Drupal 10 Open Social site" | site_announcements | Text swap only |
| drupal-access-security | "Drupal 10 Open Social site" | restricted_reports | Text swap only |
| drupal-theming | "Drupal 10 Open Social site" | featured_resources | Text swap only |
| drupal-database-api | "Drupal 10 Open Social site" | view_analytics | Text swap only |
| drupal-views-dev | "Drupal 10 Open Social site" | resource_directory | Text swap only |
| drupal-batch-queue-cron | "Drupal 10 Open Social site" | content_indexer | Text swap only |

**Category B: Full prompt redesign** (1 skill) -- The testing eval references a specific Open Social module (`social_ai_indexing`) with a specific service (`social_ai_indexing.related_content`) that won't exist on vanilla D10. Must be redesigned with a self-contained scenario.

| Skill | Issue | Solution |
|-------|-------|----------|
| drupal-testing | References non-existent `social_ai_indexing` module and `RelatedContentService` | Create a prompt that asks to write a test for a module the executor itself must also create, OR test a core service. Recommended: prompt asks to build a small service module AND write its kernel test. |

**Category C: Prompt context enrichment** (1 skill, overlaps with Category A) -- The entities-fields eval references "os-knowledge-garden" context ("Knowledge curation feature on our os-knowledge-garden site"). Should be recontextualized with a more generic Drupal 10 module development scenario.

| Skill | Issue | Solution |
|-------|-------|----------|
| drupal-entities-fields | Prompt says "knowledge curation feature on our Open Social Drupal 10 site (os-knowledge-garden)" and uses "KnowledgeResource" entity name | Reframe as generic custom entity. Can keep entity name or change to something more vanilla-D10 appropriate. Entity name itself doesn't affect assertions. |

### Prompt Hint Leakage Audit

Several current prompts contain implementation hints that partially teach the without-skill agent what to do. These hints reduce the delta between with-skill and without-skill runs, potentially masking the skill's value.

**High-impact hint leakage (should fix):**

| Skill | Hint in Prompt | What It Leaks | Recommendation |
|-------|---------------|---------------|----------------|
| drupal-caching | "Do NOT use max-age: 0" | Directly teaches the cache golden rule anti-pattern | Remove. The assertion already checks for this. Prompt should describe the REQUIREMENT, not the IMPLEMENTATION constraint. |
| drupal-batch-queue-cron | "Do NOT process items directly in hook_cron -- use the queue pattern" | Tells without-skill agent the exact architecture | Remove the "do NOT" instruction. Prompt should say "process items via a queue" without explicitly forbidding the wrong approach. |
| drupal-batch-queue-cron | "The queue worker must have a cron.time setting" | Directly teaches the non-obvious annotation pattern | Remove. This is a key differentiating pattern from SKILL.md. |
| drupal-database-api | "This is tracking data, NOT entity data -- do NOT use the Entity API" | Guides architecture choice | Rephrase: describe the task naturally and let the agent decide. The assertion already checks for Database API usage. |

**Medium-impact hint leakage (consider fixing):**

| Skill | Hint in Prompt | What It Leaks | Recommendation |
|-------|---------------|---------------|----------------|
| drupal-caching | "The block should invalidate when any of the displayed nodes are updated, and it should vary by the current route and by user" | Somewhat teaches cache tag/context thinking | Keep -- this describes the functional requirement, not implementation details. Without knowing `#cache` metadata, this is still vague. |
| drupal-routing-controllers | "Use proper DI patterns with a create() factory method" | Teaches the DI pattern name | Consider removing -- the assertion checks for DI regardless. However, this hint primarily affects standard patterns Sonnet already knows. Low impact. |
| drupal-forms-api | "Include a config schema file" | Directly instructs to create schema | Keep -- schema is a functional requirement. But note it reduces the differentiating power of the schema assertion. |
| drupal-plugins-blocks | "Include proper block configuration form and default configuration" | Teaches blockForm/blockSubmit pattern exists | Keep -- describes functional requirement. |

**Low-impact hints (keep as-is):**

| Skill | Hint | Why Keep |
|-------|------|----------|
| drupal-module-scaffold | "We're running Drupal 10 but want D11 compatibility" | Needed to test `^10 \|\| ^11` assertion |
| drupal-access-security | "Use AccessResult objects for access checking, not bare booleans" | Differentiating assertion tests for cache metadata on AccessResult, not whether AccessResult is used at all |
| drupal-theming | "Use render arrays with #theme, NOT raw HTML strings" | Standard Drupal pattern, tests non-obvious template naming convention |

### Assertion Stability Analysis

For each skill, assessment of whether assertions need adjustment for new D10 prompts:

| Skill | Assertions Stable? | Notes |
|-------|--------------------| ------|
| drupal-caching | YES | All assertions reference `related_content_block` module name and generic caching patterns |
| drupal-module-scaffold | YES | All assertions reference `event_analytics` module name |
| drupal-routing-controllers | YES | All assertions reference `api_status_endpoint` module name |
| drupal-forms-api | YES | All assertions reference `search_settings` module name |
| drupal-plugins-blocks | YES | All assertions reference `content_recommendations` module name |
| drupal-config-storage | YES | All assertions reference `site_announcements` module name |
| drupal-access-security | YES | All assertions reference `restricted_reports` module name |
| drupal-theming | YES | All assertions reference `featured_resources` module name |
| drupal-database-api | YES | All assertions reference `view_analytics` module name |
| drupal-views-dev | YES | All assertions reference `resource_directory` module name |
| drupal-batch-queue-cron | YES | All assertions reference `content_indexer` module name |
| drupal-entities-fields | MOSTLY | Assertions reference `knowledge_resource` module. If entity name changes, E2E assertions need update |
| drupal-testing | NEEDS REWRITE | All assertions reference `social_ai_indexing` module which won't exist. Must be fully rewritten. |

## Architecture Patterns

### Good Eval Prompt Structure

Based on analysis of which prompts produced meaningful deltas in Phase 6/7:

```
1. CONTEXT: Describe the site and business scenario (no platform-specific references)
2. TASK: What module to create, with functional requirements
3. CONSTRAINTS: Only constraints that define the REQUIREMENT, not the IMPLEMENTATION
4. COMPATIBILITY: D11 compat request (triggers non-obvious patterns)
```

**Pattern: Requirement-Focused, Not Implementation-Focused**

```
# BAD (leaks implementation):
"Create a caching block. Do NOT use max-age: 0. Add cache tags for each node."

# GOOD (requirement-focused):
"Create a caching block that properly invalidates when displayed data changes
and varies its output based on who is viewing it and which page they're on."
```

The key insight: prompts should describe WHAT the module should DO, not HOW it should be implemented. The "how" is what SKILL.md teaches, and the without-skill agent should have to figure it out from training data alone.

### Anti-Pattern: Assertion-Echoing Prompts

When a prompt instruction directly mirrors an assertion, the without-skill agent gets the answer for free:

```
Prompt: "The queue worker must have a cron.time setting"
Assertion: "QueueWorker annotation includes cron time setting"
Result: Even without SKILL.md, agent follows the prompt instruction -> 0% delta
```

### Testing Skill: Self-Contained Prompt Design

The testing eval requires special handling because it tests CODE QUALITY (writing tests), not MODULE BUILDING. Options:

1. **Option A: Two-step prompt** -- "Create a simple service module AND write a kernel test for it"
   - Pro: Self-contained, works on vanilla D10
   - Con: Conflates module building with test writing

2. **Option B: Test an existing core service** -- "Write a kernel test verifying the entity_type.manager service"
   - Pro: Tests pure test-writing skill
   - Con: Trivial scenario, may not exercise enough test patterns

3. **Option C: Build module first, test second (recommended)** -- "Create a module called `calculator` with a Calculator service class (add/subtract methods), then write a kernel test that verifies the service loads from the container and its methods work correctly"
   - Pro: Realistic, self-contained, exercises both service creation and test infrastructure
   - Con: Slightly more complex prompt

**Recommendation: Option C** -- It exercises the key differentiating patterns (KernelTestBase vs BrowserTestBase, $modules array, installSchema, @group annotation) while being fully self-contained for vanilla D10.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Prompt rewriting | Manual find-replace across 13 files | Systematic per-skill audit using the classification above | Need to check each prompt for hint leakage, not just text replace |
| Testing prompt | Patching the existing social_ai_indexing reference | Full redesign with self-contained scenario | The old prompt assumes a module that doesn't exist |
| Assertion validation | Assuming all assertions work with new prompts | Read each assertion against new prompt to verify coherence | Module name changes or scenario changes can break E2E assertions |

## Common Pitfalls

### Pitfall 1: Prompt Hints That Neutralize Assertions
**What goes wrong:** Prompt says "use X pattern" and assertion checks for X pattern -> 0% delta because both agents follow the instruction
**Why it happens:** Natural tendency to be helpful/specific in prompts
**How to avoid:** For each assertion, check if the prompt directly teaches the answer. If so, remove the hint from the prompt.
**Warning signs:** Assertion text mirrors prompt text almost verbatim

### Pitfall 2: Module Names Hardcoded in E2E Assertions
**What goes wrong:** New prompt changes module name but assertion still references old name
**Why it happens:** Assertions use `ddev drush en <module_name>` with hardcoded names
**How to avoid:** When changing module names, grep all assertions for that name
**Warning signs:** E2E assertions fail with "module not found" after prompt rewrite

### Pitfall 3: Testing Eval References Non-Existent Module
**What goes wrong:** Testing prompt references `social_ai_indexing` which doesn't exist on vanilla D10
**Why it happens:** v1.0 eval was designed for os-kg which has custom modules
**How to avoid:** Design self-contained prompts where the executor creates everything it needs
**Warning signs:** Prompt mentions specific module/service names that are Open Social-specific

### Pitfall 4: Over-Specifying Prompts Kills Delta
**What goes wrong:** Prompts become so detailed that any LLM can produce correct code without SKILL.md
**Why it happens:** Desire for specificity in eval requirements
**How to avoid:** Describe FUNCTIONAL requirements, not IMPLEMENTATION requirements
**Warning signs:** Both with-skill and without-skill pass 100% of assertions

### Pitfall 5: Under-Specifying Prompts Produces Untestable Code
**What goes wrong:** Prompt is so vague that the module produced doesn't exercise the patterns assertions check for
**Why it happens:** Overcorrecting for hint leakage
**How to avoid:** Ensure prompt naturally leads to code that could exhibit the patterns, just doesn't teach which patterns are correct
**Warning signs:** Both agents fail most assertions because they built something different from what assertions expect

## Code Examples

### evals.json Schema (for reference)

```json
{
  "skill_name": "drupal-example",
  "evals": [
    {
      "id": 1,
      "prompt": "Task description for the eval-executor agent...",
      "expected_output": "Brief description of expected result",
      "files": [],
      "expectations": [
        "Assertion 1 -- what the grader checks for",
        "Assertion 2 -- another check",
        "E2E: Runtime verification via ddev drush"
      ]
    }
  ]
}
```

### Example: Rewritten Prompt (drupal-caching)

Before:
```
I have a block plugin on our Open Social Drupal 10 site that displays nodes
related to the currently viewed page, filtered by the current user's group
membership. The block loads specific node entities and renders their titles as
links. Right now the block has no caching and it's hurting performance. Add
proper cache tags and contexts to the block's build() method. The block should
invalidate when any of the displayed nodes are updated, and it should vary by
the current route (so different pages show different related content) and by
user (so group filtering works per-user). Put this in a module called
related_content_block. Do NOT use max-age: 0.
```

After (removing Open Social ref AND max-age hint):
```
Create a block plugin module called `related_content_block` for a Drupal 10
site. The block should display a list of related content nodes, showing their
titles as links. It should load specific node entities based on the current
page and current user. The block is causing performance issues because it has
no caching configured. Add proper cache metadata to the block so it invalidates
when the displayed content changes, varies correctly based on which page it
appears on, and produces different output per user. We want D11 compatibility.
```

### Example: Rewritten Prompt (drupal-testing)

Before:
```
Write a kernel test for the social_ai_indexing module that verifies the
related_content service (social_ai_indexing.related_content, class
RelatedContentService) can be loaded from the container...
```

After (self-contained scenario):
```
Create a module called `calculator` that provides a simple Calculator service
class with add() and subtract() methods that accept two integers. Register the
service in services.yml. Then write a kernel test that verifies the calculator
service can be loaded from the Drupal service container and that its methods
return correct results. Use the correct test base class -- this is testing a
service, not a browser interaction. Put the test in the proper directory
following Drupal test conventions. We're running Drupal 10 and want D11
compatibility.
```

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Manual validation via eval pipeline |
| Config file | `eval/setup-fresh-drupal10.sh` (D10 provisioning) |
| Quick run command | Manual review of evals.json changes |
| Full suite command | Phase 10 pipeline validation (2-3 calibration skills) |

### Phase Requirements to Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| EVAL-01 | All 13 prompts reference "Drupal 10 site" not os-kg | code review + grep | `grep -r "Open Social\|os-kg\|os-knowledge" skills/*/evals/evals.json` (expect 0 matches) | N/A |
| EVAL-02 | Assertions target SKILL.md non-obvious patterns | code review | Manual: compare each assertion against SKILL.md content | N/A |

### Sampling Rate
- **Per task commit:** `grep -r "Open Social\|os-kg" skills/*/evals/evals.json` (verify zero matches)
- **Per wave merge:** Review all 13 evals.json for prompt-assertion coherence
- **Phase gate:** Full grep verification + spot-check 3-4 prompts for hint leakage

### Wave 0 Gaps
None -- this phase modifies existing evals.json files, no new test infrastructure needed.

## Open Questions

1. **Testing eval: should the module name change?**
   - What we know: Current prompt uses `social_ai_indexing` which is OS-specific. A new self-contained scenario needs a new module name.
   - What's unclear: Whether the new testing prompt should test a module the agent builds itself, or test against a core module.
   - Recommendation: Self-contained (Option C above) -- agent builds `calculator` module + writes kernel test for it.

2. **Entities-fields eval: keep KnowledgeResource entity name?**
   - What we know: The entity name `KnowledgeResource` is os-kg-flavored but functionally generic. Assertions reference `knowledge_resource` module name.
   - What's unclear: Whether the entity name affects eval quality.
   - Recommendation: Keep the entity name and module name. Only change the framing from "os-knowledge-garden knowledge curation" to a generic Drupal 10 scenario.

3. **How aggressive to be with hint removal?**
   - What we know: 4 prompts have high-impact hints that potentially neutralize assertions.
   - What's unclear: Whether removing hints will cause agents to produce code that doesn't exercise the patterns at all (Pitfall 5).
   - Recommendation: Remove high-impact hints, keep functional requirement descriptions. The prompt should make it NATURAL to build code where the patterns matter, without TEACHING the patterns.

## Sources

### Primary (HIGH confidence)
- All 13 `skills/drupal-*/evals/evals.json` files -- direct read of current prompt content
- All 13 `skills/drupal-*/SKILL.md` files -- direct read of skill patterns that assertions target
- `.claude/agents/eval-executor.md` -- executor workflow and skill loading mechanism
- `.claude/agents/eval-grader.md` -- grading schema and evidence standards
- `eval/setup-fresh-drupal10.sh` -- fresh D10 provisioning (no Open Social modules)

### Secondary (MEDIUM confidence)
- `.planning/phases/08-eval-optimization-loop/08-01-SUMMARY.md` -- Phase 8 decisions
- `.planning/phases/08-eval-optimization-loop/08-02-SUMMARY.md` -- Pipeline validation results
- MEMORY.md -- Phase 6/7 eval results showing which patterns differentiate

### Tertiary (LOW confidence)
- Sipos book -- referenced as source for "realistic module development tasks" but the book file is a PDF dump with base64 images, not searchable text. The SKILL.md files already incorporate Sipos book patterns, so the skills themselves serve as the authoritative source for Drupal patterns.

## Metadata

**Confidence breakdown:**
- Prompt audit: HIGH -- all 13 evals.json read in full, every reference identified
- Assertion stability: HIGH -- all assertions cross-referenced against prompt changes
- Hint leakage analysis: HIGH -- systematic comparison of prompt instructions vs assertion text
- Testing eval redesign: MEDIUM -- recommendation is sound but untested until Phase 10

**Research date:** 2026-03-06
**Valid until:** 2026-04-06 (stable -- evals.json format and D10 patterns don't change)

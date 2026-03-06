# Phase 7: Full Eval-Optimize Loop - Research

**Researched:** 2026-03-06
**Domain:** Eval infrastructure scaling, eval authoring for 9 Drupal skills, iterative skill optimization
**Confidence:** HIGH

## Summary

Phase 7 extends the proven phase 6 eval infrastructure to all 13 skills. Phase 6 established the complete pipeline: evals.json authoring, ddev environment setup/teardown, headless Sonnet 4.6 execution via `env -u CLAUDECODE claude -p --model sonnet --permission-mode bypassPermissions`, grading, benchmark aggregation, and HTML review generation. The infrastructure works end-to-end. Phase 7 needs three things: (1) create evals.json for 9 remaining skills, (2) bake the CLAUDECODE env var fix into the setup script and implement 1-agent-per-skill parallelization, and (3) run the full eval-optimize loop on all 13 skills.

The key insight from phase 6 is that **skills add the most value for patterns absent from training data** (caching +75%, scaffold +43%) and show diminishing returns for well-documented patterns that Sonnet already knows (entities 0%, testing 0% on corrected runs). For the 9 remaining skills, eval prompts must be designed to target each skill's unique "wrong-way" callouts -- the specific patterns where Claude's baseline behavior diverges from correct Drupal practice. Generic prompts will produce 0% delta.

**Primary recommendation:** Batch eval creation (9 evals.json files) as a single plan, then run all 13 skills through the pipeline with 1-agent-per-skill parallelization (max 2-3 concurrent ddev pairs to stay within ~8GB available RAM). Iterate on skills showing weak deltas by tightening assertions or enriching skill content, then re-eval until stabilized.

## Standard Stack

### Core
| Tool | Version | Purpose | Why Standard |
|------|---------|---------|--------------|
| ddev | v1.24.8 | Drupal environment management | Installed, proven in phase 6 with 4+ concurrent instances |
| claude -p --model sonnet | Sonnet 4.6 | Headless eval executor | Confirmed working; must use `env -u CLAUDECODE` prefix |
| aggregate_benchmark.py | N/A | Aggregates grading.json into benchmark.json | Standard skill-creator tool, proven in phase 6 |
| generate_review.py | N/A | HTML viewer for eval results | Standard skill-creator tool, proven in phase 6 |
| os-knowledge-garden | Drupal 10.6.3 | Eval environment base | Established; uses `--demo=cascadia` install |

### Supporting
| Tool | Purpose | When to Use |
|------|---------|-------------|
| eval/setup-drupal-env.sh | Shared env setup (clone + ddev start + install) | Before each eval run |
| eval/teardown-drupal-env.sh | Shared env teardown (ddev delete + rm) | After each eval run |
| flock | Serialize ddev starts | Already in setup script via `flock -x 200` |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Sonnet 4.6 executor | Opus 4.6 executor | Opus shows 0% delta (too capable); Sonnet is the right target for measuring skill impact |
| Sequential skill batches | Full parallel (13 at once) | RAM constraint: 15GB total, ~7.6GB available; each ddev pair needs ~1GB; max 3-4 pairs concurrent |
| Grader subagents | Direct grading by executor | Phase 6 proved direct grading is faster and equally accurate |

## Architecture Patterns

### Workspace Directory Structure (Established)
```
{skill}-workspace/
└── iteration-1/
    └── eval-{name}/
        ├── eval_metadata.json
        ├── with_skill/
        │   └── run-1/
        │       ├── outputs/
        │       ├── transcript.md
        │       └── grading.json
        └── without_skill/
            └── run-1/
                ├── outputs/
                ├── transcript.md
                └── grading.json
```

### evals.json Format (Established)
```json
{
  "skill_name": "drupal-{name}",
  "evals": [
    {
      "id": 1,
      "prompt": "...",
      "expected_output": "...",
      "files": [],
      "expectations": ["assertion 1", "assertion 2", "..."]
    }
  ]
}
```

### Pattern: Headless Eval Execution (Established in 06-05)
```bash
env -u CLAUDECODE claude -p --model sonnet --permission-mode bypassPermissions \
  "[eval prompt with instructions]" \
  > /tmp/{skill}-{config}-output.log 2>&1
```

### Pattern: 1-Agent-Per-Skill Parallelization
Rather than batching multiple skills into a single orchestrator agent, each skill's eval pair (with + without) runs as an independent unit. The orchestrator spawns them in batches limited by available RAM:

- **Batch size:** 2-3 skills at a time (4-6 ddev instances, ~2-3GB)
- **Per-skill flow:** setup 2 envs -> run 2 headless claude -> copy outputs -> teardown 2 envs -> grade
- **Between batches:** teardown previous batch's ddev instances before starting next

### Pattern: Discriminating Assertion Design
Phase 6 data shows assertion effectiveness varies dramatically:

| Assertion Type | Example | Delta Impact |
|---------------|---------|-------------|
| Training-data-absent pattern | "#cache key present with tags and contexts" | HIGH (+75%) |
| Counter-intuitive requirement | "core_version_requirement includes ^11" | HIGH (+43%) |
| Well-documented pattern | "extends KernelTestBase" | LOW (0% on corrected runs) |
| Structural check | "module enables successfully" | NONE (passes both ways) |

**For new evals, prioritize assertions targeting each skill's wrong-way callouts** -- the specific patterns where Claude's baseline produces incorrect code.

### Anti-Patterns to Avoid
- **Generic eval prompts:** "Create a form" will produce nearly identical results with and without skill. Must target specific skill differentiators (e.g., "extend ConfigFormBase with getEditableConfigName" vs baseline's FormBase).
- **Too many non-discriminating assertions:** Structural assertions like "file exists" pass both ways. Include 1-2 for validation but focus on discriminating assertions.
- **Running all 13 skills simultaneously:** 26 ddev instances would consume ~13GB. Batch in groups of 2-3.
- **Using Opus for eval:** Opus 4.6 shows 0% delta across all skills tested. Use Sonnet 4.6 to measure skill impact.
- **Skipping teardown between batches:** Stale ddev instances accumulate; always teardown before starting next batch.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Benchmark aggregation | Custom statistics | aggregate_benchmark.py | Handles multi-config comparison, delta calculation |
| HTML eval viewer | Custom HTML/JS | generate_review.py --static | Self-contained HTML with embedded data |
| Drupal env setup | Inline bash | eval/setup-drupal-env.sh | Tested, handles stale cleanup, flock serialization |
| Grading methodology | Ad-hoc checking | Established grading.json format with evidence | Consistent across all runs |
| Eval prompt design | Inventing from scratch | eval/eval-prompts.md templates | 13 single-skill + 6 multi-skill prompts already defined |

**Key insight:** The eval infrastructure is complete from phase 6. Phase 7 is primarily content authoring (9 evals.json files) and execution (running the pipeline). No new tooling needs to be built.

## Common Pitfalls

### Pitfall 1: Eval Prompts That Don't Differentiate
**What goes wrong:** Both with-skill and without-skill produce identical passing code, yielding 0% delta.
**Why it happens:** The eval prompt asks for well-documented patterns that Sonnet already knows from training data (e.g., "create a content entity" -- Sonnet knows entity patterns well).
**How to avoid:** Design prompts that specifically target the skill's wrong-way callouts. For drupal-forms-api: require ConfigFormBase with getEditableConfigName() and config schema, which the baseline often misses. For drupal-plugins-blocks: require ContainerFactoryPluginInterface with the 4-param create() and parent::__construct() with 3 plugin params.
**Warning signs:** Both configurations score 100% on all assertions.

### Pitfall 2: Entity Type / Module Name Collisions with Open Social
**What goes wrong:** Eval code collides with existing Open Social modules, confusing the baseline model.
**Why it happens:** os-knowledge-garden includes Open Social which has many entity types and modules. Using names like `event_enrollment` collides with `social_event`.
**How to avoid:** Use unique module names that don't overlap with Open Social's namespace. Prefixes like `eval_*` or domain-specific names unrelated to social networking are safe.
**Warning signs:** Module enable fails, or baseline produces different code than expected due to existing similar entities.

### Pitfall 3: CLAUDECODE Environment Variable Blocking Nested Sessions
**What goes wrong:** `claude -p` refuses to launch from within a Claude Code agent.
**Why it happens:** Claude Code sets CLAUDECODE env var to prevent recursive sessions.
**How to avoid:** Always prefix headless claude with `env -u CLAUDECODE`. This should be baked into the eval runner pattern, not left to each plan.
**Warning signs:** "Cannot run Claude Code inside an existing session" error.

### Pitfall 4: Memory Exhaustion from Too Many Concurrent ddev Instances
**What goes wrong:** System becomes unresponsive; OOM killer terminates processes.
**Why it happens:** Each ddev instance uses ~512MB. With 15GB total and ~7.6GB available, running more than 6-8 instances risks exhaustion.
**How to avoid:** Limit to 2-3 skill pairs (4-6 instances) per batch. Always teardown before starting next batch.
**Warning signs:** System slowdown, swap usage above 50%.

### Pitfall 5: Eval Prompts Referencing Non-Existent Modules
**What goes wrong:** Code verification fails because the eval references a module/service not present in os-knowledge-garden.
**Why it happens:** Some eval prompts ask to extend or test existing modules (e.g., social_ai_indexing services). If the Sonnet agent can't find the module, it may produce different code.
**How to avoid:** For prompts referencing existing services, ensure the service/module actually exists in os-knowledge-garden. For new modules, use self-contained prompts that don't depend on existing code.
**Warning signs:** ddev drush en fails, service loading fails.

### Pitfall 6: Ignoring Iteration After Initial Eval Run
**What goes wrong:** Skills with 0% delta are accepted as-is without investigating why.
**Why it happens:** Time pressure; accepting first results.
**How to avoid:** For each skill with <10% delta: (1) check if assertions are too generic, (2) tighten assertions to target skill-specific patterns, (3) review skill content for teachable patterns that training data lacks, (4) re-run. Aim for at least +10% delta on most skills.
**Warning signs:** Many skills showing 0% delta without investigation.

## Code Examples

### Eval Runner Wrapper Script Pattern
```bash
#!/usr/bin/env bash
# eval-run.sh: Run a single skill eval (with + without)
set -euo pipefail

SKILL_NAME="${1:?Usage: eval-run.sh <skill-name> <eval-name>}"
EVAL_NAME="${2:?Usage: eval-run.sh <skill-name> <eval-name>}"
PROJECT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
WORKSPACE="${PROJECT_DIR}/${SKILL_NAME}-workspace/iteration-1/eval-${EVAL_NAME}"

# Setup 2 environments
bash "${PROJECT_DIR}/eval/setup-drupal-env.sh" "${SKILL_NAME}-with"
bash "${PROJECT_DIR}/eval/setup-drupal-env.sh" "${SKILL_NAME}-without"

# Run with-skill (headless Sonnet)
env -u CLAUDECODE claude -p --model sonnet --permission-mode bypassPermissions \
  "[prompt with skill reading instructions]" > /tmp/${SKILL_NAME}-with.log 2>&1

# Run without-skill (headless Sonnet)
env -u CLAUDECODE claude -p --model sonnet --permission-mode bypassPermissions \
  "[prompt without skill]" > /tmp/${SKILL_NAME}-without.log 2>&1

# Teardown
bash "${PROJECT_DIR}/eval/teardown-drupal-env.sh" "${SKILL_NAME}-with"
bash "${PROJECT_DIR}/eval/teardown-drupal-env.sh" "${SKILL_NAME}-without"
```

### Discriminating Assertion Design for Each Remaining Skill

**drupal-routing-controllers** -- Target wrong-way callouts:
```json
{
  "expectations": [
    "Controller extends ControllerBase (not just implementing ContainerInjectionInterface)",
    "Controller has static create() method accepting ContainerInterface",
    "Constructor uses typed service parameters with DI (not \\Drupal::service())",
    "Route YAML uses _controller key with fully qualified class name",
    "Route has _permission or _access requirement (not unprotected)",
    "Controller returns JsonResponse for API endpoint (not render array or plain string)",
    "The module enables successfully: ddev drush en {module} returns exit code 0"
  ]
}
```

**drupal-forms-api** -- Target ConfigFormBase pattern:
```json
{
  "expectations": [
    "Form class extends ConfigFormBase (not FormBase) for settings forms",
    "Form class implements getEditableConfigName() returning config name string",
    "submitForm() uses $this->config() to save settings (not \\Drupal::configFactory())",
    "Route YAML uses _form key (not _controller) for the settings route",
    "Config schema YAML file exists with typed entries matching form fields",
    "Form includes proper validation in validateForm()",
    "The module enables successfully: ddev drush en {module} returns exit code 0"
  ]
}
```

**drupal-plugins-blocks** -- Target plugin DI pattern:
```json
{
  "expectations": [
    "Block class implements ContainerFactoryPluginInterface",
    "create() method has 4 parameters: container, configuration, plugin_id, plugin_definition",
    "Constructor calls parent::__construct($configuration, $plugin_id, $plugin_definition)",
    "Block uses blockForm() and blockSubmit() for config (not generic form methods)",
    "defaultConfiguration() returns array merged with parent::defaultConfiguration()",
    "build() returns render array with #cache metadata",
    "The module enables successfully: ddev drush en {module} returns exit code 0"
  ]
}
```

**drupal-config-storage** -- Target schema + config/install:
```json
{
  "expectations": [
    "config/install/ directory contains default YAML with langcode and correct keys",
    "config/schema/{module}.schema.yml exists with typed entries",
    "Schema types match config values (string, integer, boolean, mapping, sequence)",
    "Config YAML keys match what the code reads via $config->get()",
    "Does NOT use variable_get/variable_set or State API for persistent settings",
    "The module enables successfully: ddev drush en {module} returns exit code 0"
  ]
}
```

**drupal-access-security** -- Target permissions.yml + AccessResult:
```json
{
  "expectations": [
    "Permissions defined in {module}.permissions.yml with title and description",
    "Route uses _permission requirement matching a defined permission",
    "Access checks use AccessResult::allowedIfHasPermission() or similar (not bare boolean)",
    "Entity access uses $entity->access('view', $account) pattern",
    "No manual CSRF token validation (uses _csrf_token route option if needed)",
    "The module enables successfully: ddev drush en {module} returns exit code 0"
  ]
}
```

**drupal-theming** -- Target hook_theme + template naming + libraries:
```json
{
  "expectations": [
    "hook_theme() returns array with 'variables' key listing all template variables",
    "Template filename uses hyphens (not underscores): e.g., module-name-thing.html.twig",
    "Render array uses #theme key referencing the hook_theme entry",
    "CSS/JS attached via #attached.library referencing {module}/{library-name}",
    "{module}.libraries.yml defines the library with CSS/JS paths and dependencies",
    "Does NOT build raw HTML strings (uses render arrays instead)",
    "The module enables successfully: ddev drush en {module} returns exit code 0"
  ]
}
```

**drupal-database-api** -- Target hook_schema + query abstraction:
```json
{
  "expectations": [
    "hook_schema() defines table with correct column types, keys, and indexes",
    "Insert uses \\Drupal::database()->insert()->fields() (not raw SQL)",
    "Select query uses database abstraction: ->select(), ->fields(), ->condition()",
    "Aggregation uses ->addExpression() with COUNT/SUM (not raw SQL)",
    "Does NOT use Entity API for tracking/analytics data (explicit skill guidance)",
    "The module enables successfully: ddev drush en {module} returns exit code 0"
  ]
}
```

**drupal-views-dev** -- Target Views plugin attributes + group key:
```json
{
  "expectations": [
    "Custom filter plugin placed in correct PSR-4 path: src/Plugin/views/filter/",
    "Filter extends InOperator or FilterPluginBase with proper annotation/attribute",
    "hook_views_data() or entity annotation includes 'group' key for Views UI",
    "Views data definition includes correct table/field mapping",
    "D11 attribute class used (or D10 annotation with @ViewsFilter)",
    "The module enables successfully: ddev drush en {module} returns exit code 0"
  ]
}
```

**drupal-batch-queue-cron** -- Target QueueWorker + cron.time:
```json
{
  "expectations": [
    "hook_cron() adds items to queue (does NOT process them directly)",
    "QueueWorker plugin has annotation/attribute with cron.time setting",
    "processItem() handles the actual processing logic",
    "Queue worker uses try/catch for error handling",
    "Does NOT use \\Drupal::queue() static call (uses DI or hook_cron context)",
    "The module enables successfully: ddev drush en {module} returns exit code 0"
  ]
}
```

## State of the Art

| Aspect | Phase 6 State | Phase 7 Need | Gap |
|--------|--------------|-------------|-----|
| evals.json files | 4 skills have evals | All 13 need evals | 9 new evals.json files |
| CLAUDECODE fix | Runtime `env -u` workaround | Baked into eval runner | Minor script update |
| Parallelization | 2-skill batches via orchestrator | 1-agent-per-skill, automated batching | Execution pattern change |
| Eval prompts | Defined in eval-prompts.md for all 13 | Need conversion to evals.json format with assertions | Content authoring |
| Iteration loop | None (single pass) | Run -> analyze -> tighten -> re-run | New process |
| Grading | Direct grading by executor (works well) | Same approach | No gap |
| Benchmark tooling | aggregate_benchmark.py + generate_review.py | Same tools | No gap |

### Skills Likely to Show High Delta (Based on Wrong-Way Callout Analysis)

| Skill | Key Differentiator | Expected Delta | Confidence |
|-------|-------------------|----------------|------------|
| drupal-forms-api | ConfigFormBase vs FormBase, getEditableConfigName() | MEDIUM-HIGH | MEDIUM |
| drupal-plugins-blocks | 4-param create(), parent::__construct(3 params) | MEDIUM-HIGH | MEDIUM |
| drupal-config-storage | Config schema YAML, config/install structure | MEDIUM | MEDIUM |
| drupal-theming | Template naming (hyphens), #attached libraries | MEDIUM | LOW |
| drupal-access-security | AccessResult objects vs bare booleans, .permissions.yml format | MEDIUM | LOW |
| drupal-routing-controllers | create() factory, typed DI, JsonResponse | LOW-MEDIUM | MEDIUM |
| drupal-database-api | hook_schema(), query abstraction vs Entity API | MEDIUM | LOW |
| drupal-views-dev | Views plugin PSR-4 path, group key, D11 attributes | MEDIUM | LOW |
| drupal-batch-queue-cron | QueueWorker cron.time, hook_cron queueing (not processing) | MEDIUM | LOW |

**LOW confidence on delta predictions** -- phase 6 showed that predicted deltas don't always match empirical results. The only way to know is to run the evals.

## Open Questions

1. **Should eval prompts for remaining 9 skills ask agents to create NEW modules or modify existing os-knowledge-garden modules?**
   - What we know: Phase 6 used new module creation (event_analytics, knowledge_resource, related_content_block). This avoids collision with existing modules.
   - What's unclear: Whether some skills (routing, theming, access-security) would be better evaluated by adding to an existing module (social_ai_indexing).
   - Recommendation: Use new module creation for most skills. For skills that modify existing code (access-security adding permissions to an existing module), use self-contained prompts that create a new module with the pattern.

2. **How many iteration cycles to plan for?**
   - What we know: Phase 6 did 1 pass + 1 correction run. Success criterion says "until deltas stabilize."
   - What's unclear: How many skills will need iteration (tightened assertions or skill content changes).
   - Recommendation: Plan for 2 cycles: initial run + 1 optimization pass. If more than 4 skills still show weak deltas after cycle 2, add a third cycle.

3. **Should the eval runner be a reusable script or inline plan instructions?**
   - What we know: Phase 6 used inline plan instructions. Success criterion 3 says "eval runner parallelizes with 1 agent per skill."
   - What's unclear: Whether a formal eval runner script is worth building vs. keeping inline instructions.
   - Recommendation: Keep inline instructions in plans. A formal runner script adds complexity without clear benefit given the small number of runs. The key change is the 1-agent-per-skill pattern, which is an execution strategy not a tool.

4. **What module names to use for remaining 9 evals?**
   - What we know: Must avoid collision with Open Social modules. Phase 6 lessons: event_enrollment collided, knowledge_resource worked.
   - Recommendation: Use these names (all novel, no os-knowledge-garden/Open Social collision):
     - routing: `api_status_endpoint`
     - forms: `search_settings`
     - plugins-blocks: `content_recommendations`
     - config-storage: `site_announcements`
     - access-security: `restricted_reports`
     - theming: `featured_resources`
     - database-api: `view_analytics`
     - views-dev: `resource_directory` (expose knowledge_resource-like data to Views)
     - batch-queue-cron: `content_indexer`

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Manual verification + skill-creator grading pipeline |
| Config file | N/A (grading is evidence-based, aggregation is Python script) |
| Quick run command | `python aggregate_benchmark.py {workspace}/iteration-1 --skill-name {name}` |
| Full suite command | Run all 13 skills through eval pipeline + aggregate all benchmarks |

### Phase Requirements to Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| SC-1 | All 13 skills have evals.json | file check | `for s in skills/drupal-*/evals/evals.json; do python3 -m json.tool "$s" > /dev/null && echo "OK: $s"; done` | 4/13 exist |
| SC-2 | CLAUDECODE fix baked in | grep | `grep -q 'CLAUDECODE' eval/setup-drupal-env.sh` | Not yet |
| SC-3 | 1-agent-per-skill parallelization | manual | Review plan structure | N/A |
| SC-4 | All 13 benchmarks exist | file check | `ls drupal-*-workspace/iteration-1/benchmark.json \| wc -l` | 4/13 exist |
| SC-5 | Weak-delta skills iterated | manual | Review analysis for iteration notes | N/A |
| SC-6 | Final analysis covers all 13 | file check | `grep -c 'drupal-' eval/analysis-iteration-*.md` | Partial |

### Sampling Rate
- **Per evals.json creation:** Validate JSON with `python3 -m json.tool`
- **Per eval run:** Verify transcript.md and outputs/ directory exist
- **Per grading:** Validate grading.json has all expectations with evidence
- **Per batch:** Run aggregate_benchmark.py, check delta values
- **Phase gate:** All 13 skills have benchmark.json with analyzed deltas

### Wave 0 Gaps
- [ ] `skills/drupal-routing-controllers/evals/evals.json` -- needs creation
- [ ] `skills/drupal-forms-api/evals/evals.json` -- needs creation
- [ ] `skills/drupal-plugins-blocks/evals/evals.json` -- needs creation
- [ ] `skills/drupal-config-storage/evals/evals.json` -- needs creation
- [ ] `skills/drupal-access-security/evals/evals.json` -- needs creation
- [ ] `skills/drupal-theming/evals/evals.json` -- needs creation
- [ ] `skills/drupal-database-api/evals/evals.json` -- needs creation
- [ ] `skills/drupal-views-dev/evals/evals.json` -- needs creation
- [ ] `skills/drupal-batch-queue-cron/evals/evals.json` -- needs creation

## Eval Prompt Design Guide (for 9 Remaining Skills)

### Principles from Phase 6 Data

1. **Target training-data-absent patterns:** The caching skill's +75% delta comes from teaching the `#cache` golden rule, which Sonnet consistently omits. Each eval prompt should test patterns that Claude's training data likely lacks or where Claude consistently makes wrong choices.

2. **Avoid well-documented patterns:** Entity creation, basic routing, and test class selection are well-represented in Drupal documentation. Prompts testing only these will show 0% delta.

3. **Include 1-2 runtime verification assertions:** "Module enables successfully" catches code that compiles but doesn't work. But don't over-rely on these -- they pass both ways.

4. **Include explicit wrong-way trigger words:** If the skill corrects `FormBase -> ConfigFormBase`, the prompt should say "settings form" to trigger the baseline's tendency to use FormBase.

5. **Grounded in os-knowledge-garden:** Prompts should reference realistic scenarios from the os-knowledge-garden project. Available context: social_ai_indexing services (RelatedContentService, HybridSearchService, AiOverviewService, PermissionFilterService), localnodes_platform config, and the AI/search domain.

### Per-Skill Prompt Strategy

| Skill | Prompt Focus | Key Wrong-Way Trap |
|-------|-------------|-------------------|
| routing-controllers | JSON API endpoint with DI service injection | Static `\Drupal::service()` instead of create() factory |
| forms-api | Settings form saving to config with schema | FormBase instead of ConfigFormBase, missing schema |
| plugins-blocks | Block with config and service injection | Controller-style DI (missing plugin parent params) |
| config-storage | Config install YAML with matching schema | Missing schema, wrong YAML structure |
| access-security | Custom permission + route protection + entity access | hook_permission() (D7), bare boolean access |
| theming | Template with library attachment | Raw HTML strings, wrong template naming, inline CSS |
| database-api | Custom tracking table with aggregation query | Entity API for analytics, raw SQL strings |
| views-dev | Custom Views filter plugin for entity data | hook_views_data() for entity (should use annotation), missing group key |
| batch-queue-cron | Cron job with queue worker processing | Processing directly in hook_cron, missing cron.time |

## Sources

### Primary (HIGH confidence)
- Phase 6 execution data: 06-01 through 06-05 PLAN/SUMMARY files, all verified empirically
- eval/analysis-iteration-1.md: Cross-skill analysis with per-skill deltas and methodology notes
- Existing evals.json files: 4 skills (scaffold, entities, caching, testing) -- format and assertion patterns verified
- eval/eval-prompts.md: Pre-defined prompts for all 13 single-skill + 6 multi-skill evals
- skill-creator scripts: aggregate_benchmark.py, generate_review.py -- paths and CLI args verified
- eval/setup-drupal-env.sh and teardown-drupal-env.sh: Working scripts, tested in phase 6
- os-knowledge-garden module structure: social_ai_indexing services, localnodes_platform config -- verified via filesystem

### Secondary (MEDIUM confidence)
- System RAM: 15GB total, ~7.6GB available (measured at research time; varies with load)
- ddev v1.24.8 concurrent instance limit: 4+ tested successfully in phase 6
- Assertion effectiveness predictions: Based on phase 6 data patterns, but individual skill results may vary

### Tertiary (LOW confidence)
- Per-skill delta predictions: Phase 6 showed predicted deltas often don't match empirical results. The ranking above is educated guessing, not evidence.

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - all tools proven in phase 6, no new tooling needed
- Architecture: HIGH - workspace structure, eval runner pattern, grading pipeline all established
- Pitfalls: HIGH - all pitfalls observed empirically in phase 6
- Eval prompt design: MEDIUM - principles derived from phase 6 data, but per-skill effectiveness is untested
- Delta predictions: LOW - must be validated empirically

**Research date:** 2026-03-06
**Valid until:** 2026-04-06 (stable infrastructure; eval prompts are project-specific)

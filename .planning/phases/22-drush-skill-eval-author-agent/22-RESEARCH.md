# Phase 22: Drush Skill + Eval-Author Agent - Research

**Researched:** 2026-03-09 (revised)
**Domain:** Drush 13 usage for development, testing, debugging, and scaffolding; Claude Code subagent design for eval assertion generation
**Confidence:** HIGH

## Summary

Phase 22 has two distinct deliverables: (1) a 15th skill file teaching agents how to USE Drush commands for development, self-verification, debugging, and scaffolding, and (2) an Opus-class subagent that automates the design of three-tier eval assertions.

The Drush skill focuses on USAGE, not command authoring. The key insight: our existing runtime assertions use `drush php-eval` for everything — even when built-in Drush commands do the job better. For example, we write 5-line php-eval calls to check routes when `drush route --name=my_module.route` does it in one. We never check `watchdog:show` after operations to catch silent errors. And `drush generate` could save massive tokens on scaffolding boilerplate.

The skill teaches three core capabilities:
1. **Self-verification** — how agents check their own work using Drush (routes, services, entities, permissions, logs)
2. **Scaffolding** — `drush generate` for module/controller/form/entity/plugin boilerplate (saves tokens, produces phpcs-compliant code)
3. **The Drupal-first principle** — use `entity:save/create/delete` over `sql:query`, use `php:eval` for API testing when no built-in command exists, use `sql:query` ONLY as last resort

Command authoring (creating custom Drush commands) is preserved as a reference file at `references/command-authoring.md`.

The eval-author agent is unchanged from the original plan — an Opus-class subagent automating three-tier assertion design.

**Primary recommendation:** Build the Drush usage skill first (plan 22-01), then the eval-author agent (plan 22-02).

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| TOOL-01 | Drush skill teaches Drush usage for development: self-verification recipes (route/service/entity/permission inspection), scaffolding via `drush generate`, debugging via `watchdog:show`, and the Drupal-first principle (entity:save over sql:query) | Drush 13 command inventory verified: entity:create/save/delete, core:route, watchdog:show/tail, php:eval/script, generate (50+ generators), queue:list/run, state:get/set, config:get, role:perm:add, cache:tags. Current eval pipeline uses php-eval for everything — built-in commands would be simpler, more robust, and more readable. |
| TOOL-02 | Drush skill includes eval assertions testing that agents use correct Drush patterns (built-in commands over raw php-eval, watchdog checks, entity API over SQL, drush generate for scaffolding) | Current runtime assertions (v3/v4) demonstrate the anti-patterns: complex php-eval for route checking (should be `drush route`), manual entity creation via php-eval (should be `entity:create`), no watchdog monitoring. Assertions can test whether skill-guided agents use better patterns. |
| TOOL-03 | Eval-author Opus subagent designs three-tier assertions (static + runtime + browser) from skill content, module code, and phase prompt | Subagent frontmatter pattern verified from eval-grader.md and eval-browser.md. Agent needs Read/Glob/Grep/Bash tools, model: opus. |
| TOOL-04 | Eval-author enforces assertion category distribution (60% differentiating, 20% wiring, max 20% structural) with explicit tautology rejection | Distribution rationale grounded in empirical data: Phase 18 gold-standard (17 assertions, 100% differentiating, +23.3% delta). |
</phase_requirements>

## Standard Stack

### Core

No new dependencies. Both deliverables are pure knowledge artifacts.

| Artifact | Type | Purpose | Pattern Source |
|----------|------|---------|---------------|
| `skills/drupal-drush/SKILL.md` | Skill file | Teaches Drush usage for development, testing, debugging | Follows existing 14 skills |
| `skills/drupal-drush/evals/evals.json` | Eval assertions | Measures skill impact on Drush usage quality | Follows `skills/drupal-module-scaffold/evals/evals.json` |
| `skills/drupal-drush/references/command-authoring.md` | Reference file | Drush 13+ custom command creation patterns | Preserved from original research |
| `.claude/agents/eval-author.md` | Subagent definition | Automates three-tier assertion design | Follows `.claude/agents/eval-grader.md` |

### Key Drush Commands for the Skill

**Self-verification (checking your own work):**

| Command | What It Checks | Replaces |
|---------|---------------|----------|
| `drush route --name=my_module.*` | Route registration | 5-line php-eval with router.route_provider |
| `drush route --path=/admin/my-page` | Path-to-route mapping | Manual path testing |
| `drush pm:list --status=enabled --field=name` | Module enabled | `pm:list \| grep` |
| `drush watchdog:show --severity-min=Warning --count=5` | Recent errors/warnings | Nothing (currently unchecked!) |
| `drush watchdog:show --type=php` | PHP errors after operations | Nothing |
| `drush config:get my_module.settings` | Config values | php-eval with \Drupal::config() |
| `drush state:get my_module.last_run` | State values | php-eval with \Drupal::state() |
| `drush role:list --format=json` | Roles and permissions | php-eval with user.permissions service |
| `drush queue:list` | Queue status | php-eval with queue service |
| `drush cache:tags node:12,user:4` | Cache tag invalidation | No built-in equivalent |
| `drush php-eval "..."` | Arbitrary PHP (DI, class resolution) | Still needed for complex checks |
| `drush php:script path/to/test.php` | Multi-step test scripts | Complex shell-escaped php-eval |

**Entity operations (Drupal-first principle):**

| Command | Purpose | Why Not sql:query |
|---------|---------|------------------|
| `drush entity:create node article` | Create test entities interactively | Bypasses entity API, skips hooks |
| `drush entity:save node --bundle=article` | Re-save entities (triggers hooks) | Doesn't fire hook_entity_presave/update |
| `drush entity:delete node 22,24` | Delete test entities | Leaves orphaned references |
| `drush php-eval "$entity->save()"` | Programmatic entity save | Same as sql:query — no hooks |

**Scaffolding (drush generate):**

| Generator | What It Creates | Token Savings |
|-----------|----------------|---------------|
| `drush generate module` | .info.yml, .module, composer.json | ~200 tokens |
| `drush generate controller` | Controller class + routing.yml entry | ~300 tokens |
| `drush generate form:config` | Config form + routing + schema | ~500 tokens |
| `drush generate form:simple` | Simple form + routing | ~400 tokens |
| `drush generate entity:content` | Full content entity (class, schema, handlers, forms) | ~2000+ tokens |
| `drush generate entity:configuration` | Full config entity | ~1500+ tokens |
| `drush generate plugin:block` | Block plugin class | ~200 tokens |
| `drush generate service-provider` | Service provider class | ~150 tokens |
| `drush generate event-subscriber` | Event subscriber + services.yml entry | ~250 tokens |
| `drush generate hook` | Hook implementation | ~100 tokens |
| `drush generate test:kernel` | Kernel test class | ~200 tokens |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Built-in Drush commands for verification | php-eval for everything | php-eval is more flexible but harder to read, debug, and maintain. Built-in commands have proper error handling. |
| entity:save for hook-triggering saves | sql:query for direct DB updates | sql:query bypasses entity API — hooks, cache invalidation, access checks all skipped. |
| drush generate for scaffolding | Writing all boilerplate manually | Manual writing burns tokens and introduces phpcs errors. drush generate produces convention-compliant code. |
| Opus for eval-author | Sonnet for eval-author | Sonnet generates shallow assertions. Eval design requires deep reasoning — Opus-tier work. |

## Architecture Patterns

### Drush Skill File Structure

```
skills/drupal-drush/
  SKILL.md                          # Main: USING Drush for development
  evals/
    evals.json                      # Assertions testing correct Drush usage
  references/
    command-authoring.md            # Reference: creating custom Drush commands
```

### Skill Content Structure (Recommended Sections)

```markdown
# Drupal Drush for Development

## drush generate — scaffold boilerplate, save tokens
  - Available generators (module, controller, form, entity, plugin, etc.)
  - Using --answer for non-interactive generation
  - Using --dry-run to preview
  - CRITICAL: drush generate produces phpcs-compliant code
  - Recipe: scaffold a complete module skeleton in one command

## Self-verification recipes — check your own work
  - After creating routes: drush route --name=my_module.*
  - After module changes: drush watchdog:show --severity-min=Warning
  - Service resolution: drush php-eval with classResolver
  - Entity CRUD verification: entity:create/save/delete
  - Permission checks: role:list, role:perm:add
  - Config/state inspection: config:get, state:get
  - Queue verification: queue:list, queue:run

## The Drupal-first principle — entity API over SQL
  - WRONG/RIGHT: sql:query vs entity:save (hooks, cache, access)
  - entity:create for test data (fires hooks)
  - entity:save for re-saving (triggers presave/update)
  - entity:delete for cleanup (fires delete hooks)
  - php-eval for programmatic API testing
  - sql:query ONLY for raw DB inspection with no API equivalent

## Debugging with Drush
  - watchdog:show --type=php for PHP errors
  - watchdog:show --severity-min=Warning for all warnings+
  - watchdog:tail for live monitoring during development
  - core:status for environment verification
  - core:route for route debugging
  - config:get for config inspection
  - state:get for state inspection

## php:eval vs php:script — when to use each
  - php:eval for one-liners (service resolution, class checks)
  - php:script for complex multi-step tests (avoid shell escaping)
  - Shell escaping tips for php:eval
  - WRONG/RIGHT: massive shell-escaped php-eval vs php:script file

## Cross-references
  - drupal-module-scaffold: drush generate complements manual scaffolding
  - drupal-batch-queue-cron: queue:list/queue:run for testing queues
  - drupal-testing: drush test:run for running PHPUnit tests
```

### Self-Verification Recipes (Key Differentiators)

These are the patterns agents should use to check their own work. Current agents DON'T do this — the skill teaches it.

**Recipe 1: After creating routes**
```bash
# WRONG: Complex php-eval to check routes
drush php-eval "$provider = \Drupal::service('router.route_provider'); ..."

# RIGHT: Built-in command
drush route --name=my_module.api_endpoint
# or check by path
drush route --path=/api/my-module/tasks
```

**Recipe 2: After any module change — check for errors**
```bash
drush cr  # rebuild cache
drush watchdog:show --severity-min=Warning --count=5  # check for new warnings
```

**Recipe 3: Entity CRUD verification using Drupal API**
```bash
# Create test entity via Drupal API (fires hooks)
drush entity:create node article

# Or programmatically with php-eval (for custom entities)
drush php-eval "\$e = \Drupal::entityTypeManager()->getStorage('task')->create(['title' => 'Test']); \$e->save(); echo \$e->id();"

# Verify hooks fired by checking watchdog
drush watchdog:show --count=3

# Clean up
drush entity:delete node 42
```

**Recipe 4: Service/DI verification**
```bash
# Check service exists and resolves
drush php-eval "echo \Drupal::hasService('my_module.my_service') ? 'EXISTS' : 'MISSING';"

# Check class is autoloadable
drush php-eval "echo class_exists('\Drupal\my_module\MyService') ? 'LOADABLE' : 'NOT_FOUND';"

# Full DI resolution test
drush php-eval "\$svc = \Drupal::service('my_module.my_service'); echo get_class(\$svc);"
```

**Recipe 5: Permission verification**
```bash
drush role:perm:add anonymous 'view project entities'
drush php-eval "echo \Drupal::currentUser()->hasPermission('view project entities') ? 'HAS' : 'MISSING';"
```

**Recipe 6: Config and state inspection**
```bash
drush config:get my_module.settings  # full config object
drush config:get my_module.settings api_key  # specific key
drush state:get my_module.last_cron_run  # state value
```

**Recipe 7: Queue testing**
```bash
drush queue:list  # see all queues and item counts
drush queue:run my_module_queue  # process items
drush watchdog:show --type=my_module --count=5  # check results
```

**Recipe 8: Complex multi-step testing with php:script**
```php
// test-entity-workflow.php
$storage = \Drupal::entityTypeManager()->getStorage('task');
$task = $storage->create(['title' => 'Test Task', 'status' => 'todo']);
$task->save();
$id = $task->id();

// Re-load and verify
$loaded = $storage->load($id);
echo $loaded->get('title')->value === 'Test Task' ? 'PASS' : 'FAIL';

// Clean up
$loaded->delete();
```
```bash
drush php:script test-entity-workflow.php
```

### Anti-Patterns This Skill Corrects

| Current Anti-Pattern | What We Do Now | What Skill Teaches |
|---------------------|----------------|-------------------|
| php-eval for route checks | 5-line shell-escaped php-eval | `drush route --name=route_name` |
| No error checking | Operations with no watchdog review | `drush watchdog:show --severity-min=Warning` after every change |
| sql:query for entity ops | Direct DB manipulation | `entity:save`, `entity:create`, `entity:delete` |
| Manual boilerplate | Write all scaffolding by hand | `drush generate controller`, `drush generate form:config`, etc. |
| Shell escaping nightmare | Long php-eval with nested quotes | `drush php:script` for complex tests |
| No self-verification | Agent trusts its own output | Verification recipe after each major operation |

### Eval-Author Agent Architecture

Unchanged from original research. See original for details.

```
.claude/agents/eval-author.md
  - model: opus (deep reasoning for assertion quality)
  - tools: Read, Glob, Grep, Bash (read-only exploration)
  - permissionMode: bypassPermissions (consistent with eval-grader)
```

### Assertion Category Distribution (TOOL-04)

| Category | Target % | What It Tests | Example |
|----------|----------|---------------|---------|
| Differentiating | 60%+ | Non-obvious patterns from SKILL.md that Haiku gets wrong without skill | "Uses CacheableJsonResponse not plain JsonResponse for GET endpoints" |
| Wiring | 20%+ | Components connect correctly — DI resolves, routes wire to controllers | "(via ddev exec) drush php-eval tests that service container resolves MyService" |
| Structural | max 20% | Files exist, classes are loadable — necessary but not sufficient | "Controller class exists and is autoloadable" |

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Skill file format | Custom format | Existing SKILL.md format with frontmatter | 14 validated skills already use this format |
| Agent definition format | Custom YAML/JSON | `.claude/agents/*.md` frontmatter | eval-grader.md and eval-browser.md prove the pattern |
| Eval assertion format | New assertion schema | Existing evals.json + runtime-assertions.json | v3/v4 pipeline consumes this format |
| Command-authoring content | Rewrite from scratch | Preserved in references/command-authoring.md | Already written and validated in original plan |

## Common Pitfalls

### Pitfall 1: Using sql:query for Entity Operations
**What goes wrong:** Agent uses `drush sql:query "UPDATE node_field_data SET status = 1 WHERE nid = 42"` instead of `drush entity:save node 42 --publish`.
**Why it happens:** SQL feels direct and deterministic. LLMs default to it for "quick" operations.
**How to avoid:** Skill must teach the Drupal-first principle: entity API operations fire hooks (presave, update, access), invalidate caches, and update indexes. SQL bypasses all of this.

### Pitfall 2: No Self-Verification After Code Changes
**What goes wrong:** Agent creates routes, services, entities but never verifies they actually work. Silent errors accumulate.
**Why it happens:** Agents trust their own output. No habit of "run drush watchdog:show after changes."
**How to avoid:** Skill provides explicit verification recipes: after routes → `drush route`, after any change → `drush watchdog:show`, after entity changes → create+load test.

### Pitfall 3: php-eval for Everything
**What goes wrong:** Agent writes complex shell-escaped php-eval when a built-in Drush command does the job in one line.
**Why it happens:** LLM training data doesn't emphasize Drush's built-in command inventory. php-eval is the "Swiss army knife" fallback.
**How to avoid:** Skill maps common verification tasks to built-in commands. WRONG/RIGHT callouts show the simpler alternative.

### Pitfall 4: Not Using drush generate for Scaffolding
**What goes wrong:** Agent manually writes 200+ lines of boilerplate for a content entity when `drush generate entity:content` produces it in seconds.
**Why it happens:** LLMs don't know drush generate exists or what generators are available.
**How to avoid:** Skill lists all 50+ generators with what they produce and estimated token savings.

### Pitfall 5: Eval-Author Generates Tautological Assertions
**What goes wrong:** Agent produces assertions like "Controller file exists" that pass 100% for both with-skill and without-skill runs.
**Why it happens:** LLMs gravitate toward high-confidence assertions.
**How to avoid:** 60/20/20 distribution + tautology rejection rules.

## Code Examples

### Example: Route Verification — WRONG vs RIGHT

```markdown
> **WRONG — complex php-eval for route checking:**
> ```bash
> drush php-eval "$provider = \Drupal::service('router.route_provider');
>   $found = FALSE;
>   foreach (['my_module.api', 'my_module.list'] as $name) {
>     try { $provider->getRouteByName($name); $found = TRUE; break; }
>     catch (\Exception $e) {}
>   } echo $found ? 'PASS' : 'FAIL';"
> ```
> This is unreadable, fragile (shell escaping), and hard to debug.
>
> **RIGHT — built-in Drush command:**
> ```bash
> drush route --name=my_module.api
> drush route --path=/api/my-module/items
> ```
> One line, human-readable, proper error messages on failure.
```

### Example: Eval Assertion for Drush Usage Skill

```json
{
  "expectations": [
    "Agent uses drush watchdog:show or watchdog:tail to check for errors after module changes (without the skill, agents never check watchdog — silent errors accumulate and cause cascading failures in later development steps)",
    "Agent uses drush route for route verification instead of multi-line php-eval with router.route_provider (php-eval route checking requires complex shell escaping and produces cryptic output on failure)",
    "Agent uses entity:save or entity:create for entity operations instead of sql:query (sql:query bypasses Drupal's entity API — hooks, cache invalidation, and access checks are all skipped, causing state inconsistency)",
    "Agent uses drush generate for scaffolding at least one component (module, controller, form, entity, or plugin) instead of manually writing all boilerplate (drush generate produces phpcs-compliant code and saves 200-2000+ tokens per component)"
  ]
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| php-eval for route checking | `drush route --name=X --path=Y` | Drush 10.5+ | 1-line vs 5-line, readable output |
| No entity CLI commands | `entity:create/save/delete` | Drush 11+ | Proper entity API with hook firing |
| Manual boilerplate | `drush generate` (50+ generators) | Drush 12+ | phpcs-compliant scaffolding |
| No cache tag CLI | `drush cache:tags` | Drush 13+ | Direct cache tag invalidation testing |
| No self-verification habit | Verification recipes in skill | Phase 22 (new) | Agents check their own work |
| Manual eval assertion design | Eval-author Opus subagent | Phase 22 (new) | Eliminates manual bottleneck |

## Validation Architecture

### Test Framework

| Property | Value |
|----------|-------|
| Framework | Eval pipeline (headless claude -p + eval-grader agent) |
| Config file | `skills/drupal-drush/evals/evals.json` (new) |
| Quick run command | `drush route --name=my_module.*` (verifies route-level self-verification) |
| Full suite command | Full A/B eval pipeline (headless with/without runs + grading) |

### Phase Requirements to Test Map

| Req ID | Behavior | Test Type | File Exists? |
|--------|----------|-----------|-------------|
| TOOL-01 | Drush skill teaches usage patterns | manual + static | -- Wave 0 |
| TOOL-02 | Eval assertions target Drush usage patterns | static | -- Wave 0 |
| TOOL-03 | Eval-author produces three-tier assertions | manual | -- Wave 0 |
| TOOL-04 | Assertion distribution enforced | manual | -- Wave 0 |

### Wave 0 Gaps

- [ ] `skills/drupal-drush/SKILL.md` — usage-focused skill (does not exist yet)
- [ ] `skills/drupal-drush/evals/evals.json` — Drush usage assertions
- [ ] `skills/drupal-drush/references/command-authoring.md` — command authoring reference
- [ ] `.claude/agents/eval-author.md` — Opus subagent definition

## Open Questions

1. **Should drush generate be the PRIMARY scaffolding approach?**
   - What we know: drush generate produces phpcs-compliant code. It has 50+ generators covering modules, controllers, forms, entities, plugins, tests.
   - What's unclear: Whether agents can reliably use `--answer` flags for non-interactive generation, or if they need the interactive mode. Also unclear: how well ddev environments support drush generate.
   - Recommendation: Teach drush generate as a powerful option with examples. Let agents decide based on context. Don't make it mandatory — some scaffolding needs custom patterns not covered by generators.

2. **How granular should verification recipes be?**
   - What we know: Current agents don't self-verify at all. Any verification is an improvement.
   - What's unclear: Whether teaching too many recipes overwhelms the skill's signal.
   - Recommendation: Focus on the 5-7 highest-impact recipes. Link to Drush docs for the long tail.

3. **Should the skill teach ddev-specific patterns?**
   - What we know: Our eval pipeline uses ddev. All drush commands are prefixed with `ddev drush`.
   - What's unclear: Whether the skill should be ddev-agnostic or ddev-aware.
   - Recommendation: Show commands without `ddev` prefix (standard Drush). Note that in ddev environments, prefix with `ddev drush`. This keeps the skill portable.

## Sources

### Primary (HIGH confidence)
- [Drush 13.x Commands](https://www.drush.org/13.x/commands/) — Complete command inventory
- [Drush 13.x entity:save](https://www.drush.org/13.x/commands/entity_save/) — Entity save with hook firing
- [Drush 13.x entity:create](https://www.drush.org/13.x/commands/entity_create/) — Interactive entity creation
- [Drush 13.x entity:delete](https://www.drush.org/13.x/commands/entity_delete/) — Entity deletion
- [Drush 13.x core:route](https://www.drush.org/13.x/commands/core_route/) — Route inspection
- [Drush 13.x watchdog:show](https://www.drush.org/13.x/commands/watchdog_show/) — Log inspection
- [Drush 13.x php:eval](https://www.drush.org/13.x/commands/php_eval/) — Arbitrary PHP execution
- [Drush 13.x generate](https://www.drush.org/13.x/commands/generate/) — Code scaffolding
- [Drush 13.x generators](https://www.drush.org/13.x/generators/) — All available generators
- Existing eval runtime assertions (v3/v4) — Current Drush usage anti-patterns (project-specific)

### Secondary (MEDIUM confidence)
- [Drush 13.x state:get](https://www.drush.org/13.x/commands/state_get/) — State inspection
- [Drush 13.x cache:tags](https://www.drush.org/13.x/commands/cache_tags/) — Cache tag invalidation

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — Drush 13 commands verified against official docs
- Architecture: HIGH — Skill format follows 14 existing skills; agent format follows existing agents
- Pitfalls: HIGH — Anti-patterns identified from our own eval pipeline (empirical evidence)

**Research date:** 2026-03-09
**Valid until:** 2026-04-09

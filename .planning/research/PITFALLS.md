# Domain Pitfalls: v5.0 AI Integration, Drush Skill, Eval Tooling & Analytics

**Domain:** Adding AI-powered task management, a Drush CLI skill, an eval-author Opus subagent, skill gap fixes, and task analytics to an existing Drupal module (group_ai_pm) and skill collection
**Researched:** 2026-03-09
**Confidence:** HIGH for Drush command authoring (official Drush 13.x docs verified); HIGH for Drupal queue/batch patterns (existing skill + official docs); MEDIUM for Drupal AI module integration (official docs incomplete on exception handling, verified via issue queue); MEDIUM for eval-author agent design (Anthropic eval guide + project-specific empirical data); MEDIUM for analytics schema (general Drupal schema patterns verified, analytics-specific patterns from community)

**Scope:** Pitfalls specific to ADDING these six feature domains to the existing group_ai_pm module (10+ controllers, 2 entity types, Vue frontend, REST API, AJAX forms, 8 tests). v4.0 pitfalls (CSRF, Vue double-mounting, Claro CSS) are archived in git history.

---

## Critical Pitfalls

Mistakes that cause rewrites, broken eval pipelines, or wasted iteration cycles.

### Pitfall 1: Drush Skill Teaches Deprecated Command Patterns -- Generated Commands Not Discovered

**What goes wrong:**
The Drush skill teaches Haiku to create command files extending `DrushCommands` base class with annotation-based `@command` attributes and a `drush.services.yml` file. The generated command is placed in `src/Commands/` (missing the `Drush/` subdirectory). Drush 12+ cannot discover the command. `drush list` does not show it. The user gets "Command not found."

**Why it happens:**
Drush underwent three major breaking changes between Drush 11 and 13:

1. **Directory**: Commands moved from `src/Commands/` to `src/Drush/Commands/`. The `Drush/` subdirectory is a hard requirement for auto-discovery in Drush 12+.
2. **Base class**: `DrushCommands` is deprecated in favor of extending `Symfony\Component\Console\Command\Command` directly with `#[AsCommand]` attribute (Drush 13.7+).
3. **DI**: `drush.services.yml` is deprecated. Drush 13 uses `AutowireTrait` for constructor injection with type-hinted parameters. Drush 14 will remove `drush.services.yml` support entirely.

Most training data (including the Sipos book which is the basis for all existing skills) predates these changes. Claude's training data likely contains a mix of Drush 8-12 patterns, with the majority being the deprecated style. Without a skill explicitly teaching the Drush 13 pattern, code generation will reliably produce undiscoverable commands.

**Consequences:**
- Commands not found at runtime despite file existing
- Module installs fine but `drush my-command` fails
- Runtime assertions using `drush` to verify module behavior fail (eval pipeline broken)
- Developers debug nonexistent code issues when the problem is purely structural

**Prevention:**
- The Drush skill MUST teach the Drush 12/13 pattern as the PRIMARY pattern, not as a footnote:
  ```
  Directory: src/Drush/Commands/ (NOT src/Commands/)
  Base class: Symfony\Component\Console\Command\Command (NOT DrushCommands)
  Attribute: #[AsCommand(name: 'my:command')]
  DI: use AutowireTrait (NOT drush.services.yml)
  ```
- Include a CRITICAL NEVER callout: "NEVER place Drush commands in `src/Commands/`. Drush 12+ requires `src/Drush/Commands/` for auto-discovery."
- Include both D10 (Drush 12, `create()` method) and D11 (Drush 13, `AutowireTrait`) patterns, with Drush 12 as the D10-compatible baseline
- Add `#[Autowire]` attribute documentation for services that cannot be resolved by type hint alone (e.g., multiple LoggerInterface implementations)
- Eval assertions MUST test discovery: `drush list | grep my_command` as a runtime check, not just file existence

**Detection:**
- `drush list --filter=module_name` returns empty
- `drush my:command` returns "Command 'my:command' is not defined"
- `drush -vvv my:command` shows service instantiation errors (if DI is wrong)

**Phase to address:** Drush Skill phase. This is the foundational deliverable -- if the skill teaches wrong patterns, every downstream phase using Drush commands will fail.

---

### Pitfall 2: Eval-Author Agent Generates Tautological Assertions That Pass Both With and Without Skills

**What goes wrong:**
The eval-author Opus subagent reads the SKILL.md file and the eval prompt, then generates assertions like "Controller file exists at src/Controller/MyController.php" or "Module has a .info.yml file" or "Route is defined in routing.yml." These assertions pass 100% for both with-skill and without-skill runs. The eval shows 0% delta. The team concludes skills have no value, when actually the assertions are measuring the wrong thing.

**Why it happens:**
This is the #1 failure mode of automated assertion generation, observed empirically in this project and confirmed by Anthropic's eval design guidance. The root cause is that LLMs gravitate toward assertions they can verify with high confidence -- which are exactly the assertions that test obvious, undifferentiated behavior. "Does the file exist?" is easy to verify. "Does the file use CacheableJsonResponse instead of JsonResponse?" requires domain knowledge about WHY one is better.

The eval-author agent lacks the key insight that drove v2.0-v4.0 success: **assertions must target patterns where Haiku WITHOUT the skill gets it wrong**. This requires understanding what Haiku's baseline behavior is -- knowledge the eval-author does not inherently have.

From Anthropic's eval design guide: "There is a common instinct to check that agents followed very specific steps... We've found this approach too rigid." But the opposite extreme -- checking only that output exists -- is equally useless. The sweet spot is checking for specific patterns that differentiate skilled from unskilled output.

**Consequences:**
- Eval results show 0% delta across the board
- Skills appear useless when they may actually be highly effective
- The team wastes cycles iterating on skills that are already good (false negative on skill quality)
- The eval pipeline becomes a rubber stamp instead of a measurement tool

**Prevention:**
- The eval-author agent MUST receive as context: (1) the SKILL.md file, (2) the eval prompt, AND (3) a curated list of "differentiating patterns" extracted from SKILL.md -- patterns that Haiku is unlikely to produce without the skill
- Provide the agent with examples of GOOD assertions from v2.0-v4.0 (Phase 18 evals are the gold standard -- 17 assertions, all targeting non-obvious patterns, producing +23.3% delta)
- Include a system prompt rule: "NEVER generate assertions that test for file existence, standard Drupal boilerplate, or patterns that any competent Drupal developer would use. ALWAYS test for specific patterns documented in the skill as WRONG-WAY or CRITICAL callouts."
- Build a "tautology check" into the agent pipeline: after generating assertions, have a second pass ask "Would Haiku produce this pattern WITHOUT the skill?" If the answer is likely "yes," discard the assertion
- Include a mandatory assertion category distribution: at least 60% must be "differentiating" (tests non-obvious skill patterns), max 20% can be "structural" (tests file/class existence), and at least 20% must be "wiring" (tests that components connect to each other, not just exist)

**Detection:**
- All assertions pass at 100% for both with and without skill
- Assertions read like a checklist of file names rather than behavioral checks
- No assertion mentions a specific API method, class, or pattern from the SKILL.md
- Assertions do not reference any WRONG-WAY callout from the skill

**Phase to address:** Eval-Author Agent phase. This phase defines the tool that all subsequent phases depend on. Getting this wrong propagates to every future eval.

---

### Pitfall 3: AI Module Integration Swallows Rate Limit Exceptions -- Queue Worker Deletes Items That Should Be Retried

**What goes wrong:**
The batch AI QueueWorker catches `\Exception` generically in `processItem()`, logs the error, and the queue runner deletes the item. When OpenAI/Anthropic returns a rate limit error (HTTP 429), the Drupal AI module throws `AiRateLimitException`. The generic catch swallows it, the item is deleted, and the AI operation is permanently lost. The user's batch of 50 task descriptions that needed AI-generated summaries loses 15 items silently.

**Why it happens:**
The existing `OverdueNotificationWorker` in the module correctly implements the two-catch pattern (`SuspendQueueException` first, then generic `\Exception`). But `AiRateLimitException` does NOT extend `SuspendQueueException` -- it is its own exception class in the AI module hierarchy. The two-catch pattern is necessary but not sufficient for AI operations. There is a third category of exception (rate limit / quota) that means "retry later" rather than "bad item" or "systemic failure."

Additionally, Azure OpenAI provider has a known bug where `AiRateLimitException` is not thrown even when the API response indicates rate limiting, because the provider checks for specific text ("Request too large") that does not match Azure's actual rate limit response format.

**Consequences:**
- Queue items representing valid AI operations are permanently deleted
- Users see partial results with no indication that items were lost (not failed -- lost)
- Rate limits are transient -- the same items would succeed if retried 60 seconds later
- At scale (batch of 100 tasks), losing 10-20% of items to rate limiting destroys trust

**Prevention:**
- Implement a THREE-catch pattern for AI queue workers:
  ```php
  public function processItem($data) {
    try {
      // AI operation
    }
    catch (SuspendQueueException $e) {
      throw $e; // Systemic failure -- stop queue
    }
    catch (AiRateLimitException | AiQuotaException $e) {
      // Transient -- release item for retry
      throw new \Drupal\Core\Queue\RequeueException(
        'AI rate limited, requeueing: ' . $e->getMessage()
      );
    }
    catch (\Exception $e) {
      // Bad item -- log and skip
      $this->logger->error('AI processing failed: @msg', ['@msg' => $e->getMessage()]);
    }
  }
  ```
- Use `RequeueException` (not `SuspendQueueException`) for rate limits. `RequeueException` puts the single item back in the queue. `SuspendQueueException` stops ALL queue processing.
- Add exponential backoff tracking via item metadata: store retry count and last attempt timestamp in the queue item data. After N retries (e.g., 5), let the item fall through to the generic catch and be discarded with a logged error.
- Set the QueueWorker `cron = {"time" = 30}` for AI workers (higher than the 15-second default) because AI API calls have latency -- processing 1 item might take 5+ seconds
- Document in the batch-queue-cron skill that AI queue workers need three catches, not two

**Detection:**
- Queue depth drops to 0 after cron but fewer results exist than items queued
- Drupal watchdog shows "AI processing failed: rate limit" logged as error (item was deleted)
- Items succeed when manually re-queued (proves the failure was transient)

**Phase to address:** AI Integration phase (batch operations). Must be correct before any AI queue worker is built.

---

### Pitfall 4: Eval-Author Agent Cannot Assess Runtime Behavior -- Generates Only Static Assertions

**What goes wrong:**
The eval-author agent generates 15 static assertions (file patterns, class names, method signatures) and 0 runtime assertions. The static assertions pass, but the module does not actually work -- `drush en` fails due to a missing dependency, or the AI service throws an unhandled exception at runtime. The eval pipeline reports a high pass rate that does not reflect reality.

**Why it happens:**
The eval-author agent designs assertions by reading code files and SKILL.md content. Static assertions are natural outputs of code analysis. Runtime assertions require understanding what can go wrong at execution time, which requires domain expertise about Drupal's bootstrap, service container, entity schema installation, and external API behavior. An LLM generating assertions from code cannot predict that a `use` statement for a class in a non-installed module will cause a fatal error during service container compilation.

The v3.0 and v4.0 pipelines split assertions into two tiers (static + runtime) precisely because this problem was discovered empirically. Static assertions measure code quality. Runtime assertions measure "does it work."

**Consequences:**
- High eval scores that mask broken modules
- Skills appear to produce working code when they produce code that fails at install time
- False positive deltas: with-skill code might have better static patterns but equally broken runtime behavior

**Prevention:**
- Provide the eval-author agent with a mandatory runtime assertion template:
  ```
  REQUIRED runtime assertions for EVERY eval:
  1. Module enables: "ddev drush en {module} -y returns exit code 0"
  2. No PHP errors on enable: "ddev drush en {module} -y 2>&1 does not contain 'Error'"
  3. Key routes accessible: "ddev drush eval 'print \Drupal::service('router.route_provider')->getRouteByName('route.name')->getPath();'"
  4. Services resolve: "ddev drush eval 'print get_class(\Drupal::service('my.service'));'"
  5. Permissions exist: "ddev drush eval 'print_r(array_keys(\Drupal::service('user.permissions')->getPermissions()));' | grep 'expected permission'"
  ```
- Add a pipeline step that automatically prepends these 5 baseline runtime assertions to every eval, regardless of what the eval-author generates
- The eval-author should generate ADDITIONAL runtime assertions specific to the phase (e.g., "drush php-eval loads an entity and checks a computed field" or "config:get returns expected default values")
- For AI integration phases, add AI-specific runtime assertions: "AI provider service resolves," "function call plugin is discoverable," "queue worker processes a test item"

**Detection:**
- Eval results with >90% pass rate but module fails `drush en`
- Zero runtime assertions in the generated eval file
- All assertions are file-pattern greps, no `ddev drush` commands

**Phase to address:** Eval-Author Agent phase. Build runtime assertion generation as a separate capability from static assertion generation, with different prompting strategies for each.

---

### Pitfall 5: Adding Analytics Table Without Indexes on Time-Series Query Columns -- Dashboard Queries Time Out

**What goes wrong:**
A `group_ai_pm_task_history` table is created via `hook_schema()` with columns for `task_id`, `field_name`, `old_value`, `new_value`, `uid`, and `timestamp`. The table accumulates rows for every status change, priority change, and assignment change. After 6 months of use with 500 tasks and 20 active users, the table has 50,000+ rows. The analytics dashboard runs a query like `SELECT * FROM {group_ai_pm_task_history} WHERE task_id = :id ORDER BY timestamp DESC` -- but there is no index on `task_id` or `timestamp`. The query does a full table scan. The dashboard takes 8+ seconds to render.

**Why it happens:**
Drupal's entity system automatically creates indexes for entity keys, but custom tables created via `hook_schema()` have NO automatic indexes beyond the primary key. Developers create the table, verify it works with 10 test rows, and ship it. The performance problem only manifests at scale, which is never tested during development.

Additionally, analytics queries almost always involve time-range filtering (`WHERE timestamp BETWEEN ? AND ?`) combined with entity filtering (`AND task_id = ?`). Composite indexes on `(task_id, timestamp)` are required, but developers typically only index individual columns -- which MySQL/MariaDB cannot use efficiently for combined WHERE + ORDER BY queries.

**Consequences:**
- Dashboard page load times grow linearly with data volume
- Cron jobs that aggregate analytics data time out
- Database locks during INSERT + SELECT contention
- Eventually the analytics feature is disabled because it "makes the site slow"

**Prevention:**
- ALWAYS define composite indexes in `hook_schema()` for time-series data:
  ```php
  function group_ai_pm_schema() {
    $schema['group_ai_pm_task_history'] = [
      'fields' => [
        'id' => ['type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE],
        'task_id' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE],
        'field_name' => ['type' => 'varchar', 'length' => 64, 'not null' => TRUE],
        'old_value' => ['type' => 'varchar', 'length' => 255, 'not null' => FALSE],
        'new_value' => ['type' => 'varchar', 'length' => 255, 'not null' => FALSE],
        'uid' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE],
        'timestamp' => ['type' => 'int', 'not null' => TRUE],
      ],
      'primary key' => ['id'],
      'indexes' => [
        'task_timestamp' => ['task_id', 'timestamp'],
        'field_timestamp' => ['field_name', 'timestamp'],
        'uid_timestamp' => ['uid', 'timestamp'],
      ],
    ];
    return $schema;
  }
  ```
- Design indexes based on your known query patterns, not just individual columns
- Test with realistic data volumes BEFORE shipping. Insert 10,000 rows and run your dashboard queries with `EXPLAIN`
- Consider partitioning by time if data volume exceeds 1M rows (out of scope for v5.0 but plan for it)
- Add cache tags to analytics render arrays so computed aggregates are cached until the underlying data changes

**Detection:**
- Dashboard load time increases steadily over weeks/months
- `EXPLAIN SELECT ...` shows "type: ALL" (full table scan) instead of "type: ref" (index lookup)
- MySQL slow query log captures analytics queries
- `drush sqlq "SHOW INDEX FROM group_ai_pm_task_history"` shows only primary key

**Phase to address:** Analytics phase. Schema design must include indexes from day one. Adding indexes via `hook_update_N()` to a large table can lock the table for minutes.

---

### Pitfall 6: Adding Base Fields to Existing Entity Without hook_update_N() -- Existing Sites Break on Module Update

**What goes wrong:**
The analytics feature requires a new base field on the Task entity (e.g., `ai_summary` to store AI-generated task summaries). The developer adds the field to `baseFieldDefinitions()` and clears cache. New installations work fine because the entity schema is created fresh. Existing installations that already have the `group_ai_pm_task` table crash with a database error: "Unknown column 'ai_summary' in field list" because the physical column does not exist in the table.

**Why it happens:**
Drupal's entity system creates database columns from `baseFieldDefinitions()` during module installation. After installation, changes to `baseFieldDefinitions()` are NOT automatically applied to the database schema. You must write a `hook_update_N()` function that calls `\Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition()` to add the column.

This is the most common mistake when extending existing entity types. It works in development (where you reinstall frequently) and breaks in production (where the module is updated, not reinstalled).

**Consequences:**
- Existing sites get fatal errors on ANY page that loads Task entities
- The module update path is broken -- cannot run `drush updb` without manually fixing the schema
- If the field is used in entity queries, the query itself fails before any PHP code runs
- Reverting the code change does not fix the problem if any data migration was attempted

**Prevention:**
- ALWAYS pair base field additions with a `hook_update_N()`:
  ```php
  /**
   * Add ai_summary field to Task entity.
   */
  function group_ai_pm_update_10001() {
    $field_storage_definition = BaseFieldDefinition::create('text_long')
      ->setLabel(t('AI Summary'))
      ->setDescription(t('AI-generated task summary.'));

    \Drupal::entityDefinitionUpdateManager()
      ->installFieldStorageDefinition(
        'ai_summary',
        'task',
        'group_ai_pm',
        $field_storage_definition
      );
  }
  ```
- NEVER assume `baseFieldDefinitions()` changes are applied automatically
- The field definition in `hook_update_N()` must be self-contained (repeat all settings). Do NOT call the entity class's `baseFieldDefinitions()` from the update hook -- the method signature may change between versions
- Test the update path: install the module at the OLD version, then apply the update with `drush updb`
- For the Drush skill: this is a prime runtime assertion -- `drush updb --no-cache-clear && drush cr` should succeed without errors
- The entities-fields skill should document this pattern explicitly (currently missing)

**Detection:**
- `EntityStorageException` or PDOException on entity load after module update
- `drush entity:updates` shows pending entity definition updates
- `drush updb` lists no pending updates (because the developer forgot to write one)
- Works on fresh install, fails on updated site

**Phase to address:** Skill Gap Fixes phase (entities-fields) AND any phase that adds new base fields. This applies to AI Summary field, analytics tracking fields, or any entity schema extension.

---

## Moderate Pitfalls

### Pitfall 7: AI Module Provider Configuration Not Validated Before Queue Processing -- Silent Empty Responses

**What goes wrong:**
The module queues 50 tasks for AI summary generation. The QueueWorker calls `\Drupal::service('ai.provider')` to get an AI provider, makes the API call, and receives an empty response. No exception is thrown. The AI provider has no API key configured, or the configured provider does not support the requested operation type. The queue worker logs "AI summary generated" for each item, but the summaries are all empty strings.

**Why it happens:**
The Drupal AI module's provider system uses a layered architecture: the AI module provides a `ProviderInterface`, individual provider modules (OpenAI, Anthropic, Azure) implement it, and the Key module stores credentials. If no provider is configured, the `ai.provider` service may return a null provider or a provider that returns empty responses rather than throwing an exception. The `isUsable()` method on providers should be checked, but it is not enforced at the framework level.

**Prevention:**
- Validate provider configuration BEFORE queuing items, not during processing:
  ```php
  // In the batch submission handler, before queuing:
  $provider = \Drupal::service('ai.provider');
  if (!$provider || !$provider->isUsable('chat')) {
    \Drupal::messenger()->addError(t('No AI provider is configured. Please configure an AI provider before running batch AI operations.'));
    return;
  }
  ```
- In the QueueWorker, validate the response is not empty before saving:
  ```php
  $response = $provider->chat($messages, $model, $options);
  if (empty($response) || empty($response->getNormalized())) {
    throw new \Exception('Empty AI response for task ' . $data->task_id);
  }
  ```
- Add a settings form check that validates AI provider configuration and displays a status message on the dashboard if AI features are available/unavailable
- Set a reasonable timeout on AI provider calls (30-60 seconds) to prevent queue workers from hanging indefinitely

**Phase to address:** AI Integration phase. Provider validation must be the first thing implemented before any AI operation.

---

### Pitfall 8: Drush Skill and Batch-Queue-Cron Skill Teach Conflicting Queue Patterns

**What goes wrong:**
The Drush skill teaches programmatic queue processing via `drush queue:run`, where the developer writes a Drush command that claims and processes items manually. The batch-queue-cron skill teaches the QueueWorker plugin pattern where cron processes items automatically. Both are valid, but if the eval prompt asks for "queue processing" without specifying which pattern, Haiku mixes them: it creates a QueueWorker plugin AND a Drush command that both try to process the same queue, leading to double-processing and race conditions.

**Why it happens:**
Both skills document queue processing but from different angles. The batch-queue-cron skill shows the full QueueWorker plugin lifecycle (cron-driven). A Drush skill would naturally show `drush queue:run` as the Drush way to process queues. Without explicit disambiguation, Haiku sees two valid patterns and combines them, not realizing they are alternatives, not complements.

**Prevention:**
- The Drush skill must explicitly state: "drush queue:run processes items using the existing QueueWorker plugin. Do NOT create a separate Drush command that duplicates the QueueWorker's processItem() logic. Use `drush queue:run queue_name` to trigger the same QueueWorker that cron uses."
- Cross-reference between the two skills:
  - batch-queue-cron: "To process queues manually via CLI, use `drush queue:run queue_name` (see drupal-drush skill). Do NOT write a separate Drush command for queue processing."
  - drupal-drush: "To process queue items, prefer `drush queue:run` which uses the existing QueueWorker plugin. Only create a custom Drush command for queue processing if you need behavior that QueueWorker does not support (e.g., processing with different options, processing from a non-cron context with custom filtering)."
- Eval assertions should check: "If a QueueWorker plugin exists for queue X, there should NOT be a Drush command that also processes queue X items manually"

**Detection:**
- Both a QueueWorker plugin AND a Drush command reference the same queue name
- Items are processed twice (duplicate operations, double notifications)
- Race condition between cron and manual `drush queue:run`

**Phase to address:** Drush Skill phase AND Skill Gap Fixes phase (batch-queue-cron cross-reference).

---

### Pitfall 9: Module Complexity Causes Cascading Test Failures When Adding New Features

**What goes wrong:**
The module now has 8 test classes, 2 entity types, 10+ controllers, and a Vue frontend. Adding a new AI controller for natural language task creation requires modifying the Task entity (new field), adding routes, and adding a service. The new field addition breaks 3 existing kernel tests (`EntityCrudTest`, `RestApiTest`, `AccessControlTest`) because they do not install the schema for the new field's dependency module (e.g., `drupal:text` for `text_long`). The developer runs the new AI test, it passes, and ships. Later, the CI pipeline runs all tests and 3 old tests fail.

**Why it happens:**
Drupal kernel tests specify `$modules` explicitly. When you add a new base field to an entity that requires a module not in an existing test's `$modules` array, the entity schema installation in `setUp()` fails silently or throws an error. The test was passing before because the field did not exist. Now it fails because the entity definition has changed.

This is a specific instance of the general problem: in a module with 8 tests and 39 PHP files, ANY change to shared infrastructure (entities, services, routes) can break tests that the developer did not think to check.

**Prevention:**
- ALWAYS run the full test suite after ANY entity change: `ddev exec phpunit modules/custom/group_ai_pm/tests/`
- When adding a base field, check ALL test classes for `installEntitySchema('task')` or `installEntitySchema('project')` -- each one needs the new dependency module added to `$modules`
- Create a shared test trait for entity setup that ALL test classes use, so dependency changes propagate automatically:
  ```php
  trait GroupAiPmTestTrait {
    protected static $groupAiPmModules = [
      'group_ai_pm', 'system', 'user', 'options', 'text',
      'datetime', 'field', 'file',
    ];
  }
  ```
- Add a CI-equivalent runtime assertion to the eval pipeline: "All existing tests still pass after new code is added" (`ddev exec phpunit modules/custom/group_ai_pm/tests/ --no-coverage`)
- For the eval-author agent: automatically add a "regression check" assertion that runs the full test suite, not just new tests

**Detection:**
- New test passes, old tests fail
- `PHPUnit` output shows errors in tests that were not modified
- Errors mention missing tables, unknown columns, or unresolvable services
- `$modules` array in failing test does not include a module that a new field depends on

**Phase to address:** ALL phases that modify entities or services. But particularly the Skill Gap Fixes phase (entities-fields `bundle_of`) and AI Integration phase (new entity fields).

---

### Pitfall 10: AI Function Call Plugin Uses Wrong Annotation Namespace After AI Agents Module Update

**What goes wrong:**
The existing `group_ai_pm_ai` submodule has two `AiFunctionCall` plugins using the `@AiFunctionCall` annotation. The AI Agents module updates from 1.2.x to 1.3.x, changing the plugin annotation namespace or switching to PHP attributes. The existing plugins stop being discovered. The AI chatbot loses the ability to create projects or query tasks.

**Why it happens:**
The Drupal AI ecosystem is under active development. The `ai_agents` module's plugin system is not yet stable. Between minor versions, plugin discovery mechanisms can change. The `@AiFunctionCall` annotation used in the existing code may not match the current API in the pinned version (1.2.3) let alone future versions.

This is compounded by the fact that the AI module itself (drupal/ai 1.2.11) has different API conventions than the AI Agents module (drupal/ai_agents 1.2.3). Mixing them up leads to plugins that the wrong module tries to discover.

**Prevention:**
- Pin exact versions in `composer.json` and document them: `"drupal/ai": "1.2.11"`, `"drupal/ai_agents": "1.2.3"`
- Before any AI integration phase, verify the current plugin discovery mechanism:
  ```bash
  ddev drush eval "print_r(array_keys(\Drupal::service('plugin.manager.ai_function_call')->getDefinitions()));"
  ```
- Test plugin discovery as a runtime assertion in every AI eval
- When adding NEW AI function call plugins, follow the exact same pattern as the existing working plugins (CreateProjectTool, QueryProjectsTool) -- do not modernize the annotation style unless explicitly updating all existing plugins simultaneously
- Consider adding the AI integration as a soft dependency (check `\Drupal::moduleHandler()->moduleExists('ai_agents')` before registering tools)

**Detection:**
- AI chatbot commands stop working
- `drush eval "plugin.manager.ai_function_call->getDefinitions()"` returns empty or missing expected plugins
- PHP warnings about "unknown annotation" in the plugin directory
- Module installs fine but AI features silently do nothing

**Phase to address:** AI Integration phase. Verify existing plugin discovery works before adding new plugins.

---

### Pitfall 11: Eval-Author Agent Generates Assertions That Are Too Specific to One Valid Implementation

**What goes wrong:**
The eval-author generates an assertion: "TaskApiController::kanban() method calls `$this->entityTypeManager->getStorage('task')->loadByProperties(['project' => $project->id()])` on line 45." This is correct for one implementation, but equally valid implementations use entity queries (`$this->taskStorage->getQuery()->condition('project', $project->id())->execute()`). The without-skill run uses entity queries and the assertion fails. The delta appears to be +1 because of implementation style, not skill quality.

**Why it happens:**
This is the "rigid step-checking" anti-pattern identified by Anthropic's eval design guide. The eval-author, having generated or observed one implementation, locks onto its specific API calls as the "correct" pattern. But Drupal provides multiple valid ways to achieve the same result. Assertions must test OUTCOMES (are tasks loaded and serialized correctly?) not IMPLEMENTATIONS (which API was called?).

v4.0 Phase 18 assertions successfully avoided this by testing for patterns like "uses CacheableJsonResponse" (there is only one correct class for cacheable JSON responses) rather than "calls addCacheableDependency on line 52."

**Prevention:**
- Train the eval-author with examples of good vs bad assertions:
  - BAD: "Uses loadByProperties() to load tasks" (implementation-specific)
  - GOOD: "Uses CacheableJsonResponse instead of JsonResponse for GET endpoints" (there is one correct choice)
  - BAD: "Calls $entity->access('update') on line 38" (line-specific)
  - GOOD: "Calls $entity->access('view') or $entity->access('update') on each entity, NOT relying solely on route-level permission" (pattern-level)
- Assertions should test for CLASSES USED, PATTERNS FOLLOWED, and ANTI-PATTERNS AVOIDED rather than specific method calls or line numbers
- Include negative assertions: "Does NOT use `\Drupal::service()` static calls in controllers" is more robust than "Uses dependency injection via create()" because there are many ways to implement DI but only one wrong pattern to avoid
- Runtime assertions sidestep this entirely: "GET /api/kanban/project/1 returns JSON with status 200" tests outcome regardless of implementation

**Detection:**
- Delta appears on assertion-by-assertion analysis but both implementations are actually correct
- Manual review of failing assertions shows the "wrong" code is functionally equivalent
- Assertions reference specific line numbers or exact method signatures

**Phase to address:** Eval-Author Agent phase. Build assertion review into the pipeline.

---

## Minor Pitfalls

### Pitfall 12: Drush Command Namespace Collision with Existing Core/Contrib Commands

**What goes wrong:**
The Drush skill teaches creating a command named `project:list` or `task:status`. These names collide with potential contrib module commands or future Drupal core commands. When two commands share a name, Drush behavior is undefined -- it may pick one arbitrarily or throw an ambiguity error.

**Prevention:**
- Prefix ALL custom Drush commands with the module name: `gapm:project:list`, `gapm:task:create`
- The Drush skill should include: "ALWAYS prefix custom command names with your module's short name to avoid collisions. Use `module:action:target` format."
- Test with `drush list --filter=gapm` to verify namespace isolation

**Phase to address:** Drush Skill phase.

---

### Pitfall 13: Analytics hook_schema() Defined but hook_update_N() Missing -- Table Not Created on Existing Installs

**What goes wrong:**
The analytics table is added to `hook_schema()` for new installations. But existing installations that update the module do not trigger `hook_schema()` (it only runs on fresh install). The analytics dashboard shows a "Table not found" error.

**Prevention:**
- ALWAYS pair new tables with `hook_update_N()`:
  ```php
  function group_ai_pm_update_10002() {
    $schema = group_ai_pm_schema();
    \Drupal::database()->schema()->createTable(
      'group_ai_pm_task_history',
      $schema['group_ai_pm_task_history']
    );
  }
  ```
- Eval pipeline: test on BOTH fresh install AND update path
- The database-api skill should document this pattern (currently missing)

**Phase to address:** Analytics phase.

---

### Pitfall 14: AI Timeout Exceeds PHP max_execution_time -- Fatal Error During Batch Processing

**What goes wrong:**
AI API calls to GPT-4 or Claude take 10-30 seconds per request. The Batch API runs operations within a PHP request. If `max_execution_time` is 30 seconds (common default), processing 3 AI calls in one batch request triggers a fatal timeout. The batch progress bar freezes at 15% and the operation cannot be resumed.

**Prevention:**
- Use Queue API (not Batch API) for AI operations. Queue workers run in cron context where timeouts are configured differently
- If Batch API is required for UX (progress bar), process only ONE item per batch operation pass and set a generous request timeout
- Set AI provider timeouts lower than PHP timeout: if `max_execution_time` is 30s, set AI timeout to 20s so the PHP error handler can catch timeouts gracefully
- The batch-queue-cron skill should add: "For operations involving external API calls (especially AI), prefer Queue API over Batch API because queue items can be retried independently and cron timeouts are separately configurable"

**Detection:**
- Batch progress bar freezes; page eventually shows a server error
- PHP error log shows "Maximum execution time of 30 seconds exceeded"
- AI operations work individually but fail in batch

**Phase to address:** AI Integration phase (batch operations).

---

### Pitfall 15: forms-api Skill Describes AJAX But Has No #ajax Content -- Haiku Generates Broken AJAX Forms

**What goes wrong:**
The forms-api skill description says "Covers form elements, validation, AJAX callbacks" but the skill body has zero content about `#ajax` properties, `AjaxResponse`, or `ReplaceCommand`. Haiku reads the skill description, sees "AJAX callbacks" mentioned, and generates AJAX form code based on training data. The training data patterns may be outdated or incomplete. The generated AJAX forms either do not trigger callbacks or produce "An AJAX error occurred" messages.

**Why it happens:**
The forms-api skill was written from Chapters 2 of the Sipos book, which covers basic Form API. AJAX forms are covered in a different part of the book (Chapter 3 sidebar or Chapter 14). The skill description overestimates its scope. This was identified as a gap in v4.0 Phase 20 results but not yet fixed.

**Prevention:**
- Add concrete `#ajax` patterns to the forms-api skill body:
  ```php
  $form['status'] = [
    '#type' => 'select',
    '#title' => $this->t('Status'),
    '#options' => $options,
    '#ajax' => [
      'callback' => '::statusCallback',
      'wrapper' => 'status-result-wrapper',
      'effect' => 'fade',
    ],
  ];
  ```
- Document the callback signature: `public function statusCallback(array &$form, FormStateInterface $form_state)` must return a render array (the replacement content), NOT an AjaxResponse (unless you need multiple commands)
- Document the `wrapper` pattern: the `#ajax.wrapper` value MUST match the `#prefix`/`#suffix` container ID of the element to replace
- Include an AjaxResponse pattern for multi-command callbacks:
  ```php
  $response = new AjaxResponse();
  $response->addCommand(new ReplaceCommand('#wrapper', $rendered));
  $response->addCommand(new MessageCommand($this->t('Status updated.')));
  return $response;
  ```

**Phase to address:** Skill Gap Fixes phase (forms-api #ajax).

---

## Phase-Specific Warnings

| Phase Topic | Likely Pitfall | Mitigation |
|-------------|---------------|------------|
| Drush Skill creation | Wrong directory structure and deprecated patterns (#1) | Verify against Drush 13.x official docs; test discovery as runtime assertion |
| Drush Skill creation | Namespace collision with core commands (#12) | Prefix all commands with module short name |
| Drush Skill creation | Conflicting queue patterns with batch-queue-cron (#8) | Explicit cross-reference disambiguation in both skills |
| Eval-Author Agent | Tautological assertions measuring file existence (#2) | Require 60% differentiating assertions; provide v4.0 Phase 18 as gold standard examples |
| Eval-Author Agent | No runtime assertions generated (#4) | Inject mandatory runtime assertion template; auto-prepend baseline checks |
| Eval-Author Agent | Over-specific implementation assertions (#11) | Test patterns and anti-patterns, not specific method calls or line numbers |
| Skill Gap Fixes (entities-fields) | Missing hook_update_N() for new base fields (#6) | Pair every baseFieldDefinitions change with update hook |
| Skill Gap Fixes (forms-api) | #ajax content missing from skill body (#15) | Add concrete #ajax patterns with callback, wrapper, and AjaxResponse examples |
| Skill Gap Fixes (caching) | lazy_builder content incomplete | Add TrustedCallbackInterface pattern with scalar-only arguments rule |
| AI Integration (NL task creation) | Provider not configured, silent empty responses (#7) | Validate provider before queuing; check response non-empty |
| AI Integration (batch operations) | Rate limit exceptions swallowed (#3) | Three-catch pattern with RequeueException for transient failures |
| AI Integration (batch operations) | PHP timeout during batch AI calls (#14) | Use Queue API for AI, not Batch API; set AI timeout < PHP timeout |
| AI Integration (function call plugins) | Annotation namespace breaks on module update (#10) | Pin versions; verify plugin discovery; follow existing plugin patterns exactly |
| Analytics schema | Missing indexes on query columns (#5) | Design composite indexes based on query patterns before table creation |
| Analytics schema | hook_schema() without hook_update_N() (#13) | Always pair new table creation with update hook for existing installs |
| All entity-modifying phases | Cascading test failures (#9) | Run full test suite after entity changes; use shared test trait for $modules |

## "Looks Done But Isn't" Checklist

Things that appear complete but are missing critical pieces.

- [ ] **Drush command created:** Check that `drush list | grep module_name` shows the command -- not just that the file exists at the correct path
- [ ] **Eval assertions designed:** Check that at least 60% test non-obvious skill patterns -- not just file existence and class names
- [ ] **Eval has runtime checks:** Check that the eval includes `drush en` + at least 2 drush-based runtime assertions -- not just static file pattern matching
- [ ] **AI queue worker handles errors:** Check for THREE catch blocks (SuspendQueueException, AiRateLimitException/AiQuotaException, generic Exception) -- not just the standard two-catch pattern
- [ ] **AI provider validated:** Check that provider configuration is validated BEFORE items are queued -- not just during processing
- [ ] **New entity field added:** Check that a `hook_update_N()` exists alongside the `baseFieldDefinitions()` change -- not just that the field works on fresh install
- [ ] **Analytics table created:** Check that `hook_schema()` AND `hook_update_N()` both define the table -- not just one or the other
- [ ] **Analytics indexes exist:** Check that composite indexes cover the actual query patterns (task_id + timestamp, not just task_id alone)
- [ ] **Skills cross-reference correctly:** Check that the Drush skill and batch-queue-cron skill do not teach conflicting queue processing patterns
- [ ] **Existing tests still pass:** Check that `phpunit modules/custom/group_ai_pm/tests/` passes AFTER changes -- not just the new test in isolation

## Recovery Strategies

When pitfalls occur despite prevention, how to recover.

| Pitfall | Recovery Cost | Recovery Steps |
|---------|---------------|----------------|
| Drush wrong directory (#1) | LOW | Move file from `src/Commands/` to `src/Drush/Commands/`, update namespace. 5-minute fix. |
| Tautological assertions (#2) | HIGH | Must redesign eval assertions from scratch, re-run both variants, re-grade. Full eval cycle wasted. |
| Rate limit exceptions swallowed (#3) | MEDIUM | Add catch block, but already-lost queue items cannot be recovered. Must re-queue. |
| No runtime assertions (#4) | HIGH | Add runtime assertions and re-run evals. All previous eval results are unreliable -- may need to re-run everything. |
| Missing indexes (#5) | MEDIUM | `hook_update_N()` to add indexes. `ALTER TABLE ADD INDEX` on large table locks it for seconds to minutes. |
| Missing hook_update_N() (#6) | MEDIUM | Write update hook, but existing installs may need manual database repair if schema is corrupted. |
| AI provider not validated (#7) | LOW | Add validation check. Queue items with empty results need manual re-processing. |
| Conflicting queue patterns (#8) | LOW | Remove duplicate Drush command. Clarify skill cross-references. |
| Cascading test failures (#9) | MEDIUM | Update `$modules` in all affected tests. May require debugging multiple test classes. |
| AI plugin annotation break (#10) | MEDIUM | Update annotations/attributes. Requires understanding new API version's discovery mechanism. |
| Over-specific assertions (#11) | MEDIUM | Rewrite assertions to test patterns not implementations. Partial re-run may suffice. |
| Drush namespace collision (#12) | LOW | Rename command. Quick fix. |
| Missing update hook for table (#13) | LOW | Write `hook_update_N()` with `createTable()`. Quick fix. |
| PHP timeout in batch (#14) | MEDIUM | Refactor from Batch to Queue API. Architecture change. |
| Missing #ajax in skill (#15) | LOW | Add content to skill body. Re-run eval if delta was affected. |

## Sources

- [Drush 13.x Command Authoring](https://www.drush.org/13.x/commands/) -- PHP attributes, directory structure, AsCommand, deprecated patterns (HIGH confidence)
- [Drush 13.x Dependency Injection](https://www.drush.org/13.x/dependency-injection/) -- AutowireTrait, constructor injection, #[Autowire] attribute (HIGH confidence)
- [Drush AutowireTrait Source](https://github.com/drush-ops/drush/blob/13.x/src/Commands/AutowireTrait.php) -- Trait implementation details (HIGH confidence)
- [Drush 12 Autodiscovery Issue](https://www.drupal.org/project/single_content_sync/issues/3415040) -- Discovery requirements for Drush 12+ (HIGH confidence)
- [Drupalize.Me Drush Command Tutorials Updated](https://drupalize.me/blog/drush-custom-command-tutorials-updated) -- Migration guidance from annotations to attributes (MEDIUM confidence)
- [Drupal AI Module](https://www.drupal.org/project/ai) -- AI provider architecture (MEDIUM confidence)
- [Drupal AI Rate Limits Issue](https://www.drupal.org/project/ai/issues/3492086) -- Rate limiting discussion for AI interactions (MEDIUM confidence)
- [Drupal AI Better Error Handling Issue](https://www.drupal.org/project/ai/issues/3499597) -- AiRateLimitException, AiQuotaException error handling (MEDIUM confidence)
- [Azure AI Provider Rate Limit Bug](https://www.drupal.org/project/ai_provider_azure/issues/3557858) -- AiRateLimitException not thrown correctly (MEDIUM confidence)
- [Drupal AI Custom Timeout Issue](https://www.drupal.org/project/ai/issues/3479159) -- Timeout configuration for AI providers (MEDIUM confidence)
- [Drupal AI Provider Development Guide](https://project.pages.drupalcode.org/ai/1.1.x/developers/writing_an_ai_provider/) -- Provider interface, exception types (MEDIUM confidence)
- [Drupal Update API Documentation](https://www.drupal.org/docs/drupal-apis/update-api/updating-database-schema-andor-data-in-drupal) -- hook_update_N(), entity schema updates (HIGH confidence)
- [Drupal Entity Schema Update Change Record](https://www.drupal.org/node/2554097) -- installFieldStorageDefinition() API (HIGH confidence)
- [Drupal Entity Schema Index Issue](https://www.drupal.org/project/drupal/issues/3005447) -- Indexes not auto-applied on entity schema update (HIGH confidence)
- [Anthropic: Demystifying Evals for AI Agents](https://www.anthropic.com/engineering/demystifying-evals-for-ai-agents) -- Eval design best practices, assertion quality, rigid step-checking anti-pattern (HIGH confidence)
- [Hamel Husain: Your AI Product Needs Evals](https://hamel.dev/blog/posts/evals/) -- Eval methodology, common mistakes (MEDIUM confidence)
- [Red Hat Research: LLMs for Unit Test Generation](https://research.redhat.com/blog/2025/04/21/choosing-llms-to-generate-high-quality-unit-tests-for-code/) -- Initial assertion correctness rates (53-75%), iterative refinement (MEDIUM confidence)
- [Drupal Database Optimization](https://www.cmsdrupal.com/blog/5-easy-tips-optimize-your-drupal-database-and-speed-your-sql-queries-part-1) -- Index design, query optimization (MEDIUM confidence)
- [DrupalZone: Indexing Database Tables](https://drupalzone.com/tutorial/performance-optimization/17-indexing-database-tables) -- Custom table index patterns (MEDIUM confidence)
- v2.0-v4.0 Eval Results -- Empirical assertion quality data, tautological assertion patterns, delta measurements (HIGH confidence, project-specific)
- v4.0 Phase 18 Evals -- Gold standard assertion design with 17 assertions producing +23.3% delta (HIGH confidence, project-specific)
- v4.0 Phase 20 Results -- forms-api #ajax gap identified, skill propagation patterns (HIGH confidence, project-specific)
- Existing group_ai_pm module source -- Current module structure, entity definitions, queue workers, AI plugins (HIGH confidence, first-party)

---
*Pitfalls research for: v5.0 AI Integration, Drush Skill, Eval Tooling & Analytics -- adding AI operations, CLI skill, automated eval design, analytics schema, and skill improvements to existing Drupal 10 module and skill collection*
*Researched: 2026-03-09*

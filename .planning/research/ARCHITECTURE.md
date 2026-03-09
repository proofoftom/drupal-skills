# Architecture Patterns

**Domain:** AI integration, Drush skill, eval-author agent, and task analytics for existing Drupal skills + module project (v5.0)
**Researched:** 2026-03-09
**Confidence:** MEDIUM -- Drupal AI module API patterns verified via official docs; Drush 13 command structure verified; eval-author agent design extrapolated from established eval pipeline patterns; task history via custom entity is standard Drupal but unverified at scale

## System Overview

v5.0 adds five distinct capability streams to the existing codebase. Unlike v4.0 (which was primarily a frontend layer), v5.0 touches every layer: skills, agents, module backend, and eval tooling. The key architectural challenge is that these streams have cross-cutting dependencies -- the eval-author agent needs the Drush skill to generate runtime assertions, and AI features need eval coverage that the eval-author should design.

```
EXISTING                                    NEW (v5.0)
-----------------------------------------------------------
skills/drupal-*/SKILL.md (14)       -->   skills/drupal-drush/SKILL.md (15th)
                                    -->     references/drush-generators.md
                                    -->     evals/evals.json

.claude/agents/
  eval-grader.md                    -->   eval-author.md (NEW)
  eval-browser.md                         (eval-grader.md unchanged)

modules/group_ai_pm/
  src/Controller/
    TaskApiController.php           -->   src/Service/
                                    -->     AiTaskService.php (NEW)
  src/Entity/
    Task.php                        -->   src/Entity/
                                    -->     TaskHistory.php (NEW)
                                    -->     TaskHistoryInterface.php (NEW)
                                    -->     TaskHistoryViewsData.php (NEW)
  modules/group_ai_pm_ai/
    src/Plugin/AiFunctionCall/
      CreateProjectTool.php         -->     CreateTaskTool.php (NEW)
      QueryProjectsTool.php         -->     UpdateTaskStatusTool.php (NEW)
                                    -->     SuggestAssigneeTool.php (NEW)
                                    -->     BatchUpdateTool.php (NEW)
  group_ai_pm.routing.yml          -->   (add AI endpoints)
  group_ai_pm.module                -->   (add hook_entity_insert/update for history)

eval/v5/                            -->   (NEW directory, all phase evals)
```

## Component 1: drupal-drush Skill

### Where It Fits

The Drush skill is the 15th skill in `skills/drupal-drush/`. It follows the same anatomy as all other skills: SKILL.md (<500 lines), frontmatter with name/description, decision-guide body, references/ directory, and evals/evals.json.

### Skill Structure

```
skills/drupal-drush/
  SKILL.md               # <500 lines, Drush command creation + common drush CLI patterns
  references/
    drush-generators.md   # Drush generate patterns (lower priority, overflow content)
  evals/
    evals.json            # A/B eval for drush command creation task
```

### SKILL.md Content Architecture

The skill must cover two audiences with different needs:

**Audience 1: Module developers creating Drush commands.**
This is the primary code-generation use case. Content should cover:
- File location: `src/Drush/Commands/` (Drush 12+ requirement -- NOT `src/Command/`)
- Console command pattern: extend `Symfony\Component\Console\Command\Command`
- `#[AsCommand]` attribute (Drush 13.7+ preference) -- NOT the deprecated `#[CLI\Command]`
- `AutowireTrait` for dependency injection (replaces `drush.services.yml`)
- Input/output patterns: `$io->writeln()`, `$this->io()->success()`, Symfony Console helpers
- D10/D11 compatibility notes (none -- Drush version matters, not Drupal core version)

**Audience 2: Agents writing eval runtime assertions.**
The Drush skill is referenced by eval pipeline for `drush php-eval` and `drush config:get` patterns. While not the primary audience, this is the motivating use case per PROJECT.md. The skill should include a section on common Drush CLI commands used programmatically:
- `drush php-eval` for arbitrary PHP execution
- `drush pm:list` for module status checks
- `drush config:get` for configuration verification
- `drush user:info` for user/permission checks
- `drush cache:rebuild` patterns

### Critical Callouts (HIGH-value for skill delta)

Based on patterns from HIGH-delta skills (caching +37.5%, routing-controllers +33.3%), the Drush skill should emphasize non-obvious patterns:

```
> WRONG: Placing Drush commands in `src/Command/` or `src/Commands/`.
> RIGHT: Drush 12+ requires `src/Drush/Commands/`. The `Drush/` subdirectory is
> mandatory for command discovery. Commands in the wrong directory are silently
> ignored.

> WRONG: Using `drush.services.yml` to register command classes.
> RIGHT: Drush 13+ uses AutowireTrait for dependency injection. No services file
> needed. Constructor type-hints are resolved automatically from the container.

> WRONG: Using `#[CLI\Command(name: 'my:command')]` annotation for command metadata.
> RIGHT: Use `#[AsCommand(name: 'my:command')]` from Symfony Console. The Drush
> CLI\Command attribute is deprecated in favor of the standard Symfony attribute.
```

### Cross-References

- drupal-module-scaffold: PSR-4 structure, module directory layout
- drupal-batch-queue-cron: programmatic queue processing via Drush (drush queue:run)
- drupal-testing: running tests via Drush (drush test:run)

### References Directory

`references/drush-generators.md` should cover `drush generate` commands for scaffolding, which is overflow content from the main SKILL.md. This parallels how `drupal-routing-controllers` puts menu links in `references/menus.md`.

### Eval Design

The eval should test Drush command creation (the code-generation use case), not CLI usage patterns. A good eval prompt would ask to create a custom Drush command that processes entities -- combining Drush command structure with entity operations.

Key assertion targets:
- File location in `src/Drush/Commands/` (not `src/Command/`)
- `#[AsCommand]` attribute usage
- `AutowireTrait` for DI (not drush.services.yml)
- Command extends `Command` (Symfony), not `DrushCommands` (legacy)

**Confidence:** MEDIUM -- Drush 13 command patterns verified via official docs, but `#[AsCommand]` vs `#[CLI\Command]` deprecation timeline needs confirmation against Drush 13.7 (the version in composer.json).

## Component 2: eval-author Agent

### Design Rationale

The eval-author agent automates what has been the most context-heavy manual task: designing three-tier assertions (static + runtime + browser) for each phase. In v3.0 and v4.0, eval design happened in the main context window, consuming significant tokens and attention. The eval-author agent encapsulates this expertise.

### Agent Specification

```yaml
# .claude/agents/eval-author.md
---
name: eval-author
description: |
  Design three-tier eval assertions for a Drupal module development phase.
  Reads the phase prompt, existing SKILL.md files, and previous eval results
  to produce static expectations, runtime assertions (drush-based), and
  browser assertions. Outputs structured JSON files.
model: opus
permissionMode: bypassPermissions
tools: Read, Bash, Glob, Grep
---
```

### Why Opus (not Sonnet)

The eval-author must reason about:
1. What patterns a SKILL.md teaches vs what Haiku already knows (to target non-obvious patterns)
2. Which assertions discriminate between with-skill and without-skill code generation
3. How to construct valid `drush php-eval` commands that test runtime behavior

This requires understanding the skill delta model -- a meta-reasoning task about LLM behavior that Sonnet handles poorly. Opus is the correct choice because:
- It must read multiple SKILL.md files and identify which patterns are non-obvious
- It must reason about what Haiku will and won't produce without guidance
- It designs assertions that will be used to measure skill effectiveness
- Mistakes here propagate: bad assertions produce bad eval results

### Inputs

The eval-author receives from the orchestrator:

| Input | Source | Purpose |
|-------|--------|---------|
| Phase prompt | Inline in task | What code will be generated |
| Skills tested | Inline list | Which SKILL.md files to read |
| Previous phase results | `eval/v{N}/phase-{N-1}-*-results.json` | Calibrate difficulty based on what Haiku struggled with |
| Existing module code | `modules/group_ai_pm/` | Understand what exists, what's new |
| Output path | `eval/v5/phase-{N}-evals.json` | Where to write static assertions |
| Runtime output path | `eval/v5/phase-{N}-runtime-assertions.json` | Where to write runtime assertions |

### Outputs

Three files per phase:

**1. Static assertions (`phase-{N}-evals.json`)**

Same format as existing eval files. Each expectation targets a specific SKILL.md pattern that differentiates with-skill from without-skill output.

```json
{
  "phase": "22-drush-skill-eval",
  "skills_tested": ["drupal-drush", "drupal-module-scaffold"],
  "evals": [{
    "id": 1,
    "prompt": "...",
    "expected_output": "...",
    "expectations": [
      "Drush command class is in src/Drush/Commands/ (not src/Command/ or src/Commands/) because Drush 12+ requires the Drush/ subdirectory for command discovery",
      "..."
    ]
  }]
}
```

**2. Runtime assertions (`phase-{N}-runtime-assertions.json`)**

Same format as existing. Uses `drush php-eval` and `ddev drush` commands.

```json
{
  "phase": "22-drush-skill-eval",
  "runtime_assertions": [{
    "id": "rt-1",
    "name": "Module still installs",
    "command": "ddev drush cr && ddev drush pm:list ...",
    "expected": "PASS",
    "rationale": "..."
  }]
}
```

**3. Browser assertions (`phase-{N}-browser-assertions.json`)** (when applicable)

New format for eval-browser. Not all phases need browser assertions.

```json
{
  "phase": "24-ai-task-creation",
  "browser_assertions": [{
    "id": "br-1",
    "name": "AI create form renders",
    "url": "/admin/content/project/1/ai-create",
    "check": "Page contains form with 'Describe the task' textarea",
    "rationale": "..."
  }]
}
```

### Agent Workflow

The eval-author follows this internal process:

1. **Read the phase prompt** -- understand what code will be generated
2. **Read each tested SKILL.md** -- identify non-obvious patterns (the kind that Haiku misses without guidance)
3. **Read previous phase results** -- identify patterns that previously discriminated (passed WITH, failed WITHOUT)
4. **Design static assertions** -- 12-18 assertions per phase targeting SKILL.md-specific patterns
5. **Design runtime assertions** -- 10-15 drush-based checks for functional correctness
6. **Design browser assertions** -- 3-5 checks for UI-visible behavior (when applicable)
7. **Write all three JSON files**

### Quality Criteria for Assertions

The eval-author must follow these rules (encode in agent prompt):

- **Target non-obvious patterns.** "Module has an info.yml" is worthless. "info.yml uses `^10 || ^11`" (from scaffold skill) is good. "Drush command is in `src/Drush/Commands/`" (non-obvious directory) is excellent.
- **Explain the reasoning in parenthetical.** Every expectation includes `(reason this matters -- skill reference)`. This is the existing convention across all eval files.
- **Runtime assertions check WIRING, not just existence.** "Controller class exists" is weak. "Controller board() method executes without exception" (actually runs code) is strong. This was the key insight from v4.0 Phase 18 rt-15.
- **Flexible naming in runtime assertions.** Check multiple naming conventions (e.g., `group_ai_pm.board`, `group_ai_pm.kanban`, `group_ai_pm.project.board`). This prevents false failures from naming differences between with/without runs.
- **No assertion should test standard Drupal patterns.** If Haiku produces it without a skill, the assertion has zero discriminatory value.

### Tool Access

The eval-author needs:
- **Read** -- to read SKILL.md files, previous results, existing module code
- **Bash** -- ONLY for `ls` and file path discovery (NOT for running ddev or modifying files)
- **Glob** -- to find skill files, eval files
- **Grep** -- to search for patterns in existing code and skills

It should NOT have Write or Edit tools. It writes output via Bash `echo > file` or the orchestrator captures its output and writes files. Actually, on reflection, the agent needs Write to produce the JSON output files. Revising:

- **Read, Write, Bash, Glob, Grep** -- standard agent toolset
- No ddev access needed (it designs assertions, does not execute them)

## Component 3: AI Feature Integration

### Architecture Decision: Service Layer, Not Controller Expansion

The existing `TaskApiController` handles CRUD operations directly in controller methods. AI features should NOT be added to this controller. Instead, create a `AiTaskService` that the AI tools and a new `AiTaskController` both consume.

**Rationale:**
1. AI operations (NLP parsing, provider calls) are reusable across UI endpoints and AI agent tools
2. The existing `TaskApiController` is already 383 lines -- it should not grow further
3. AI module dependency is optional -- the service can be conditionally available
4. Testability: services are easier to unit test than controllers

### Service Layer Design

```
src/Service/
  AiTaskService.php       # Core AI logic, injected with ai.provider
```

```php
namespace Drupal\group_ai_pm\Service;

use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;

class AiTaskService {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ?object $aiProvider,  // Optional -- NULL if AI module not installed
    protected readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Parse natural language into task fields.
   *
   * @param string $text
   *   Natural language description like "Fix the login bug by Friday, high priority, assign to Tom"
   * @param int $project_id
   *   The project context.
   *
   * @return array
   *   Parsed fields: title, description, priority, due_date, assignee_name.
   */
  public function parseNaturalLanguage(string $text, int $project_id): array;

  /**
   * Suggest assignee based on task content and project history.
   */
  public function suggestAssignee(string $title, int $project_id): ?int;

  /**
   * Batch process multiple tasks with AI operations.
   */
  public function batchProcess(array $task_ids, string $operation): array;
}
```

### Service Registration (Conditional AI Dependency)

The AI module is optional. The service must work without it (returning empty results or throwing a clear exception).

```yaml
# group_ai_pm.services.yml (NEW FILE)
services:
  group_ai_pm.ai_task:
    class: Drupal\group_ai_pm\Service\AiTaskService
    arguments:
      - '@entity_type.manager'
      - '@?ai.provider'       # ? prefix = optional, resolves to NULL if missing
      - '@config.factory'
```

**Critical:** The `@?` prefix for optional service injection is the correct Drupal pattern. Without it, the module crashes when the AI module is not installed.

**Confidence:** MEDIUM -- the `@?` optional service syntax is standard Symfony DI. Verified that Drupal 10.6 supports it. The `ai.provider` service name needs verification against the actual AI module (it may be `plugin.manager.ai_provider` instead).

### Controller Integration

New API endpoints for AI features go in a NEW controller, not the existing TaskApiController.

```
src/Controller/
  AiTaskController.php    # Handles AI-specific REST endpoints
```

Routes:

```yaml
# New routes (added to group_ai_pm.routing.yml)
group_ai_pm.api.ai_create:
  path: '/api/kanban/project/{project}/ai-create'
  defaults:
    _controller: '\Drupal\group_ai_pm\Controller\AiTaskController::aiCreate'
  methods: [POST]
  requirements:
    _permission: 'access group_ai_pm dashboard'
    _csrf_request_header_token: 'TRUE'
    _format: json
  options:
    _admin_route: TRUE
    parameters:
      project:
        type: entity:project

group_ai_pm.api.ai_suggest_assignee:
  path: '/api/kanban/task/{task}/suggest-assignee'
  defaults:
    _controller: '\Drupal\group_ai_pm\Controller\AiTaskController::suggestAssignee'
  methods: [GET]
  requirements:
    _permission: 'access group_ai_pm dashboard'
    _format: json
  options:
    _admin_route: TRUE
    parameters:
      task:
        type: entity:task

group_ai_pm.api.ai_batch:
  path: '/api/kanban/project/{project}/ai-batch'
  defaults:
    _controller: '\Drupal\group_ai_pm\Controller\AiTaskController::batchProcess'
  methods: [POST]
  requirements:
    _permission: 'administer group_ai_pm'
    _csrf_request_header_token: 'TRUE'
    _format: json
  options:
    _admin_route: TRUE
    parameters:
      project:
        type: entity:project
```

### AI Agent Tool Expansion

The existing `group_ai_pm_ai` submodule has 2 tools. v5.0 adds more:

```
modules/group_ai_pm_ai/src/Plugin/AiFunctionCall/
  CreateProjectTool.php       # EXISTS
  QueryProjectsTool.php       # EXISTS
  CreateTaskTool.php          # NEW -- create task from structured or NL input
  UpdateTaskStatusTool.php    # NEW -- change task status
  SuggestAssigneeTool.php     # NEW -- suggest best assignee
  BatchUpdateTool.php         # NEW -- batch operations on multiple tasks
```

Each new tool follows the existing pattern: extends `AiFunctionCallBase`, implements `ContainerFactoryPluginInterface`, injects `entity_type.manager` and `group_ai_pm.ai_task` service, defines `getArguments()` and `execute()`.

### Drupal AI Module Integration Pattern

Based on verified API research, the AI call pattern is:

```php
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;

// Get configured provider from settings
$provider_id = $this->configFactory->get('group_ai_pm.settings')->get('ai_provider');
$provider = $this->aiProviderManager->createInstance($provider_id);

// Build chat input with system prompt
$provider->setChatSystemRole('You are a project management assistant. Parse task descriptions into structured fields.');

$input = new ChatInput([
  new ChatMessage('user', $natural_language_text),
]);

$output = $provider->chat($input, $model_id, ['group_ai_pm']);
$parsed = json_decode($output->getNormalized()->getText(), TRUE);
```

The `ai_provider` setting already exists in `SettingsForm.php` (line 57-65). The v5.0 work extends this to also store a model ID and system prompt template.

### Vue Frontend Extensions

AI features surface in the existing Vue Kanban board. New components:

```
js/src/components/
  AiCreateDialog.vue       # NEW -- modal with NL text input, shows parsed preview
  AiSuggestBadge.vue       # NEW -- small badge on task card showing AI suggestion
  BatchActionBar.vue       # NEW -- toolbar for multi-select + AI batch operations
```

New composable:

```
js/src/composables/
  useAi.js                 # NEW -- AI API calls, NL parsing, suggestion fetching
```

These integrate with the existing `useKanban.js` composable. The AI create dialog is triggered from QuickCreateForm.vue (an "AI Create" button alongside the existing title input).

## Component 4: Task History (Analytics)

### Architecture Decision: New Entity Type (Not Custom Table, Not Fields)

**Option A: Fields on existing Task entity** -- REJECTED. History needs multiple records per task. Adding a multi-value field would bloat the Task entity and make querying inefficient.

**Option B: Custom database table** -- REJECTED. Loses Entity API benefits (access control, Views integration, cache tags). The database-api skill covers raw tables, but entity storage is the Drupal way for data that needs Views integration.

**Option C: New TaskHistory content entity** -- CHOSEN. Clean separation, native Views integration, standard Entity API patterns.

### Entity Design

```php
/**
 * @ContentEntityType(
 *   id = "task_history",
 *   label = @Translation("Task History"),
 *   base_table = "group_ai_pm_task_history",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   handlers = {
 *     "views_data" = "Drupal\group_ai_pm\Entity\TaskHistoryViewsData",
 *   },
 * )
 */
class TaskHistory extends ContentEntityBase implements TaskHistoryInterface {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Reference to the task this event is about
    $fields['task'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Task'))
      ->setSetting('target_type', 'task')
      ->setRequired(TRUE);

    // What changed: 'status_change', 'assignment_change', 'priority_change', 'created', 'deleted'
    $fields['event_type'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Event Type'))
      ->setRequired(TRUE);

    // Previous value (e.g., 'todo')
    $fields['old_value'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Old Value'));

    // New value (e.g., 'in_progress')
    $fields['new_value'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('New Value'));

    // Who made the change
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('User'))
      ->setSetting('target_type', 'user');

    // When it happened
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'));

    return $fields;
  }
}
```

### History Recording

History events are recorded via `hook_entity_presave()` and `hook_entity_insert()` in `group_ai_pm.module`. NOT via entity hooks in the Task entity class (hooks in .module are the standard Drupal pattern for cross-cutting concerns).

```php
/**
 * Implements hook_entity_presave().
 */
function group_ai_pm_entity_presave(EntityInterface $entity) {
  if ($entity->getEntityTypeId() !== 'task' || $entity->isNew()) {
    return;
  }
  // Compare original vs new values, queue history records.
  $original = $entity->original;
  if ($original->getStatus() !== $entity->getStatus()) {
    _group_ai_pm_record_history($entity, 'status_change', $original->getStatus(), $entity->getStatus());
  }
  // ... similar for priority, assignee
}
```

### Views Integration

`TaskHistoryViewsData` extends `EntityViewsData` to expose the history table to Views. This enables:
- Task activity timeline view
- Project-level analytics (tasks completed per week, average time in status)
- Per-user activity reports

### Analytics API Endpoint

A new GET endpoint surfaces aggregated analytics:

```yaml
group_ai_pm.api.task_analytics:
  path: '/api/kanban/project/{project}/analytics'
  defaults:
    _controller: '\Drupal\group_ai_pm\Controller\AnalyticsController::projectAnalytics'
  methods: [GET]
  requirements:
    _permission: 'access group_ai_pm dashboard'
    _format: json
  options:
    _admin_route: TRUE
    parameters:
      project:
        type: entity:project
```

## Component 5: Skill Gap Fixes

### What Changes and Where

Three existing skills get targeted patches. These are small edits, not rewrites.

| Skill | Gap | Fix | Lines Affected |
|-------|-----|-----|---------------|
| drupal-entities-fields | No `bundle_of` coverage | Add section on bundle entity types with `bundle_of` key | ~25 lines added |
| drupal-caching | No `lazy_builder` coverage | Add section on `#lazy_builder` + `CacheableMetadata::createFromRenderArray()` | ~30 lines added |
| drupal-forms-api | Description mentions AJAX but body has no `#ajax` content | Add `#ajax` section with callback, wrapper, effect patterns | ~40 lines added |

These patches stay within the <500 line SKILL.md budget. Each skill currently uses 300-400 lines, leaving room.

## Build Order and Dependencies

```
Phase 22: Drush Skill + Eval-Author Agent
  Depends on: nothing (pure skill/agent creation)
  Unlocks: eval-author can generate assertions for all subsequent phases

Phase 23: Skill Gap Fixes + Eval-Author Validation
  Depends on: Phase 22 (eval-author agent is available)
  What: Patch 3 skills, run eval-author on skill evals, validate agent output
  Why here: skill fixes affect all subsequent eval measurements

Phase 24: AI Task Service + NL Creation
  Depends on: Phase 23 (patched skills, validated eval-author)
  What: AiTaskService, AiTaskController, NL parsing endpoint, Vue AI dialog
  Eval: eval-author designs assertions, standard A/B pipeline

Phase 25: AI Agent Tools + Batch Operations
  Depends on: Phase 24 (AiTaskService exists for tools to consume)
  What: 4 new AiFunctionCall plugins, batch endpoint, Vue batch UI
  Eval: eval-author designs assertions

Phase 26: Task History Entity + Analytics
  Depends on: Phase 24 (task mutation hooks need the AI service layer in place)
  What: TaskHistory entity, hooks, Views integration, analytics endpoint
  Eval: eval-author designs assertions

Phase 27: Cross-Cutting Eval + Final Report
  Depends on: Phases 22-26 all complete
  What: eval-author generates comprehensive cross-cutting eval, full A/B pipeline run
  Produces: final v5.0 report with aggregate delta
```

### Why This Order

1. **Drush + eval-author first** because they are pure tooling with no module dependencies, and every subsequent phase benefits from automated eval design.
2. **Skill gaps second** because patching skills changes eval baselines -- do it before measuring AI features.
3. **AI service before AI tools** because tools consume the service. The service layer also establishes the AI module integration pattern that tools extend.
4. **History entity last among module work** because it records changes to tasks -- it needs the AI-driven task mutations to exist so history captures them.
5. **Cross-cutting eval last** because it measures the cumulative effect of all v5.0 changes.

## Data Flow Diagrams

### AI Task Creation Flow

```
User types NL text in AiCreateDialog.vue
  |
  v
useAi.js composable calls POST /api/kanban/project/{pid}/ai-create
  |
  v
AiTaskController::aiCreate() receives text
  |
  v
AiTaskService::parseNaturalLanguage() builds ChatInput
  |
  v
AI Provider (via ai.provider plugin manager) sends chat completion
  |
  v
AI returns structured JSON: {title, description, priority, due_date, assignee_name}
  |
  v
AiTaskService resolves assignee_name to user ID
  |
  v
AiTaskController creates Task entity with parsed fields
  |
  v
hook_entity_insert() records TaskHistory 'created' event
  |
  v
JSON response with serialized task -> Vue updates board optimistically
```

### Eval-Author Flow

```
Orchestrator provides: phase prompt + skills list + output paths
  |
  v
eval-author agent reads SKILL.md files for listed skills
  |
  v
eval-author reads previous phase results (if any)
  |
  v
eval-author reads existing module code (modules/group_ai_pm/)
  |
  v
For each skill pattern: classify as obvious (Haiku knows) vs non-obvious (skill-dependent)
  |
  v
Generate static expectations targeting non-obvious patterns (12-18 per phase)
  |
  v
Generate runtime assertions with drush php-eval (10-15 per phase)
  |
  v
Generate browser assertions if UI-facing (3-5 per phase, optional)
  |
  v
Write three JSON files to eval/v5/
```

### Task History Recording Flow

```
Task entity saved (create, update, or delete)
  |
  v
hook_entity_presave() fires (for updates)
  |
  v
Compare $entity->original vs $entity for each tracked field
  |
  v
For each changed field: create TaskHistory entity
  {task: $entity->id(), event_type: 'status_change',
   old_value: 'todo', new_value: 'in_progress',
   uid: current_user, created: time()}
  |
  v
TaskHistory::save() -> stored in group_ai_pm_task_history table
  |
  v
Cache tag invalidation: task_history_list (for Views displays)
```

## Anti-Patterns to Avoid

### Anti-Pattern 1: AI Logic in Controllers
**What:** Putting AI provider calls directly in controller methods.
**Why bad:** Duplicates logic between REST endpoints and AI agent tools. Makes testing impossible without HTTP mocking.
**Instead:** All AI logic goes in `AiTaskService`. Controllers and AI tools both inject and call the service.

### Anti-Pattern 2: Hard AI Module Dependency
**What:** `group_ai_pm.info.yml` listing `drupal_ai:ai` as a dependency.
**Why bad:** Module becomes uninstallable without the AI module. The existing module works fine without AI -- don't break that.
**Instead:** AI module is an optional dependency. Use `@?ai.provider` in services.yml. Check for NULL before making AI calls. AI-specific tools live in the existing `group_ai_pm_ai` submodule.

### Anti-Pattern 3: Task History via Entity Hooks
**What:** Implementing history recording in `Task::preSave()` or `Task::postSave()`.
**Why bad:** Entity class should not have cross-cutting logging concerns. Makes testing the entity class harder.
**Instead:** Use `hook_entity_presave()` in `.module` file. This is standard Drupal practice for cross-cutting entity operations (per Sipos Ch. 6).

### Anti-Pattern 4: Monolithic Eval Author Prompt
**What:** Giving the eval-author agent a single huge prompt with all instructions inline.
**Why bad:** Context length issues, no reusability, hard to iterate.
**Instead:** The agent MD file contains the methodology. The orchestrator provides only phase-specific inputs (prompt, skills, paths). The agent applies its methodology to the inputs.

### Anti-Pattern 5: Custom Table for History
**What:** Using `hook_schema()` to create a raw database table for task history.
**Why bad:** Loses Entity API benefits: no access control, no Views integration, no cache tags, no revision support.
**Instead:** TaskHistory as a content entity with ViewsData handler.

## Patterns to Follow

### Pattern 1: Optional Service Injection
**What:** Using `@?service_name` in services.yml for optional dependencies.
**When:** Any service that depends on a module that may not be installed.
**Example:**
```yaml
services:
  group_ai_pm.ai_task:
    class: Drupal\group_ai_pm\Service\AiTaskService
    arguments: ['@entity_type.manager', '@?ai.provider', '@config.factory']
```

### Pattern 2: Submodule for Hard Dependencies
**What:** Keeping AI agent tools in `group_ai_pm_ai` submodule.
**When:** Code that REQUIRES a specific contrib module (ai_agents).
**Why:** Submodule declares the hard dependency. Parent module stays optional.
**Existing example:** `modules/group_ai_pm_ai/group_ai_pm_ai.info.yml` already does this.

### Pattern 3: Entity ViewsData Handler
**What:** Custom ViewsData class for entity Views integration.
**When:** Entity needs custom Views field plugins or non-standard join relationships.
**Example:** `TaskHistoryViewsData` extends `EntityViewsData` to add a computed "duration in status" field.

### Pattern 4: Eval Assertion Layering
**What:** Three-tier assertions (static + runtime + browser) with each tier testing different concerns.
**When:** Every eval phase.
**Why:**
- Static: tests code patterns (file structure, class signatures, annotation content)
- Runtime: tests wiring (DI resolves, routes registered, entities saveable)
- Browser: tests UI behavior (page renders, interactions work)

### Pattern 5: Segregated AI Endpoints
**What:** AI-specific endpoints in their own controller, not mixed with CRUD endpoints.
**When:** Adding AI features to a module that already has REST endpoints.
**Why:** Different rate limiting needs, different error handling (AI timeouts vs validation errors), different permission levels (admin-only batch operations vs regular dashboard access).

## Scalability Considerations

| Concern | Current (100 tasks) | At 1K tasks | At 10K tasks |
|---------|---------------------|-------------|-------------|
| Task history table size | Negligible | ~10K rows | ~100K rows |
| AI API calls per NL create | 1 call | Same | Same (per-task, not batch) |
| History Views queries | Fast | Add index on task + created | Aggregate views may need caching |
| Batch AI operations | Sync OK | Sync OK | Queue + QueueWorker |

At 10K+ tasks, batch AI operations should use the Queue API (existing `group_ai_pm_overdue_notifications` worker is the pattern). For the v5.0 scope, synchronous processing is sufficient.

## File-Level Change Map

### New Files

| File | Component | Purpose |
|------|-----------|---------|
| `skills/drupal-drush/SKILL.md` | Drush Skill | Drush command creation patterns |
| `skills/drupal-drush/references/drush-generators.md` | Drush Skill | Overflow: drush generate |
| `skills/drupal-drush/evals/evals.json` | Drush Skill | A/B eval for Drush commands |
| `.claude/agents/eval-author.md` | Eval-Author | Agent specification |
| `modules/group_ai_pm/group_ai_pm.services.yml` | AI Service | Service definitions |
| `modules/group_ai_pm/src/Service/AiTaskService.php` | AI Service | AI logic service |
| `modules/group_ai_pm/src/Controller/AiTaskController.php` | AI API | REST endpoints for AI features |
| `modules/group_ai_pm/src/Controller/AnalyticsController.php` | Analytics | Task history analytics endpoints |
| `modules/group_ai_pm/src/Entity/TaskHistory.php` | History | Task history entity |
| `modules/group_ai_pm/src/Entity/TaskHistoryInterface.php` | History | Entity interface |
| `modules/group_ai_pm/src/Entity/TaskHistoryViewsData.php` | History | Views integration |
| `modules/group_ai_pm/modules/group_ai_pm_ai/src/Plugin/AiFunctionCall/CreateTaskTool.php` | AI Tools | NL task creation tool |
| `modules/group_ai_pm/modules/group_ai_pm_ai/src/Plugin/AiFunctionCall/UpdateTaskStatusTool.php` | AI Tools | Status update tool |
| `modules/group_ai_pm/modules/group_ai_pm_ai/src/Plugin/AiFunctionCall/SuggestAssigneeTool.php` | AI Tools | Assignee suggestion tool |
| `modules/group_ai_pm/modules/group_ai_pm_ai/src/Plugin/AiFunctionCall/BatchUpdateTool.php` | AI Tools | Batch operations tool |
| `modules/group_ai_pm/js/src/components/AiCreateDialog.vue` | Vue AI | NL create modal |
| `modules/group_ai_pm/js/src/components/BatchActionBar.vue` | Vue AI | Batch toolbar |
| `modules/group_ai_pm/js/src/composables/useAi.js` | Vue AI | AI API composable |
| `eval/v5/` (directory) | Eval | v5.0 eval results |

### Modified Files

| File | Component | Change |
|------|-----------|--------|
| `skills/drupal-entities-fields/SKILL.md` | Skill Gap | Add `bundle_of` section (~25 lines) |
| `skills/drupal-caching/SKILL.md` | Skill Gap | Add `lazy_builder` section (~30 lines) |
| `skills/drupal-forms-api/SKILL.md` | Skill Gap | Add `#ajax` section (~40 lines) |
| `modules/group_ai_pm/group_ai_pm.module` | History | Add hook_entity_presave/insert for history recording |
| `modules/group_ai_pm/group_ai_pm.routing.yml` | AI + Analytics | Add 4 new routes |
| `modules/group_ai_pm/group_ai_pm.permissions.yml` | AI | Add 'use group_ai_pm ai features' permission |
| `modules/group_ai_pm/group_ai_pm.info.yml` | History | No change needed (no new hard deps) |
| `.claude-plugin/plugin.json` | Plugin | Update version to 5.0.0 |

### Unchanged Files

All existing controllers, entity classes, Vue components, and templates remain unchanged. AI features are additive -- they sit alongside existing CRUD operations without modifying them.

## Sources

- [Drush 13.x Command Authoring](https://www.drush.org/13.x/commands/) -- HIGH confidence, official docs
- [Drupal AI Module Documentation](https://project.pages.drupalcode.org/ai/) -- MEDIUM confidence, verified API patterns
- [AI Chat Call API](https://project.pages.drupalcode.org/ai/1.1.x/developers/call_chat/) -- MEDIUM confidence, 1.1.x docs, may differ in 1.2.x
- [Claude Code Sub-Agent Best Practices](https://claudefa.st/blog/guide/agents/sub-agent-best-practices) -- MEDIUM confidence, community patterns
- [Drupal AI Module Project Page](https://www.drupal.org/project/ai) -- HIGH confidence for provider list and capabilities
- [Custom Drush Commands with Drush Generate](https://www.fourkitchens.com/blog/development/custom-drush-commands-drush-generate/) -- MEDIUM confidence, 2025 tutorial
- [Building a Custom AI Provider Module for Drupal](https://www.thedroptimes.com/44275/building-custom-ai-provider-module-drupal-practical-guide) -- MEDIUM confidence, provider implementation details

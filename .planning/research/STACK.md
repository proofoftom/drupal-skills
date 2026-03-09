# Technology Stack: v5.0 AI Integration & Eval Tooling

**Project:** Drupal Skills / group_ai_pm module
**Researched:** 2026-03-09
**Focus:** NEW stack additions for (1) drupal-drush skill, (2) eval-author Opus subagent, (3) AI-powered task features, (4) task history analytics
**Overall confidence:** MEDIUM-HIGH -- Drush 13 and AI module APIs verified via official docs; eval-author agent pattern extrapolated from existing eval-grader; analytics schema uses well-established Drupal Database API patterns

## Context: What Already Exists (DO NOT ADD)

The v4.0 stack is validated and in production:

| Component | Version | Status |
|-----------|---------|--------|
| Drupal core | ^10.6 | In composer.json |
| Group | ^3.3 | In composer.json |
| AI module | 1.2.11 | In composer.lock |
| AI Agents | 1.3.0-beta2 | In composer.lock |
| Key module | 1.22.0 | In composer.lock (AI dependency) |
| Drush | 13.7.1 | In composer.lock |
| Vue 3 | ^3.5.0 | In package.json |
| vue-draggable-plus | ^0.6.0 | In package.json |
| SortableJS | ^1.15.0 | In package.json |
| tinykeys | ^3.0.0 | In package.json |
| Vite | ^6.0.0 | In package.json (devDep) |
| Custom REST controllers | -- | In routing.yml (7 API routes) |
| Custom entities (Project, Task) | -- | In src/Entity/ |
| AiFunctionCall plugins | -- | In modules/group_ai_pm_ai/ |

**No new composer or npm packages are needed for v5.0.** All four feature areas use existing dependencies or Drupal core APIs. This is the key finding.

---

## 1. Drupal-Drush Skill: Stack Requirements

### What It Is

A new skill file at `skills/drupal-drush/SKILL.md` teaching Claude to write Drush commands and use Drush for runtime assertions. No new code dependencies -- this is a knowledge artifact.

### Drush 13 Command Architecture (Already Installed)

Drush 13.7.1 is already in composer.lock. The skill teaches these patterns:

| Pattern | Details | Confidence |
|---------|---------|------------|
| **File location** | `src/Drush/Commands/` (NOT `src/Commands/`, NOT root `drush/`) | HIGH -- verified via [Drush 13 docs](https://www.drush.org/13.4.0/commands/) |
| **Base class** | `extends DrushCommands` | HIGH |
| **DI pattern** | `use AutowireTrait;` with constructor injection (NOT drush.services.yml, NOT create()) | HIGH -- [Drush 13 DI docs](https://www.drush.org/13.x/dependency-injection/) confirm autowire is preferred, services.yml deprecated |
| **Command declaration** | `#[CLI\Command(name: 'module:action')]` PHP 8 attribute | HIGH -- verified via official docs |
| **Arguments** | `#[CLI\Argument(name: 'arg', description: '...')]` | HIGH |
| **Options** | `#[CLI\Option(name: 'opt', description: '...')]` | HIGH |
| **Aliases** | `aliases: ['shortcut']` param in Command attribute | HIGH |
| **Usage examples** | `#[CLI\Usage(name: 'drush module:action foo', description: '...')]` | HIGH |
| **Output** | `$this->io()->writeln()` via SymfonyStyle | HIGH |

### Key Drush 13 Patterns for the Skill

```php
namespace Drupal\my_module\Drush\Commands;

use Drush\Attributes as CLI;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;
use Drupal\Core\Entity\EntityTypeManagerInterface;

final class MyModuleCommands extends DrushCommands {
  use AutowireTrait;

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct();
  }

  #[CLI\Command(name: 'my-module:list-items', aliases: ['mm:list'])]
  #[CLI\Argument(name: 'type', description: 'Entity type to list')]
  #[CLI\Option(name: 'limit', description: 'Max items', suggestedValues: [10, 50, 100])]
  #[CLI\Usage(name: 'drush my-module:list-items task --limit=10', description: 'List 10 tasks')]
  public function listItems(string $type, array $options = ['limit' => 50]): void {
    $storage = $this->entityTypeManager->getStorage($type);
    $ids = $storage->getQuery()->accessCheck(TRUE)->range(0, $options['limit'])->execute();
    $this->io()->writeln(count($ids) . " items found.");
  }
}
```

### Critical Wrong-Way Patterns for Drush Skill

| Wrong | Right | Why |
|-------|-------|-----|
| `src/Commands/` directory | `src/Drush/Commands/` | Drush 13 requires the `Drush` subdirectory -- commands in old location are NOT discovered |
| `drush.services.yml` for DI | `use AutowireTrait;` in class | Services file is deprecated in Drush 12+, will be removed in Drush 14 |
| `create()` factory method | Constructor type-hint injection | AutowireTrait resolves services from constructor parameter types automatically |
| `@command` annotation in docblock | `#[CLI\Command()]` attribute | PHP 8 attributes are preferred for PHP 8+; annotations still work but are legacy |
| `use Drush\Commands\DrushCommands;` only | Also `use Drush\Attributes as CLI;` | Missing the attribute import causes all command metadata to be invisible |

### Runtime Assertion Patterns (Skill Content)

The Drush skill should cover these eval-relevant patterns:

```bash
# Module status check
ddev drush pm:list --status=enabled --format=json | grep module_name

# Config inspection
ddev drush config:get module.settings key_name

# Entity count
ddev drush php-eval "echo \Drupal::entityQuery('task')->accessCheck(FALSE)->count()->execute();"

# Route verification
ddev drush php-eval "\$route = \Drupal::service('router.route_provider')->getRouteByName('route.name'); echo \$route->getPath();"

# Permission check
ddev drush php-eval "echo \Drupal::service('user.permissions')->getPermissions()['perm_name']['title'] ?? 'NOT FOUND';"

# Queue inspection
ddev drush php-eval "\$q = \Drupal::queue('queue_name'); echo 'Items: ' . \$q->numberOfItems();"

# Service resolution (tests DI wiring)
ddev drush php-eval "echo get_class(\Drupal::service('my_module.service'));"
```

### No New Dependencies

The Drush skill is a pure knowledge artifact. Drush 13.7.1 is already installed. The skill covers:
- Command authoring (PHP attributes, AutowireTrait)
- Common Drush commands for runtime assertions (php-eval, config:get, pm:list)
- Drush testing patterns (DrushTestTrait for functional tests)
- drush_backend_batch_process() for Batch API from CLI

---

## 2. Eval-Author Opus Subagent: Stack Requirements

### What It Is

A Claude Code subagent (`.claude/agents/eval-author.md`) that designs three-tier eval assertions for each phase. Uses the existing subagent infrastructure -- no new tools or dependencies.

### Subagent Specification

| Attribute | Value | Rationale |
|-----------|-------|-----------|
| **File** | `.claude/agents/eval-author.md` | Project-level agent, checked into VCS |
| **Model** | `opus` | Eval design requires deep reasoning about what patterns are non-obvious, what Haiku will miss, and what the skill uniquely teaches. Sonnet and Haiku produce shallow assertions. |
| **Tools** | `Read, Glob, Grep, Bash` | Read-only access to skills, evals, and module code. Bash for exploring file structure. No Write/Edit -- it returns assertions, orchestrator writes them. |
| **permissionMode** | `bypassPermissions` | Consistent with existing eval agents. Subagent needs free file read access. |
| **Confidence** | HIGH -- follows exact same pattern as eval-grader.md |

### Subagent Frontmatter Format

```yaml
---
name: eval-author
description: |
  Design three-tier eval assertions (static, runtime, browser) for Drupal
  module development phases. Reads skill files, existing evals, and module code
  to produce assertions that measure skill impact on code generation quality.
  Use when starting a new eval phase.
model: opus
permissionMode: bypassPermissions
tools: Read, Glob, Grep, Bash
---
```

### What the Eval-Author Produces (Output Format)

The agent outputs JSON matching the existing eval structure:

```json
{
  "phase": "N-description",
  "skills_tested": ["drupal-caching", "drupal-routing-controllers"],
  "evals": [{
    "id": 1,
    "prompt": "...",
    "expected_output": "...",
    "expectations": [
      "Static assertion targeting non-obvious SKILL.md pattern...",
      "(via ddev exec) Runtime assertion using drush php-eval...",
      "(via eval-browser) Browser assertion checking rendered output..."
    ]
  }],
  "runtime_assertions": [{
    "id": "rt-1",
    "name": "descriptive name",
    "command": "ddev drush php-eval \"...\"",
    "expected": "PASS",
    "rationale": "why this tests skill impact"
  }]
}
```

### Key Design Principles (System Prompt Content)

The eval-author's system prompt must encode these validated learnings:

1. **Target non-obvious patterns** -- assertions should test things the SKILL.md uniquely teaches, not standard Drupal knowledge Haiku already knows
2. **The best static assertions explain WHY** -- each expectation string includes parenthetical rationale (see phase-18-evals.json for the gold standard)
3. **Runtime assertions test WIRING not existence** -- `drush php-eval` that actually executes code catches DI failures, method signature errors, and service resolution bugs that grep-based assertions miss
4. **Browser assertions test RENDERED output** -- verify the page actually renders with correct content, not just that templates exist
5. **Three-tier coverage** -- every phase should have all three: static (code patterns), runtime (functional), browser (visual)

### Input Context for Eval-Author

The subagent needs access to:

| Input | Path | Purpose |
|-------|------|---------|
| Skill files | `skills/drupal-*/SKILL.md` | Know what patterns are "non-obvious" |
| Previous evals | `eval/v*/phase-*-evals.json` | Learn assertion writing style |
| Previous results | `eval/v*/phase-*-results-*.json` | See what passed/failed and why |
| Module code | `modules/group_ai_pm/` | Understand current module state |
| Phase plan | `.planning/phases/*/plan.md` | Know what this phase builds |

### No New Dependencies

The eval-author uses the same subagent infrastructure as eval-grader and eval-browser. The only new artifact is the `.md` file defining the agent.

---

## 3. AI-Powered Task Features: Stack Requirements

### What Already Exists

The module already has AI integration infrastructure:

| Component | Location | Status |
|-----------|----------|--------|
| AI submodule | `modules/group_ai_pm/modules/group_ai_pm_ai/` | Exists, has .info.yml |
| CreateProjectTool | `src/Plugin/AiFunctionCall/CreateProjectTool.php` | Working, annotated |
| QueryProjectsTool | `src/Plugin/AiFunctionCall/QueryProjectsTool.php` | Working, annotated |
| AI module | `drupal/ai` 1.2.11 | In composer.lock |
| AI Agents | `drupal/ai_agents` 1.3.0-beta2 | In composer.lock |
| Key module | `drupal/key` 1.22.0 | In composer.lock |

### AI Module API Surface (for New Features)

Use the `ai.provider` service for all AI interactions. The API is provider-agnostic:

```php
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;

// Get the default chat provider
$sets = \Drupal::service('ai.provider')->getDefaultProviderForOperationType('chat');
$provider = \Drupal::service('ai.provider')->createInstance($sets['provider_id']);

// Create chat input with system prompt
$input = new ChatInput([
  new ChatMessage('user', 'Create a task titled "Fix login bug" with high priority'),
]);
$input->setSystemPrompt('You are a project management assistant...');

// Call the provider
$response = $provider->chat($input, $sets['model_id'], ['group_ai_pm']);
$text = $response->getNormalized()->getText();
```

### New AiFunctionCall Plugins Needed

Following the existing `CreateProjectTool` pattern (annotation-based, ContainerFactoryPluginInterface):

| Plugin | Purpose | Arguments |
|--------|---------|-----------|
| `CreateTaskTool` | AI creates task from natural language | title, description, status, priority, project_id, assignee_uid |
| `UpdateTaskStatusTool` | AI changes task status | task_id, new_status |
| `AssignTaskTool` | AI assigns task to user | task_id, assignee_uid |
| `QueryTasksTool` | AI searches tasks | project_id, status, priority, assignee, keyword |
| `BatchUpdateTool` | AI updates multiple tasks | task_ids[], field, value |
| `SuggestAssigneeTool` | AI recommends assignee based on workload | task_id |

### Plugin Pattern (Matches Existing Code)

```php
/**
 * @AiFunctionCall(
 *   id = "create_task_tool",
 *   label = @Translation("Create Task"),
 *   description = @Translation("Create a new task with title, status, priority, and assignment")
 * )
 */
class CreateTaskTool extends AiFunctionCallBase implements ContainerFactoryPluginInterface {
  // Same DI pattern as CreateProjectTool
  // execute() creates entity, returns confirmation string
  // getArguments() defines JSON Schema for function calling
}
```

### AI Chat Controller (New REST Endpoint)

A new controller for the chat interface that processes natural language and dispatches to AI:

```
POST /api/group-ai-pm/ai/chat
```

This route follows the existing API pattern:
- `_csrf_request_header_token: 'TRUE'` (consistent with existing mutation routes)
- `_format: json` (consistent with existing API routes)
- Returns `CacheableJsonResponse` with `max-age: 0` (AI responses are never cacheable)

### No New Composer Dependencies

All AI functionality uses `drupal/ai` 1.2.11 and `drupal/ai_agents` 1.3.0-beta2 already installed. The `ai.provider` service, `ChatInput`/`ChatMessage` classes, and `AiFunctionCallBase` plugin base are all available.

**Decision: Stay on AI 1.2.11, not 1.3.0-rc2.** AI 1.3.0-rc2 requires Drupal ^10.5 || ^11.2 (our template is 10.6, so it would work), but it's an RC not stable. The 1.2.11 API surface covers everything we need. Upgrade to 1.3.x stable when it ships -- it adds Markdown editor for prompts and expanded automators, neither of which we need yet.

**Decision: AI Agents 1.3.0-beta2 is acceptable.** Already in composer.lock and working. The beta status is for the full agent framework; the `AiFunctionCallBase` plugin API is stable and used by our existing tools.

---

## 4. Task History Analytics: Stack Requirements

### Database Schema (hook_schema)

Task history requires a custom database table. This uses the existing Drupal Database API -- no new dependencies.

**Table: `group_ai_pm_task_history`**

```php
function group_ai_pm_schema(): array {
  $schema['group_ai_pm_task_history'] = [
    'description' => 'Tracks task status changes and field modifications.',
    'fields' => [
      'id' => [
        'type' => 'serial',
        'unsigned' => TRUE,
        'not null' => TRUE,
      ],
      'task_id' => [
        'type' => 'int',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'description' => 'The task entity ID.',
      ],
      'project_id' => [
        'type' => 'int',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'description' => 'The project entity ID (denormalized for query performance).',
      ],
      'uid' => [
        'type' => 'int',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'description' => 'The user who made the change.',
      ],
      'field_name' => [
        'type' => 'varchar',
        'length' => 64,
        'not null' => TRUE,
        'description' => 'Which field changed (status, priority, assignee, title).',
      ],
      'old_value' => [
        'type' => 'varchar',
        'length' => 255,
        'description' => 'Previous field value.',
      ],
      'new_value' => [
        'type' => 'varchar',
        'length' => 255,
        'description' => 'New field value.',
      ],
      'created' => [
        'type' => 'int',
        'not null' => TRUE,
        'description' => 'Unix timestamp of the change.',
      ],
    ],
    'primary key' => ['id'],
    'indexes' => [
      'task_id' => ['task_id'],
      'project_id' => ['project_id'],
      'field_name' => ['field_name'],
      'created' => ['created'],
      'task_field' => ['task_id', 'field_name'],
    ],
  ];
  return $schema;
}
```

### Why a Custom Table (Not an Entity)

History records are append-only log data, not user-editable content. Custom tables via hook_schema() are the correct pattern because:

1. **No CRUD UI needed** -- history is read-only, displayed in views/analytics
2. **High insert frequency** -- every task field change inserts a row; entity overhead is wasteful
3. **Aggregate queries** -- analytics need COUNT/GROUP BY/date ranges, which Database API handles directly
4. **No revision/translation needs** -- bare data, not translatable content

This is the exact use case the drupal-database-api skill covers ("Custom tables for logs, statistics, integration data").

### History Recording Pattern

Hook into entity presave to capture changes:

```php
/**
 * Implements hook_entity_presave().
 */
function group_ai_pm_entity_presave(EntityInterface $entity) {
  if ($entity->getEntityTypeId() !== 'task' || $entity->isNew()) {
    return;
  }
  $original = $entity->original;
  $tracked_fields = ['status', 'priority', 'assignee', 'title'];
  foreach ($tracked_fields as $field) {
    $old = $original->get($field)->value ?? '';
    $new = $entity->get($field)->value ?? '';
    if ($old !== $new) {
      \Drupal::database()->insert('group_ai_pm_task_history')
        ->fields([
          'task_id' => $entity->id(),
          'project_id' => $entity->get('project')->target_id,
          'uid' => \Drupal::currentUser()->id(),
          'field_name' => $field,
          'old_value' => $old,
          'new_value' => $new,
          'created' => \Drupal::time()->getRequestTime(),
        ])
        ->execute();
    }
  }
}
```

### hook_update_N for Existing Installations

Since the module already exists, adding the table requires both hook_schema (for fresh installs) AND hook_update_N (for existing installs):

```php
/**
 * Create group_ai_pm_task_history table for analytics.
 */
function group_ai_pm_update_10001(&$sandbox): void {
  $schema = \Drupal::database()->schema();
  if (!$schema->tableExists('group_ai_pm_task_history')) {
    $schema->createTable('group_ai_pm_task_history', [
      // ... same schema definition as hook_schema
    ]);
  }
}
```

### Views Integration

Expose the history table to Views via `hook_views_data()`:

```php
function group_ai_pm_views_data(): array {
  $data['group_ai_pm_task_history'] = [
    'table' => [
      'group' => t('Task History'),
      'base' => [
        'field' => 'id',
        'title' => t('Task History'),
      ],
      'join' => [
        'group_ai_pm_task' => [
          'left_field' => 'id',
          'field' => 'task_id',
        ],
      ],
    ],
    // ... field definitions for each column
  ];
  return $data;
}
```

### Analytics API Endpoint

```
GET /api/group-ai-pm/project/{project}/analytics
```

Returns aggregated history data:
- Status change counts per time period
- Average time in each status
- Most active users
- Task velocity (completions per week)

Uses Database API aggregate queries -- no new dependencies.

### No New Dependencies

All analytics uses:
- `hook_schema()` / Database API (Drupal core)
- `hook_entity_presave()` (Drupal core)
- `hook_views_data()` (Drupal core Views module, already a dependency)
- Custom REST controller (existing pattern from v4.0)

---

## Complete Stack: v5.0 Additions Summary

### New Code Artifacts (No New Dependencies)

| Artifact | Type | Location |
|----------|------|----------|
| drupal-drush SKILL.md | Skill file | `skills/drupal-drush/SKILL.md` |
| eval-author.md | Subagent definition | `.claude/agents/eval-author.md` |
| AiFunctionCall plugins (6) | PHP plugin classes | `modules/group_ai_pm/modules/group_ai_pm_ai/src/Plugin/AiFunctionCall/` |
| AI chat controller | PHP controller | `modules/group_ai_pm/src/Controller/AiChatController.php` |
| AI chat route | YAML route | `group_ai_pm.routing.yml` addition |
| Task history schema | PHP install file | `modules/group_ai_pm/group_ai_pm.install` |
| Task history recording | PHP hook | `modules/group_ai_pm/group_ai_pm.module` addition |
| Analytics controller | PHP controller | `modules/group_ai_pm/src/Controller/AnalyticsController.php` |
| Analytics route | YAML route | `group_ai_pm.routing.yml` addition |
| Views data hook | PHP hook | `modules/group_ai_pm/group_ai_pm.module` addition |
| Skill gap fixes | Skill file edits | `skills/drupal-entities-fields/SKILL.md`, `skills/drupal-caching/SKILL.md`, `skills/drupal-forms-api/SKILL.md` |

### New Composer Dependencies: NONE

Everything builds on the existing stack. This is intentional -- v5.0 is about capabilities (AI features, better evals, analytics) not infrastructure.

### New NPM Dependencies: NONE

The Vue frontend may get minor UI additions (AI chat panel, history timeline) but these use the existing Vue 3 + Vite pipeline.

---

## Alternatives Considered

| Category | Recommended | Alternative | Why Not |
|----------|-------------|-------------|---------|
| Drush skill location | Standalone `skills/drupal-drush/` | Embedded in batch-queue-cron skill | Drush is cross-cutting (routing, testing, entities all use it); separate skill is cleaner |
| Eval-author model | Opus | Sonnet | Eval design requires deep reasoning about non-obvious patterns; Sonnet produces surface-level assertions (observed in v3.0/v4.0 manual design) |
| Eval-author output | JSON returned to orchestrator | Direct file writes | Orchestrator controls file layout, naming, and integration with the eval pipeline; agent should not own file structure |
| AI chat backend | ai.provider service (provider-agnostic) | Direct OpenAI API calls | Provider abstraction lets site admins choose their AI provider (Anthropic, OpenAI, local LLM) without code changes |
| History storage | Custom table via hook_schema | Content entity type | Append-only log data; entity overhead (forms, access control, revision) is pure waste for write-heavy analytics |
| History storage | Custom table via hook_schema | Custom entity type (lightweight) | Even lightweight entities add unnecessary abstraction; Database API queries for aggregation are simpler and faster |
| History recording | hook_entity_presave | Entity event subscriber | hook_entity_presave is simpler and doesn't require services.yml registration; presave gives access to $entity->original for diff |
| Analytics queries | Database API dynamic queries | Entity Query | Entity Query cannot do GROUP BY, date aggregation, or cross-table joins efficiently; Database API is the right tool for analytics |
| Views integration | hook_views_data on custom table | Views integration via entity | Custom table views_data is well-documented and works with all Views features (filters, sorts, relationships) |
| AI module version | Stay on 1.2.11 | Upgrade to 1.3.0-rc2 | RC not stable; our API surface needs (ChatInput, AiFunctionCall) are fully covered by 1.2.11 |

---

## What NOT to Add (Over-Engineering Risks)

| Avoid | Why | Impact if Added |
|-------|-----|----------------|
| **Separate analytics module** | History is integral to group_ai_pm, not reusable independently | Fragmented codebase, unnecessary module dependency chain |
| **Event system for history** | hook_entity_presave is simple and sufficient; event subscribers add DI complexity | Over-abstraction for a simple append-only pattern |
| **Redis/queue for history writes** | History inserts are single-row, sub-millisecond; queueing adds latency and complexity | Premature optimization; revisit only if write volume causes issues |
| **GraphQL for analytics** | Single consumer (the admin UI); REST endpoint with shaped response is simpler | Massive over-engineering for one data consumer |
| **Time-series database** | MySQL/MariaDB handles the expected data volume (thousands of changes, not millions) | Infrastructure complexity with zero benefit at this scale |
| **LangChain/LlamaIndex integration** | Drupal AI module already provides provider abstraction; adding Python tooling is absurd for a Drupal module | Wrong language ecosystem, deployment complexity |
| **Separate eval-runner agent** | Orchestrator already handles headless `claude -p` dispatch and result collection | Would require subagent-to-subagent communication (not supported) |
| **TypeScript for Vue AI components** | Eval pipeline uses Haiku for code generation; TS adds compilation failure risk | Build complexity for zero eval benefit |

---

## Integration Points with Existing Module

### Drush Skill -> Eval Pipeline

The Drush skill directly improves runtime assertion authoring:

```
eval-author (Opus) designs assertions
  -> uses drupal-drush skill patterns for runtime commands
  -> outputs drush php-eval commands that test WIRING
  -> orchestrator runs via ddev drush in eval pipeline
```

### AI Features -> Existing Entity Infrastructure

```
AI Chat Controller
  -> calls ai.provider service (already in container)
  -> dispatches to AiFunctionCall plugins
  -> plugins use EntityTypeManager (existing DI)
  -> entities save() -> triggers history recording
  -> cache tags invalidate -> Vue board refreshes
```

### Analytics -> Existing Caching

```
Task entity presave
  -> history table INSERT
  -> Cache::invalidateTags(['group_ai_pm_analytics:{project_id}'])
  -> Analytics API endpoint returns CacheableJsonResponse
  -> Drupal Dynamic Page Cache serves cached analytics
```

---

## Version Compatibility Matrix

| Component | Version | Compatible With | Notes |
|-----------|---------|-----------------|-------|
| Drush | 13.7.1 | Drupal ^10.2 \|\| ^11 | AutowireTrait requires PHP 8.1+ |
| AI module | 1.2.11 | Drupal ^10.4 \|\| ^11 | ChatInput/ChatMessage API stable |
| AI Agents | 1.3.0-beta2 | Drupal ^10.3 \|\| ^11, AI ^1.2.0 | AiFunctionCallBase plugin API stable |
| Key module | 1.22.0 | Drupal ^10 \|\| ^11 | Required by AI module for API key storage |
| Claude Code agents | Current | Claude Code 2.x+ | Subagent frontmatter format stable |
| Opus model | 4+ | Claude Code subagents | `model: opus` in frontmatter |

---

## Skill Gap Fixes (Existing Skills)

Three existing skills need targeted patches based on v4.0 findings:

### entities-fields: bundle_of coverage

**Gap:** SKILL.md mentions `bundle_of` in the decision tree but has no code example showing a config entity with `bundle_of` pointing back to the content entity.

**Fix:** Add a complete bundled entity example (ContentEntityType with `bundle_entity_type` + ConfigEntityType with `bundle_of`).

### caching: lazy_builder coverage

**Gap:** SKILL.md covers cache tags/contexts/max-age but does not document `#lazy_builder` for deferring expensive render elements out of cached pages.

**Fix:** Add a `#lazy_builder` section with the callback pattern and `#create_placeholder` usage.

### forms-api: #ajax hardening

**Gap:** The forms-api skill description mentions AJAX but the body has NO `#ajax` content. Phase 20 showed AJAX forms as a weak area.

**Fix:** Add `#ajax` property documentation covering callback, wrapper, effect, progress, and the common `ReplaceCommand` pattern.

---

## Sources

### Drush
- [Drush 13 Command Authoring](https://www.drush.org/13.4.0/commands/) (HIGH) -- file location, PHP attributes, AutowireTrait
- [Drush 13 Dependency Injection](https://www.drush.org/13.x/dependency-injection/) (HIGH) -- AutowireTrait, constructor injection, deprecation of drush.services.yml
- [Matt Glaman: Writing Drush Commands with PHP Attributes](https://mglaman.dev/blog/writing-drush-commands-php-attributes) (MEDIUM) -- practical attribute examples
- [Drush AutowireTrait source](https://github.com/drush-ops/drush/blob/13.x/src/Commands/AutowireTrait.php) (HIGH) -- trait implementation

### Drupal AI Module
- [Drupal AI module project page](https://www.drupal.org/project/ai) (HIGH) -- v1.2.11 stable, submodule list
- [AI: How to integrate in contrib modules](https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-module-documentation/ai/ai-how-to-use-it-or-integrate-it-in-contrib-modules) (HIGH) -- ChatInput/ChatMessage API, provider service
- [Basic Chat Call docs](https://project.pages.drupalcode.org/ai/1.2.x/developers/call_chat/) (HIGH) -- ChatInput, setSystemPrompt, getNormalized, streaming
- [AI Agents project page](https://www.drupal.org/project/ai_agents) (HIGH) -- v1.2.3 stable, framework overview
- [AI Agents documentation](https://ai-agents-project-eb5f6489e826e45857a7585a7d05c3e39463e30c9c8d5.pages.drupalcode.org/) (MEDIUM) -- plugin architecture overview
- [Drupal AI Roadmap for 2026](https://www.drupal.org/blog/drupals-ai-roadmap-for-2026) (MEDIUM) -- ecosystem direction

### Claude Code Subagents
- [Claude Code: Create custom subagents](https://code.claude.com/docs/en/sub-agents) (HIGH) -- complete frontmatter spec, model options, tool restrictions, permission modes
- Existing eval-grader.md in this project (HIGH) -- validated pattern for Drupal eval agents

### Drupal Database API
- [hook_schema API reference](https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Database!database.api.php/function/hook_schema/8.9.x) (HIGH) -- table definition format
- [Update API: Updating Database Schema](https://www.drupal.org/docs/drupal-apis/update-api/updating-database-schema-andor-data-in-drupal) (HIGH) -- hook_update_N patterns
- [Schema API quick start](https://www.drupal.org/docs/7/api/schema-api/schema-api-quick-start-guide) (MEDIUM) -- field types, indexes

---
*Stack research for: Drupal Skills v5.0 -- AI Integration & Eval Tooling*
*Researched: 2026-03-09*

# Feature Research: v5.0 AI Integration, Eval Tooling, & Analytics

**Domain:** AI-powered project management features, developer tooling (Drush skill, eval-author agent), skill gap fixes, and task analytics for an existing Drupal 10 module and Claude skill collection
**Researched:** 2026-03-09
**Confidence:** MEDIUM (Drush patterns well-documented via official docs; AI integration patterns proven in existing module; eval-author agent is novel -- no direct precedent; analytics schema is standard database patterns)

## Scope

This research covers ONLY new v5.0 features. The existing project already has:
- 14 Drupal skills with headless eval pipeline (9/13 positive delta)
- group_ai_pm module: Project/Task CRUD, Vue Kanban board with DnD, REST API (GET/PATCH/POST/DELETE), AJAX TaskStatusForm, dashboard, assignee autocomplete, toast notifications, filters, context menus, display options
- Eval pipeline: setup-fresh-drupal10.sh, headless `claude -p` code gen, eval-grader agent (Sonnet), eval-browser agent for E2E
- Three-tier assertions: static (evals.json), runtime (drush-based), browser (agent-browser)
- AI sub-module (group_ai_pm_ai) with CreateProjectTool and QueryProjectsTool AiFunctionCall plugins
- Existing batch-queue-cron skill covering QueueWorker, Batch API, hook_cron, Lock API

---

## Table Stakes

Features that are expected given the milestone's stated goals. Missing these would mean the milestone failed to deliver.

### A. Drush Skill (drupal-drush)

| Feature | Why Expected | Complexity | Depends On |
|---------|--------------|------------|------------|
| **Custom Drush command patterns** | Developers using Claude for Drupal ask "create a Drush command" regularly. Without this skill, Claude generates outdated annotation patterns or misplaces command files. Drush 13+ requires `src/Drush/Commands/` directory (not `src/Command/`), `#[AsCommand]` attributes (not `@command` annotations), and `AutowireTrait` for DI (not `drush.services.yml`). | MEDIUM | None -- standalone new skill |
| **Drush command lifecycle** | A Drush skill must cover the full command lifecycle: `configure()` for arguments/options, `interact()` for prompts, `execute()` for logic. Without lifecycle coverage, Claude generates commands that silently ignore options or fail on missing arguments. | LOW | Part of Drush command patterns |
| **Service injection via AutowireTrait** | Drush 13+ uses constructor autowiring, Drush 14+ requires it. The deprecated `drush.services.yml` approach is the #1 error Claude makes with Drush commands (training data is stale). The skill must teach AutowireTrait as the only correct pattern. | LOW | Part of Drush command patterns |
| **File placement and discovery** | Drush 12+ requires commands in `src/Drush/Commands/` with `*Commands` suffix. Wrong placement = commands not discovered. This is a concrete, testable wrong-way pattern. | LOW | Part of Drush command patterns |
| **Drush-based testing patterns** | The eval pipeline already uses `drush php-eval` for runtime assertions. The skill should teach DrushTestTrait for PHPUnit integration and `$this->drush()` assertion patterns. Enables smarter runtime assertion design in the eval-author agent. | MEDIUM | drupal-testing skill reference |
| **Common drush commands for development** | `drush cr`, `drush en`, `drush php-eval`, `drush config:get`, `drush user:info`, `drush queue:run`. Not about creating commands -- about USING drush for verification and debugging. Directly feeds eval runtime assertions. | LOW | None |

### B. Eval-Author Agent

| Feature | Why Expected | Complexity | Depends On |
|---------|--------------|------------|------------|
| **Static assertion generation** | Given a phase prompt and skill files, the agent must produce static assertions targeting non-obvious SKILL.md patterns (the proven differentiating strategy from v2-v4). Must follow the existing evals.json format exactly. | HIGH | Understanding of all 14 skills, existing eval format |
| **Runtime assertion generation** | Generate `drush php-eval` and `drush en` based runtime assertions that test functional correctness -- DI resolution, entity installation, permission loading, config storage. Must produce executable shell commands. | HIGH | Drush skill (for smarter drush commands), existing runtime assertion format |
| **Three-tier coverage analysis** | Analyze which skill patterns a given phase prompt exercises and flag gaps: "This prompt tests caching but not the CacheableJsonResponse pattern." Ensures no critical skill pattern goes untested. | MEDIUM | All 14 skill files as input context |
| **Difficulty calibration via past results** | Use v2-v4 eval results to calibrate assertion difficulty. Assertions that both with/without always pass are too easy. Assertions that neither passes are too hard or testing the wrong thing. Target the "with-passes, without-fails" sweet spot. | MEDIUM | Historical eval results from eval/results/ and eval/v3/ and eval/v4/ |
| **Output format compliance** | Must produce valid JSON matching existing evals.json schema. Must produce runtime assertions as executable bash commands. Grading pipeline breaks on malformed output. | LOW | Existing eval format as specification |

### C. Skill Gap Fixes

| Feature | Why Expected | Complexity | Depends On |
|---------|--------------|------------|------------|
| **entities-fields: bundle_of coverage** | Identified gap: SKILL.md mentions bundles in the decision tree but has no code example for `bundle_of` on the config entity side. Haiku gets this wrong consistently. | LOW | drupal-entities-fields skill |
| **caching: lazy_builder hardening** | Existing skill covers lazy_builder but the pattern has proven weak in evals -- Haiku misuses arguments (passes objects instead of scalars). Needs a stronger WRONG/RIGHT callout with scalar-only rule. | LOW | drupal-caching skill |
| **forms-api: #ajax content** | Identified in v4.0 phase 20 results: skill description mentions AJAX but body has NO `#ajax` code examples. Phase 20 delta was only MOD because Haiku had to figure out `#ajax` patterns without skill guidance. | MEDIUM | drupal-forms-api skill |

---

## Differentiators

Features that elevate the project beyond "functional" to "impressive." Not required for milestone completion, but add substantial value.

### D. AI-Powered Task Creation from Natural Language

| Feature | Value Proposition | Complexity | Depends On |
|---------|-------------------|------------|------------|
| **CreateTaskFromTextTool** | New AiFunctionCall plugin that parses natural language like "Create a high priority task to fix the login bug, assign to admin, due Friday" into structured Task entity creation. Existing CreateProjectTool provides the exact plugin pattern. The AI module handles intent parsing via LLM -- the tool just needs to expose the right argument schema. | MEDIUM | AI Agents module (already installed), existing AiFunctionCall pattern |
| **Intent-to-field mapping** | AI extracts: title (free text), priority (inferred from "urgent"/"important"/"low"), assignee (user lookup by name), due_date (relative date parsing "Friday"/"next week"/"March 15"), status (defaults to 'todo' unless specified). The LLM does the NLP; the tool validates and creates. | MEDIUM | CreateTaskFromTextTool base, user entity lookup |
| **Structured argument schema** | The tool's `getArguments()` defines the contract: title (required string), priority (enum), assignee_name (optional string for fuzzy user lookup), due_date (optional ISO date), status (enum with default), description (optional text). The AI provider maps natural language to these structured args. | LOW | AiFunctionCall plugin API |
| **User lookup by name** | When AI extracts "assign to Jane", the tool must fuzzy-match against Drupal user display names. Use `user_load_by_name()` or entity query on `name` field. Return helpful error if no match or ambiguous match. | LOW | Drupal user entity storage |
| **Confirmation and feedback** | Tool returns a structured summary: "Created task 'Fix login bug' (priority: high, assigned: admin, due: 2026-03-14)". The AI agent can present this to the user for confirmation or as a completion message. | LOW | Tool return value pattern |

### E. AI-Suggested Task Assignments

| Feature | Value Proposition | Complexity | Depends On |
|---------|-------------------|------------|------------|
| **SuggestAssigneeTool** | AiFunctionCall plugin that takes a task description and returns ranked user suggestions with reasoning. Uses LLM to analyze: user's current workload (task count by status), past assignment patterns (how often each user works on similar tasks), availability (ratio of done vs in_progress tasks). | HIGH | AI Agents module, task entity queries for workload data |
| **Workload metrics query** | For each candidate user: count active tasks (in_progress + review), count completed tasks (done), calculate completion rate. Users with fewer active tasks and higher completion rates rank higher. Pure entity query -- no ML needed. | MEDIUM | Task entity storage, user entity references |
| **Confidence scoring** | Each suggestion includes a confidence level: HIGH (clear best match -- low workload, many similar completed tasks), MEDIUM (reasonable match), LOW (fallback -- no clear signal). Confidence helps users decide whether to accept. | MEDIUM | Scoring algorithm based on workload metrics |
| **User approval workflow** | Suggestions are presented, NOT auto-applied. The AI agent returns "I suggest assigning to @admin (confidence: HIGH, currently 2 active tasks, 15 completed)" and the user explicitly accepts or rejects. No autonomous entity mutation. | LOW | Tool return value is advisory only |

### F. Batch AI Operations

| Feature | Value Proposition | Complexity | Depends On |
|---------|-------------------|------------|------------|
| **BatchUpdateTasksTool** | AiFunctionCall plugin for bulk operations: "Set all todo tasks in Project Alpha to high priority" or "Assign all unassigned review tasks to admin." Parses intent, queries matching tasks, applies updates in a single tool call. | MEDIUM | AI Agents module, task entity queries |
| **QueueWorker for AI batch processing** | For large batches (>50 tasks), use existing QueueWorker pattern to process updates asynchronously. The AI tool queues items, a cron-triggered `AiTaskBatchWorker` processes them. Leverages the proven batch-queue-cron skill. | MEDIUM | drupal-batch-queue-cron patterns (already in skill), Queue API |
| **Progress feedback** | For synchronous small batches: return "Updated 12 tasks: set priority to high." For queued large batches: return "Queued 150 task updates. Processing via cron." The user always knows what happened and when to expect completion. | LOW | Tool return values, queue count |
| **Error handling per item** | If some updates fail (access denied on specific tasks), the tool should report partial success: "Updated 10/12 tasks. 2 failed: Task 45 (access denied), Task 67 (invalid status)." Not all-or-nothing. | MEDIUM | Per-item try/catch in processing loop, SuspendQueueException pattern |
| **Dry run mode** | Optional `dry_run` argument that queries matching tasks and returns what WOULD change without actually changing anything. "12 tasks would be updated: 8 todo -> in_progress, 4 in_progress -> review." Users can verify before committing. | LOW | Argument flag, conditional entity save |

### G. Task History Analytics

| Feature | Value Proposition | Complexity | Depends On |
|---------|-------------------|------------|------------|
| **Status change tracking table** | Custom database table (`group_ai_pm_task_history`) recording: task_id, old_status, new_status, changed_by (uid), changed_at (timestamp). Populated via `hook_entity_update()` on task entities. This is the foundation for all analytics. | MEDIUM | Custom install schema, hook_entity_update |
| **Views integration for history** | Expose the history table to Views via `hook_views_data()`. Admins can create Views showing status transition history, filtered by date range, user, or project. No custom UI needed -- Views handles display. | MEDIUM | Custom database table, Views API knowledge from drupal-views-dev skill |
| **Cycle time calculation** | Compute average time from 'todo' to 'done' per project. Derived from the history table: find earliest 'todo' entry and latest 'done' entry per task, compute delta. Surface as a REST endpoint or Views field. | MEDIUM | History table, date arithmetic |
| **Status distribution over time** | Query the history table to show how many tasks were in each status on a given date. Enables "burndown-like" visualization without the complexity of actual burndown charts. | HIGH | History table, point-in-time reconstruction |
| **Dashboard integration** | Add analytics summary to the existing dashboard: average cycle time, tasks completed this week, overdue count, bottleneck detection (which status column has the most tasks stuck). | MEDIUM | History table queries, dashboard controller enhancement |

---

## Anti-Features

Features to explicitly NOT build. Each is tempting but adds disproportionate complexity or undermines the project's eval-driven methodology.

| Anti-Feature | Why Tempting | Why Avoid | What to Do Instead |
|--------------|-------------|-----------|-------------------|
| **General-purpose Drush command generation** | A Drush skill could try to teach every drush subcommand (site:install, config:export, etc.). | The skill is for AUTHORING custom commands, not documenting existing ones. Drush docs already cover usage. Bloating the skill with usage docs pushes it past the 500-line limit and dilutes the custom command patterns that actually differ between with/without skill. | Focus on custom command creation patterns only. Reference drush docs for existing commands. Include common commands for eval assertions in a concise reference section. |
| **Real-time AI chat interface** | A conversational chatbot in the Kanban board for "talk to your project." | Requires WebSocket/polling infrastructure Drupal does not have. The AI Agents module already provides a chatbot interface at `/admin/config/ai/agents`. Building a second one inside the Kanban is duplicative. | Use the existing AI Agents chatbot interface. The new AiFunctionCall tools register with it automatically. |
| **Autonomous AI actions** | AI that automatically assigns tasks or changes status without user approval. | Autonomous mutations without human-in-the-loop are dangerous for a project management tool. A misassignment or wrong status change could lose work visibility. Also impossible to eval meaningfully -- "did the AI make a good autonomous decision?" is subjective. | All AI tools return suggestions/results. Users explicitly confirm before entity mutation. SuggestAssigneeTool returns recommendations; the user clicks "Accept." |
| **Machine learning model training** | Training a custom ML model on task history for predictions (completion time, assignment optimization). | Requires training infrastructure, labeled datasets, model versioning, and ongoing maintenance. Far exceeds module scope. The project is a Drupal module, not an ML platform. | Use LLM-based reasoning via AI Agents. The LLM analyzes workload data and task descriptions without custom model training. Simpler, cheaper, good enough. |
| **Full burndown charts** | Sprint velocity, burndown curves, capacity planning visualizations. | Requires: Sprint entity type, story points, date-range assignment UI, chart rendering library (Chart.js/D3), calculation engine. Massive scope that does not test Drupal skills. The Burndown contrib module already exists for this. | Track status change history (simple table). Surface basic metrics (cycle time, throughput). Let Views handle display. If burndown is needed later, it builds on the history table as a separate sub-module. |
| **Eval-author generating browser assertions** | The eval-author agent could also design browser-based E2E assertions (puppeteer-style). | Browser assertions are fragile, expensive to run, and produced zero discriminatory value in v2.0 backend evals. They were revived for v4.0 UX testing but are best designed manually by a human who understands the visual expectations. Automated browser assertion generation would produce flaky, over-specified tests. | Eval-author generates static and runtime assertions only. Browser assertions remain manually designed for phases that need UX verification. |
| **Plugin-based analytics engine** | A pluggable analytics framework with custom metric plugins, configurable dashboards, and third-party integrations. | Over-architecture. The module needs 3-4 specific metrics (cycle time, throughput, bottleneck detection, status distribution). A plugin system for 3 metrics is overhead without benefit. | Hardcode the metrics as service methods. If someone needs custom metrics later, they can extend the service or add Views computed fields. |
| **Multi-provider AI support** | Supporting OpenAI, Google, Anthropic, and local models for task creation/assignment. | The AI module already abstracts provider selection. The AiFunctionCall plugins are provider-agnostic -- they receive structured arguments regardless of which LLM parsed the natural language. Provider configuration is the AI module's job, not ours. | Register tools with the AI Agents framework. Let the AI module handle provider routing. |

---

## Feature Dependencies

```
[Drush Skill]
    |
    +-- enables --> [Eval-Author Agent] (smarter runtime assertions)
    |                   |
    |                   +-- enables --> [All subsequent phase evals]
    |                                     (automated 3-tier assertion design)
    |
    +-- enables --> [Runtime assertion quality] (drush php-eval patterns in skill)

[Skill Gap Fixes]
    |
    +-- entities-fields: bundle_of --> [Future entity evals]
    +-- caching: lazy_builder --> [Future caching evals]
    +-- forms-api: #ajax --> [Phase evals with AJAX interactions]

[AI Task Creation]
    |
    +-- requires --> [AI Agents module] (already installed)
    +-- requires --> [CreateProjectTool pattern] (already exists as reference)
    +-- enhances --> [Kanban board] (tasks created via AI appear on board)

[AI-Suggested Assignments]
    |
    +-- requires --> [Task entity with assignee field] (exists)
    +-- requires --> [AI Agents module]
    +-- requires --> [Multiple tasks exist for workload calculation]
    +-- enhances --> [AI Task Creation] (suggest assignee during creation)

[Batch AI Operations]
    |
    +-- requires --> [AI Agents module]
    +-- requires --> [Task entity CRUD] (exists)
    +-- uses --> [QueueWorker pattern] (drupal-batch-queue-cron skill)
    +-- enhances --> [AI Task Creation] (bulk create variant)

[Task History Analytics]
    |
    +-- requires --> [Custom database table] (hook_schema)
    +-- requires --> [hook_entity_update on task entity]
    +-- enables --> [AI-Suggested Assignments] (completion rate data)
    +-- enables --> [Dashboard improvements] (metrics display)
    +-- enhances --> [Views integration] (drupal-views-dev skill)

[Eval-Author Agent]
    |
    +-- requires --> [Drush Skill] (runtime assertion quality)
    +-- requires --> [Historical eval results] (difficulty calibration)
    +-- requires --> [All 14 skill files] (pattern coverage analysis)
    +-- produces --> [evals.json, runtime assertions] (per phase)
```

### Dependency Notes

- **Drush Skill enables Eval-Author Agent**: The eval-author agent generates `drush php-eval` runtime assertions. A Drush skill teaches the correct syntax and common verification patterns, making the agent's output more reliable.
- **Skill Gap Fixes are independent**: Each fix is a targeted SKILL.md edit. No external dependencies. Can be done in parallel with anything.
- **AI features share the AI Agents module**: All three AI features (task creation, assignment suggestion, batch operations) use the same AiFunctionCall plugin pattern. They can be built in parallel.
- **Task History Analytics feeds AI Assignment**: The SuggestAssigneeTool uses completion rate data from the history table. History table must exist before assignment suggestions can use historical data. However, the tool can work with simple entity queries alone (no history) and gain the historical dimension later.
- **Eval-Author Agent is cross-cutting**: Once built, it designs evals for ALL subsequent phases. Build it early to reduce manual eval design effort for the rest of the milestone.

---

## MVP Definition

### Build First (Foundations)

These features are prerequisites for everything else in the milestone.

- [ ] **Drush skill** -- Enables smarter runtime assertions. Required knowledge for eval-author agent. Fills a real gap in the skill collection (15th skill).
- [ ] **Eval-author agent** -- Eliminates the manual bottleneck of designing 15-30 assertions per phase. The orchestrator has spent ~30min per phase on assertion design in v3-v4. Automating this unlocks faster iteration.
- [ ] **Skill gap fixes** -- Quick wins (LOW complexity). Each fix directly improves eval pass rates for affected patterns. Shippable in hours, not days.

### Build After Foundations (AI Features)

These require the AI Agents module and the existing AiFunctionCall pattern. Each is independently valuable.

- [ ] **CreateTaskFromTextTool** -- Most user-visible AI feature. Natural language task creation is the "wow" moment. Demonstrates practical AI integration with Drupal entities.
- [ ] **Task history analytics table** -- Foundation for metrics. The hook_entity_update listener and schema are simple to implement. Every feature in the analytics domain depends on this.

### Build Last (Enhancement Layer)

These build on the foundations and are independently deferrable.

- [ ] **SuggestAssigneeTool** -- Requires tasks to exist and benefits from history data. More valuable later in the milestone when there is data to analyze.
- [ ] **BatchUpdateTasksTool** -- Quality-of-life tool for power users. Less flashy than task creation, but demonstrates queue-based AI patterns.
- [ ] **Analytics dashboard integration** -- Cycle time, throughput, bottleneck detection metrics displayed on the existing dashboard. Builds on the history table.
- [ ] **Views integration for history** -- Exposes history table to Views. Advanced feature for admin customization.
- [ ] **Cross-cutting eval pass with eval-author** -- Final validation that the eval-author agent + Drush skill + gap fixes actually improve eval outcomes. Measures the milestone's impact.

---

## Feature Prioritization Matrix

| Feature | User Value | Implementation Cost | Eval Value | Priority |
|---------|-----------|---------------------|------------|----------|
| Drush skill | HIGH (fills gap in 14-skill collection) | MEDIUM (research done, ~300 lines) | HIGH (enables runtime assertions) | P1 |
| Eval-author agent | HIGH (eliminates manual bottleneck) | HIGH (novel, needs prompt engineering) | HIGH (multiplier for all future phases) | P1 |
| Skill gap fixes (3 patches) | MEDIUM (targeted improvements) | LOW (hours of SKILL.md editing) | MEDIUM (direct pass rate improvement) | P1 |
| CreateTaskFromTextTool | HIGH (visible AI feature) | MEDIUM (follows existing pattern) | MEDIUM (tests AI integration skill) | P2 |
| Task history table + hook | MEDIUM (invisible foundation) | MEDIUM (schema + hook) | MEDIUM (feeds analytics evals) | P2 |
| SuggestAssigneeTool | MEDIUM (useful when data exists) | HIGH (scoring algorithm, user lookup) | LOW (subjective output, hard to eval) | P3 |
| BatchUpdateTasksTool | MEDIUM (power user tool) | MEDIUM (queue integration) | MEDIUM (tests batch-queue-cron skill) | P2 |
| Analytics dashboard | MEDIUM (visibility into metrics) | MEDIUM (queries + template) | LOW (dashboard already tested in v4) | P3 |
| Views history integration | LOW (admin-only, Views handles display) | MEDIUM (hook_views_data boilerplate) | MEDIUM (tests drupal-views-dev skill) | P3 |
| Cross-cutting eval pass | HIGH (validates milestone) | LOW (run existing pipeline with new agent) | HIGH (proves eval-author works) | P2 |

**Priority key:**
- P1: Must build. Milestone fails without these.
- P2: Should build. Demonstrates the milestone's value.
- P3: Nice to have. Defers gracefully if time is short.

---

## Feature Specification Details

### Drush Skill Specifics

**Target patterns** (from research):

1. **Symfony Console command** (Drush 13.7+ recommended approach):
   ```php
   namespace Drupal\my_module\Drush\Commands;

   use Drupal\Core\Entity\EntityTypeManagerInterface;
   use Drush\Attributes as CLI;
   use Drush\Commands\AutowireTrait;
   use Symfony\Component\Console\Attribute\AsCommand;
   use Symfony\Component\Console\Command\Command;

   #[AsCommand(name: 'my_module:import', description: 'Import data')]
   class ImportCommand extends Command {
     use AutowireTrait;

     public function __construct(
       private readonly EntityTypeManagerInterface $entityTypeManager,
     ) {
       parent::__construct();
     }

     protected function execute($input, $output): int {
       // Logic here.
       return Command::SUCCESS;
     }
   }
   ```

2. **File placement**: `src/Drush/Commands/` (not `src/Command/`, not root-level)
3. **AutowireTrait** (not `drush.services.yml`)
4. **Return codes**: `Command::SUCCESS` (0), `Command::FAILURE` (1), `Command::INVALID` (2)
5. **Testing**: `DrushTestTrait` with `$this->drush('command', ['arg'], ['--option' => 'value'])`

**WRONG-way callouts** (the skill's value):
- WRONG: Placing commands in `src/Command/` (Symfony convention, not Drush convention)
- WRONG: Using `drush.services.yml` for DI (deprecated in Drush 13, removed in Drush 14)
- WRONG: Using `@command` DocBlock annotations (deprecated in favor of `#[AsCommand]`)
- WRONG: Forgetting `parent::__construct()` in AutowireTrait classes
- WRONG: Missing `*Commands` suffix on command file/class names for DrushCommands approach

**Confidence:** HIGH -- Drush 13.x official docs at drush.org/13.x/commands/ confirm all patterns.

### Eval-Author Agent Specifics

**Agent role:** Opus-class subagent spawned by the orchestrator to design three-tier assertions for a given phase.

**Inputs:**
- Phase prompt (the eval task description)
- Primary skill files being tested (SKILL.md contents)
- Existing module code snapshot (for context)
- Historical eval results (for difficulty calibration)

**Outputs:**
- `evals.json` with static expectations targeting non-obvious skill patterns
- Runtime assertions as executable bash commands (`drush php-eval '...'`, `drush en ...`)
- Coverage report: which skill patterns are exercised vs untested

**Design principles** (from Anthropic eval best practices):
1. **Grade outcomes, not paths** -- Assert what the code produces, not how it gets there
2. **Balanced assertions** -- Test both positive ("does X") and negative ("does NOT do Y") patterns
3. **Calibrated difficulty** -- Target the with-passes/without-fails sweet spot using historical data
4. **Isolated trials** -- Each assertion must be independently verifiable
5. **Avoid rigid grading** -- Allow valid alternative implementations (e.g., `$entity->getCacheTags()` OR `addCacheableDependency()` -- both correct)

**Assertion generation strategy:**
1. Parse the phase prompt to identify Drupal patterns being exercised
2. For each pattern, check which SKILL.md covers it
3. For each SKILL.md pattern, identify the non-obvious aspect (the WRONG/RIGHT callouts)
4. Generate an assertion targeting that non-obvious aspect
5. Cross-reference with historical results: if a similar assertion always passed without-skill, it's too easy -- find a harder variant

### AI Task Creation Specifics

**Plugin pattern** (mirrors existing CreateProjectTool):
```php
namespace Drupal\group_ai_pm_ai\Plugin\AiFunctionCall;

/**
 * @AiFunctionCall(
 *   id = "create_task_from_text",
 *   label = @Translation("Create Task"),
 *   description = @Translation("Create a task from natural language description")
 * )
 */
class CreateTaskFromTextTool extends AiFunctionCallBase {

  public function getArguments() {
    return [
      'title' => ['type' => 'string', 'required' => TRUE],
      'description' => ['type' => 'string', 'required' => FALSE],
      'priority' => ['type' => 'string', 'enum' => [...], 'required' => FALSE],
      'assignee_name' => ['type' => 'string', 'required' => FALSE],
      'due_date' => ['type' => 'string', 'description' => 'ISO 8601 date'],
      'project_id' => ['type' => 'integer', 'required' => TRUE],
      'status' => ['type' => 'string', 'enum' => [...], 'required' => FALSE],
    ];
  }
}
```

The LLM parses "Create a high priority task to fix the login bug for Project Alpha, assign to Jane, due Friday" into the structured arguments. The tool validates and creates the entity.

### Task History Analytics Specifics

**Schema** (hook_schema in .install):
```php
function group_ai_pm_schema() {
  $schema['group_ai_pm_task_history'] = [
    'description' => 'Task status change history',
    'fields' => [
      'id' => ['type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE],
      'task_id' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE],
      'project_id' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE],
      'old_status' => ['type' => 'varchar', 'length' => 32, 'not null' => FALSE],
      'new_status' => ['type' => 'varchar', 'length' => 32, 'not null' => TRUE],
      'changed_by' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE],
      'changed_at' => ['type' => 'int', 'not null' => TRUE],
    ],
    'primary key' => ['id'],
    'indexes' => [
      'task_id' => ['task_id'],
      'project_id' => ['project_id'],
      'changed_at' => ['changed_at'],
    ],
  ];
  return $schema;
}
```

**Tracking hook** (in .module):
```php
function group_ai_pm_entity_update(EntityInterface $entity) {
  if ($entity->getEntityTypeId() !== 'task') return;
  $original = $entity->original;
  if ($original && $original->get('status')->value !== $entity->get('status')->value) {
    \Drupal::database()->insert('group_ai_pm_task_history')
      ->fields([...])
      ->execute();
  }
}
```

**Key metrics:**
- **Cycle time**: Average elapsed time from first 'todo' to first 'done' per task
- **Throughput**: Tasks moved to 'done' per week/month
- **Bottleneck detection**: Status column with highest average dwell time
- **Completion rate**: Done tasks / total tasks per project (already computed in dashboard)

---

## Competitor/Precedent Analysis

| Feature | Existing Module/Pattern | Our Approach | Delta |
|---------|------------------------|--------------|-------|
| Drush command skill | No existing Claude skill covers Drush | New skill targeting Drush 13+ patterns | First of its kind |
| Eval automation | Promptfoo YAML-based eval framework | Custom Opus agent with project-specific knowledge | Tailored to our 3-tier assertion model |
| AI task creation | AI Agents chatbot (generic) | Purpose-built AiFunctionCall tools with PM domain knowledge | Structured entity creation vs generic chat |
| Task history tracking | Activity Tracking module (generic CRUD logging) | PM-specific status transition tracking with cycle time | Focused on Kanban workflow metrics |
| AI assignment | Monday.com, Linear AI features | LLM-based reasoning over workload data, advisory only | Human-in-the-loop, not autonomous |
| Batch AI ops | No Drupal precedent | Queue-based processing with dry run mode | Combines AI Agents + Batch/Queue patterns |

---

## Sources

- [Drush 13.x Command Authoring](https://www.drush.org/13.x/commands/) -- PHP attributes, AutowireTrait, file placement, lifecycle -- HIGH confidence
- [Drush 12.x Command Authoring](https://www.drush.org/12.x/commands/) -- Legacy patterns still referenced in older docs -- MEDIUM confidence
- [Custom Drush Commands with Drush Generate](https://www.fourkitchens.com/blog/development/custom-drush-commands-drush-generate/) -- Practical tutorial for modern Drush commands -- MEDIUM confidence
- [Drush Test Traits (Unish)](https://www.drush.org/13.x/contribute/unish/) -- PHPUnit testing for Drush commands -- HIGH confidence
- [Anthropic: Demystifying Evals for AI Agents](https://www.anthropic.com/engineering/demystifying-evals-for-ai-agents) -- Assertion design, grader patterns, difficulty calibration, coverage -- HIGH confidence
- [AI Agents Drupal Module](https://www.drupal.org/project/ai_agents) -- AiFunctionCall plugin pattern, agent architecture -- HIGH confidence
- [AI Agents Documentation](https://project.pages.drupalcode.org/ai_agents) -- Module configuration and tool registration -- MEDIUM confidence
- [QED42: Building AI Agents, Tools, and Assistants in Drupal](https://www.qed42.com/insights/exploring-drupals-ai-agents-a-practical-guide-for-site-builders) -- Practical AiFunctionCall examples -- MEDIUM confidence
- [Drupal Events Vienna 2025: Building AI Agents Workshop](https://events.drupal.org/vienna2025/session/building-ai-agents-tools-and-assistants-drupal-hands-workshop) -- Custom tool development patterns -- MEDIUM confidence
- [Drupalize.Me: Expose Custom Database Table to Views](https://drupalize.me/tutorial/expose-custom-database-table-views) -- hook_views_data() for custom tables -- HIGH confidence
- [Activity Tracking Module](https://www.drupal.org/project/activitytracking) -- Entity operation logging precedent -- MEDIUM confidence
- [Redgate: Project Management Data Model](https://www.red-gate.com/blog/organize-your-time-and-resources-a-project-management-data-model) -- Schema patterns for PM databases -- MEDIUM confidence
- [Martin Fowler: Temporal Patterns](https://martinfowler.com/eaaDev/timeNarrative.html) -- Bi-temporal data modeling theory -- HIGH confidence
- [LLM as a Judge: 2026 Guide](https://labelyourdata.com/articles/llm-as-a-judge) -- Automated assessment patterns, calibration -- MEDIUM confidence
- [Sana Labs: 7 AI Task Managers 2025](https://sanalabs.com/agents-blog/ai-task-managers-to-boost-productivity) -- AI task management market context -- LOW confidence
- [ScienceDirect: ML Algorithms for Task Allocation](https://www.sciencedirect.com/science/article/pii/S2405844024159579) -- Academic precedent for AI assignment -- MEDIUM confidence

---
*Feature research for: v5.0 AI Integration, Eval Tooling, & Analytics*
*Researched: 2026-03-09*

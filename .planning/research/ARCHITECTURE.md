# Architecture Patterns

**Domain:** Group-based AI project management module + Claude Code plugin packaging (v3.0 integration)
**Researched:** 2026-03-07
**Confidence:** MEDIUM -- Drupal Group/AI module APIs verified via official sources; Claude Code plugin format verified via official docs; integration between all three is novel territory requiring phase-specific validation

## Three Architectural Workstreams

v3.0 has three distinct but interconnected workstreams that layer onto the existing v1.0/v2.0 repo:

1. **Claude Code Plugin Packaging** -- Restructure repo as an installable plugin with auto-triggering skill descriptions
2. **Group AI Project Management Module** -- Drupal contrib module combining Group 3.x + AI Agents for project management
3. **Phase-Level Integration Eval** -- Each module-building phase doubles as a real-world skill auto-trigger test

These are not independent -- the module (workstream 2) is the test vehicle for the plugin (workstream 1), and the eval methodology (workstream 3) validates both.

## System Overview

```
drupal-skills/ (repo root)
  |
  |-- [RESTRUCTURED] Plugin packaging (.claude-plugin/, skills/, etc.)
  |       Skills auto-trigger from natural Drupal dev prompts
  |
  |-- [NEW] modules/group_ai_pm/
  |       Drupal contrib module: Group + AI Agents project management
  |       Built phase-by-phase, each phase exercises different skills
  |
  |-- [EXISTING] eval/ pipeline
  |       v2.0 headless pipeline preserved for regression
  |       v3.0 adds phase-level "with-plugin vs without-plugin" comparison
  |
  |-- [EXISTING] .planning/ GSD workflow
  |       Orchestrates the build phases
```

## Workstream 1: Claude Code Plugin Packaging

### Current State -> Target State

**Current:** Skills live in `skills/drupal-*/SKILL.md`, installed via `install.sh` to `~/.claude/skills/`. No plugin manifest. No namespace. Skills invoked as `/drupal-module-scaffold` etc.

**Target:** Repo IS a Claude Code plugin. Install via marketplace or `--plugin-dir`. Skills namespaced as `/drupal-skills:drupal-module-scaffold`. Auto-trigger from natural Drupal development prompts without explicit invocation.

### Plugin Directory Structure

```
drupal-skills/                          # Plugin root
  .claude-plugin/
    plugin.json                         # Plugin manifest (NEW)
  skills/                               # Existing location, unchanged
    drupal-module-scaffold/
      SKILL.md
      evals/
        evals.json
      references/
        *.md
    drupal-caching/
      SKILL.md
      ...
    drupal-coding-standards/
      SKILL.md
  eval/                                 # NOT part of plugin distribution
    setup-fresh-drupal10.sh
    teardown-drupal-env.sh
    results/
  install.sh                            # Keep for backward compat (copies to ~/.claude/skills/)
  README.md
  .gitignore
```

### Plugin Manifest

```json
{
  "name": "drupal-skills",
  "description": "14 skills for generating correct Drupal 10/11 module code. Covers scaffolding, entities, routing, forms, caching, testing, and more.",
  "version": "3.0.0",
  "author": {
    "name": "proofoftom"
  },
  "repository": "https://github.com/proofoftom/drupal-skills",
  "license": "MIT",
  "keywords": ["drupal", "drupal-10", "drupal-11", "module-development", "php"]
}
```

### Auto-Triggering Architecture

Skills auto-trigger based on their `description` field in SKILL.md frontmatter. Claude builds an `<available_skills>` list from all installed skill names + descriptions, and semantically matches user requests against them.

**Key constraint:** Descriptions must be under a character budget (2% of context window, ~16,000 chars fallback). With 14 skills, each description gets roughly ~1,100 chars max. Current descriptions range 200-400 chars -- well within budget.

**Triggering rules from official docs:**
- Claude only consults skills for tasks it cannot easily handle on its own
- Simple one-step queries may not trigger even with matching description
- Complex, multi-step, specialized queries reliably trigger when description matches
- Description should include both WHAT the skill does and WHEN to use it

**Current descriptions already follow this pattern.** Example from drupal-caching:
```yaml
description: |
  Apply correct cache metadata (tags, contexts, max-age) to Drupal render arrays...
  Use WHENEVER producing render arrays that display entity or config data...
  Do NOT use for building templates...
```

**Potential issue:** When used as a plugin, skill names become `/drupal-skills:drupal-caching` instead of `/drupal-caching`. The description-based auto-triggering should be unaffected since it matches on description content, not name. But verify empirically.

### What Changes vs What Stays

| Component | Change | Rationale |
|-----------|--------|-----------|
| `skills/drupal-*/SKILL.md` | NO CHANGE | Already follows Agent Skills standard |
| `skills/drupal-*/evals/` | NO CHANGE | Not loaded by plugin system |
| `skills/drupal-*/references/` | NO CHANGE | Loaded by SKILL.md `Read` calls |
| `.claude-plugin/plugin.json` | **NEW** | Required for plugin recognition |
| `install.sh` | KEEP | Backward compat for non-plugin install |
| `README.md` | UPDATE | Add plugin install instructions |
| `.gitignore` | UPDATE | May need to exclude eval artifacts from plugin cache |
| `eval/`, `.planning/` | NO CHANGE | Not discovered by plugin system |

### Critical Architectural Decision: Repo-as-Plugin vs Separate Plugin Dir

**Decision: Repo IS the plugin.** The `skills/` directory is already at root level in the correct location. Adding `.claude-plugin/plugin.json` is the only structural change needed.

**Why not a separate plugin subdirectory:** Would require duplicating or symlinking skills. The current repo structure already matches Claude Code's expected plugin layout. The eval infrastructure (`eval/`, `.planning/`) is simply ignored by the plugin system since it is not in a recognized component directory.

## Workstream 2: Group AI Project Management Module

### Module Architecture

The contrib module `group_ai_pm` combines Drupal Group 3.x entities with AI Agents tools to create an AI-enhanced project management system within groups.

```
modules/group_ai_pm/
  group_ai_pm.info.yml
  group_ai_pm.module
  group_ai_pm.install
  group_ai_pm.routing.yml
  group_ai_pm.permissions.yml
  group_ai_pm.links.menu.yml
  group_ai_pm.links.task.yml
  group_ai_pm.links.action.yml
  config/
    install/
      group.relation_type.group_project.yml    # Group relation plugin config
      core.entity_form_display.*.yml
      core.entity_view_display.*.yml
      views.view.group_projects.yml
    schema/
      group_ai_pm.schema.yml
  src/
    Entity/
      Project.php                   # Content entity: Project
      ProjectType.php               # Config entity: Project bundle (if needed)
      Task.php                      # Content entity: Task
      TaskType.php                  # Config entity: Task bundle
    Form/
      ProjectForm.php
      TaskForm.php
      ProjectSettingsForm.php
    Controller/
      ProjectController.php
      TaskController.php
      ProjectDashboardController.php
    Plugin/
      GroupRelation/
        GroupProject.php            # Group relation plugin for Projects
        GroupTask.php               # Group relation plugin for Tasks
      Block/
        ProjectStatusBlock.php
        TaskListBlock.php
        AiAssistantBlock.php
      AiFunctionCall/
        CreateProjectTool.php       # AI Agents tool: create project
        CreateTaskTool.php          # AI Agents tool: create task
        UpdateTaskStatusTool.php    # AI Agents tool: update task
        QueryProjectsTool.php       # AI Agents tool: query projects
      Views/
        field/
          TaskStatusField.php
        filter/
          ProjectGroupFilter.php
    Access/
      ProjectAccessControlHandler.php
      TaskAccessControlHandler.php
    Service/
      ProjectManager.php            # Business logic service
      TaskWorkflowManager.php       # Task state transitions
  templates/
    project-dashboard.html.twig
    task-list.html.twig
  tests/
    src/
      Kernel/
        ProjectEntityTest.php
        TaskEntityTest.php
        GroupRelationTest.php
      Functional/
        ProjectCrudTest.php
        TaskWorkflowTest.php
        AiToolsTest.php
```

### Entity Model

```
Group (from Group 3.x)
  |-- GroupRelationship --> Project (custom content entity)
  |                           |-- title
  |                           |-- description (text_long)
  |                           |-- status (list_string: planning/active/review/completed)
  |                           |-- priority (list_string: low/medium/high/critical)
  |                           |-- owner (entity_reference: user)
  |                           |-- created, changed (timestamps)
  |
  |-- GroupRelationship --> Task (custom content entity)
                              |-- title
                              |-- description (text_long)
                              |-- project (entity_reference: Project)
                              |-- assignee (entity_reference: user)
                              |-- status (list_string: todo/in_progress/review/done)
                              |-- priority (list_string: low/medium/high/critical)
                              |-- due_date (datetime)
                              |-- created, changed (timestamps)
```

### Group 3.x Integration Points

**Confidence: MEDIUM** -- Group 3.x API is confirmed via drupal.org change records, but implementation details need phase-specific research.

Group 3.x uses GroupRelationship entities (renamed from GroupContent) to link content to groups. Key integration:

| Group 3.x Concept | Our Usage | Notes |
|-------------------|-----------|-------|
| GroupType | "Project Team" bundle | Config entity defining group behavior |
| GroupRole | PM, Developer, Observer | Per-group roles with permissions |
| GroupRelationship | Links Projects and Tasks to Groups | Each relationship is an entity with metadata |
| GroupRelationType | `group_project`, `group_task` | Plugin-defined relationship types |
| Group Relation Plugin | `GroupProject`, `GroupTask` | PHP classes defining how entities relate to groups |

**Group 3.x API (confirmed changes from Group 2.x):**
- `Group::addContent()` renamed to `Group::addRelationship()` -- returns the created entity
- GroupContent entity renamed to GroupRelationship
- Plugin manager behaves like EntityTypeManager
- Relation plugins define handlers as services, not annotations
- Both content entities and config entities can be group content

### AI Module / AI Agents Integration

**Confidence: MEDIUM** -- Architecture verified from official docs and QED42 guide; specific tool plugin implementation needs validation.

The Drupal AI module (1.2.x stable) provides:
- Provider abstraction layer (48+ AI providers including Anthropic)
- OperationType system (Chat, Embedding, etc.)
- Tools/Function Calling support via ChatInterface

The AI Agents module (1.2.x stable) provides:
- Plugin-based agent framework
- `AiFunctionCall` plugin type for custom tools
- `solve()` method for programmatic agent execution
- Built-in agents (Field, Content Type, Taxonomy)

**Our custom AI tools (AiFunctionCall plugins):**

```php
// src/Plugin/AiFunctionCall/CreateProjectTool.php
#[AiFunctionCall(
  id: 'group_ai_pm_create_project',
  label: new TranslatableMarkup('Create Project'),
  description: new TranslatableMarkup('Create a new project in a group'),
)]
class CreateProjectTool extends AiFunctionCallBase {

  public function execute(array $arguments): string {
    // Create Project entity, add as GroupRelationship
    // Return success/error message to agent
  }

  public function getArguments(): array {
    return [
      'group_id' => ['type' => 'integer', 'description' => 'Group ID'],
      'title' => ['type' => 'string', 'description' => 'Project title'],
      'description' => ['type' => 'string', 'description' => 'Project description'],
    ];
  }
}
```

**Agent configuration (config entity, not code):**
A custom "Project Manager" agent is defined via config/install YAML, not code:
- System prompt: "You manage projects and tasks within Drupal groups..."
- Tools: CreateProject, CreateTask, UpdateTaskStatus, QueryProjects
- Max loops: 3 (create, verify, report)

### Dependencies

```yaml
# group_ai_pm.info.yml
name: 'Group AI Project Management'
type: module
description: 'AI-enhanced project management within Drupal Groups'
core_version_requirement: '^10 || ^11'
package: 'Group'
dependencies:
  - group:group
  - ai:ai
  - ai_agents:ai_agents
```

### Data Flow: User Creates Project via AI

```
1. User in Group context sends natural language:
   "Create a project called API Refactor with high priority"

2. AI Agents framework routes to Project Manager agent

3. Agent calls CreateProjectTool with parsed arguments:
   {group_id: 5, title: "API Refactor", priority: "high"}

4. Tool creates Project entity via ProjectManager service

5. Tool calls Group::addRelationship() to link Project to Group

6. Tool returns success message to agent

7. Agent confirms to user with project details and link
```

### Data Flow: Standard CRUD (No AI)

```
1. User navigates to Group -> Projects tab
2. Clicks "Add Project" action link
3. ProjectForm renders with Group context
4. On submit: Project entity created, GroupRelationship created
5. Redirects to project view within group context
```

## Workstream 3: Phase-Level Integration Eval

### Methodology Shift from v2.0

| Aspect | v2.0 | v3.0 |
|--------|------|------|
| What is tested | Individual skill content value | Plugin auto-triggering + skill content in realistic workflow |
| Execution model | Headless `claude -p` with explicit "read SKILL.md" | Interactive Claude Code with plugin installed vs not installed |
| Skill activation | Forced (prompt says "read the skill file") | Organic (description-based auto-trigger from natural prompts) |
| Task scope | Single eval prompt, one module | Full development phase (multiple files, multiple concerns) |
| Environment | Fresh D10, disposable | Persistent D10 with Group + AI modules installed |
| Eval unit | 13 individual skills x 1 prompt each | N phases x multiple skill domains per phase |

### Phase-Eval Architecture

Each development phase of the Group AI PM module becomes an eval:

```
Phase: "Create Project and Task entities with Group relations"

WITHOUT-PLUGIN baseline:
  1. Setup fresh D10 with Group + AI modules
  2. Headless claude -p with natural prompt (no plugin, no skills)
  3. Grade generated code against expectations
  4. Teardown

WITH-PLUGIN build:
  1. Same D10 environment
  2. Claude Code with --plugin-dir ./  (plugin installed)
  3. Natural prompt (same as baseline) -- skills auto-trigger
  4. Grade + compare to baseline
  5. Keep result -- this IS the real module code
```

**Key difference:** The with-plugin run produces the ACTUAL module code that ships. It is not a throwaway eval -- it is the real development process. The without-plugin baseline is the throwaway.

### Eval Environment

```
/tmp/d10-group-ai-pm/                  # Persistent ddev project
  web/
    modules/
      contrib/
        group/                          # Group 3.x (composer require)
        ai/                             # AI module (composer require)
        ai_agents/                      # AI Agents (composer require)
      custom/
        group_ai_pm/                    # Our module, built incrementally

/tmp/d10-group-ai-pm-baseline/         # Per-phase throwaway for without-plugin
  web/
    modules/
      contrib/
        group/
        ai/
        ai_agents/
      custom/
        group_ai_pm_baseline/           # Baseline code, discarded after grading
```

## Component Boundaries (All Workstreams)

### New Components

| Component | Responsibility | Communicates With | Location | Workstream |
|-----------|---------------|-------------------|----------|------------|
| Plugin manifest | Plugin identity, metadata | Claude Code plugin system | `.claude-plugin/plugin.json` | 1 |
| Project entity | Custom content entity type | Group relations, Task entity, services | `modules/group_ai_pm/src/Entity/Project.php` | 2 |
| Task entity | Custom content entity type | Project entity, Group relations, services | `modules/group_ai_pm/src/Entity/Task.php` | 2 |
| Group relation plugins | Link Project/Task to Groups | Group 3.x API | `modules/group_ai_pm/src/Plugin/GroupRelation/` | 2 |
| AI tools (AiFunctionCall) | Expose CRUD to AI Agents | AI Agents framework, entity services | `modules/group_ai_pm/src/Plugin/AiFunctionCall/` | 2 |
| Project Manager agent config | AI agent definition | AI Agents explorer, tools | `modules/group_ai_pm/config/install/` | 2 |
| Entity forms | CRUD UI for Projects/Tasks | Entity API, Form API | `modules/group_ai_pm/src/Form/` | 2 |
| Controllers | Dashboard, listings | Entity queries, services | `modules/group_ai_pm/src/Controller/` | 2 |
| Views plugins | Group-scoped project views | Views API, Group API | `modules/group_ai_pm/src/Plugin/Views/` | 2 |
| Block plugins | Dashboard blocks | Block API, entity queries | `modules/group_ai_pm/src/Plugin/Block/` | 2 |
| Access handlers | Per-group permission checks | Group roles, entity access | `modules/group_ai_pm/src/Access/` | 2 |
| Templates | Twig templates for output | Theme system | `modules/group_ai_pm/templates/` | 2 |
| Kernel/functional tests | Automated testing | PHPUnit, entity API | `modules/group_ai_pm/tests/` | 2 |
| Phase eval scripts | Setup/grade per phase | ddev, drush, headless claude | `eval/v3/` (or inline in phase plans) | 3 |

### Existing Components -- Integration Changes

| Existing Component | v3.0 Changes | Rationale |
|-------------------|-------------|-----------|
| `skills/drupal-*/SKILL.md` | Description optimization if auto-trigger fails | Descriptions may need tuning for plugin context |
| `skills/drupal-*/evals/evals.json` | NO CHANGE | v2.0 evals preserved for regression |
| `eval/setup-fresh-drupal10.sh` | May need Group/AI module variant | Phase eval needs D10 with contrib modules pre-installed |
| `eval/teardown-drupal-env.sh` | NO CHANGE | Still used for baseline teardown |
| `.claude/agents/eval-grader.md` | NO CHANGE | Still used for grading |
| `install.sh` | KEEP with deprecation note | Plugin install is preferred path |
| `README.md` | UPDATE | Plugin install as primary, install.sh as fallback |

## Patterns to Follow

### Pattern 1: Repo-as-Plugin with Minimal Restructuring

**What:** Add `.claude-plugin/plugin.json` at repo root. Keep everything else in place.
**Why:** Current `skills/` directory is already in the correct location for plugin discovery. No need to move files.
**Verification:** `claude --plugin-dir ./ ` should discover all 14 skills.

### Pattern 2: Module in `modules/` Subdirectory (Not Repo Root)

**What:** Place the Drupal module in `modules/group_ai_pm/` rather than at repo root.
**Why:** Separation of concerns. The repo is a Claude Code plugin; the module is a deliverable built using the plugin. Module code should be copyable to any Drupal site's `modules/custom/` directory.
**How:** `modules/group_ai_pm/` is a standard Drupal module directory structure, self-contained.

### Pattern 3: Group Relation Plugins for Entity Integration

**What:** Use Group 3.x's relation plugin system to integrate Project and Task entities.
**Why:** Group 3.x creates a GroupRelationshipType bundle for each GroupType + plugin combination. This is the standard way to make custom entities groupable -- do not use entity reference fields or ad-hoc solutions.
**How:** Create `GroupProject` and `GroupTask` classes extending `GroupRelationBase`, register as plugins.

### Pattern 4: AiFunctionCall Plugins for AI Tools

**What:** Create custom tools as `AiFunctionCall` plugins, not custom agent classes.
**Why:** AI Agents framework delegates to tools. "Only development needed is if there is not a tool for what your agent is trying to solve." Agents are config entities (UI-configurable); tools are code.
**How:** Implement `AiFunctionCallBase` with `execute()` and `getArguments()`.

### Pattern 5: Incremental Module Build Per Phase

**What:** Build the module in phases where each phase exercises specific skill domains.
**Why:** Each phase is both a development milestone AND an auto-trigger eval. If entities-fields skill does not trigger during entity creation phase, we catch it immediately.
**Phase-to-skill mapping:**

| Build Phase | Primary Skills Exercised | Secondary Skills |
|-------------|------------------------|------------------|
| Module scaffold + entity types | scaffold, entities-fields | config-storage |
| Routing + controllers | routing-controllers | access-security |
| Forms | forms-api | config-storage |
| Group integration (relation plugins) | plugins-blocks, entities-fields | access-security |
| AI tools integration | plugins-blocks | routing-controllers |
| Views + blocks | views-dev, plugins-blocks | caching |
| Theming + templates | theming | caching |
| Caching layer | caching | -- |
| Access control | access-security | -- |
| Testing | testing | all above |
| Batch/queue operations | batch-queue-cron | -- |

### Pattern 6: Without-Plugin Baseline First, Then Real Build

**What:** Generate baseline code WITHOUT the plugin installed before building the real module.
**Why:** Establishes what Claude produces from training data alone. The with-plugin version becomes the actual shipped code. Comparing the two proves plugin value in realistic workflow.
**How:** Per phase: headless baseline -> grade -> real build with plugin -> grade -> delta.

## Anti-Patterns to Avoid

### Anti-Pattern 1: Moving Skills for Plugin Packaging

**Why bad:** Skills are already in `skills/` at repo root -- exactly where plugins expect them. Moving to a subdirectory or restructuring breaks backward compatibility and gains nothing.
**Instead:** Add `.claude-plugin/plugin.json` only. No file moves.

### Anti-Pattern 2: Custom Agent Classes for AI Integration

**Why bad:** AI Agents framework uses config entity agents with tool plugins. Writing custom agent PHP classes is fighting the framework.
**Instead:** Define the "Project Manager" agent as a config entity (YAML in config/install/). Write tools as AiFunctionCall plugins.

### Anti-Pattern 3: Entity Reference Instead of Group Relations

**Why bad:** Using a plain `entity_reference` field to link Projects to Groups bypasses Group's permission system, role-based access, and relationship metadata.
**Instead:** Use Group relation plugins (GroupProject, GroupTask). This integrates with Group's access control, membership, and admin UI automatically.

### Anti-Pattern 4: Mixing Eval and Development Environments

**Why bad:** Building the real module in a throwaway eval environment risks losing work. Running evals against the real module risks corrupting it.
**Instead:** Baseline runs in disposable environment. Real module built in persistent environment. Clear separation.

### Anti-Pattern 5: Testing All Skills in One Phase

**Why bad:** A single mega-prompt exercising all skills tells you nothing about which skills triggered. Skill auto-triggering is contextual -- you need focused contexts.
**Instead:** Isolated phases where 1-3 skills should trigger. Observe which actually do. Iterate descriptions if needed.

## Build Order (Dependency Chain)

```
Phase 1: Plugin Packaging (no module dependencies)
  1. Create .claude-plugin/plugin.json
  2. Verify plugin loads: claude --plugin-dir ./
  3. Verify auto-triggering: natural Drupal prompts trigger skills
  4. Optimize descriptions if needed (skill-creator optimization loop)
  --> Deliverable: installable Claude Code plugin

Phase 2: Module Scaffold + Entity Types (depends: Phase 1 verified)
  1. Create modules/group_ai_pm/ scaffold
  2. Define Project + Task content entities
  3. Define entity handlers (form, list, access, route_provider)
  4. Install schema, base field definitions
  5. Eval: scaffold + entities-fields skills should auto-trigger
  --> Exercises: drupal-module-scaffold, drupal-entities-fields, drupal-config-storage

Phase 3: Routing + Forms (depends: Phase 2 entities exist)
  1. Define routes for CRUD operations
  2. Create entity form classes
  3. Create settings form with config schema
  4. Controller for project dashboard
  --> Exercises: drupal-routing-controllers, drupal-forms-api

Phase 4: Group Integration (depends: Phase 2 entities, Phase 3 forms)
  1. Create GroupProject and GroupTask relation plugins
  2. Configure GroupType with relation plugins installed
  3. Group-scoped access control
  4. Group membership roles (PM, Developer, Observer)
  --> Exercises: drupal-plugins-blocks, drupal-entities-fields, drupal-access-security

Phase 5: AI Agents Integration (depends: Phase 2 entities, Phase 4 group context)
  1. Create AiFunctionCall tool plugins (CRUD operations)
  2. Define Project Manager agent via config
  3. Wire agent to group context
  4. Test solve() programmatically
  --> Exercises: drupal-plugins-blocks, drupal-routing-controllers

Phase 6: Views + Blocks (depends: Phase 2 entities, Phase 4 group context)
  1. Views data integration for Project/Task entities
  2. Custom Views filter for group scoping
  3. Block plugins for dashboard (status, task list, AI assistant)
  --> Exercises: drupal-views-dev, drupal-plugins-blocks

Phase 7: Theming + Caching (depends: Phase 3 controllers, Phase 6 blocks)
  1. Twig templates for dashboard and task views
  2. Asset library (CSS/JS)
  3. Cache metadata on all render arrays
  4. Cache tag invalidation on entity CRUD
  --> Exercises: drupal-theming, drupal-caching

Phase 8: Access Control (depends: Phase 4 group roles)
  1. Custom permission definitions
  2. Access control handlers for Project/Task
  3. Group-aware route access checking
  4. CSRF protection on state-changing operations
  --> Exercises: drupal-access-security

Phase 9: Batch/Queue (depends: Phase 2 entities)
  1. Batch operation for bulk task updates
  2. Queue worker for AI agent processing
  3. Cron hook for periodic project status checks
  --> Exercises: drupal-batch-queue-cron

Phase 10: Testing (depends: all above)
  1. Kernel tests for entity CRUD
  2. Kernel tests for Group relations
  3. Functional tests for form submission
  4. Functional tests for AI tool execution
  --> Exercises: drupal-testing

Phase 11: Polish + Full Integration Eval
  1. Coding standards pass (phpcs)
  2. Full module install test
  3. End-to-end workflow verification
  4. Final delta report: with-plugin vs without-plugin per phase
  --> Exercises: drupal-coding-standards (cross-cutting)
```

**Critical path:** Phase 1 (plugin) -> Phase 2 (entities) -> Phase 4 (group integration) -> Phase 5 (AI integration). Everything else can be reordered within dependency constraints.

**Parallel opportunities:**
- Phase 1 can happen independently of all module work
- Phases 6, 7, 8, 9 are relatively independent once Phase 4 is complete
- Phase 10 testing can start as early as Phase 2 for entity tests

## Integration Points Summary

| Existing Component | v3.0 Integration | Changes Needed |
|-------------------|-----------------|----------------|
| `skills/drupal-*/SKILL.md` | Auto-discovered by plugin system | Potentially tune descriptions |
| `skills/drupal-*/evals/evals.json` | Kept for v2.0 regression testing | None |
| `eval/setup-fresh-drupal10.sh` | Extended for Group+AI module setup | New variant or flags |
| `eval/teardown-drupal-env.sh` | Used for baseline env cleanup | None |
| `.claude/agents/eval-grader.md` | Used for phase-level grading | May need updated rubric |
| `install.sh` | Backward compat, deprecation path | Add deprecation notice |
| `README.md` | Primary install method changes to plugin | Rewrite install section |
| `.gitignore` | Exclude `modules/` from plugin cache if needed | Minimal update |

## Scalability Considerations

| Concern | Current (14 skills) | v3.0 (14 skills + module) | Future (20+ skills) |
|---------|---------------------|---------------------------|---------------------|
| Skill description budget | ~5,600 chars / 16,000 budget | Same (module does not add skills) | Monitor with `/context` command |
| Plugin load time | N/A (no plugin) | Fast (14 SKILL.md frontmatter reads) | Fast (descriptions only loaded) |
| Module complexity | N/A | Medium (2 entities, 4 tools, 6 plugins) | N/A -- module is fixed scope |
| Eval time per phase | N/A | ~10 min (setup + baseline + build + grade) | Same |
| ddev resource usage | 2 instances per skill eval | 1 persistent + 1 throwaway per phase | Same |

## Sources

- [Claude Code Plugins docs](https://code.claude.com/docs/en/plugins) (HIGH): Plugin structure, manifest, directory layout
- [Claude Code Plugins Reference](https://code.claude.com/docs/en/plugins-reference) (HIGH): Full manifest schema, component specifications
- [Claude Code Skills docs](https://code.claude.com/docs/en/skills) (HIGH): Auto-triggering, description matching, frontmatter fields
- [Drupal Group module](https://www.drupal.org/project/group) (HIGH): Version 3.3.5, D10/D11 compat
- [Group addRelationship() change record](https://www.drupal.org/node/3292844) (HIGH): Group 3.x API rename confirmed
- [Drupal AI module](https://www.drupal.org/project/ai) (HIGH): Version 1.2.11, provider abstraction
- [Drupal AI Agents module](https://www.drupal.org/project/ai_agents) (HIGH): Version 1.2.3, agent framework
- [AI Agents developer docs](https://project.pages.drupalcode.org/ai/2.0.x/agents/running/) (MEDIUM): solve() method, plugin manager
- [QED42 AI Agents guide](https://www.qed42.com/insights/exploring-drupals-ai-agents-a-practical-guide-for-site-builders) (MEDIUM): AiFunctionCall plugin pattern, tool creation
- [AI Provider developer guide](https://project.pages.drupalcode.org/ai/1.1.x/developers/writing_an_ai_provider/) (MEDIUM): OperationType system, plugin structure
- [Skill auto-trigger deep dive](https://mikhail.io/2025/10/claude-code-skills/) (MEDIUM): How available_skills list is built
- [Anthropic skills repo](https://github.com/anthropics/skills/blob/main/skills/skill-creator/SKILL.md) (HIGH): skill-creator anatomy reference
- [Shared bundle class for group relationships](https://www.drupal.org/node/3383311) (MEDIUM): Group 3.x plugin architecture
- [Document AI module architecture issue](https://www.drupal.org/project/ai/issues/3566997) (LOW): Architecture documentation in progress

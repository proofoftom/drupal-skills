# Feature Research: v3.0 Group AI Project Management

**Domain:** Drupal contrib module (Group-based project management with AI Agents integration) + Claude Code plugin packaging + skill auto-trigger evaluation
**Researched:** 2026-03-07
**Confidence:** MEDIUM (Group and AI modules verified via drupal.org docs; Claude Code plugin structure verified via official docs; integration patterns between them are novel and uncharted territory)

## Feature Landscape

This milestone has three distinct feature domains that converge:

1. **Plugin Packaging** -- restructure existing 14 skills as a Claude Code plugin with auto-triggering
2. **Group-based Project Management Module** -- custom Drupal contrib module built on Group 3.x
3. **AI Agents Integration** -- connect project management to Drupal AI/AI Agents framework

Each domain has its own table stakes, but the real value is proving them together: building a real module with the plugin installed to validate auto-triggering across all skill domains.

---

### Table Stakes (Users Expect These)

#### A. Plugin Packaging (Claude Code Plugin)

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| **`.claude-plugin/plugin.json` manifest** | Required for Claude Code to recognize the plugin. Contains name, description, version, author. Without it, skills are not namespaced or discoverable as a plugin. | LOW | Name becomes the namespace prefix: `/drupal-skills:skill-name`. Version should be 3.0.0 to match milestone. |
| **`skills/` directory with SKILL.md per skill** | Plugin structure requires `skills/<skill-name>/SKILL.md`. This is the exact layout already used in the repo. Current `skills/drupal-*/SKILL.md` maps 1:1. | LOW | Already matches the required structure. No restructuring needed -- the repo root IS the plugin directory. |
| **Skill descriptions optimized for model invocation** | Skills must auto-trigger from natural prompts. The `description` field is the ONLY mechanism for model invocation. Current descriptions are good but may need tuning. Claude undertriggers by default, so descriptions need to be "pushy" per Anthropic best practices. | MEDIUM | Each description is max 1024 chars. Must be third-person. Must include both WHAT it does and WHEN to use it. Current descriptions already follow this pattern. |
| **`disable-model-invocation: false` (default)** | All 14 skills should be model-invocable. The default is false, so no explicit setting needed. Skills should activate when Claude sees Drupal development context. | LOW | Already the default. Verify no skills accidentally have this set to true. |
| **CLAUDE.md at plugin root** | System-level instructions applying across all skills. Should contain cross-cutting guidance like "always use D10 annotation syntax unless explicitly asked for D11 attributes" and "load coding-standards alongside any domain skill." | LOW | New file. Replaces what install.sh currently does by copying to ~/.claude/skills/. |
| **install.sh updated or replaced** | Users need a way to install. Plugin system supports `claude --plugin-dir ./drupal-skills` for local dev and marketplace distribution for sharing. The existing install.sh (copy to ~/.claude/skills/) should still work as fallback. | LOW | Two installation paths: plugin-dir for dev, marketplace for distribution. Keep install.sh as legacy option. |

#### B. Group-based Project Management Module

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| **Custom content entity: Task** | Core entity for project management. Must have title, description, status, priority, assignee (entity reference to user), due date, and group reference. Replaces what Drupal PM provides but scoped to Group context. | HIGH | Exercises drupal-entities-fields, drupal-module-scaffold skills. Needs base fields, form handlers, list builders, access handlers. |
| **Custom content entity: Project** | Container for tasks within a group. Has title, description, status, owner. Relates to Group via GroupRelationship. Projects belong to exactly one group. | HIGH | Core data model. GroupRelation plugin needed to make Project a group content type. |
| **Group integration via GroupRelation plugins** | Projects and Tasks must be group content -- accessible only to group members, with group-scoped permissions. Requires implementing GroupRelation plugins (src/Plugin/Group/Relation/). | HIGH | Group 3.x API. GroupRelationBase class must be extended. Exercises drupal-plugins-blocks skill (plugin DI pattern), drupal-access-security skill (access results). |
| **Group-scoped permissions** | "create project", "edit any project", "edit own project", "delete own project", same for tasks. Must integrate with Group's flexible_permissions module. | MEDIUM | Group handles permission calculation via IndividualGroupPermissionCalculator. Module declares permissions in group relation plugin annotation/attribute. |
| **Task status workflow** | At minimum: Open, In Progress, Done. Status transitions need to be enforced. Kanban-style status model is table stakes for project management. | MEDIUM | Could be a simple allowed_values list field, or a proper state machine. Start simple (allowed_values). |
| **Views integration** | Users expect to see task lists, project boards, and group dashboards via Views. Need hook_views_data() for custom entity fields and/or entity-based Views integration. | MEDIUM | Exercises drupal-views-dev skill. Content entities automatically get Views integration, but custom base fields may need explicit Views data definitions. |
| **Routes and admin pages** | List projects, view project, list tasks within project, task detail page. Standard CRUD routes via entity route provider + custom routes for dashboards. | MEDIUM | Exercises drupal-routing-controllers skill. Entity route providers handle most CRUD. Custom routes for project board views. |
| **Config forms for module settings** | Default task statuses, AI provider selection, group-level AI settings. Standard ConfigFormBase with config schema. | LOW | Exercises drupal-forms-api, drupal-config-storage skills. |
| **Proper cache metadata** | Task lists must invalidate when tasks change. Project views must respect group membership cache contexts. Cache tags on entities, cache contexts for group access. | MEDIUM | Exercises drupal-caching skill. Critical for Group module integration -- group membership is a cache context. |

#### C. AI Agents Integration

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| **AI module dependency and provider configuration** | Module must depend on `ai` module and allow admin to configure which AI provider to use for project management features. Uses `ai.provider` service. | LOW | Standard dependency declaration in .info.yml. Config form for provider selection. |
| **Custom AI Agent: Project Assistant** | An AI Agent plugin (`src/Plugin/AiAgent/ProjectAssistant.php`) that can answer questions about project status, summarize tasks, suggest priorities. Implements AiAgentInterface / extends AiAgentBase. | HIGH | Exercises drupal-plugins-blocks skill (plugin pattern). AI Agents use plugin discovery. Must implement `agentsCapabilities()`, `determineSolvability()`, `solve()`. |
| **Custom tools for the agent** | The Project Assistant agent needs tools to: list tasks in a project, get task details, update task status, create tasks. These are the Drupal API operations the agent can invoke. | HIGH | AI Agents framework uses tool calling. Each tool maps to an entity API operation. Must implement proper access checks. |
| **Task summarization/analysis service** | A service that calls the AI provider to analyze project state: overdue tasks, blocked items, sprint velocity. Uses provider-agnostic API (`ChatInput`/`ChatMessage`). | MEDIUM | Standard Drupal service with DI. Exercises drupal-routing-controllers skill (service DI). Provider-agnostic pattern via `getNormalized()`. |

---

### Differentiators (Competitive Advantage)

These features are not required for the module to function but create compelling value and exercise more skill domains.

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| **AI-powered task creation from natural language** | Users describe what needs to be done in plain text; AI agent creates properly structured tasks with appropriate fields, priority, and assignment suggestions. | HIGH | Exercises AI Agents text-to-action pattern. The differentiator is GROUP-SCOPED AI actions -- agent must respect group membership when suggesting assignees. |
| **Batch task operations via AI** | "Move all overdue tasks to next sprint" or "Reassign John's tasks to Jane" -- AI interprets natural language commands and executes batch entity operations. | HIGH | Exercises drupal-batch-queue-cron skill. AI determines the batch operation, queue workers execute it. |
| **Project health dashboard block** | Block plugin showing AI-generated project health analysis: risk items, velocity trends, completion predictions. Cached with appropriate tags/contexts. | MEDIUM | Exercises drupal-plugins-blocks, drupal-caching, drupal-theming skills in combination. Block calls AI service, renders themed output with cache metadata. |
| **Task notification via cron** | Cron job that checks for overdue tasks, approaching deadlines, and sends notifications. Can optionally include AI-generated summaries. | MEDIUM | Exercises drupal-batch-queue-cron skill. Hook_cron + queue worker pattern for scalability. |
| **Automated testing suite** | Kernel tests for entity CRUD, access control, AI service mocking. Functional tests for form submission, Views rendering. | HIGH | Exercises drupal-testing skill. Kernel tests for services/entities, functional tests for UI. AI provider can be mocked via test double. |
| **Database schema for analytics** | Custom table tracking task state changes over time (task_history). hook_schema() + hook_update_N() for schema management. Exposed to Views. | MEDIUM | Exercises drupal-database-api, drupal-views-dev skills. Analytics data is supplementary to entity storage. |
| **Twig templates for task cards and project boards** | Custom theme hooks and templates for task cards, kanban columns, project overview. CSS/JS library for drag-and-drop (stretch). | MEDIUM | Exercises drupal-theming skill. hook_theme() + preprocess functions + .libraries.yml. |

---

### Anti-Features (Commonly Requested, Often Problematic)

| Feature | Why Requested | Why Problematic | Alternative |
|---------|---------------|-----------------|-------------|
| **Full Gantt chart / timeline visualization** | Project management tools typically have timeline views | Massive frontend complexity (JS libraries, drag-resize, dependency arrows). Way out of scope for a module that validates Drupal skills. | Simple list views with due dates and status columns. Views provides adequate tabular/grid display. |
| **Real-time collaboration / WebSocket updates** | Modern PM tools show live updates | Drupal is not architected for real-time. Adding WebSocket infrastructure is a separate infrastructure concern that does not exercise any Drupal skill. | Standard page reload. Cache invalidation ensures fresh data on next request. |
| **Standalone chat interface for AI agent** | Chatbot-style UI for interacting with project AI | The AI Agents module already provides a chatbot framework. Building a custom one duplicates effort and does not exercise Drupal skills. | Use AI Agents' built-in chatbot integration. The module provides the agent, the framework provides the UI. |
| **Full sprint/scrum workflow (velocity, burndown, story points)** | Enterprise PM expectations | Over-engineering for a contrib module whose primary purpose is validating skill auto-triggering. Adds entity complexity without exercising new skill domains. | Simple status workflow (Open/In Progress/Done) covers the Group access + entity lifecycle patterns. |
| **Multi-group project sharing** | Projects spanning multiple groups | Group module deliberately scopes content to a single group. Cross-group sharing requires Subgroup module and complex permission layering. | Projects belong to exactly one group. Users who need cross-group visibility join multiple groups. |
| **Import/export from Jira/Asana/etc.** | Migration path from existing tools | Integration code with third-party APIs does not exercise Drupal skills. It is REST client code, not Drupal module patterns. | Manual task creation and AI-assisted bulk creation cover the use case. |
| **Mobile-responsive kanban with drag-and-drop** | Modern UX expectation | JavaScript-heavy feature that doesn't exercise any Drupal PHP skill. Frontend complexity adds risk without eval value. | Server-rendered task lists with form-based status changes. If drag-and-drop is desired, use SortableJS as a library attachment (exercises drupal-theming at most). |
| **Skill content changes based on v3.0 findings** | Feedback loop temptation | Skills are LOCKED from v2.0. v3.0 proves auto-triggering and integration, not skill content. Changing content invalidates v2.0 benchmarks. | Document findings for a potential v4.0 skill iteration milestone. |

---

## Feature Dependencies

```
[Plugin Packaging]
    plugin.json manifest
        |
    skills/ directory (already exists)
        |
    CLAUDE.md root instructions
        |
    description optimization ──> auto-trigger eval

[Module: Core Entities]
    drupal-module-scaffold (module skeleton)
        |
        +-- Project entity ──requires──> drupal-entities-fields
        |       |
        |       +-- GroupRelation plugin ──requires──> drupal-plugins-blocks (plugin DI)
        |       |                         ──requires──> drupal-access-security (access results)
        |       |
        +-- Task entity ──requires──> drupal-entities-fields
                |
                +-- GroupRelation plugin (same pattern as Project)
                |
                +-- Task status field (base field with allowed_values)

[Module: UI Layer]
    Routes/Controllers ──requires──> Core Entities
        |                ──uses──> drupal-routing-controllers
        |
    Views integration ──requires──> Core Entities
        |              ──uses──> drupal-views-dev
        |
    Config forms ──uses──> drupal-forms-api, drupal-config-storage
        |
    Templates/theming ──requires──> Routes (need pages to theme)
                      ──uses──> drupal-theming
        |
    Cache metadata ──requires──> All render output
                   ──uses──> drupal-caching

[Module: AI Integration]
    AI provider config ──requires──> Config forms
        |               ──uses──> drupal-config-storage
        |
    ProjectAssistant agent plugin ──requires──> Core Entities (needs tasks/projects to query)
        |                         ──requires──> AI module dependency
        |                         ──uses──> drupal-plugins-blocks (plugin pattern)
        |
    Custom agent tools ──requires──> ProjectAssistant agent
        |
    Task summarization service ──requires──> AI provider config
                               ──uses──> drupal-routing-controllers (service DI)

[Module: Background Processing]
    Cron notifications ──requires──> Core Entities
                       ──uses──> drupal-batch-queue-cron
        |
    Batch AI operations ──requires──> AI Integration + Core Entities
                        ──uses──> drupal-batch-queue-cron

[Module: Quality]
    Testing suite ──requires──> All module features
                  ──uses──> drupal-testing
        |
    Analytics schema ──uses──> drupal-database-api
                     ──enhances──> Views integration

[Eval: Auto-Trigger Validation]
    Plugin installed ──requires──> Plugin Packaging complete
        |
    Without-plugin baseline per phase ──requires──> ddev environment
        |
    With-plugin module build per phase ──requires──> Plugin installed + ddev environment
        |
    Phase-level skill coverage tracking ──requires──> All phases complete
```

### Dependency Notes

- **Plugin Packaging must come first:** The entire v3.0 eval methodology depends on skills being installed as a plugin that auto-triggers. Without this, the module-building phases cannot validate auto-triggering.
- **Core Entities before UI:** Routes, Views, and templates all depend on the entity types being defined. Entity creation is the foundational phase.
- **AI Integration requires Core Entities:** The AI agent needs tasks and projects to query. AI features layer on top of the data model.
- **Testing is last:** Tests validate all other features. Cannot write tests for features that do not exist yet.
- **Each module phase isolates skill domains:** This is intentional -- each phase should exercise 1-3 skills, allowing per-phase auto-trigger evaluation.

---

## MVP Definition

### Launch With (Phase 1-3: Plugin + Core Module)

- [ ] **Plugin packaging** -- `.claude-plugin/plugin.json`, CLAUDE.md, verify skills auto-trigger from natural Drupal prompts
- [ ] **Module scaffold** -- .info.yml with Group and AI module dependencies, PSR-4 structure, .module file
- [ ] **Project entity** -- Content entity with base fields, form handler, list builder, access handler
- [ ] **Task entity** -- Content entity with status, priority, assignee, due date, project reference
- [ ] **GroupRelation plugins** -- Both entities registered as group content types
- [ ] **Group-scoped permissions** -- CRUD permissions for projects and tasks within groups
- [ ] **Basic routes** -- Entity CRUD routes via route providers + project task listing page
- [ ] **Config forms** -- Module settings with config schema

### Add After Validation (Phase 4-6: UI + AI)

- [ ] **Views integration** -- Task list views, project dashboards, group-scoped displays
- [ ] **Cache metadata** -- Proper tags/contexts on all render output, group membership cache context
- [ ] **AI provider configuration** -- Settings form for selecting AI provider
- [ ] **ProjectAssistant AI Agent plugin** -- Agent with tools for task CRUD and project status queries
- [ ] **Task summarization service** -- AI-powered project health analysis
- [ ] **Templates and theming** -- Custom Twig templates for task cards and project views
- [ ] **Cron notifications** -- Overdue task detection and notification queue

### Future Consideration (Phase 7+: Polish)

- [ ] **Automated test suite** -- Kernel and functional tests for all features
- [ ] **Analytics schema** -- Task history tracking via custom database table
- [ ] **Batch AI operations** -- Natural language batch commands via AI agent
- [ ] **AI-powered task creation** -- Natural language to structured task entity
- [ ] **Project health dashboard block** -- Block plugin with AI analysis and cache metadata

---

## Feature Prioritization Matrix

| Feature | User Value | Implementation Cost | Eval Value (skill coverage) | Priority |
|---------|------------|--------------------|-----------------------------|----------|
| Plugin packaging | HIGH | LOW | HIGH (enables all auto-trigger eval) | P1 |
| Module scaffold | HIGH | LOW | HIGH (drupal-module-scaffold) | P1 |
| Project + Task entities | HIGH | HIGH | HIGH (drupal-entities-fields) | P1 |
| GroupRelation plugins | HIGH | HIGH | HIGH (drupal-plugins-blocks, drupal-access-security) | P1 |
| Group-scoped permissions | HIGH | MEDIUM | HIGH (drupal-access-security) | P1 |
| Basic routes | HIGH | MEDIUM | MEDIUM (drupal-routing-controllers) | P1 |
| Config forms + schema | MEDIUM | LOW | MEDIUM (drupal-forms-api, drupal-config-storage) | P2 |
| Views integration | MEDIUM | MEDIUM | MEDIUM (drupal-views-dev) | P2 |
| Cache metadata | MEDIUM | MEDIUM | HIGH (drupal-caching) | P2 |
| AI Agent plugin | HIGH | HIGH | HIGH (novel integration) | P2 |
| Task summarization | MEDIUM | MEDIUM | MEDIUM (service DI) | P2 |
| Templates/theming | MEDIUM | MEDIUM | MEDIUM (drupal-theming) | P2 |
| Cron notifications | LOW | MEDIUM | MEDIUM (drupal-batch-queue-cron) | P3 |
| Testing suite | HIGH | HIGH | HIGH (drupal-testing) | P3 |
| Analytics schema | LOW | MEDIUM | MEDIUM (drupal-database-api) | P3 |
| Batch AI operations | LOW | HIGH | MEDIUM (drupal-batch-queue-cron) | P3 |

**Priority key:**
- P1: Must have -- core module structure and eval infrastructure
- P2: Should have -- completes skill coverage and AI integration
- P3: Nice to have -- exercises remaining skills, adds polish

---

## Skill Coverage Map

The following shows which features exercise which existing skills, ensuring maximum eval coverage:

| Skill | Exercised By | Phase Suggestion |
|-------|-------------|-----------------|
| drupal-module-scaffold | Module skeleton, .info.yml, dependencies | Phase 1 (early) |
| drupal-entities-fields | Project entity, Task entity, base fields, form/list/access handlers | Phase 2 (core) |
| drupal-routing-controllers | Entity routes, custom dashboard routes, service DI | Phase 2-3 |
| drupal-plugins-blocks | GroupRelation plugins, AI Agent plugin, dashboard block | Phase 2, 5 |
| drupal-access-security | Group permissions, access handlers, AccessResult caching | Phase 2-3 |
| drupal-forms-api | Config forms, entity forms (if custom beyond default) | Phase 3 |
| drupal-config-storage | Module settings, config schemas, config/install YAMLs | Phase 3 |
| drupal-views-dev | Task list views, project dashboards, hook_views_data() | Phase 4 |
| drupal-caching | Cache tags on entities, group membership cache context, block cache | Phase 4 |
| drupal-theming | Task card templates, project board layout, .libraries.yml | Phase 4 |
| drupal-batch-queue-cron | Notification cron, AI batch operations, queue workers | Phase 5-6 |
| drupal-testing | Kernel tests (entity CRUD, access), functional tests (forms, views) | Phase 6-7 |
| drupal-database-api | Analytics table schema, hook_update_N() | Phase 6 |
| drupal-coding-standards | Cross-cutting baseline for all generated code | All phases |

**Coverage:** 14/14 skills (100%) are exercised by the planned feature set.

---

## Competitor Feature Analysis

| Feature | Drupal PM | Burndown | OpenLucius | Our Approach |
|---------|-----------|----------|------------|--------------|
| Task entity | Custom entity types | Custom entities | Node-based | Custom content entity with Group integration |
| Group scoping | No group integration | No group integration | OpenSocial groups | Native Group 3.x GroupRelation plugins |
| AI integration | None | None | None | AI Agents framework + custom agent + tool calling |
| Kanban board | PM App submodule | Built-in | Built-in | Views-based list with status columns (no drag-and-drop) |
| Permissions | Custom PM permissions | Role-based | OpenSocial permissions | Group-scoped PBAC via flexible_permissions |
| Sprint tracking | Limited | Full (burndown charts) | No | Not in scope (anti-feature) |
| API access | No dedicated API | No | REST | AI Agent tools provide structured API access |

**Our differentiation:** No existing Drupal project management module integrates with the Group module's access control system AND the AI Agents framework. This combination is unique and provides the eval surface area needed for v3.0.

---

## Sources

- [Group module - drupal.org](https://www.drupal.org/project/group) -- version 3.x, entity architecture
- [Group v2/v3 guides](https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-module-documentation/group/group-v2v3-guides) -- GroupRelationship API changes
- [AI Agents module - drupal.org](https://www.drupal.org/project/ai_agents) -- framework, built-in agents, custom agent creation
- [AI module - drupal.org](https://www.drupal.org/project/ai) -- provider abstraction, ChatInput/ChatMessage API
- [AI integration in contrib modules](https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-module-documentation/ai/ai-how-to-use-it-or-integrate-it-in-contrib-modules) -- provider-agnostic API pattern
- [AI Agents developer docs](https://project.pages.drupalcode.org/ai_agents) -- AiAgentInterface, plugin structure
- [Drupal's AI Roadmap for 2026](https://www.drupal.org/blog/drupals-ai-roadmap-for-2026) -- background agents, context management
- [Claude Code plugin docs](https://code.claude.com/docs/en/plugins) -- plugin.json structure, plugin directory layout
- [Claude Code skills docs](https://code.claude.com/docs/en/skills) -- SKILL.md anatomy, model invocation, frontmatter fields
- [Skill authoring best practices](https://platform.claude.com/docs/en/agents-and-tools/agent-skills/best-practices) -- description writing, progressive disclosure, anti-patterns
- [Drupal PM module](https://www.drupal.org/project/pm) -- existing PM landscape
- [Burndown module](https://www.drupal.org/project/burndown) -- agile PM alternative
- [Group permissions explanation](https://www.drupal.org/docs/contributed-modules/group/the-permission-layers-explained) -- PBAC via flexible_permissions

---
*Feature research for: v3.0 Group AI Project Management*
*Researched: 2026-03-07*

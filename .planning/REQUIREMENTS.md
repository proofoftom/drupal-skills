# Requirements: Drupal Skills v3.0

**Defined:** 2026-03-07
**Core Value:** Claude can generate correct, production-ready Drupal module code across all major development domains when guided by these skills.

## v3.0 Requirements

Requirements for v3.0 milestone. Each maps to roadmap phases.

### Plugin Packaging

- [x] **PLUG-01**: Plugin manifest (.claude-plugin/plugin.json) registers all 14 skills with correct namespace
- [ ] **PLUG-02**: Skill descriptions optimized for auto-triggering from natural Drupal development prompts (>80% activation rate)
- [x] **PLUG-03**: Minimal CLAUDE.md at plugin root with only non-obvious, project-specific rules (developer-written, not LLM-generated)
- [x] **PLUG-04**: install.sh deprecated with migration path documented for plugin-based installation

### Module Scaffold

- [ ] **SCAF-01**: Module skeleton with .info.yml declaring Group and AI module dependencies
- [ ] **SCAF-02**: Composer.json with pinned versions for drupal/group, drupal/ai, drupal/ai_agents
- [ ] **SCAF-03**: Module directory structure follows PSR-4 with src/, config/, templates/ directories

### Entities

- [ ] **ENTY-01**: Project custom content entity with title, description, status, and owner base fields
- [ ] **ENTY-02**: Task custom content entity with title, description, status, priority, assignee, due date, and project reference fields
- [ ] **ENTY-03**: Entity form handlers for Project and Task with proper validation
- [ ] **ENTY-04**: Entity list builders for Project and Task with sortable columns
- [ ] **ENTY-05**: Entity access handlers enforcing group-based permissions

### Group Integration

- [ ] **GRP-01**: GroupProject relation plugin linking Project entities to groups via Group 3.x API (GroupRelationship, not GroupContent)
- [ ] **GRP-02**: GroupTask relation plugin linking Task entities to groups via Group 3.x API
- [ ] **GRP-03**: Group-scoped CRUD permissions for projects (create/edit own/edit any/delete own/delete any)
- [ ] **GRP-04**: Group-scoped CRUD permissions for tasks (create/edit own/edit any/delete own/delete any)
- [ ] **GRP-05**: Open Social compatibility considered in entity and permission design

### Routing & Forms

- [ ] **ROUTE-01**: Entity CRUD routes via entity route providers for Project and Task
- [ ] **ROUTE-02**: Custom dashboard controller showing project overview within a group
- [ ] **ROUTE-03**: Module settings form (ConfigFormBase) with config schema for default statuses and AI provider
- [ ] **ROUTE-04**: Entity form classes with proper form validation and submit handlers

### Views & Display

- [ ] **VIEW-01**: Views data integration for Project and Task entities with all base fields exposed
- [ ] **VIEW-02**: Default task list view with status, priority, assignee filters
- [ ] **VIEW-03**: Project dashboard view with task counts and status summary
- [ ] **VIEW-04**: Group-scoped Views filters restricting display to current group context

### AI Integration

- [ ] **AI-01**: AiFunctionCall tool plugins for task CRUD operations (CreateTask, UpdateTaskStatus, QueryTasks)
- [ ] **AI-02**: AiFunctionCall tool plugins for project operations (CreateProject, QueryProjects)
- [ ] **AI-03**: ProjectManager AI Agent config entity with system prompt and tool set
- [ ] **AI-04**: Service abstraction layer wrapping AI provider calls for API stability
- [ ] **AI-05**: AI module declared as optional dependency (module functions without it)

### Theming & Caching

- [ ] **THEME-01**: Twig templates for task card and project view with hook_theme() registration
- [ ] **THEME-02**: .libraries.yml with CSS for task cards and project layouts
- [ ] **CACHE-01**: Cache tags on Project and Task entities with proper invalidation
- [ ] **CACHE-02**: Group membership cache context applied to all group-scoped render output
- [ ] **CACHE-03**: Block plugin caching with appropriate max-age and cache tags

### Background Processing

- [ ] **BG-01**: Cron hook for overdue task detection
- [ ] **BG-02**: Queue worker for task notification processing
- [ ] **BG-03**: Block plugins for project status and task list displays

### Testing & Quality

- [ ] **TEST-01**: Kernel tests for entity CRUD operations and access control
- [ ] **TEST-02**: Functional tests for forms, views, and route access
- [ ] **TEST-03**: phpcs compliance pass on entire module (drupal/coder standards)

### Eval & Validation

- [ ] **EVAL-01**: Auto-trigger validation confirming skills activate from natural development prompts with plugin installed
- [ ] **EVAL-02**: Without-plugin baseline generated per phase for comparison
- [ ] **EVAL-03**: Phase-level delta report comparing with-plugin vs without-plugin output quality
- [ ] **EVAL-04**: Full module install test and end-to-end workflow verification

## Future Requirements

Deferred to v4.0+. Tracked but not in current roadmap.

### Advanced AI

- **AI-06**: AI-powered task creation from natural language free text
- **AI-07**: Batch AI operations from natural language commands
- **AI-08**: Project health dashboard block with AI-generated analysis

### Analytics

- **DB-01**: Task history tracking database table (hook_schema)
- **DB-02**: Views integration for analytics data

## Out of Scope

Explicitly excluded. Documented to prevent scope creep.

| Feature | Reason |
|---------|--------|
| Gantt charts / timeline visualization | Massive frontend complexity, doesn't exercise Drupal PHP skills |
| Real-time collaboration / WebSocket | Drupal not architected for real-time, separate infrastructure concern |
| Standalone AI chat interface | AI Agents module provides chatbot framework already |
| Sprint/scrum/velocity/burndown | Over-engineering for skill validation purpose |
| Multi-group project sharing | Group module scopes to single group by design |
| Jira/Asana import/export | REST client code, not Drupal module patterns |
| Drag-and-drop kanban | JavaScript-heavy, doesn't exercise Drupal PHP skills |
| Skill content changes | Skills locked from v2.0; changing content invalidates benchmarks |
| Multi-run variance analysis | Single-run sufficient for tier classification (v2.0 decision) |

## Traceability

Which phases cover which requirements. Updated during roadmap creation.

| Requirement | Phase | Status |
|-------------|-------|--------|
| PLUG-01 | Phase 13 | Complete |
| PLUG-02 | Phase 13 | Pending |
| PLUG-03 | Phase 13 | Complete |
| PLUG-04 | Phase 13 | Complete |
| EVAL-01 | Phase 13 | Pending |
| SCAF-01 | Phase 14 | Pending |
| SCAF-02 | Phase 14 | Pending |
| SCAF-03 | Phase 14 | Pending |
| ENTY-01 | Phase 14 | Pending |
| ENTY-02 | Phase 14 | Pending |
| ENTY-03 | Phase 14 | Pending |
| ENTY-04 | Phase 14 | Pending |
| ROUTE-01 | Phase 14 | Pending |
| ROUTE-02 | Phase 14 | Pending |
| ROUTE-03 | Phase 14 | Pending |
| ROUTE-04 | Phase 14 | Pending |
| EVAL-02 | Phase 14 | Pending |
| GRP-01 | Phase 15 | Pending |
| GRP-02 | Phase 15 | Pending |
| GRP-03 | Phase 15 | Pending |
| GRP-04 | Phase 15 | Pending |
| GRP-05 | Phase 15 | Pending |
| ENTY-05 | Phase 15 | Pending |
| AI-01 | Phase 15 | Pending |
| AI-02 | Phase 15 | Pending |
| AI-03 | Phase 15 | Pending |
| AI-04 | Phase 15 | Pending |
| AI-05 | Phase 15 | Pending |
| VIEW-01 | Phase 16 | Pending |
| VIEW-02 | Phase 16 | Pending |
| VIEW-03 | Phase 16 | Pending |
| VIEW-04 | Phase 16 | Pending |
| THEME-01 | Phase 16 | Pending |
| THEME-02 | Phase 16 | Pending |
| CACHE-01 | Phase 16 | Pending |
| CACHE-02 | Phase 16 | Pending |
| CACHE-03 | Phase 16 | Pending |
| BG-01 | Phase 16 | Pending |
| BG-02 | Phase 16 | Pending |
| BG-03 | Phase 16 | Pending |
| TEST-01 | Phase 17 | Pending |
| TEST-02 | Phase 17 | Pending |
| TEST-03 | Phase 17 | Pending |
| EVAL-03 | Phase 17 | Pending |
| EVAL-04 | Phase 17 | Pending |

**Coverage:**
- v3.0 requirements: 45 total
- Mapped to phases: 45
- Unmapped: 0

---
*Requirements defined: 2026-03-07*
*Last updated: 2026-03-08 after roadmap creation (traceability added)*

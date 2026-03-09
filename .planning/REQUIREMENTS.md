# Requirements: Drupal Skills

**Defined:** 2026-03-09
**Core Value:** Claude can generate correct, production-ready Drupal module code across all major development domains when guided by these skills.

## v5.0 Requirements

### Tooling

- [x] **TOOL-01**: Drush skill teaches Drush usage for development: self-verification recipes (route/service/entity/permission inspection via built-in commands), scaffolding via `drush generate`, debugging via `watchdog:show`, and the Drupal-first principle (entity:save over sql:query). Command-authoring patterns preserved as reference file.
- [x] **TOOL-02**: Drush skill includes eval assertions targeting Drush usage patterns (built-in commands over raw php-eval, watchdog checks, entity API over SQL, drush generate for scaffolding)
- [x] **TOOL-03**: Eval-author Opus subagent designs three-tier assertions (static + runtime + browser) from skill content, module code, and phase prompt
- [x] **TOOL-04**: Eval-author enforces assertion category distribution (60% differentiating, 20% wiring, max 20% structural) to prevent tautological assertions
- [x] **TOOL-05**: Eval-author output validated against Phase 18 gold-standard before relying on it for new phases
- [x] **TOOL-06**: entities-fields skill updated with bundle_of pattern and hook_update_N() for schema changes
- [x] **TOOL-07**: caching skill updated with lazy_builder pattern and CacheableMetadata bubbling

### AI Features

- [ ] **AI-01**: AiTaskService encapsulates all AI logic, injectable by both REST controllers and AiFunctionCall plugins
- [ ] **AI-02**: AiTaskService uses optional AI dependency (@? injection) so module functions without AI module
- [ ] **AI-03**: CreateTaskTool AiFunctionCall plugin creates tasks from natural language, following existing CreateProjectTool pattern
- [ ] **AI-04**: REST endpoint (POST) accepts natural language text and returns created task with parsed fields
- [ ] **AI-05**: BatchUpdateTool processes multiple tasks via Queue API with dry-run mode and per-item error reporting
- [ ] **AI-06**: Queue workers implement three-catch pattern (SuspendQueueException, AiRateLimitException + RequeueException, generic Exception)
- [ ] **AI-07**: UpdateTaskStatusTool AiFunctionCall plugin for AI-driven status changes

### Analytics

- [ ] **ANLZ-01**: Custom task history table via hook_schema() with composite indexes (task_id+timestamp, field_name+timestamp, uid+timestamp)
- [ ] **ANLZ-02**: hook_entity_presave() records all task field changes to history table
- [ ] **ANLZ-03**: hook_update_N() pairs with hook_schema() for existing installations
- [ ] **ANLZ-04**: hook_views_data() exposes history table to Views for custom reports
- [ ] **ANLZ-05**: Analytics REST endpoint returns aggregated metrics via CacheableJsonResponse
- [ ] **ANLZ-06**: Dashboard integration displays analytics summary with task history metrics

### Validation

- [ ] **EVAL-01**: Cross-cutting eval pass measures v5.0 cumulative impact using eval-author-designed assertions
- [ ] **EVAL-02**: v5.0 aggregate delta computed and compared to v3.0 (+16.7%) and v4.0 (+7.6%) baselines

## v6+ Requirements

- **AI-DEFER-01**: SuggestAssigneeTool based on workload history (needs sufficient data)
- **AI-DEFER-02**: Real-time AI chat in Kanban (requires WebSocket)
- **ANLZ-DEFER-01**: Full burndown charts with Sprint entity
- **ANLZ-DEFER-02**: Plugin-based analytics engine

## Out of Scope

| Feature | Reason |
|---------|--------|
| AI-suggested assignments | Needs history data; HIGH complexity for LOW eval value now |
| Real-time AI chat | Duplicates AI Agents chatbot; needs WebSocket |
| Burndown charts | Requires Sprint entity, story points -- massive scope |
| AI module 1.3.0-rc2 upgrade | RC not stable, no needed features |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| TOOL-01 | Phase 22 | Complete |
| TOOL-02 | Phase 22 | Complete |
| TOOL-03 | Phase 22 | Complete |
| TOOL-04 | Phase 22 | Complete |
| TOOL-05 | Phase 23 | Complete |
| TOOL-06 | Phase 23 | Complete |
| TOOL-07 | Phase 23 | Complete |
| AI-01 | Phase 24 | Pending |
| AI-02 | Phase 24 | Pending |
| AI-03 | Phase 24 | Pending |
| AI-04 | Phase 24 | Pending |
| AI-05 | Phase 25 | Pending |
| AI-06 | Phase 25 | Pending |
| AI-07 | Phase 25 | Pending |
| ANLZ-01 | Phase 26 | Pending |
| ANLZ-02 | Phase 26 | Pending |
| ANLZ-03 | Phase 26 | Pending |
| ANLZ-04 | Phase 26 | Pending |
| ANLZ-05 | Phase 26 | Pending |
| ANLZ-06 | Phase 26 | Pending |
| EVAL-01 | Phase 27 | Pending |
| EVAL-02 | Phase 27 | Pending |

**Coverage:** 22 requirements, 22 mapped, 0 unmapped

---
*Requirements defined: 2026-03-09*

# Requirements: Drupal Skills

**Defined:** 2026-03-05
**Core Value:** Claude can generate correct, production-ready Drupal module code across all major development domains when guided by these skills.

## v1 Requirements

Requirements for initial release. Each maps to roadmap phases.

### Skill Creation

- [x] **SKIL-01**: Each skill follows SKILL.md anatomy (YAML frontmatter, <500 line body, references/ subdirectory)
- [x] **SKIL-02**: Each skill uses decision-guide format (decision trees, not reference docs)
- [x] **SKIL-03**: Each skill includes "wrong way" callouts for patterns Claude commonly generates incorrectly
- [x] **SKIL-04**: Each skill produces complete file ecosystems (PHP classes paired with required YAML files)
- [x] **SKIL-05**: Each skill shows D10 annotation syntax with D11 PHP attribute syntax alongside
- [x] **SKIL-06**: Each skill directory is self-contained and works independently when installed to ~/.claude/skills/
- [x] **SKIL-07**: Each skill includes advisory cross-references to related skills that degrade gracefully

### Wave 1 — Foundations

- [x] **FOUN-01**: drupal-module-scaffold skill covers module creation, .info.yml, PSR-4 namespaces, .module file patterns
- [x] **FOUN-02**: drupal-routing-controllers skill covers routes, controllers, services, DI, with menus reference file
- [x] **FOUN-03**: drupal-entities-fields skill covers content/config entities, base fields, entity handlers, custom fields, with files/images reference file

### Wave 2 — Core Workflow

- [x] **CORE-01**: drupal-forms-api skill covers Form API lifecycle, form altering, submit handlers, validation
- [x] **CORE-02**: drupal-plugins-blocks skill covers block plugins, custom plugin types, plugin discovery
- [x] **CORE-03**: drupal-config-storage skill covers Config API, State API, TempStore, config schemas, with i18n reference file
- [x] **CORE-04**: drupal-access-security skill covers permissions, access handlers, route access, CSRF/XSS prevention

### Wave 3 — Presentation and Quality

- [x] **PRES-01**: drupal-theming skill covers render arrays, Twig templates, theme hooks, preprocess functions, with JS/Ajax reference file
- [x] **PRES-02**: drupal-caching skill covers cache tags, contexts, max-age, lazy builders, cache invalidation
- [x] **PRES-03**: drupal-testing skill covers PHPUnit test types, kernel tests, functional tests, browser tests
- [x] **PRES-04**: drupal-database-api skill covers database abstraction layer, schema API, dynamic queries

### Wave 4 — Specialized Patterns

- [x] **SPEC-01**: drupal-views-dev skill covers hook_views_data, Views field/filter/sort plugins, Views integration
- [x] **SPEC-02**: drupal-batch-queue-cron skill covers Batch API, queue workers, cron hooks, with logging/mail/tokens reference file

### Eval and Optimization

- [x] **EVAL-01**: Each skill passes skill-creator eval loop (with-skill vs baseline comparison shows improvement)
- [x] **EVAL-02**: Eval prompts grounded in os-knowledge-garden project tasks
- [x] **EVAL-03**: Trigger descriptions optimized holistically across all 13 skills to prevent overlap
- [x] **EVAL-04**: Multi-skill interaction testing with cross-domain prompts produces coherent output

### Packaging and Distribution

- [x] **PACK-01**: skills/ folder in repo contains all 13 skill directories ready for GitHub publishing
- [x] **PACK-02**: install.sh script copies/symlinks skills to ~/.claude/skills/
- [x] **PACK-03**: Repository README documents skill inventory, installation, and usage

## v2 Requirements

### Extended Coverage

- **EXT-01**: Migration API skill (not in source book)
- **EXT-02**: Drush command development skill
- **EXT-03**: Contrib module integration patterns (Views, Commerce, Paragraphs)

### Advanced Eval

- **ADV-01**: Automated regression testing for skill updates
- **ADV-02**: Community-contributed eval prompts

## Out of Scope

| Feature | Reason |
|---------|--------|
| Migration API skill | Not in source book, would require external sources |
| Contrib module patterns | Stale quickly, not in book's scope |
| Drupal installation/setup | Not module development |
| Drush command catalog | Runtime tooling, not module code |
| Composer management | Package management, not module code |
| Exhaustive form element reference | Covered by api.drupal.org |
| Full Twig syntax reference | Covered by Twig docs |
| D11-only skills without D10 baseline | Book is D10, D11 is additive |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| SKIL-01 | Phase 1 | Complete |
| SKIL-02 | Phase 1 | Complete |
| SKIL-03 | Phase 1 | Complete |
| SKIL-04 | Phase 1 | Complete |
| SKIL-05 | Phase 1 | Complete |
| SKIL-06 | Phase 1 | Complete |
| SKIL-07 | Phase 1 | Complete |
| FOUN-01 | Phase 1 | Complete |
| FOUN-02 | Phase 1 | Complete |
| FOUN-03 | Phase 1 | Complete |
| CORE-01 | Phase 2 | Complete |
| CORE-02 | Phase 2 | Complete |
| CORE-03 | Phase 2 | Complete |
| CORE-04 | Phase 2 | Complete |
| PRES-01 | Phase 3 | Complete |
| PRES-02 | Phase 3 | Complete |
| PRES-03 | Phase 3 | Complete |
| PRES-04 | Phase 3 | Complete |
| SPEC-01 | Phase 4 | Complete |
| SPEC-02 | Phase 4 | Complete |
| EVAL-01 | Phase 5 | Complete |
| EVAL-02 | Phase 5 | Complete |
| EVAL-03 | Phase 5 | Complete |
| EVAL-04 | Phase 5 | Complete |
| PACK-01 | Phase 5 | Complete |
| PACK-02 | Phase 5 | Complete |
| PACK-03 | Phase 5 | Complete |

**Coverage:**
- v1 requirements: 27 total
- Mapped to phases: 27
- Unmapped: 0

---
*Requirements defined: 2026-03-05*
*Last updated: 2026-03-05 after initial definition*

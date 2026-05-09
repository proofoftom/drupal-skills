# Drupal Skills for Claude

![Drupal Skills for Claude](assets/hero.png)

A Claude Code plugin marketplace for Drupal development. Includes:

- **drupal-skills** -- 15 skills for generating correct, production-ready Drupal 10/11 module code. Distilled from Drupal core documentation, community publications, and refined through eval-driven iteration.
- **drupal-tdd** -- test-driven development discipline (red/green/refactor, outside-in test ordering). Built on patterns established by the Drupal TDD community.

Each plugin is versioned independently.

## Quick Start

In Claude Code, add this marketplace and install whichever plugin you want:

```
/plugin marketplace add proofoftom/drupal-skills
/plugin install drupal-skills@drupal-skills    # the 15-skill core
/plugin install drupal-tdd@drupal-skills       # optional TDD discipline
```

Skills activate automatically when you ask Claude Code to work on Drupal projects. To update later: `/plugin marketplace update drupal-skills`.

## What's Included

### Foundations

| Skill | What It Does |
|-------|-------------|
| drupal-module-scaffold | Scaffolds modules with .info.yml, PSR-4 structure, and .module files |
| drupal-routing-controllers | Routes, controllers, services, and dependency injection |
| drupal-entities-fields | Content and config entity types, base fields, and entity handlers |

### Core Workflow

| Skill | What It Does |
|-------|-------------|
| drupal-forms-api | Form API lifecycle, form altering, and settings forms |
| drupal-plugins-blocks | Block plugins, custom plugin types, and plugin dependency injection |
| drupal-config-storage | Config API, State API, TempStore, and config schemas |
| drupal-access-security | Permissions, access control, CSRF protection, and XSS prevention |

### Presentation and Quality

| Skill | What It Does |
|-------|-------------|
| drupal-theming | Render arrays, Twig templates, theme hooks, and asset libraries |
| drupal-caching | Cache tags, contexts, max-age, and lazy builders |
| drupal-testing | PHPUnit tests: unit, kernel, functional, and browser |
| drupal-database-api | Schema API, dynamic queries, and update hooks |

### Specialized

| Skill | What It Does |
|-------|-------------|
| drupal-views-dev | Views data integration and custom Views plugins |
| drupal-batch-queue-cron | Batch API, queue workers, and cron hooks |

### Cross-Cutting

| Skill | What It Does |
|-------|-------------|
| drupal-coding-standards | phpcs compliance for Drupal/DrupalPractice coding standards |

### Optional companion plugin: drupal-tdd

| Skill | What It Does |
|-------|-------------|
| drupal-tdd | Red/green/refactor cadence, outside-in test ordering, test-first feature growth. Pairs with `drupal-testing` (which covers base classes and assertion APIs) -- `drupal-testing` answers "which test type and skeleton?", `drupal-tdd` answers "in what order, and how do I grow the code from the tests?" |

Install separately: `/plugin install drupal-tdd@drupal-skills`.

## Usage

Skills activate automatically based on your prompt content. You don't need to reference them directly. Just describe what you want to build:

- **"Create a new Drupal module called my_events"** -- triggers module scaffolding
- **"Add a settings form with config schema"** -- triggers forms and config skills
- **"Create a custom content entity type for events"** -- triggers entity and field skills
- **"Write kernel tests for my service"** -- triggers testing skill

Multi-skill prompts work naturally. Ask Claude to "create a block plugin with a settings form and cache tags" and the relevant skills activate together.

## Uninstall

```
/plugin uninstall drupal-skills@drupal-skills
/plugin uninstall drupal-tdd@drupal-skills
```

To remove the marketplace itself: `/plugin marketplace remove drupal-skills`.

## Drupal Version Support

These skills cover Drupal 10 patterns with Drupal 11 PHP attribute syntax shown alongside D10 annotations where applicable. Both annotation and attribute styles are valid in Drupal 11; annotations remain supported in Drupal 10.

## License

MIT

# Drupal Skills for Claude

13 Claude Code skills for generating correct, production-ready Drupal 10/11 module code.

## Quick Start

```bash
git clone https://github.com/proofoftom/drupal-skills.git
cd drupal-skills
./install.sh
```

That's it. Skills activate automatically when you ask Claude Code to work on Drupal projects.

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

## Usage

Skills activate automatically based on your prompt content. You don't need to reference them directly. Just describe what you want to build:

- **"Create a new Drupal module called my_events"** -- triggers module scaffolding
- **"Add a settings form with config schema"** -- triggers forms and config skills
- **"Create a custom content entity type for events"** -- triggers entity and field skills
- **"Write kernel tests for my service"** -- triggers testing skill

Multi-skill prompts work naturally. Ask Claude to "create a block plugin with a settings form and cache tags" and the relevant skills activate together.

## Installation Options

### Default (copy)

```bash
./install.sh
```

Copies all skill directories to `~/.claude/skills/`. Robust and self-contained -- skills work even if you move or delete the repo. Re-run the installer to pick up updates after `git pull`.

### Symlink (live updates)

```bash
./install.sh --symlink
```

Creates symlinks from `~/.claude/skills/` to the repo. Skills update automatically when you `git pull`. However, skills break if you move or delete the repo directory.

## Uninstall

```bash
rm -rf ~/.claude/skills/drupal-*
```

## Drupal Version Support

These skills cover Drupal 10 patterns with Drupal 11 PHP attribute syntax shown alongside D10 annotations where applicable. Both annotation and attribute styles are valid in Drupal 11; annotations remain supported in Drupal 10.

## License

MIT

# Drupal Skills for Claude

A Claude Code plugin marketplace bundling Drupal-development skills. Two plugins ship today:

- **drupal-skills** -- 15 skills for generating correct, production-ready Drupal 10/11 module code. Distilled from Drupal core documentation, community publications, and refined through eval-driven iteration.
- **drupal-tdd** -- test-driven development discipline (red/green/refactor, outside-in test ordering). Built on patterns established by the Drupal TDD community.

The two are versioned independently because they evolve on different cycles.

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

## Installation

### Plugin (recommended)

```bash
git clone https://github.com/proofoftom/drupal-skills.git
claude --plugin-dir /path/to/drupal-skills
```

The plugin system auto-discovers all 15 skills. No configuration needed.

### Legacy Installation (deprecated)

> **Deprecated:** The install.sh method copies skills to `~/.claude/skills/` and requires manual re-runs to update. Use the plugin method above instead.

#### Default (copy)

```bash
./install.sh
```

Copies all skill directories to `~/.claude/skills/`. Re-run the installer to pick up updates after `git pull`.

#### Symlink (live updates)

```bash
./install.sh --symlink
```

Creates symlinks from `~/.claude/skills/` to the repo. Skills update automatically when you `git pull`. However, skills break if you move or delete the repo directory.

## Migration from install.sh

If you previously installed skills using `install.sh`, migrate to the plugin system:

1. Remove previously installed skills:
   ```bash
   ./install.sh --uninstall
   ```

2. Use the plugin system going forward:
   ```bash
   claude --plugin-dir /path/to/drupal-skills
   ```

The `--uninstall` flag removes all `drupal-*` directories from `~/.claude/skills/`. The plugin loads skills directly from the repository, so there is nothing to install or keep in sync.

## Uninstall

### Plugin

Stop passing `--plugin-dir` when launching Claude Code. No files to clean up.

### Legacy

```bash
./install.sh --uninstall
```

Or manually:

```bash
rm -rf ~/.claude/skills/drupal-*
```

## Drupal Version Support

These skills cover Drupal 10 patterns with Drupal 11 PHP attribute syntax shown alongside D10 annotations where applicable. Both annotation and attribute styles are valid in Drupal 11; annotations remain supported in Drupal 10.

## License

MIT

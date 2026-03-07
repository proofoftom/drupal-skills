# Technology Stack

**Project:** Drupal Skills v2.0 -- Eval & Optimization Pipeline
**Researched:** 2026-03-06
**Scope:** NEW stack additions for automated eval pipeline only. Existing skill/SKILL.md stack is unchanged.

## Recommended Stack Additions

### 1. Custom Subagents (Claude Code Native)

| Component | Version | Purpose | Why |
|-----------|---------|---------|-----|
| `eval-executor` subagent | Claude Code native (`.claude/agents/eval-executor.md`) | Run skill eval prompts against Drupal env with controlled model | Frontmatter `model: sonnet` ensures consistent, cost-effective eval execution without manual `/model` switching. Replaces fragile Agent-tool-based approach from Phase 6/7 |
| `eval-browser` subagent | Claude Code native (`.claude/agents/eval-browser.md`) | Automated E2E/UAT assertions via browser | Runs agent-browser commands against ddev sites for page-contains, element-exists, form validation. Replaces bash-only e2e-assert.sh with AI-driven browser interaction |

**Confidence: HIGH** -- Verified from official Claude Code docs at code.claude.com/docs/en/sub-agents.

#### Subagent File Format (Verified)

```yaml
---
name: eval-executor
description: >-
  Execute Drupal skill eval prompts in an isolated ddev environment.
  Use when running with-skill or without-skill eval comparisons.
tools: Read, Write, Edit, Bash, Glob, Grep
model: sonnet
permissionMode: bypassPermissions
maxTurns: 50
skills:
  - drupal-routing-controllers  # Only for with-skill runs
---

You are a Drupal module developer. Given a prompt and a ddev project directory,
implement the requested Drupal module code. Do NOT ask questions -- just create the code.
```

**Critical fields for eval pipeline:**

| Field | Value | Why |
|-------|-------|-----|
| `model` | `sonnet` | Controls cost and ensures consistent eval executor model. Accepts `sonnet`, `opus`, `haiku`, or `inherit` |
| `permissionMode` | `bypassPermissions` | Eval agents must run unattended without permission prompts |
| `skills` | list of skill names | Injects SKILL.md content into subagent context at startup. Use for with-skill runs; omit for without-skill runs |
| `maxTurns` | `50` | Prevents runaway agents; Drupal module creation typically takes 15-30 turns |
| `tools` | explicit list | Restrict to needed tools only. Subagents cannot spawn other subagents |

**Two-agent pattern for knowledge isolation:**
- `eval-executor` (without skills field) -- baseline runs
- `eval-executor-skilled` (with `skills: [drupal-X]`) -- with-skill runs
- OR: use `--agents` CLI flag to pass JSON config dynamically per run, toggling skills field

**Dynamic agent via CLI (recommended for flexibility):**

```bash
claude --agent eval-executor --agents '{
  "eval-executor": {
    "description": "Execute Drupal eval prompt in ddev environment",
    "prompt": "You are a Drupal module developer...",
    "tools": ["Read", "Write", "Edit", "Bash", "Glob", "Grep"],
    "model": "sonnet",
    "permissionMode": "bypassPermissions",
    "maxTurns": 50,
    "skills": ["drupal-caching"]
  }
}'
```

This avoids maintaining two separate agent files per skill. The orchestrator generates the JSON dynamically, including or excluding the `skills` field.

#### Environment Variable Alternative

```bash
export CLAUDE_CODE_SUBAGENT_MODEL=claude-sonnet-4-5-20250929
```

Sets the model for ALL subagents globally. Less granular than per-agent `model` field but useful as a fallback.

### 2. agent-browser (Already Installed)

| Component | Version | Purpose | Why |
|-----------|---------|---------|-----|
| agent-browser | 0.16.3 (globally installed via npm) | Headless browser automation for E2E assertions | Already used by e2e-assert.sh. CLI-first design fits bash scripting. Provides `open`, `snapshot`, `eval`, `click`, `type`, `fill`, `screenshot` commands |

**Confidence: HIGH** -- Verified installed at `/home/proofoftom/.nvm/versions/node/v24.12.0/bin/agent-browser`, version 0.16.3.

**No changes needed.** agent-browser is already integrated via `eval/e2e-assert.sh`. The eval-browser subagent will use it for more complex E2E scenarios (login flows, multi-page navigation, form submission verification).

#### Key CLI Commands for Eval

| Command | Use Case |
|---------|----------|
| `agent-browser --session S open <url>` | Navigate to Drupal page |
| `agent-browser --session S snapshot` | Get accessibility tree (AI-readable page content) |
| `agent-browser --session S eval <js>` | Run JS assertions (querySelector, etc.) |
| `agent-browser --session S get text <sel>` | Extract text from specific elements |
| `agent-browser --session S get title` | Check page title for errors |
| `agent-browser --session S screenshot [path]` | Capture visual state for debugging |
| `agent-browser --session S --ignore-https-errors open <url>` | Handle ddev self-signed certs |
| `agent-browser --session S close` | Clean up browser process |

**Critical flag:** `--ignore-https-errors` is required for ddev HTTPS sites with self-signed certificates.

**Session management:** Use unique session names (`eval-{skill}-{run}`) to prevent collisions during parallel eval runs.

### 3. ddev for Fresh Drupal 10 Environments

| Component | Version | Purpose | Why |
|-----------|---------|---------|-----|
| ddev | v1.24.8 (installed) | Isolated Drupal 10 environments per eval run | Fast, reproducible, supports `--project-type=drupal10`. Replaces os-knowledge-garden cloning with fresh D10 installs for cleaner eval baselines |

**Confidence: HIGH** -- Verified installed, `--project-type=drupal10` confirmed in help output.

#### Fresh D10 Setup (Replaces os-knowledge-garden Cloning)

The existing `eval/setup-drupal-env.sh` clones os-knowledge-garden. The new pipeline should use fresh Drupal 10 instead:

```bash
#!/usr/bin/env bash
# eval/setup-fresh-drupal10.sh <unique-name>
set -euo pipefail
unset CLAUDECODE 2>/dev/null || true

NAME="${1:?Usage: setup-fresh-drupal10.sh <unique-name>}"
TARGET_DIR="/tmp/drupal10-${NAME}"

# Clean up stale environment
if [ -d "$TARGET_DIR" ]; then
  (yes | ddev delete -O "drupal10-${NAME}" 2>/dev/null) || true
  rm -rf "$TARGET_DIR"
fi

mkdir -p "$TARGET_DIR"
cd "$TARGET_DIR"

# Configure ddev for Drupal 10
ddev config --project-name="drupal10-${NAME}" \
  --project-type=drupal10 \
  --docroot=web \
  --php-version=8.2

# Start ddev (serialized to prevent router conflicts)
(flock -x 200; ddev start) 200>/tmp/ddev-start.lock

# Create Drupal project via composer
ddev composer create drupal/recommended-project:^10 --no-interaction

# Install Drupal with minimal profile (fast, clean baseline)
ddev drush site:install minimal --account-name=admin --account-pass=admin -y

# Enable common modules that eval tasks may need
ddev drush pm:install node,block,field_ui,views,views_ui,path -y

echo "$TARGET_DIR"
```

**Why fresh D10 instead of os-knowledge-garden:**
- Cleaner baseline: no pre-existing modules/config to interfere with eval
- Faster setup: ~2 min for fresh D10 vs ~4 min for os-kg with cascadia demo
- Reproducible: same starting state every time
- Simpler teardown: no root-owned files from Docker volumes

**Why `--project-type=drupal10` specifically:**
- Skills are based on Sipos book (D10, 4th ed 2023)
- D10 requires PHP 8.1+ (use 8.2 for stability)
- D10 uses `drupal/recommended-project` composer template

### 4. skill-creator Scripts (Already Available)

| Script | Path | Purpose | Integration |
|--------|------|---------|-------------|
| `aggregate_benchmark.py` | `~/.claude/plugins/cache/claude-plugins-official/skill-creator/205b6e0b3036/skills/skill-creator/scripts/aggregate_benchmark.py` | Aggregate grading.json files into benchmark.json with stats | Run after all eval runs complete. Reads workspace layout: `eval-N/{with,without}_skill/run-N/grading.json` |
| `generate_review.py` | `~/.claude/plugins/cache/claude-plugins-official/skill-creator/205b6e0b3036/skills/skill-creator/eval-viewer/generate_review.py` | Generate HTML eval viewer for human review | Run after aggregate_benchmark. Opens browser with side-by-side comparison |
| `run_eval.py` | (same cache path)/scripts/run_eval.py | Run trigger evaluation (description testing) | For Phase 2 (description optimization), NOT content eval |
| `grader.md` | (same cache path)/agents/grader.md | Grader agent specification | Load into grader subagent. Grades transcripts + outputs against expectations |

**Confidence: HIGH** -- All scripts verified on disk, source code read and analyzed.

#### Workspace Directory Layout (Required by aggregate_benchmark.py)

```
drupal-{skill}-workspace/
  iteration-{N}/
    eval-{eval-name}/
      with_skill/
        run-1/
          outputs/          # Files created by eval-executor
          transcript.md     # Execution log
          grading.json      # Written by grader agent
          timing.json       # Optional: wall-clock timing
        run-2/
          ...
      without_skill/
        run-1/
          ...
    benchmark.json          # Generated by aggregate_benchmark.py
    benchmark.md            # Human-readable summary
```

This layout is already in use from Phase 6/7 iterations. No changes needed.

#### grading.json Schema (Required by aggregate_benchmark.py)

```json
{
  "expectations": [
    {"text": "...", "passed": true, "evidence": "..."}
  ],
  "summary": {
    "passed": 5,
    "failed": 1,
    "total": 6,
    "pass_rate": 0.83
  }
}
```

The `summary.pass_rate` field is what aggregate_benchmark.py uses for delta calculations.

### 5. Orchestrator Script (New)

| Component | Purpose | Why |
|-----------|---------|-----|
| `eval/run-skill-eval.sh` | Orchestrate single-skill eval cycle: setup ddev, spawn with/without agents, grade, aggregate | Replaces manual orchestration from main Claude session. Reduces context window pressure. Can be called in a loop for batch processing |

**Not a new dependency** -- this is a bash script that ties existing components together. Details belong in ARCHITECTURE.md.

## What NOT to Add

| Technology | Why NOT |
|------------|---------|
| Playwright/Puppeteer directly | agent-browser already wraps Playwright with AI-friendly CLI. Adding raw Playwright adds complexity without benefit |
| pytest/PHPUnit for assertions | skill-creator's grader agent + expectations model is the standard. Custom test frameworks diverge from skill-creator methodology |
| Docker Compose for Drupal | ddev already manages Docker. Adding compose files duplicates ddev's job |
| Custom eval dashboard | skill-creator's generate_review.py provides HTML viewer. Build custom only if generate_review proves insufficient |
| claude -p (headless CLI) | Unreliable in nested sessions (hangs, black box). Custom subagents with `--agent` flag provide observability |
| os-knowledge-garden for evals | Pre-existing modules contaminate eval baselines. Fresh D10 is cleaner |
| Multiple runs per eval (3+) | Start with 1 run per config. Add multi-run only after pipeline is stable and delta signal is clear |
| CLAUDE_CODE_SUBAGENT_MODEL env var | Per-agent `model: sonnet` in frontmatter is more precise. Global env var would affect ALL subagents including grader (which should run on opus) |

## Alternatives Considered

| Category | Recommended | Alternative | Why Not |
|----------|-------------|-------------|---------|
| Model control | `model: sonnet` in agent frontmatter | `CLAUDE_CODE_SUBAGENT_MODEL` env var | Env var is global; we need sonnet for executor but opus for grader |
| Eval execution | Custom subagent files | `claude -p` headless CLI | Headless CLI hangs in nested sessions, no observability, documented failure mode from Phase 6 |
| Knowledge isolation | Two agent configs (with/without skills field) | Single agent + prompt manipulation | `skills` field in frontmatter injects full SKILL.md content at startup; cannot be "un-injected" mid-session |
| Browser testing | agent-browser CLI (installed) | Playwright MCP server | agent-browser already works, is simpler, and is designed for AI agent use. No MCP overhead |
| Drupal env | Fresh D10 via ddev | os-knowledge-garden clone | Fresh D10 = clean baseline, faster setup, no interference from pre-existing modules |
| Dynamic agent config | `--agents` CLI flag with JSON | Two static .md files per skill | CLI flag avoids 26 agent files (2 per 13 skills). Orchestrator generates JSON dynamically |

## Version Summary

| Tool | Installed Version | Status |
|------|-------------------|--------|
| ddev | v1.24.8 | Already installed, no update needed |
| agent-browser | 0.16.3 | Already installed globally, no update needed |
| Node.js | v24.12.0 | Already installed (agent-browser host) |
| Python 3 | System | Required for skill-creator scripts (aggregate_benchmark.py, generate_review.py) |
| Claude Code | Current | Custom subagent support via `.claude/agents/` and `--agents` flag |
| skill-creator plugin | Installed at marketplace path | Provides grader.md, aggregate_benchmark.py, generate_review.py |

## Installation

No new packages to install. All dependencies are already present.

```bash
# Verify everything is in place
which ddev           # v1.24.8
which agent-browser  # 0.16.3
python3 --version    # 3.x

# Verify skill-creator scripts
ls ~/.claude/plugins/cache/claude-plugins-official/skill-creator/*/skills/skill-creator/scripts/aggregate_benchmark.py
ls ~/.claude/plugins/cache/claude-plugins-official/skill-creator/*/skills/skill-creator/eval-viewer/generate_review.py
ls ~/.claude/plugins/cache/claude-plugins-official/skill-creator/*/skills/skill-creator/agents/grader.md

# Only new file to create:
# eval/setup-fresh-drupal10.sh (replaces os-kg-based setup for eval pipeline)
# .claude/agents/eval-executor.md (optional if using --agents CLI flag)
```

## Sources

- **Claude Code subagent docs** (PRIMARY): https://code.claude.com/docs/en/sub-agents -- Verified 2026-03-06. Frontmatter fields, model control, skills injection, permissionMode, --agents CLI flag, tool restrictions
- **agent-browser CLI**: Verified locally via `agent-browser --help` and `agent-browser --version` (0.16.3). Source: https://github.com/vercel-labs/agent-browser
- **ddev docs**: Verified locally via `ddev config --help`. `--project-type=drupal10` confirmed
- **skill-creator scripts**: Verified on disk at `~/.claude/plugins/cache/claude-plugins-official/skill-creator/205b6e0b3036/skills/skill-creator/scripts/`. Source code for aggregate_benchmark.py and run_eval.py read and analyzed
- **skill-creator grader.md**: Verified on disk. Grading schema with expectations/summary/claims/eval_feedback structure documented
- **Phase 6/7 eval results**: From MEMORY.md. Documents known issues with claude -p, knowledge isolation requirements, and eval execution rules

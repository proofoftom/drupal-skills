# Phase 8: Eval Infrastructure - Research

**Researched:** 2026-03-06
**Domain:** Claude Code subagents, ddev automation, A/B eval pipeline design
**Confidence:** HIGH

## Summary

Phase 8 builds the foundational infrastructure for an autonomous eval pipeline: four subagent definitions (eval-executor, eval-grader, eval-browser, and their orchestration), a fresh Drupal 10 setup script replacing the os-knowledge-garden approach, and verified knowledge isolation between with-skill and without-skill runs.

The key technologies are well-documented and available: Claude Code subagents support `model: sonnet` frontmatter for controlled model routing, the `skills:` frontmatter field enables explicit skill injection for knowledge isolation, `agent-browser` is installed and operational for E2E verification, and ddev v1.24.8 is available with straightforward Drupal 10 quickstart commands. The v1.0 eval infrastructure provides proven patterns for grading.json and benchmark.json schemas.

**Primary recommendation:** Build four `.claude/agents/*.md` files with precise frontmatter, a `eval/setup-fresh-drupal10.sh` script using `ddev config --project-type=drupal10`, and validate the full pipeline with a single skill (caching) before declaring Phase 8 complete.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| INFRA-01 | eval-executor subagent with `model: sonnet` in frontmatter for controlled A/B execution | Claude Code subagent docs confirm `model: sonnet` frontmatter field; `skills:` field enables knowledge isolation; `permissionMode: bypassPermissions` for unattended runs |
| INFRA-02 | eval-grader subagent following skill-creator grader.md, producing compliant grading.json | v1.0 grading.json schema documented; grader needs `model: inherit` (Opus) for reliable grading; expectations array + summary object format proven |
| INFRA-03 | Fresh Drupal 10 ddev setup script with auto-retry for ddev-router failures | ddev v1.24.8 available; `ddev config --project-type=drupal10 --docroot=web` + `ddev composer create-project "drupal/recommended-project:^10"` is official quickstart; auto-retry via flock + router restart |
| INFRA-04 | eval-browser subagent using agent-browser + drush uli for E2E/UAT verification | agent-browser installed at `/home/proofoftom/.nvm/versions/node/v24.12.0/bin/agent-browser`; supports `open`, `snapshot`, `eval`, `find` commands; ddev drush uli generates login URL |
</phase_requirements>

## Standard Stack

### Core

| Tool | Version | Purpose | Why Standard |
|------|---------|---------|--------------|
| Claude Code subagents | Current | Agent definitions in `.claude/agents/*.md` | Official Claude Code feature; YAML frontmatter for model, tools, skills, permissions |
| ddev | v1.24.8 | Local Drupal environment management | Already installed; proven in v1.0 eval infrastructure |
| agent-browser | Current | Headless browser automation for E2E assertions | Already installed; used by existing `e2e-assert.sh` |
| Drupal 10 | ^10 (latest) | Target CMS for skill evaluation | Project's target platform; fresh installs via composer |
| drush | Latest via composer | Drupal CLI (site:install, uli, cron, pm:enable) | Standard Drupal admin tool; required for automated setup and teardown |

### Supporting

| Tool | Version | Purpose | When to Use |
|------|---------|---------|-------------|
| flock | System | Serialize concurrent ddev starts | When multiple eval envs start in parallel (prevents ddev-router conflicts) |
| docker | 28.5.1 | Container runtime for ddev | Already available; used by ddev transparently |
| jq | System | JSON processing for grading/benchmark files | Parse and validate grading.json output |
| curl | System | HTTP assertions against ddev sites | Simpler than agent-browser for status code checks (per MEMORY.md: "Use curl for E2E") |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Fresh D10 ddev instances | os-knowledge-garden clones | os-kg is heavier, has Open Social collisions (see v1.0 entities prompt issue), slower to provision |
| `model: sonnet` frontmatter | `/model sonnet` interactive switching | Manual model switching is error-prone, not automatable, breaks knowledge isolation |
| `skills:` frontmatter preloading | Read tool loading SKILL.md at runtime | Preloading is deterministic -- skill content injected before first turn; Read requires the agent to remember to load it |
| `permissionMode: bypassPermissions` | Interactive permission prompts | Eval runs must be unattended; bypassPermissions skips all prompts |

## Architecture Patterns

### Recommended Project Structure

```
.claude/
  agents/
    eval-executor.md         # model: sonnet, runs code generation
    eval-grader.md           # model: inherit (Opus), grades output
    eval-browser.md          # model: haiku, E2E page verification
eval/
    setup-fresh-drupal10.sh  # Creates fresh D10 ddev instance
    teardown-drupal-env.sh   # Existing teardown (works for new envs too)
    e2e-assert.sh            # Existing E2E helper (may need base URL fix)
skills/
    drupal-*/
      SKILL.md               # Skill content (13 skills, locked)
      evals/
        evals.json           # Eval definitions with expectations
```

### Pattern 1: Subagent-Based Knowledge Isolation

**What:** Two eval-executor instances with different `skills:` frontmatter create the A/B comparison.

**When to use:** Every eval run -- this IS the core isolation mechanism.

**How it works:**

The with-skill executor includes the skill in its `skills:` list:

```yaml
---
name: eval-executor
description: Execute Drupal module development tasks for eval. Use when running skill evaluations.
model: sonnet
permissionMode: bypassPermissions
tools: Read, Write, Edit, Bash, Glob, Grep
skills:
  - drupal-caching  # Injected at startup
---
```

The without-skill executor omits the `skills:` field entirely. Since subagents do NOT inherit skills from the parent conversation, the without-skill agent has zero access to SKILL.md content.

**Critical insight from official docs:** "Subagents don't inherit skills from the parent conversation; you must list them explicitly." This means omitting `skills:` guarantees complete knowledge isolation -- no accidental skill leakage.

**Implementation approach:** Rather than creating two separate agent files, the orchestrator (main session) can use the `--agents` CLI flag to inject the skill dynamically:

```bash
# With-skill run
claude --agent eval-executor --agents '{
  "eval-executor": {
    "skills": ["drupal-caching"]
  }
}' ...

# Without-skill run
claude --agent eval-executor ...
```

However, the simpler approach for this project is to spawn the Agent tool from the main session with explicit instructions -- the main session controls what context gets passed. The eval-executor.md defines the base behavior and model; the parent orchestrator passes (or withholds) the SKILL.md path.

### Pattern 2: Fresh Drupal 10 Environment Setup

**What:** Script that creates a clean Drupal 10 ddev instance from scratch (no os-kg dependency).

**When to use:** Before every eval run.

**Sequence:**

```bash
# 1. Create project directory
mkdir -p /tmp/drupal10-${NAME} && cd /tmp/drupal10-${NAME}

# 2. Configure ddev
ddev config --project-type=drupal10 --docroot=web --project-name="d10-${NAME}"

# 3. Start ddev (with flock for serialization + retry for router failures)
(flock -x 200; ddev start) 200>/tmp/ddev-start.lock

# 4. Install Drupal via composer
ddev composer create-project "drupal/recommended-project:^10" .

# 5. Install drush
ddev composer require drush/drush

# 6. Run site install
ddev drush site:install standard --account-name=admin --account-pass=admin -y

# 7. Verify
ddev drush status | grep -q "Drupal bootstrap.*Successful"
```

**Auto-retry pattern for ddev-router failures:**

```bash
MAX_RETRIES=3
for attempt in $(seq 1 $MAX_RETRIES); do
  if (flock -x 200; ddev start) 200>/tmp/ddev-start.lock; then
    break
  fi
  echo "ddev start failed (attempt $attempt/$MAX_RETRIES), restarting router..." >&2
  docker restart ddev-router 2>/dev/null || true
  sleep 20
  if [ "$attempt" -eq "$MAX_RETRIES" ]; then
    echo "FATAL: ddev start failed after $MAX_RETRIES attempts" >&2
    exit 1
  fi
done
```

### Pattern 3: Grading.json Schema (Proven from v1.0)

**What:** Standardized JSON format for eval grading output.

**Schema:**

```json
{
  "expectations": [
    {
      "text": "Description of what was expected",
      "passed": true,
      "evidence": "Specific file/line/output proving the assertion"
    }
  ],
  "summary": {
    "passed": 8,
    "failed": 0,
    "total": 8,
    "pass_rate": 1.0
  }
}
```

### Pattern 4: Benchmark.json Schema (Proven from v1.0)

**What:** Aggregated benchmark data across with/without skill runs.

**Schema:**

```json
{
  "metadata": {
    "skill_name": "drupal-caching",
    "skill_path": "/path/to/skill",
    "executor_model": "sonnet",
    "analyzer_model": "opus",
    "timestamp": "2026-03-06T11:05:00Z",
    "evals_run": [1],
    "runs_per_configuration": 1
  },
  "runs": [
    {
      "eval_id": 1,
      "configuration": "with_skill",
      "run_number": 1,
      "result": {
        "pass_rate": 1.0,
        "passed": 8,
        "failed": 0,
        "total": 8
      }
    }
  ],
  "run_summary": {
    "with_skill": {
      "pass_rate": { "mean": 1.0, "min": 1.0, "max": 1.0 }
    },
    "without_skill": {
      "pass_rate": { "mean": 0.25, "min": 0.25, "max": 0.25 }
    },
    "delta": { "pass_rate": "+0.75" }
  }
}
```

### Pattern 5: eval-browser E2E Verification

**What:** Subagent that uses agent-browser + drush uli for authenticated page checks.

**How it works:**

```bash
# Get one-time login URL
LOGIN_URL=$(ddev drush uli --uri=https://d10-${NAME}.ddev.site)

# Use agent-browser to navigate
agent-browser --session eval-${NAME} open "$LOGIN_URL"

# Take snapshot for AI analysis
agent-browser --session eval-${NAME} snapshot

# Check for specific content
agent-browser --session eval-${NAME} eval "document.querySelector('.block-my-module') !== null"

# Cleanup
agent-browser --session eval-${NAME} close
```

### Anti-Patterns to Avoid

- **Using `claude -p` headless mode for eval execution:** Black box, hangs silently, no observability. Use Agent tool subagents from main session instead (per MEMORY.md rule #2).
- **Using `/model sonnet` switching:** Not automatable, breaks knowledge isolation, requires manual intervention (per MEMORY.md rule #5 noting Agent tool has NO model parameter).
- **Inheriting skills from parent conversation:** Subagents do NOT inherit skills. Must use `skills:` frontmatter or explicit Read instructions. Assuming inheritance would break knowledge isolation.
- **Processing heavy work in the grader:** The grader should ONLY read files and grade against expectations. All E2E verification should happen BEFORE grading (by eval-browser or eval-executor).
- **Sharing ddev instances between with/without runs:** Each run needs its own clean environment to prevent cross-contamination.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Model routing for A/B testing | Custom model switching logic | `model: sonnet` in subagent frontmatter | Official Claude Code feature, deterministic, no model inheritance bugs |
| Knowledge isolation | Custom prompt stripping/filtering | `skills:` frontmatter field (include or omit) | Official mechanism; subagents don't inherit parent skills by design |
| Drupal 10 provisioning | Custom docker-compose setup | `ddev config --project-type=drupal10` + `ddev composer create-project` | ddev handles nginx, PHP, MariaDB, Composer, drush integration |
| Browser automation | Custom Puppeteer/Playwright scripts | `agent-browser` CLI (already installed) | Purpose-built for AI agent browser automation, proven in e2e-assert.sh |
| JSON schema validation for grading | Custom validation code | Grader agent prompt with schema example | AI-powered grading is the whole point; schema compliance enforced by clear examples in prompt |

**Key insight:** The entire eval pipeline is orchestrated by AI agents. There is no "eval runner" binary to build. The main Claude session IS the orchestrator, spawning subagents for each step.

## Common Pitfalls

### Pitfall 1: ddev-router Health Check Failures

**What goes wrong:** ~50% of first `ddev start` calls fail when ddev-router can't bind ports or pass health checks.
**Why it happens:** Docker networking race conditions, stale router containers, port conflicts from previous runs.
**How to avoid:** Auto-retry loop with `docker restart ddev-router && sleep 20` between attempts. Use flock for serialization when multiple environments start concurrently.
**Warning signs:** "ddev-router failed to become ready: health check timed out" in stderr.

### Pitfall 2: CLAUDECODE Environment Variable Blocking Nested Sessions

**What goes wrong:** Nested `claude` invocations from within a Claude Code session fail silently.
**Why it happens:** The CLAUDECODE env var signals an active session and blocks re-entry.
**How to avoid:** `unset CLAUDECODE` at the top of any shell script that might be called from within a Claude session. The existing `setup-drupal-env.sh` already does this.
**Warning signs:** Subagent spawning hangs or returns empty results.

### Pitfall 3: Skill Content Leaking to Without-Skill Runs

**What goes wrong:** The without-skill agent produces output that shows knowledge of SKILL.md content, invalidating the A/B comparison.
**Why it happens:** Skills can leak through: parent conversation context, shared CLAUDE.md references, or the eval prompt itself containing hints.
**How to avoid:** (1) Subagents don't inherit parent skills -- verified by official docs. (2) CLAUDE.md must not reference skill content. (3) Eval prompts must not contain implementation hints. (4) Without-skill prompt must include "Do NOT ask questions -- just create the code" per MEMORY.md rule #6.
**Warning signs:** Both with and without skill runs produce identical patterns for domain-specific assertions.

### Pitfall 4: Composer Create-Project Overwriting ddev Config

**What goes wrong:** Running `ddev composer create-project` can overwrite the `.ddev/` directory if the project template includes one.
**Why it happens:** `composer create-project` unpacks files into the current directory. If `drupal/recommended-project` ever includes a `.ddev/` dir, it would overwrite the config.
**How to avoid:** Run `ddev config` BEFORE composer, then verify `.ddev/config.yaml` still has correct project name after composer runs. The ddev quickstart docs recommend this order.
**Warning signs:** ddev project name reverts to default after composer step.

### Pitfall 5: drush uli URL Not Matching ddev Site URL

**What goes wrong:** `drush uli` generates a URL with `http://default` or wrong hostname.
**Why it happens:** drush doesn't know the ddev hostname unless told via `--uri` flag.
**How to avoid:** Always use `ddev drush uli --uri=https://d10-${NAME}.ddev.site` to get a URL with the correct hostname and HTTPS.
**Warning signs:** agent-browser gets connection refused or wrong site when following drush uli output.

### Pitfall 6: Root-Owned Files Preventing Cleanup

**What goes wrong:** `rm -rf` fails on Drupal's `sites/default/files/` directory because ddev's containers create files as root.
**Why it happens:** Docker containers run as root by default; files created inside containers are owned by root on the host.
**How to avoid:** Use `docker run --rm -v /tmp:/tmp alpine rm -rf "/tmp/d10-${NAME}"` as fallback (existing teardown pattern).
**Warning signs:** "Permission denied" errors during teardown.

## Code Examples

### eval-executor.md Subagent Definition

```markdown
---
name: eval-executor
description: |
  Execute Drupal module development tasks for skill evaluation.
  Spawned by the eval orchestrator with a specific task prompt.
  Creates Drupal modules in a ddev environment.
model: sonnet
permissionMode: bypassPermissions
tools: Read, Write, Edit, Bash, Glob, Grep
---

You are a Drupal 10 module developer. You will be given a task to create
a Drupal module in a ddev environment.

IMPORTANT RULES:
- Create all module files in the specified ddev project directory
- Use `ddev drush` for all Drupal CLI operations
- Enable your module with `ddev drush en <module_name> -y`
- Verify the module works by running `ddev drush cr` and checking for errors
- Do NOT ask questions -- just create the code
- Do NOT modify any files outside the specified project directory

When done, report what files you created and whether the module enabled successfully.
```

Source: Based on Claude Code subagent documentation at https://code.claude.com/docs/en/sub-agents

### eval-grader.md Subagent Definition

```markdown
---
name: eval-grader
description: |
  Grade Drupal module eval output against expectations.
  Reads generated code files and produces a compliant grading.json.
model: inherit
permissionMode: bypassPermissions
tools: Read, Bash, Glob, Grep
---

You are a code grader for Drupal skill evaluations. You receive:
1. A list of expectations (assertions) to check
2. A path to the generated module code
3. A ddev project name for runtime checks

For each expectation:
- Examine the generated code files to determine if the expectation is met
- For runtime/E2E expectations, use `ddev drush` or `curl -sk` commands
- Record specific evidence (file paths, line numbers, command output)
- Mark as passed: true or passed: false

Output a single JSON file at the specified path in this exact format:

{
  "expectations": [
    {
      "text": "exact text of the expectation",
      "passed": true,
      "evidence": "specific evidence: file.php line 42 shows X"
    }
  ],
  "summary": {
    "passed": <count>,
    "failed": <count>,
    "total": <count>,
    "pass_rate": <0.0 to 1.0>
  }
}

Be precise and fair. Only mark an expectation as passed if there is clear
evidence. Quote specific code or command output as evidence.
```

### eval-browser.md Subagent Definition

```markdown
---
name: eval-browser
description: |
  Perform E2E browser verification on ddev Drupal sites.
  Uses agent-browser for page navigation and content checks.
  Authenticates via drush uli one-time login.
model: haiku
permissionMode: bypassPermissions
tools: Bash, Read
---

You are a browser-based E2E verifier for Drupal sites running in ddev.

WORKFLOW:
1. Get an authenticated login URL: `ddev drush uli --uri=https://<project>.ddev.site`
2. Open the login URL with agent-browser: `agent-browser --session <session> open "<url>"`
3. Navigate to the target page
4. Take snapshots and check content
5. Report findings

AVAILABLE COMMANDS:
- `agent-browser --session <s> open <url>` -- navigate to URL
- `agent-browser --session <s> snapshot` -- get page accessibility tree
- `agent-browser --session <s> eval "<js>"` -- run JavaScript
- `agent-browser --session <s> get title` -- get page title
- `agent-browser --session <s> close` -- close browser

ALWAYS close the browser session when done to prevent leaked processes.
For simple status code checks, prefer `curl -sk` over agent-browser.

Report results as structured JSON with pass/fail for each check.
```

### setup-fresh-drupal10.sh Script

```bash
#!/usr/bin/env bash
# Creates a fresh Drupal 10 ddev instance for eval.
# Usage: ./eval/setup-fresh-drupal10.sh <unique-name>
# Output: The ddev project directory path on success.
set -euo pipefail

unset CLAUDECODE 2>/dev/null || true

NAME="${1:?Usage: setup-fresh-drupal10.sh <unique-name>}"
TARGET_DIR="/tmp/d10-${NAME}"
MAX_RETRIES=3

# Cleanup stale environment
if [ -d "$TARGET_DIR" ]; then
  echo "Cleaning up stale: $TARGET_DIR" >&2
  (cd "$TARGET_DIR" && ddev delete -O -y 2>/dev/null) || true
  docker run --rm -v /tmp:/tmp alpine rm -rf "/tmp/d10-${NAME}" 2>/dev/null || rm -rf "$TARGET_DIR"
fi

mkdir -p "$TARGET_DIR"
cd "$TARGET_DIR"

# Configure ddev for Drupal 10
ddev config --project-type=drupal10 --docroot=web --project-name="d10-${NAME}"

# Start ddev with retry for router failures
for attempt in $(seq 1 $MAX_RETRIES); do
  if (flock -x 200; ddev start) 200>/tmp/ddev-start.lock; then
    break
  fi
  echo "ddev start failed (attempt $attempt/$MAX_RETRIES), restarting router..." >&2
  docker restart ddev-router 2>/dev/null || true
  sleep 20
  if [ "$attempt" -eq "$MAX_RETRIES" ]; then
    echo "FATAL: ddev start failed after $MAX_RETRIES attempts" >&2
    exit 1
  fi
done

# Install Drupal via Composer
ddev composer create-project "drupal/recommended-project:^10" .
ddev composer require drush/drush

# Install site
ddev drush site:install standard --account-name=admin --account-pass=admin -y

# Verify
if ! ddev drush status --field=bootstrap | grep -q "Successful"; then
  echo "FATAL: Drupal bootstrap failed" >&2
  exit 1
fi

echo "$TARGET_DIR"
```

## State of the Art

| Old Approach (v1.0) | Current Approach (v2.0) | When Changed | Impact |
|----------------------|-------------------------|--------------|--------|
| os-knowledge-garden clones for eval envs | Fresh Drupal 10 ddev instances | v2.0 design | Eliminates Open Social collision issues, faster provisioning, cleaner baseline |
| `/model sonnet` manual switching | `model: sonnet` subagent frontmatter | Claude Code subagent feature | Deterministic model control, no manual intervention |
| `claude -p` headless for eval execution | Agent tool subagents from main session | v2.0 design (per MEMORY lessons) | Full observability, no silent hangs |
| Manual grading by Opus in main session | Dedicated eval-grader subagent | v2.0 design | Consistent grading format, isolated context |
| E2E via manual curl or ad-hoc commands | eval-browser subagent with agent-browser | v2.0 design | Authenticated page verification, visual confirmation |
| Skills passed via prompt injection | `skills:` frontmatter for preloading | Claude Code feature | Deterministic skill injection, guaranteed isolation |

**Deprecated/outdated:**
- `setup-drupal-env.sh`: Still works but depends on os-knowledge-garden; replaced by `setup-fresh-drupal10.sh`
- `eval-prompts.md`: Contains os-kg-grounded prompts; will be rewritten in Phase 9
- `claude -p --model sonnet` headless: Proven unreliable for observability (MEMORY.md rule #2)

## Open Questions

1. **Skill preloading mechanics with `skills:` frontmatter -- does the skill name match the directory name or the `name:` field?**
   - What we know: The `skills:` field takes a list of skill names. Skills are stored in `.claude/skills/<name>/SKILL.md` or `~/.claude/skills/<name>/SKILL.md`. The project's skills are in `skills/drupal-*/SKILL.md` (not in `.claude/skills/`).
   - What's unclear: Whether `.claude/agents/*.md` can reference skills that are NOT in `.claude/skills/` or `~/.claude/skills/`. The project's skills live in `skills/` (a non-standard location).
   - Recommendation: During implementation, test whether skills need to be symlinked or copied to `.claude/skills/` for the `skills:` frontmatter to find them. Alternatively, use the agent's system prompt to instruct it to `Read` the SKILL.md at a given path -- this is more explicit and guaranteed to work.

2. **Agent spawning from main session -- how does the parent pass the ddev project path and eval prompt to the subagent?**
   - What we know: The Agent tool takes a prompt string. The subagent receives only its system prompt (from the .md file) plus the delegation message.
   - What's unclear: Whether the delegation message can include multi-line structured data (eval prompt, ddev path, skill path).
   - Recommendation: Include all context in the delegation prompt. The Agent tool's prompt field supports arbitrary text. Structure as: "Task: [eval prompt]. Environment: ddev project at [path]. Module path: [web/modules/custom/]. [Optional: Read SKILL.md at /path/to/skill first.]"

3. **Teardown script compatibility with fresh D10 instances**
   - What we know: Existing `teardown-drupal-env.sh` uses `os-kg-${NAME}` prefix for project names.
   - What's unclear: Whether to update the existing script or create a new one for `d10-${NAME}` prefix.
   - Recommendation: Create new `teardown-fresh-drupal10.sh` with `d10-${NAME}` naming convention, or parameterize the existing one to accept a prefix.

## Validation Architecture

### Test Framework

| Property | Value |
|----------|-------|
| Framework | Shell scripts (bash) + manual verification |
| Config file | None -- shell-based validation |
| Quick run command | `bash eval/setup-fresh-drupal10.sh test-validation && bash eval/teardown-drupal-env.sh test-validation` |
| Full suite command | Run all 4 validation scenarios below sequentially |

### Phase Requirements to Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| INFRA-01 | eval-executor spawns with model: sonnet and generates Drupal code | integration | Spawn eval-executor agent, have it create a simple .info.yml file in a ddev env, verify file exists | Wave 0 |
| INFRA-02 | eval-grader produces compliant grading.json | integration | Spawn eval-grader agent with a known code directory and expectations, verify JSON schema of output | Wave 0 |
| INFRA-03 | setup-fresh-drupal10.sh creates working Drupal 10 instance | smoke | `bash eval/setup-fresh-drupal10.sh smoke-test && ddev drush status --root=/tmp/d10-smoke-test/web` | Wave 0 |
| INFRA-04 | eval-browser navigates ddev site via drush uli | integration | Spawn eval-browser agent, have it login via drush uli and snapshot a page | Wave 0 |
| INFRA-01+isolation | with-skill agent reads SKILL.md, without-skill does not | integration | Run with-skill for drupal-caching, verify #cache in output; run without-skill, verify differences | Wave 0 |

### Sampling Rate

- **Per task commit:** Run INFRA-03 smoke test (fastest validation)
- **Per wave merge:** Run all 5 validation scenarios
- **Phase gate:** All 5 scenarios pass before `/gsd:verify-work`

### Wave 0 Gaps

- [ ] `eval/setup-fresh-drupal10.sh` -- new script, does not exist yet
- [ ] `.claude/agents/eval-executor.md` -- new file, does not exist yet
- [ ] `.claude/agents/eval-grader.md` -- new file, does not exist yet
- [ ] `.claude/agents/eval-browser.md` -- new file, does not exist yet
- [ ] `.claude/agents/` directory -- does not exist yet, needs creation
- [ ] Validation that `skills:` frontmatter can find project skills in `skills/` path (or determine alternative approach)

## Sources

### Primary (HIGH confidence)

- [Claude Code subagent docs](https://code.claude.com/docs/en/sub-agents) - Complete frontmatter reference, model field, skills field, permissionMode, tools configuration, isolation behavior
- [Claude Code skills docs](https://code.claude.com/docs/en/skills) - Skills preloading into subagents, `context: fork`, skill directory structure
- [DDEV Drupal quickstart](https://docs.ddev.com/en/latest/users/quickstart/) - Fresh Drupal 10 setup commands with ddev
- v1.0 grading.json/benchmark.json files in `.planning/milestones/v1.0-phases/workspaces/` - Proven JSON schemas
- MEMORY.md project context - Eval execution rules, lessons learned from v1.0

### Secondary (MEDIUM confidence)

- [Claude Code subagent frontmatter issue #8501](https://github.com/anthropics/claude-code/issues/8501) - Documents undocumented frontmatter fields like `color:`
- [ddev-router health check issue](https://github.com/ddev/ddev/issues/814) - Router failure patterns and recovery steps
- [Drupal.org ddev installation guide](https://www.drupal.org/docs/getting-started/installing-drupal/install-drupal-using-ddev-for-local-development) - Alternative installation reference

### Tertiary (LOW confidence)

- `skills:` frontmatter discovery path behavior for non-standard skill locations -- needs empirical validation during implementation

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - All tools verified installed and operational; Claude Code docs comprehensive
- Architecture: HIGH - Patterns proven in v1.0; subagent docs clearly document all needed features
- Pitfalls: HIGH - Based on empirical v1.0 lessons documented in MEMORY.md and analysis-iteration-1.md
- Skill preloading path: MEDIUM - Docs clear on mechanism but unclear if `skills/drupal-*/SKILL.md` path is discoverable; may need `.claude/skills/` symlinks

**Research date:** 2026-03-06
**Valid until:** 2026-04-06 (30 days -- Claude Code subagent API is stable)

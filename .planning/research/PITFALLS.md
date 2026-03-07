# Domain Pitfalls

**Domain:** Automated eval pipeline for Claude Code skills (subagents, ddev, agent-browser, batch execution)
**Researched:** 2026-03-06
**Overall confidence:** HIGH (derived from v1.0 empirical evidence across 9+ eval runs, project memory, source code analysis)

**Scope:** This document covers pitfalls specific to ADDING an automated eval pipeline (v2.0) to the existing Drupal skills project. v1.0 skill-authoring pitfalls are preserved in the appendix for reference but are not the focus.

---

## Critical Pitfalls

Mistakes that produce invalid eval data, waste entire sessions, or require full reruns.

### Pitfall 1: Subagent Model Inheritance is Invisible and Uncontrollable

**What goes wrong:** The Agent tool has no `model` parameter (schema enforces `additionalProperties: false`). Subagents silently inherit the main session's active model. If the orchestrator runs on Opus, eval-executor agents also run on Opus -- producing inflated without-skill scores that invalidate the entire A/B comparison.

**Why it happens:** The assumption that subagent model can be configured per-call (like temperature or system prompt) is natural but wrong. Claude Code's Agent tool does not expose model selection. GSD's `model_profile` system also does NOT control Agent tool model -- this was verified empirically in v1.0 session 1.

**Consequences:** Eval data is invalid but looks valid. Without-skill Opus scores match with-skill Opus scores, producing false 0% deltas. You cannot detect this from grading results alone because both runs produce high-quality code.

**Prevention:**
- v1.0 workaround: Use `/model sonnet` in the main session before spawning eval agents, then `/model opus` after for grading. This is manual and error-prone -- easy to forget the switch-back.
- v2.0 solution: Build a dedicated `eval-executor` subagent with `model: sonnet` in its `.md` frontmatter. This locks the model at the subagent definition level, not the call site. The orchestrator stays on Opus throughout.
- Validation gate: After each eval run, compare code style. If without-skill code shows Opus-level sophistication (detailed comments, edge case handling, non-obvious patterns), the model was wrong.

**Detection:** Compare code verbosity and quality between with/without runs. Sonnet produces shorter, more formulaic code. Opus produces more detailed, context-aware code. If without-skill output reads like Opus, model inheritance leaked.

**Phase to address:** Subagent architecture phase -- must be solved before any eval runs begin.

---

### Pitfall 2: Knowledge Contamination Defeats the Entire Eval

**What goes wrong:** The without-skill agent has indirect access to SKILL.md content through leak paths, producing artificially high without-skill scores and false 0% deltas.

**Why it happens:** Multiple contamination vectors exist, and any single leak invalidates results:
1. **Same agent, two calls:** A single agent that runs with-skill first retains SKILL.md knowledge for the without-skill run. Context cannot be "unlearned."
2. **Project-level files:** CLAUDE.md, `.claude/settings.json`, or installed skills in `~/.claude/skills/` contain or reference skill content.
3. **GSD executor discovery:** The GSD executor proactively scans the project for skills and loads them into context -- confirmed in v1.0.
4. **Eval prompt leakage:** The eval prompt itself (evals.json expectations) contains implementation hints that teach the agent what patterns to produce.
5. **Workspace cross-contamination:** If with-skill and without-skill agents share a workspace directory, generated files from the first run are visible to the second.

**Consequences:** The fundamental A/B comparison is invalid. Skills that genuinely add value show 0% delta. This was the MOST damaging pitfall in v1.0 -- though ultimately the 0% deltas were attributed to non-discriminating assertions (#3), contamination had to be ruled out first, consuming significant debugging time.

**Prevention:**
- **Two separate Agent subagent calls per skill.** Never reuse an agent across with/without.
- **No skills installed globally.** Skills must NOT be in `~/.claude/skills/`. Verify before each batch with `ls ~/.claude/skills/drupal-* 2>/dev/null`.
- **No CLAUDE.md skill references.** The project CLAUDE.md must not mention skill content or paths.
- **Skip GSD executor.** Orchestrate eval runs directly from the main session; the GSD executor layer auto-discovers skills.
- **Minimal eval prompts.** The prompt given to without-skill agents should describe WHAT to build, not HOW. No Drupal-specific implementation hints.
- **Separate workspace directories.** Each agent gets its own `/tmp/os-kg-*` directory.
- **Pre-flight contamination check:** Before spawning without-skill agent, verify no SKILL.md in agent context, no `~/.claude/skills/drupal-*`, no skill references in CLAUDE.md.

**Detection:** If without-skill code contains patterns that are non-obvious and specific to SKILL.md (e.g., cache max-age 0 avoidance, D11 Hook attribute syntax, specific entity annotation field ordering from Sipos), contamination occurred.

**Phase to address:** Subagent architecture phase. Must be validated in the first eval run before proceeding to batch execution.

---

### Pitfall 3: Non-Discriminating Assertions Produce Meaningless Evals

**What goes wrong:** Assertions test patterns that Sonnet already knows from training data. Both with-skill and without-skill produce identical correct code, yielding 0% delta across the board.

**Why it happens:** This is the single most impactful finding from v1.0. Eval authors (often Sonnet-powered agents) naturally write assertions for patterns they know -- which are exactly the patterns Sonnet already handles correctly. This is circular: testing "what I know" guarantees passing without the skill. v1.0 empirical data:
- 9 of 13 skills showed 0% delta with standard assertions (routing, forms, plugins, config, access, theming, database, views, batch)
- Only 4 skills with genuinely non-obvious assertions showed meaningful deltas (caching +75%, scaffold +43%, entities +21%, testing +19%)

**Consequences:** Skills that genuinely add value appear worthless. The entire eval investment produces no actionable data.

**Prevention:**
- **Assertions MUST come from SKILL.md non-obvious patterns, not general Drupal knowledge.** Read each SKILL.md and identify patterns that are counter-intuitive, book-specific, or conflict with common practice.
- **Target "golden rules" and "gotchas":** Caching golden rule (never set max-age 0), entity annotation field ordering requirements, hook_theme render array structure, config schema strictness, proper test base class selection.
- **Anti-test before finalizing:** For each assertion, ask: "Would Sonnet without this specific book knowledge produce this pattern?" If yes, the assertion is non-discriminating. Remove it.
- **Calibration skills:** Use caching (75% delta in v1.0) and scaffold (43% delta) as calibration benchmarks. If the new pipeline produces lower deltas on these skills, something is wrong with the pipeline, not the skills.
- **Empirical validation:** Run one calibration skill's eval before batch execution. If delta is unexpectedly 0%, assertions need more work before running all 13.

**Detection:** 0% delta on skills whose SKILL.md contains clearly non-obvious patterns. All skills scoring 100%/100% is a red flag, not a success.

**Phase to address:** Eval design phase. Must be complete before any eval runs. This is the highest-leverage pitfall -- getting assertions right is worth more than any other pipeline improvement.

---

### Pitfall 4: ddev-router Health Check Failures Stall the Pipeline

**What goes wrong:** `ddev start` fails with traefik/ddev-router health check errors on approximately 50% of first starts. The error looks like a configuration problem but is a Docker networking race condition.

**Why it happens:** The ddev-router (traefik) container needs time to register new project routes. When multiple ddev projects start in sequence, the router can get into a bad state. The os-knowledge-garden install compounds this with heavy Drupal install (`--demo=cascadia`) taking 2-5 minutes.

**Consequences:** Eval setup fails, requiring manual intervention. In a batch execution loop, one failure stalls the entire session. v1.0 experienced this on roughly half of all environment setups.

**Prevention:**
- **Auto-retry with router restart built into setup script:** After a failed `ddev start`, automatically run `docker restart ddev-router && sleep 20 && ddev start`. This should be in the script, not manual.
- **Serialize ddev starts:** The setup script already uses `flock -x 200` on `/tmp/ddev-start.lock`. Keep this.
- **Fresh Drupal 10 over os-knowledge-garden:** v2.0 targets fresh D10 installs. This eliminates os-kg-specific traefik complexity (Qdrant, Solr, extra services). Faster startup, fewer failure modes.
- **Health check before proceeding:** After `ddev start` succeeds, run `curl -sk https://<project>.ddev.site/` and verify 200 before declaring environment ready.
- **Parallel instance limit:** Never run more than 2 ddev instances simultaneously.

**Detection:** `ddev start` exits non-zero with health check timeout in stderr. Easy to detect programmatically.

**Phase to address:** Environment setup phase. Build retry logic into the setup script itself.

---

### Pitfall 5: Context Window Overflow During Batch Execution

**What goes wrong:** The orchestrating session accumulates context from multiple eval runs until it hits the context window limit. The session becomes sluggish, loses state, or silently drops early context (including grading methodology).

**Why it happens:** Each skill eval involves: reading evals.json, reading SKILL.md (for with-skill), spawning 2 agents (whose outputs return to context), reading generated files for grading, writing grading results. For 13 skills, this is ~26 agent outputs plus ~50+ file reads. v1.0 limited sessions to 3-4 skills for this reason.

**Consequences:** Late-session evals get worse grading quality because grading criteria from early context has been evicted. The orchestrator may forget completed skills and re-run them, or skip incomplete ones.

**Prevention:**
- **Hard limit: 3-4 skills per orchestrator session.** Do not attempt all 13 in one session.
- **Externalize state to disk:** Write `benchmark.json` and `grading.json` after each skill. The next session reads disk state, not memory.
- **Minimize context consumption:** Have the grading agent read files and return only pass/fail per assertion, not entire file contents.
- **Session handoff via `.continue-here.md`:** Write completed/remaining skills, iteration numbers, and anomalies after each session.
- **Stateless orchestrator:** Each session should be able to start from scratch by reading disk state. No implicit memory dependencies between sessions.

**Detection:** Model responses become shorter, less detailed, or repeat questions already answered. Context warning messages appear (like the ones this session is generating).

**Phase to address:** Batch execution architecture phase. Design the session boundary strategy before starting eval runs.

---

## Moderate Pitfalls

### Pitfall 6: agent-browser Unreliability for E2E Assertions

**What goes wrong:** agent-browser times out, fails to render JavaScript-heavy pages, or cannot handle ddev's self-signed certificates. E2E assertions fail not because the code is wrong but because the test tool is flaky.

**Prevention:**
- **Prefer curl for simple assertions:** `curl -sk` handles self-signed certs and non-standard ports reliably. Use it for status checks, page-contains (with grep), and API responses.
- **Reserve agent-browser for UI-specific tests:** Only use when JavaScript execution is required (Ajax forms, dynamic blocks) or visual verification.
- **Always use `--ignore-https-errors`** flag with agent-browser.
- **Wrap calls with `timeout 30s`** to prevent indefinite hangs.
- **Session cleanup in trap handler** to prevent leaked Chromium processes consuming RAM.
- **drush uli for authenticated pages:** Use `drush uli` to get one-time login URLs instead of managing cookies.

**Phase to address:** E2E assertion tooling phase. Decide curl vs agent-browser per assertion type upfront.

---

### Pitfall 7: Drupal Registry Corruption on Unclean Module Removal

**What goes wrong:** Removing module files with `rm -rf` before running `drush pm:uninstall` leaves Drupal's module registry pointing at nonexistent files. All subsequent operations throw fatal PHP errors.

**Prevention:**
- **Always `drush pm:uninstall <module>` before deleting files.** This is non-negotiable for reusable environments.
- **For eval, prefer tearing down the entire ddev env** rather than selectively uninstalling. Faster and more reliable.
- **v2.0 with fresh D10 instances:** Each eval gets a fresh environment, making module removal unnecessary.

**Phase to address:** Environment lifecycle phase. Already addressed in teardown script; maintain in v2.0.

---

### Pitfall 8: Grader Bias Toward Generous Scoring

**What goes wrong:** The grading agent (Opus) tends toward generous interpretation. It marks assertions as "PASS" when code is close but not exactly right, or when the pattern exists in a different form than expected.

**Prevention:**
- **Binary assertions only:** Each assertion should be verifiable with grep/AST check, not subjective judgment. "File contains `CacheBackendInterface`" not "code uses proper caching patterns."
- **Automated grading where possible:** Use grep, jq, `php -l` (syntax check), drush commands for objective verification. Reserve LLM grading for semantic checks only.
- **Explicit rubric in evals.json:** Each assertion specifies exact strings, patterns, or file structures. The grader applies the rubric, not judgment.
- **Separate grader from skill author:** Fresh agent with only the rubric, no skill context.
- **Suspicious perfection check:** Any skill scoring 100%/100% with/without should be manually reviewed -- likely indicates non-discriminating assertions.

**Phase to address:** Grading methodology phase. Define rubric format before eval runs.

---

### Pitfall 9: Stale Docker/ddev State Between Runs

**What goes wrong:** Docker containers, volumes, or network configs from previous eval runs persist and interfere with new runs. Port conflicts, stale databases, or orphaned containers cause setup failures.

**Prevention:**
- **Full teardown between skills:** Run `teardown-drupal-env.sh` and verify via `ddev list`.
- **Pre-run cleanup:** `ddev list | grep -o 'os-kg-[^ ]*' | xargs -I{} ddev delete -O {}` to catch orphans.
- **Docker prune on session start:** `docker container prune -f && docker network prune -f` at beginning of each session.
- **Unique naming per eval run:** `os-kg-caching-with-iter3` -- never reuse names within a session.

**Phase to address:** Environment setup phase. Build into setup/teardown scripts.

---

### Pitfall 10: Port Conflicts with Non-Standard ddev Ports

**What goes wrong:** ddev assigns dynamic HTTPS ports when default (443) is taken. Hardcoded URLs in e2e-assert.sh and eval scripts assume standard ports, causing all E2E assertions to fail against valid running sites.

**Prevention:**
- **Use `ddev describe -j | jq -r '.raw.httpurl'`** to get actual URL with correct port.
- **Pass full base URL to assertion scripts** rather than constructing from project name.
- **Use `ddev exec curl localhost`** (inside container) to bypass port issues entirely for simple checks.
- **Configure explicit ports** in ddev config.yaml for eval environments if consistency is needed.

**Phase to address:** Environment setup phase. Bake URL discovery into setup script output.

---

### Pitfall 11: Eval Prompt Wording Overfits to Specific Implementation

**What goes wrong:** Assertions pass only when the eval prompt uses specific wording. Minor prompt rephrasing causes different (but equally valid) code that fails assertions. This means the eval measures prompt sensitivity, not skill value.

**Prevention:**
- **Assert patterns, not exact code:** Check for `use Drupal\Core\Cache\CacheBackendInterface` not a specific import order or line number.
- **Accept multiple valid patterns:** Where Drupal allows alternatives (annotation vs attribute, YAML vs PHP config), accept both.
- **Test invariants, not implementation:** "Module installs without error" and "route responds 200" are robust. "File line 42 contains X" is brittle.
- **Run each eval with 2+ prompt variations** during validation to ensure assertions are prompt-independent.

**Phase to address:** Eval design phase.

---

## Minor Pitfalls

### Pitfall 12: CLAUDECODE Environment Variable Blocks Nested Sessions

**What goes wrong:** Nested Claude sessions fail because the `CLAUDECODE` env var from the parent session interferes with child processes.

**Prevention:** `unset CLAUDECODE` at the top of any script that runs inside a Claude session. Already implemented in `setup-drupal-env.sh`. Ensure all new scripts follow the same pattern.

**Phase to address:** Already addressed. Verify in all new scripts.

---

### Pitfall 13: Forgetting to Switch Model Back After Eval Spawning (v1.0 only)

**What goes wrong:** After spawning eval agents on Sonnet via `/model`, the orchestrator forgets to switch back to Opus for grading. Grading runs on Sonnet, producing lower-quality analysis.

**Prevention:** v2.0 eliminates this entirely -- eval-executor subagent is locked to Sonnet via frontmatter, orchestrator stays on Opus throughout. No manual model switching needed.

**Phase to address:** Subagent architecture phase. Solved by design in v2.0.

---

### Pitfall 14: RAM Exhaustion from Leaked Browser Processes

**What goes wrong:** Each agent-browser call spawns a Chromium process. If sessions are not properly closed (e.g., script crashes before cleanup), Chromium processes accumulate and consume all available RAM. After 3-4 leaked sessions, the system becomes unresponsive.

**Prevention:**
- **Always use trap handlers** to close agent-browser sessions on script exit.
- **Monitor with `pgrep -c chromium`** before each eval run. If count > 2, kill orphans.
- **Prefer curl over agent-browser** to minimize browser process usage.

**Phase to address:** E2E assertion tooling phase.

---

## Phase-Specific Warnings

| Phase Topic | Likely Pitfall | Mitigation |
|-------------|---------------|------------|
| Subagent architecture | Model inheritance (#1), knowledge contamination (#2), model switch-back (#13) | Lock model in subagent frontmatter, separate agents per run, pre-flight contamination check |
| Eval design (assertions) | Non-discriminating assertions (#3), overfitting to prompts (#11) | Source assertions from SKILL.md non-obvious patterns, assert patterns not exact code, calibrate against known-good skills |
| Environment setup | ddev-router failures (#4), port conflicts (#10), stale state (#9) | Auto-retry in script, URL discovery via ddev describe, full teardown between runs |
| Batch execution | Context overflow (#5), stale state (#9), RAM exhaustion (#14) | 3-4 skills per session, externalize state to disk, monitor Chromium processes |
| E2E verification | agent-browser flakiness (#6), port conflicts (#10), RAM leaks (#14) | Prefer curl, reserve agent-browser for JS-only tests, trap cleanup handlers |
| Grading | Grader bias (#8), non-discriminating assertions (#3) | Binary assertions, automated grep/drush checks, suspicious perfection review |
| Module lifecycle | Registry corruption (#7), CLAUDECODE env (#12) | Tear down entire ddev env, unset CLAUDECODE in all scripts |

---

## Appendix: v1.0 Skill-Authoring Pitfalls (preserved for reference)

The following pitfalls from v1.0 research remain relevant to the skills themselves but are not the focus of v2.0 pipeline work:

1. **Annotation-Only Code Examples** -- Skills must show both D10 annotations and D11 PHP attributes
2. **Skills as Reference Docs** -- Skills must be decision guides, not API dumps
3. **Trigger Description Tuning** -- Descriptions must match developer intent, not skill content
4. **Cross-Reference Loops** -- Each Drupal concept owned by exactly one skill
5. **500-Line Limit on Deep Topics** -- Use reference files for overflow
6. **Drupal Version Confusion** -- Explicitly anchor to D10/D11, blacklist D7/D8 patterns
7. **Missing YAML Ecosystem** -- Every PHP example needs corresponding YAML files
8. **Non-Grounded Eval Prompts** -- Prompts should reference real project code
9. **Training Data Conflicts** -- Skills must work with Claude's existing knowledge, not against it
10. **Monolithic Skills** -- Validate groupings against real developer prompts

These are documented in detail in the git history of this file (v1.0 research, 2026-03-05).

---

## Sources

- v1.0 empirical eval results: `.planning/phases/07-full-eval-optimize-loop/.continue-here.md` (HIGH confidence -- 9 skills evaluated, direct observations)
- v1.0 phase 6 eval results: `.planning/phases/06-live-eval-loop/.continue-here.md` (HIGH confidence -- 4 skills with meaningful deltas)
- Project memory: MEMORY.md eval execution rules (HIGH confidence -- validated across 7+ sessions)
- Setup/teardown source code: `eval/setup-drupal-env.sh`, `eval/teardown-drupal-env.sh` (HIGH confidence)
- e2e-assert.sh source code: `eval/e2e-assert.sh` (HIGH confidence)
- Claude Code Agent tool schema: empirically verified `additionalProperties: false`, no model parameter (HIGH confidence)
- ddev-router behavior: consistent with observed 50% failure rate across v1.0 sessions (HIGH confidence)

---
*Pitfalls research for: Automated eval pipeline for Claude Code Drupal skills*
*Researched: 2026-03-06*

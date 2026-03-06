# Skill-Creator Eval Loop Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Run 4 representative Drupal skills through the skill-creator's real eval infrastructure with Sonnet 4.6 subagents against live Drupal instances, producing graded benchmarks and an HTML eval viewer for human review.

**Architecture:** For each of the 4 skills, create evals.json with functional assertions, spawn with-skill and without-skill Sonnet 4.6 subagents that each clone os-knowledge-garden, stand up a Drupal instance via ddev, generate code from the eval prompt, and verify it works. Grade outputs, aggregate benchmarks, launch viewer.

**Tech Stack:** skill-creator eval infrastructure, ddev, Drupal 10 (Open Social), Sonnet 4.6, Python (generate_review.py, aggregate_benchmark.py)

**Constants:**
- Skill-creator path: `/home/proofoftom/.claude/plugins/cache/claude-plugins-official/skill-creator/205b6e0b3036/skills/skill-creator`
- os-knowledge-garden source: `/home/proofoftom/Code/drupal-skills/os-knowledge-garden`
- Gemini API key: already configured in os-knowledge-garden/.ddev/.env
- Skills base: `/home/proofoftom/Code/drupal-skills/skills`

---

## Task 1: Create evals.json for drupal-module-scaffold

**Files:**
- Create: `skills/drupal-module-scaffold/evals/evals.json`

**Step 1: Create the evals directory and evals.json**

```json
{
  "skill_name": "drupal-module-scaffold",
  "evals": [
    {
      "id": 1,
      "prompt": "I need to create a new Drupal module called event_analytics for tracking event attendance on our Open Social site. It should depend on the node module and be part of a custom 'Events' package. Set it up so I can start adding services and controllers to it. We're running Drupal 10 but want D11 compatibility.",
      "expected_output": "A complete Drupal module scaffold with .info.yml, .module file, and src/ directory following PSR-4 conventions",
      "files": [],
      "expectations": [
        "event_analytics.info.yml contains 'type: module'",
        "event_analytics.info.yml contains 'core_version_requirement' with '^10 || ^11' or '^10' (NOT 'core: 8.x')",
        "event_analytics.info.yml contains 'package: Events'",
        "event_analytics.info.yml lists 'node' as a dependency using 'drupal:node' format",
        "A .module file exists with 'declare(strict_types=1)'",
        "The module enables successfully: ddev drush en event_analytics returns exit code 0",
        "No PHP syntax errors: ddev drush php-eval \"echo 'ok';\" returns 'ok' after enabling"
      ]
    }
  ]
}
```

**Step 2: Commit**

```bash
git add skills/drupal-module-scaffold/evals/evals.json
git commit -m "eval(scaffold): add evals.json with functional assertions"
```

---

## Task 2: Create evals.json for drupal-entities-fields

**Files:**
- Create: `skills/drupal-entities-fields/evals/evals.json`

**Step 1: Create the evals directory and evals.json**

```json
{
  "skill_name": "drupal-entities-fields",
  "evals": [
    {
      "id": 1,
      "prompt": "I'm building an event registration system on our Open Social Drupal 10 site. Create a custom content entity type called EventEnrollment for tracking event enrollments. It should have base fields for: event reference (entity_reference to node), user reference (entity_reference to user), enrollment status (list_string with allowed values: pending, confirmed, cancelled), and enrollment date (created timestamp). Include all necessary handlers - forms, list builder, access handler, and route provider. Put it in a module called event_enrollment.",
      "expected_output": "A complete content entity type with entity class, base fields, all handlers, and module scaffold that installs and creates entities successfully",
      "files": [],
      "expectations": [
        "Entity class file exists at src/Entity/EventEnrollment.php",
        "Entity class has either #[ContentEntityType] attribute (D11) or @ContentEntityType annotation (D10)",
        "baseFieldDefinitions() calls parent::baseFieldDefinitions() first",
        "event_reference field uses setSetting('target_type', 'node')",
        "status field defines allowed_values with pending, confirmed, cancelled",
        "Entity handlers include form, list_builder, and access handler declarations",
        "The module enables successfully: ddev drush en event_enrollment returns exit code 0",
        "Entity install succeeds: ddev drush entity:updates shows no pending updates or completes without error",
        "An entity can be created: ddev drush php-eval creates an EventEnrollment entity and saves it without error"
      ]
    }
  ]
}
```

**Step 2: Commit**

```bash
git add skills/drupal-entities-fields/evals/evals.json
git commit -m "eval(entities): add evals.json with functional assertions"
```

---

## Task 3: Create evals.json for drupal-caching

**Files:**
- Create: `skills/drupal-caching/evals/evals.json`

**Step 1: Create the evals directory and evals.json**

```json
{
  "skill_name": "drupal-caching",
  "evals": [
    {
      "id": 1,
      "prompt": "I have a block plugin on our Open Social Drupal 10 site that displays nodes related to the currently viewed page, filtered by the current user's group membership. The block loads specific node entities and renders their titles as links. Right now the block has no caching and it's hurting performance. Add proper cache tags and contexts to the block's build() method. The block should invalidate when any of the displayed nodes are updated, and it should vary by the current route (so different pages show different related content) and by user (so group filtering works per-user). Put this in a module called related_content_block. Do NOT use max-age: 0.",
      "expected_output": "A block plugin with correct cache metadata - tags for each displayed node, route and user contexts, and NO max-age: 0",
      "files": [],
      "expectations": [
        "Block class implements build() returning a render array",
        "Render array includes #cache key with 'tags' array",
        "Cache tags include node-specific tags (e.g., 'node:ID' pattern for displayed nodes)",
        "Cache contexts include 'route' or 'route.name'",
        "Cache contexts include 'user' or 'user.permissions' or 'user.roles'",
        "Code does NOT contain 'max-age' => 0 or '#max-age' => 0",
        "Block class implements getCacheContexts() or getCacheTags() methods, or sets cache metadata in build()",
        "The module enables successfully: ddev drush en related_content_block returns exit code 0"
      ]
    }
  ]
}
```

**Step 2: Commit**

```bash
git add skills/drupal-caching/evals/evals.json
git commit -m "eval(caching): add evals.json with functional assertions"
```

---

## Task 4: Create evals.json for drupal-testing

**Files:**
- Create: `skills/drupal-testing/evals/evals.json`

**Step 1: Create the evals directory and evals.json**

```json
{
  "skill_name": "drupal-testing",
  "evals": [
    {
      "id": 1,
      "prompt": "Write a kernel test for the social_ai_indexing module that verifies its content indexing service works correctly. The test should: install the node module and social_ai_indexing module, create test node entities of type 'topic' (Open Social's content type), and verify the service can be loaded from the container. Use the correct test base class - this is testing a service, not a browser interaction. Our site runs Drupal 10 with Open Social.",
      "expected_output": "A kernel test extending KernelTestBase with correct module installation, entity schema setup, and service testing",
      "files": [],
      "expectations": [
        "Test class extends KernelTestBase (NOT BrowserTestBase or WebDriverTestBase)",
        "Test class has protected static $modules array listing required modules",
        "$modules includes 'node' and 'social_ai_indexing'",
        "setUp() calls parent::setUp()",
        "setUp() calls $this->installEntitySchema('node') or similar entity schema installation",
        "Test has @group annotation",
        "Test uses $this->container->get() or \\Drupal::service() to load the service under test",
        "Test uses proper assertion methods ($this->assertNotNull, $this->assertEquals, etc.)"
      ]
    }
  ]
}
```

**Step 2: Commit**

```bash
git add skills/drupal-testing/evals/evals.json
git commit -m "eval(testing): add evals.json with functional assertions"
```

---

## Task 5: Create shared Drupal environment setup script

Each eval subagent needs to clone os-knowledge-garden, start ddev, and install Drupal. Rather than duplicate this in every agent prompt, create a reusable setup script.

**Files:**
- Create: `eval/setup-drupal-env.sh`

**Step 1: Write the setup script**

```bash
#!/usr/bin/env bash
#
# Sets up an isolated Drupal environment for skill eval.
#
# Usage: ./eval/setup-drupal-env.sh <unique-name>
# Example: ./eval/setup-drupal-env.sh scaffold-with-skill
#
# Outputs the ddev project directory path on success.
# The caller is responsible for teardown: ddev delete -O -y
#
set -euo pipefail

NAME="${1:?Usage: setup-drupal-env.sh <unique-name>}"
SOURCE_DIR="$(cd "$(dirname "$0")/../os-knowledge-garden" && pwd)"
TARGET_DIR="/tmp/os-kg-${NAME}"

# Clone (local, fast)
if [ -d "$TARGET_DIR" ]; then
  echo "Target directory already exists: $TARGET_DIR" >&2
  exit 1
fi
cp -a "$SOURCE_DIR" "$TARGET_DIR"

# Give ddev a unique project name
cd "$TARGET_DIR"
sed -i "s/^name:.*/name: os-kg-${NAME}/" .ddev/config.yaml

# Start ddev and install
ddev start
bash scripts/install.sh --demo=cascadia

# Output the working directory
echo "$TARGET_DIR"
```

**Step 2: Make executable and commit**

```bash
chmod +x eval/setup-drupal-env.sh
git add eval/setup-drupal-env.sh
git commit -m "eval: add shared Drupal environment setup script"
```

---

## Task 6: Create shared teardown script

**Files:**
- Create: `eval/teardown-drupal-env.sh`

**Step 1: Write the teardown script**

```bash
#!/usr/bin/env bash
#
# Tears down a Drupal eval environment.
#
# Usage: ./eval/teardown-drupal-env.sh <unique-name>
#
set -euo pipefail

NAME="${1:?Usage: teardown-drupal-env.sh <unique-name>}"
TARGET_DIR="/tmp/os-kg-${NAME}"

if [ ! -d "$TARGET_DIR" ]; then
  echo "Directory not found: $TARGET_DIR" >&2
  exit 0
fi

cd "$TARGET_DIR"
ddev delete -O -y 2>/dev/null || true
rm -rf "$TARGET_DIR"
echo "Cleaned up: $TARGET_DIR"
```

**Step 2: Make executable and commit**

```bash
chmod +x eval/teardown-drupal-env.sh
git add eval/teardown-drupal-env.sh
git commit -m "eval: add shared Drupal environment teardown script"
```

---

## Task 7: Run eval subagents for drupal-module-scaffold (with-skill + baseline)

This is the first skill through the eval loop. Spawn 2 Sonnet 4.6 subagents in parallel.

**Step 1: Create workspace directories**

```bash
mkdir -p drupal-module-scaffold-workspace/iteration-1/eval-scaffold-module/with_skill/outputs
mkdir -p drupal-module-scaffold-workspace/iteration-1/eval-scaffold-module/without_skill/outputs
```

**Step 2: Create eval_metadata.json**

```json
{
  "eval_id": 1,
  "eval_name": "eval-scaffold-module",
  "prompt": "I need to create a new Drupal module called event_analytics for tracking event attendance on our Open Social site. It should depend on the node module and be part of a custom 'Events' package. Set it up so I can start adding services and controllers to it. We're running Drupal 10 but want D11 compatibility.",
  "assertions": [
    "event_analytics.info.yml contains 'type: module'",
    "event_analytics.info.yml contains 'core_version_requirement' with '^10 || ^11' or '^10' (NOT 'core: 8.x')",
    "event_analytics.info.yml contains 'package: Events'",
    "event_analytics.info.yml lists 'node' as a dependency using 'drupal:node' format",
    "A .module file exists with 'declare(strict_types=1)'",
    "The module enables successfully: ddev drush en event_analytics returns exit code 0",
    "No PHP syntax errors: ddev drush php-eval \"echo 'ok';\" returns 'ok' after enabling"
  ]
}
```

Save to `drupal-module-scaffold-workspace/iteration-1/eval-scaffold-module/eval_metadata.json`.

**Step 3: Spawn with-skill agent (Sonnet 4.6)**

Prompt for the with-skill subagent:

```
You are evaluating a Drupal skill. Your job:

1. Set up a Drupal environment:
   bash /home/proofoftom/Code/drupal-skills/eval/setup-drupal-env.sh scaffold-with

2. Read the skill at: /home/proofoftom/Code/drupal-skills/skills/drupal-module-scaffold/SKILL.md
   Also read any files in: /home/proofoftom/Code/drupal-skills/skills/drupal-module-scaffold/references/
   Follow the skill's guidance while completing the task.

3. Complete this task in the Drupal instance (work in /tmp/os-kg-scaffold-with/html/modules/custom/):
   "I need to create a new Drupal module called event_analytics for tracking event attendance on our Open Social site. It should depend on the node module and be part of a custom 'Events' package. Set it up so I can start adding services and controllers to it. We're running Drupal 10 but want D11 compatibility."

4. Verify your work:
   - cd /tmp/os-kg-scaffold-with
   - ddev drush cr
   - ddev drush en event_analytics -y
   - ddev drush php-eval "echo 'ok';"

5. Copy all generated module files to the outputs directory:
   cp -r /tmp/os-kg-scaffold-with/html/modules/custom/event_analytics/* /home/proofoftom/Code/drupal-skills/drupal-module-scaffold-workspace/iteration-1/eval-scaffold-module/with_skill/outputs/

6. Write a transcript of everything you did to:
   /home/proofoftom/Code/drupal-skills/drupal-module-scaffold-workspace/iteration-1/eval-scaffold-module/with_skill/outputs/../transcript.md

7. Tear down:
   bash /home/proofoftom/Code/drupal-skills/eval/teardown-drupal-env.sh scaffold-with
```

Use: `Agent(subagent_type="general-purpose", model="claude-sonnet-4-6")`

**Step 4: Spawn without-skill agent (Sonnet 4.6) in parallel**

Same prompt but step 2 is removed (no skill to read). Environment name is `scaffold-without`. Outputs go to `without_skill/outputs/`.

**Step 5: Capture timing data from both agents**

When each agent completes, save `total_tokens` and `duration_ms` from the task notification to:
- `drupal-module-scaffold-workspace/iteration-1/eval-scaffold-module/with_skill/timing.json`
- `drupal-module-scaffold-workspace/iteration-1/eval-scaffold-module/without_skill/timing.json`

---

## Task 8: Run eval subagents for drupal-entities-fields

Same pattern as Task 7 but for the entities skill.

**Step 1: Create workspace directories**

```bash
mkdir -p drupal-entities-fields-workspace/iteration-1/eval-entity-enrollment/with_skill/outputs
mkdir -p drupal-entities-fields-workspace/iteration-1/eval-entity-enrollment/without_skill/outputs
```

**Step 2: Create eval_metadata.json** using the entities eval prompt and assertions from Task 2.

**Step 3-5:** Spawn with-skill and without-skill agents (Sonnet 4.6) using environment names `entities-with` and `entities-without`. The with-skill agent reads `skills/drupal-entities-fields/SKILL.md` and `skills/drupal-entities-fields/references/`. Task prompt is the entities eval prompt. Agents work in `/tmp/os-kg-entities-with/html/modules/custom/event_enrollment/`. Verification includes:
- `ddev drush en event_enrollment -y`
- `ddev drush entity:updates`
- `ddev drush php-eval "use Drupal\event_enrollment\Entity\EventEnrollment; \$e = EventEnrollment::create(['event_reference' => 1, 'user_reference' => 1, 'status' => 'pending']); \$e->save(); echo 'Created entity ' . \$e->id();"`

---

## Task 9: Run eval subagents for drupal-caching

Same pattern. Environment names: `caching-with`, `caching-without`. Workspace: `drupal-caching-workspace/`. Eval dir: `eval-cache-block/`.

The with-skill agent reads `skills/drupal-caching/SKILL.md` and references. Task prompt is the caching eval. Verification:
- `ddev drush en related_content_block -y`
- `ddev drush php-eval` to confirm no PHP errors
- Manual inspection of outputs for cache metadata patterns

---

## Task 10: Run eval subagents for drupal-testing

Same pattern. Environment names: `testing-with`, `testing-without`. Workspace: `drupal-testing-workspace/`. Eval dir: `eval-kernel-test/`.

The with-skill agent reads `skills/drupal-testing/SKILL.md` and references. Task prompt is the testing eval. Verification:
- Check the test file exists and has correct base class
- `ddev drush php-eval "echo class_exists('Drupal\\Tests\\social_ai_indexing\\Kernel\\RelatedContentServiceTest') ? 'found' : 'missing';"` (if possible)

Note: actually *running* the test may fail if social_ai_indexing has complex dependencies. The assertions focus on code quality (correct base class, correct setup patterns) rather than test pass/fail.

---

## Task 11: Grade all runs

For each of the 8 runs (4 skills × 2 configurations), spawn a grader subagent.

**Step 1: For each run directory, spawn grader**

Follow the grader protocol from `/home/proofoftom/.claude/plugins/cache/claude-plugins-official/skill-creator/205b6e0b3036/skills/skill-creator/agents/grader.md`.

Prompt pattern:
```
Grade this eval run.

**Expectations:**
[list from eval_metadata.json]

**Transcript path:** {workspace}/iteration-1/{eval-name}/{config}/transcript.md
**Outputs dir:** {workspace}/iteration-1/{eval-name}/{config}/outputs/

Read the grader instructions at:
/home/proofoftom/.claude/plugins/cache/claude-plugins-official/skill-creator/205b6e0b3036/skills/skill-creator/agents/grader.md

Write grading.json to: {workspace}/iteration-1/{eval-name}/{config}/grading.json
```

Spawn all 8 graders in parallel (or in batches if too many).

---

## Task 12: Aggregate benchmarks and launch viewer

**Step 1: Run aggregate_benchmark.py for each skill**

```bash
SKILL_CREATOR="/home/proofoftom/.claude/plugins/cache/claude-plugins-official/skill-creator/205b6e0b3036/skills/skill-creator"

for skill in drupal-module-scaffold drupal-entities-fields drupal-caching drupal-testing; do
  python -m scripts.aggregate_benchmark \
    "${skill}-workspace/iteration-1" \
    --skill-name "$skill"
done
```

Run from `$SKILL_CREATOR` directory, or adjust PYTHONPATH.

**Step 2: Launch eval viewer for each skill**

```bash
for skill in drupal-module-scaffold drupal-entities-fields drupal-caching drupal-testing; do
  python "$SKILL_CREATOR/eval-viewer/generate_review.py" \
    "${skill}-workspace/iteration-1" \
    --skill-name "$skill" \
    --benchmark "${skill}-workspace/iteration-1/benchmark.json" \
    --static "${skill}-workspace/iteration-1/review.html"
done
```

Using `--static` to generate standalone HTML files since we're generating multiple viewers.

**Step 3: Present results to user**

Open each review.html and report:
- Pass rates: with-skill vs without-skill for each of the 4 skills
- Which assertions differentiated (passed with skill, failed without)
- Which assertions were non-discriminating (passed both ways)
- Notable findings from grader eval_feedback

**Step 4: Commit workspace results**

```bash
git add */iteration-1/
git commit -m "eval: benchmark results for 4 representative skills (iteration 1)"
```

---

## Task 13: Analyst pass and summary

**Step 1: Read all benchmark.json files**

Look for:
- Assertions that always pass regardless of skill (non-discriminating — consider removing)
- Assertions that always fail (possibly too strict or testing the wrong thing)
- High variance between with/without (the skill is making a real difference)
- Time/token tradeoffs (does the skill make agents slower?)

**Step 2: Write analysis summary**

Create `eval/analysis-iteration-1.md` summarizing findings across all 4 skills:
- Overall pass rate delta
- Most impactful skill (biggest improvement)
- Least impactful (smallest delta — may need skill revision)
- Recommendations: scale to all 13, iterate on specific skills, or adjust assertions

**Step 3: Commit**

```bash
git add eval/analysis-iteration-1.md
git commit -m "eval: analysis summary for iteration 1"
```

---

## Execution Notes

**Parallelism strategy:**
- Tasks 1-4 (create evals.json): Run sequentially (fast, just file creation)
- Task 5-6 (setup/teardown scripts): Run sequentially
- Tasks 7-10 (run evals): The 4 with-skill + 4 without-skill agents CAN run in parallel, but each needs its own ddev instance. To avoid overwhelming the machine, run 2 skills at a time (4 agents per batch):
  - Batch 1: scaffold (2 agents) + entities (2 agents)
  - Batch 2: caching (2 agents) + testing (2 agents)
- Task 11 (grading): All 8 graders can run in parallel (no ddev needed)
- Task 12-13 (aggregate + analyze): Sequential

**ddev resource management:** Each ddev instance uses ~512MB RAM + containers. With 4 running simultaneously, expect ~2-3GB overhead. The machine should handle this fine.

**Timeout considerations:** `scripts/install.sh --demo=cascadia` takes 3-5 minutes (composer install + site install + demo content + indexing). Set agent timeouts accordingly (10+ minutes per eval agent).

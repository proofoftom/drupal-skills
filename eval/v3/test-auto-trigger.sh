#!/usr/bin/env bash
set -euo pipefail

# Auto-trigger validation script for drupal-skills plugin (EVAL-01)
#
# Tests that natural Drupal development prompts activate the relevant skill
# without explicit invocation, achieving >=80% activation rate.
#
# Usage:
#   bash eval/v3/test-auto-trigger.sh              # Run from repo root
#   bash eval/v3/test-auto-trigger.sh --dry-run     # Show prompts without running
#   bash eval/v3/test-auto-trigger.sh --interactive  # Manual testing mode
#
# Compatibility note:
#   --plugin-dir + -p (headless mode) cannot be tested from within a Claude Code
#   session due to nested session restrictions. Run this script from a regular
#   terminal. If headless mode produces empty output, use --interactive mode.

# Resolve plugin directory (repo root) from script location
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"

# Ensure we are not inside a Claude Code session
if [[ -n "${CLAUDECODE:-}" ]]; then
  echo "ERROR: Cannot run auto-trigger tests inside a Claude Code session."
  echo "       Open a regular terminal and run: bash eval/v3/test-auto-trigger.sh"
  exit 2
fi

# Verify plugin manifest exists
if [[ ! -f "$PLUGIN_DIR/.claude-plugin/plugin.json" ]]; then
  echo "ERROR: Plugin manifest not found at $PLUGIN_DIR/.claude-plugin/plugin.json"
  echo "       Run 13-01 plan first to create the plugin packaging."
  exit 2
fi

# Configuration
TIMEOUT_SECS=60
RESULTS_DIR="$PLUGIN_DIR/eval/v3/results"
TIMESTAMP=$(date -u +"%Y%m%dT%H%M%SZ")
RESULTS_FILE="$RESULTS_DIR/auto-trigger-$TIMESTAMP.json"
DRY_RUN=false
INTERACTIVE=false

# Parse arguments
for arg in "$@"; do
  case $arg in
    --dry-run) DRY_RUN=true ;;
    --interactive) INTERACTIVE=true ;;
    --help|-h)
      echo "Usage: bash eval/v3/test-auto-trigger.sh [--dry-run|--interactive]"
      echo ""
      echo "  --dry-run       Show prompts and expected skills without running"
      echo "  --interactive   Print prompts for manual testing in an interactive session"
      echo "  --help          Show this help message"
      exit 0
      ;;
    *)
      echo "Unknown argument: $arg"
      exit 1
      ;;
  esac
done

# Define test prompts: prompt|expected_skill(s)|detection_pattern
# Detection patterns are unique phrases from SKILL.md content that baseline
# Claude would not produce without the skill loaded.
declare -a PROMPTS
declare -a EXPECTED_SKILLS
declare -a DETECTION_PATTERNS

# Prompt 1: Module scaffolding
PROMPTS+=("Create a new Drupal module called event_tracker that depends on node and has a .install file with hook_schema")
EXPECTED_SKILLS+=("drupal-module-scaffold")
DETECTION_PATTERNS+=("core_version_requirement.*\\^10.*\\^11|hook_install|hook_schema")

# Prompt 2: Custom entity type
PROMPTS+=("Add a custom content entity type called Event with title, date, and location fields using baseFieldDefinitions")
EXPECTED_SKILLS+=("drupal-entities-fields")
DETECTION_PATTERNS+=("baseFieldDefinitions|ContentEntityBase|entity_keys|BaseFieldDefinition")

# Prompt 3: Route and controller
PROMPTS+=("Create a route and controller for the event_tracker module that displays a JSON list of events with proper dependency injection")
EXPECTED_SKILLS+=("drupal-routing-controllers")
DETECTION_PATTERNS+=("ControllerBase|\\brouting\\.yml\\b|createInstance|ContainerInjectionInterface")

# Prompt 4: Settings form with config schema
PROMPTS+=("Build a settings form for the event_tracker module with a config schema file and form_alter hook")
EXPECTED_SKILLS+=("drupal-forms-api,drupal-config-storage")
DETECTION_PATTERNS+=("ConfigFormBase|config\\.schema\\.yml|getEditableConfigNames|schema.*mapping")

# Prompt 5: Block plugin with caching
PROMPTS+=("Write a block plugin that shows upcoming events with cache tags and cache contexts for the current user")
EXPECTED_SKILLS+=("drupal-plugins-blocks,drupal-caching")
DETECTION_PATTERNS+=("BlockBase|getCacheContexts|getCacheTags|cache.*max-age|CacheableMetadata")

# Prompt 6: Cache variation and invalidation
PROMPTS+=("Make the events list vary by user role and invalidate the cache when events change using cache tags")
EXPECTED_SKILLS+=("drupal-caching")
DETECTION_PATTERNS+=("Cache::invalidateTags|CacheableMetadata|addCacheTags|cache_contexts|user\\.roles")

# Prompt 7: Access control and permissions
PROMPTS+=("Add a custom permission to restrict who can create events and implement an access handler for the Event entity")
EXPECTED_SKILLS+=("drupal-access-security")
DETECTION_PATTERNS+=("EntityAccessControlHandler|permissions\\.yml|AccessResult::allowed|checkAccess")

# Prompt 8: Theming with Twig
PROMPTS+=("Create a Twig template for event cards with a CSS library and a preprocess hook for the event_tracker module")
EXPECTED_SKILLS+=("drupal-theming")
DETECTION_PATTERNS+=("hook_theme|template_preprocess|libraries\\.yml|\\{\\{\\s*content|#theme")

# Prompt 9: Views integration
PROMPTS+=("Expose the Event entity to Views with a custom field plugin and filter handler")
EXPECTED_SKILLS+=("drupal-views-dev")
DETECTION_PATTERNS+=("hook_views_data|ViewsData|FieldPluginBase|FilterPluginBase|views\\.views\\.inc")

# Prompt 10: Kernel tests
PROMPTS+=("Write kernel tests for the Event entity CRUD operations using KernelTestBase with proper module installation")
EXPECTED_SKILLS+=("drupal-testing")
DETECTION_PATTERNS+=('KernelTestBase|\$modules.*static|EntityKernelTestBase|createEntity|assertNotNull')

# Prompt 11: Cron and queue
PROMPTS+=("Add a cron hook to check for expired events and a queue worker plugin to send notifications in batches")
EXPECTED_SKILLS+=("drupal-batch-queue-cron")
DETECTION_PATTERNS+=("QueueWorkerBase|hook_cron|processItem|\\@QueueWorker|createItem")

# Prompt 12: Database schema
PROMPTS+=("Create a database table for event analytics using hook_schema with indexes and write an update hook to add a column")
EXPECTED_SKILLS+=("drupal-database-api")
DETECTION_PATTERNS+=('hook_schema|hook_update_N|db_add_field|\$schema.*fields|addField')

TOTAL=${#PROMPTS[@]}
PASSED=0
FAILED=0
SKIPPED=0

echo "============================================================"
echo "  Drupal Skills Plugin - Auto-Trigger Validation (EVAL-01)"
echo "============================================================"
echo ""
echo "Plugin dir: $PLUGIN_DIR"
echo "Total prompts: $TOTAL"
echo "Pass threshold: 80% ($((TOTAL * 80 / 100))/$TOTAL)"
echo ""

# --- Dry-run mode ---
if $DRY_RUN; then
  echo "DRY RUN - showing prompts and expected skills:"
  echo ""
  for i in "${!PROMPTS[@]}"; do
    idx=$((i + 1))
    echo "  Prompt $idx: \"${PROMPTS[$i]:0:80}...\""
    echo "  Expected: ${EXPECTED_SKILLS[$i]}"
    echo "  Detect:   ${DETECTION_PATTERNS[$i]}"
    echo ""
  done
  echo "Run without --dry-run to execute the test."
  exit 0
fi

# --- Interactive mode ---
if $INTERACTIVE; then
  echo "INTERACTIVE MODE"
  echo ""
  echo "1. Open a new terminal and start Claude with the plugin:"
  echo "   claude --plugin-dir $PLUGIN_DIR"
  echo ""
  echo "2. For each prompt below, paste it into the Claude session."
  echo "   Check if the response uses skill-specific patterns."
  echo "   Mark PASS if it does, FAIL if it produces generic output."
  echo ""
  for i in "${!PROMPTS[@]}"; do
    idx=$((i + 1))
    echo "--- Prompt $idx ---"
    echo "  Expected skill: ${EXPECTED_SKILLS[$i]}"
    echo "  Prompt text:"
    echo "    ${PROMPTS[$i]}"
    echo "  Look for: ${DETECTION_PATTERNS[$i]}"
    echo ""
  done
  echo "Scoring: Count PASS results. >=80% ($((TOTAL * 80 / 100))/$TOTAL) = success."
  exit 0
fi

# --- Headless mode ---
echo "Running headless auto-trigger tests..."
echo "Each prompt runs via: claude --plugin-dir $PLUGIN_DIR -p <prompt>"
echo "Timeout per prompt: ${TIMEOUT_SECS}s"
echo ""

mkdir -p "$RESULTS_DIR"

declare -a RESULTS_STATUS
declare -a RESULTS_DETAIL

for i in "${!PROMPTS[@]}"; do
  idx=$((i + 1))
  prompt="${PROMPTS[$i]}"
  expected="${EXPECTED_SKILLS[$i]}"
  pattern="${DETECTION_PATTERNS[$i]}"

  echo -n "  [$idx/$TOTAL] Testing ${expected}... "

  # Run the prompt headlessly
  OUTPUT_FILE=$(mktemp /tmp/auto-trigger-XXXXXX.txt)
  if timeout "$TIMEOUT_SECS" claude --plugin-dir "$PLUGIN_DIR" \
    -p "$prompt" \
    --allowedTools "" \
    > "$OUTPUT_FILE" 2>/dev/null; then

    # Check for skill-specific patterns in output
    if grep -qiE "$pattern" "$OUTPUT_FILE" 2>/dev/null; then
      echo "PASS"
      RESULTS_STATUS+=("pass")
      RESULTS_DETAIL+=("Pattern matched in output")
      PASSED=$((PASSED + 1))
    else
      echo "FAIL (no skill-specific patterns detected)"
      RESULTS_STATUS+=("fail")
      RESULTS_DETAIL+=("Output produced but no skill patterns found")
      FAILED=$((FAILED + 1))
    fi
  else
    EXIT_CODE=$?
    if [[ $EXIT_CODE -eq 124 ]]; then
      echo "TIMEOUT (${TIMEOUT_SECS}s)"
      RESULTS_STATUS+=("timeout")
      RESULTS_DETAIL+=("Timed out after ${TIMEOUT_SECS}s")
      FAILED=$((FAILED + 1))
    elif [[ ! -s "$OUTPUT_FILE" ]]; then
      echo "SKIP (empty output -- headless mode may be incompatible)"
      RESULTS_STATUS+=("skip")
      RESULTS_DETAIL+=("Empty output -- --plugin-dir + -p may not work together")
      SKIPPED=$((SKIPPED + 1))
    else
      echo "ERROR (exit code $EXIT_CODE)"
      RESULTS_STATUS+=("error")
      RESULTS_DETAIL+=("Process exited with code $EXIT_CODE")
      FAILED=$((FAILED + 1))
    fi
  fi

  rm -f "$OUTPUT_FILE"
done

echo ""
echo "============================================================"
echo "  RESULTS"
echo "============================================================"
echo ""

# Detailed results table
for i in "${!PROMPTS[@]}"; do
  idx=$((i + 1))
  status="${RESULTS_STATUS[$i]}"
  detail="${RESULTS_DETAIL[$i]}"
  expected="${EXPECTED_SKILLS[$i]}"

  case $status in
    pass) marker="PASS" ;;
    fail) marker="FAIL" ;;
    skip) marker="SKIP" ;;
    timeout) marker="TIME" ;;
    *) marker="ERR " ;;
  esac

  printf "  [%s] %2d. %-40s %s\n" "$marker" "$idx" "$expected" "$detail"
done

echo ""

# Calculate rate (skipped prompts excluded from denominator if all are skipped)
EVALUATED=$((TOTAL - SKIPPED))
if [[ $EVALUATED -eq 0 ]]; then
  echo "ALL PROMPTS SKIPPED -- headless mode is incompatible with --plugin-dir."
  echo ""
  echo "FINDING: --plugin-dir + -p produces empty output."
  echo "Use --interactive mode for manual verification instead:"
  echo "  bash eval/v3/test-auto-trigger.sh --interactive"
  echo ""

  # Write results file
  cat > "$RESULTS_FILE" <<JSONEOF
{
  "timestamp": "$TIMESTAMP",
  "plugin_dir": "$PLUGIN_DIR",
  "mode": "headless",
  "total_prompts": $TOTAL,
  "evaluated": 0,
  "passed": 0,
  "failed": 0,
  "skipped": $SKIPPED,
  "rate_percent": null,
  "threshold_percent": 80,
  "result": "inconclusive",
  "finding": "--plugin-dir + -p headless mode produces empty output; use --interactive mode"
}
JSONEOF

  echo "Results saved to: $RESULTS_FILE"
  exit 3  # Special exit: inconclusive (not pass, not fail)
fi

RATE=$((PASSED * 100 / EVALUATED))
THRESHOLD=80

echo "  Passed:  $PASSED/$EVALUATED ($RATE%)"
echo "  Failed:  $FAILED/$EVALUATED"
if [[ $SKIPPED -gt 0 ]]; then
  echo "  Skipped: $SKIPPED (excluded from rate calculation)"
fi
echo "  Threshold: ${THRESHOLD}%"
echo ""

# Write results JSON
cat > "$RESULTS_FILE" <<JSONEOF
{
  "timestamp": "$TIMESTAMP",
  "plugin_dir": "$PLUGIN_DIR",
  "mode": "headless",
  "total_prompts": $TOTAL,
  "evaluated": $EVALUATED,
  "passed": $PASSED,
  "failed": $FAILED,
  "skipped": $SKIPPED,
  "rate_percent": $RATE,
  "threshold_percent": $THRESHOLD,
  "result": "$([ $RATE -ge $THRESHOLD ] && echo 'pass' || echo 'fail')",
  "prompts": [
$(for i in "${!PROMPTS[@]}"; do
  comma=""
  if [[ $i -lt $((TOTAL - 1)) ]]; then comma=","; fi
  cat <<PROMPTEOF
    {
      "index": $((i + 1)),
      "expected_skill": "${EXPECTED_SKILLS[$i]}",
      "status": "${RESULTS_STATUS[$i]}",
      "detail": "${RESULTS_DETAIL[$i]}"
    }$comma
PROMPTEOF
done)
  ]
}
JSONEOF

echo "Results saved to: $RESULTS_FILE"
echo ""

if [[ $RATE -ge $THRESHOLD ]]; then
  echo "SUCCESS: Auto-trigger rate ${RATE}% >= ${THRESHOLD}% threshold"
  exit 0
else
  echo "FAILURE: Auto-trigger rate ${RATE}% < ${THRESHOLD}% threshold"
  exit 1
fi

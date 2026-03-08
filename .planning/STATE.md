---
gsd_state_version: 1.0
milestone: v3.0
milestone_name: Group AI Project Management
status: in_progress
stopped_at: Phase 14 eval complete — module promoted, skills patched
last_updated: "2026-03-08T12:00:00.000Z"
last_activity: 2026-03-08 -- Phase 14 eval pipeline complete (+25% delta after skill iteration)
progress:
  total_phases: 5
  completed_phases: 2
  total_plans: 2
  completed_plans: 2
  percent: 40
---

# Project State

## Project Reference

See: .planning/PROJECT.md (updated 2026-03-08)

**Core value:** Claude can generate correct, production-ready Drupal module code across all major development domains when guided by these skills.
**Current focus:** Phase 14 - Module Foundation (eval-driven, see workflow note below)

## Current Position

Phase: 14 of 17 (Module Foundation) -- COMPLETE
Plan: eval pipeline (not GSD plans)
Status: Phase 14 complete — module promoted to modules/group_ai_pm/
Last activity: 2026-03-08 -- Reverted contaminated GSD executor run of Phase 14 (HEAD at dbe9ff6)

Progress: [██░░░░░░░░] 20% (v3.0)

## WORKFLOW CHANGE: Eval-Driven Phases (Phase 14+)

**DO NOT use /gsd:plan-phase or /gsd:execute-phase for module building.**
Phase 14+ uses the eval pipeline, not GSD executors. GSD executor agents have full context + all skills = not a valid A/B comparison. The module code must come from controlled headless `claude -p` runs.

**Correct workflow for Phase 14:**
1. Write evals.json with task prompt + expectations (target non-obvious skill patterns)
2. Run parallel headless `claude -p` from orchestrator Bash tool (unset CLAUDECODE first):
   - WITHOUT plugin: `claude -p --model claude-haiku-4-5-20251001` (no --plugin-dir)
   - WITH plugin: `claude -p --model claude-haiku-4-5-20251001 --plugin-dir ./`
3. Grade both outputs with eval-grader agent (sonnet)
4. Generate benchmark.json, use skill-creator eval-viewer for review
5. Promote with-plugin output to `modules/group_ai_pm/`
6. Iterate on skills if delta insufficient

**Skills tested in Phase 14:** scaffold, entities-fields, routing-controllers, forms-api, config-storage
**Reference:** MEMORY.md has full phase-to-skill mapping and pipeline details
**Reference:** /skill-creator protocol for eval viewer, grading schemas, benchmark format

## Accumulated Context

### Decisions

Decisions are logged in PROJECT.md Key Decisions table.
Recent decisions affecting current work:

- [v3.0 roadmap]: Coarse granularity -- 5 phases (13-17), 45 requirements compressed into natural delivery boundaries
- [v3.0 roadmap]: Plugin packaging gated first -- all eval depends on auto-trigger working
- [v3.0 roadmap]: Group + AI combined in one phase (15) since AI agents need group context
- [v3.0 roadmap]: Views, theming, caching, and background processing combined in Phase 16
- [13-01]: No custom component paths in plugin.json -- Claude Code auto-discovers skills/ at plugin root
- [13-01]: CLAUDE.md limited to 4 rules -- per Gloaguen 2026, LLM-generated boilerplate hurts performance
- [13-02]: --plugin-dir + -p headless mode confirmed compatible -- enables fully automated v3.0 eval pipeline
- [13-02]: Pattern-based skill detection: grep for SKILL.md-specific patterns in headless output to verify activation
- [13-02]: 5 script fixes needed for real-world headless execution (CLAUDECODE unset, no --allowedTools, 2>&1 capture, 120s timeout, explicit model)

### Pending Todos

- entities-fields bundle_of gap: SKILL.md needs explicit coverage

### Blockers/Concerns

- [13-02 RESOLVED]: `--plugin-dir` + `-p` headless mode confirmed compatible -- 100% activation rate achieved
- [Research]: Group 3.x relation plugin API under-documented -- Phase 15 may need research-phase
- [Research]: AiFunctionCall plugin contract marked WIP -- Phase 15 needs source code validation

## Session Continuity

Last session: 2026-03-08
Stopped at: Phase 14 COMPLETE — module promoted, skills patched, ready for Phase 15
Resume action: Phase 15 (Group + AI integration) — write evals.json, run eval pipeline
Resume file: None

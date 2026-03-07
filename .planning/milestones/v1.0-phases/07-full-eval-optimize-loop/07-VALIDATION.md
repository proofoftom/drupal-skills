---
phase: 7
slug: full-eval-optimize-loop
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-06
---

# Phase 7 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Manual verification + skill-creator grading pipeline |
| **Config file** | N/A (grading is evidence-based, aggregation is Python script) |
| **Quick run command** | `python aggregate_benchmark.py {workspace}/iteration-1 --skill-name {name}` |
| **Full suite command** | Run all 13 skills through eval pipeline + aggregate all benchmarks |
| **Estimated runtime** | ~30 minutes per skill pair (setup + 2 headless runs + teardown + grading) |

---

## Sampling Rate

- **After every evals.json creation:** Validate JSON with `python3 -m json.tool`
- **After every eval run:** Verify transcript.md and outputs/ directory exist
- **After every grading:** Validate grading.json has all expectations with evidence
- **After every batch:** Run `aggregate_benchmark.py`, check delta values
- **Before `/gsd:verify-work`:** All 13 skills have benchmark.json with analyzed deltas

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| SC-1 | 01 | 1 | All 13 evals.json | file check | `for s in skills/drupal-*/evals/evals.json; do python3 -m json.tool "$s" > /dev/null && echo "OK: $s"; done` | 4/13 | ⬜ pending |
| SC-2 | 01 | 1 | CLAUDECODE fix baked in | grep | `grep -q 'CLAUDECODE' eval/setup-drupal-env.sh` | ❌ | ⬜ pending |
| SC-3 | 02+ | 2 | 1-agent-per-skill | manual | Review execution plan structure | N/A | ⬜ pending |
| SC-4 | 02+ | 2 | All 13 benchmarks | file check | `ls drupal-*-workspace/iteration-1/benchmark.json \| wc -l` | 4/13 | ⬜ pending |
| SC-5 | 03 | 3 | Weak-delta iteration | manual | Review analysis for iteration notes | N/A | ⬜ pending |
| SC-6 | 03 | 3 | Final analysis | file check | `grep -c 'drupal-' eval/analysis-iteration-*.md` | Partial | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `skills/drupal-routing-controllers/evals/evals.json` — needs creation
- [ ] `skills/drupal-forms-api/evals/evals.json` — needs creation
- [ ] `skills/drupal-plugins-blocks/evals/evals.json` — needs creation
- [ ] `skills/drupal-config-storage/evals/evals.json` — needs creation
- [ ] `skills/drupal-access-security/evals/evals.json` — needs creation
- [ ] `skills/drupal-theming/evals/evals.json` — needs creation
- [ ] `skills/drupal-database-api/evals/evals.json` — needs creation
- [ ] `skills/drupal-views-dev/evals/evals.json` — needs creation
- [ ] `skills/drupal-batch-queue-cron/evals/evals.json` — needs creation

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| 1-agent-per-skill parallelization | SC-3 | Execution pattern, not code | Review plan structure confirms 1 agent per skill |
| Weak-delta skills iterated | SC-5 | Requires human judgment on delta improvement | Review analysis notes for evidence of iteration |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 60s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

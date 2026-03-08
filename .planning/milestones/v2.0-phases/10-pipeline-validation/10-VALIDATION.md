---
phase: 10
slug: pipeline-validation
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-06
---

# Phase 10 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Shell-based validation (jq + bash assertions) |
| **Config file** | none — validation is inline during pipeline execution |
| **Quick run command** | `jq '.summary.pass_rate' <path>/grading.json` |
| **Full suite command** | Validate both grading.json files + benchmark.json per skill |
| **Estimated runtime** | ~300 seconds per skill (setup + execute + grade + teardown) |

---

## Sampling Rate

- **After every task commit:** Run `jq '.summary.pass_rate' <path>/grading.json`
- **After every plan wave:** Validate benchmark.json and delta thresholds for completed skills
- **Before `/gsd:verify-work`:** Both calibration skills have benchmark.json with deltas exceeding thresholds
- **Max feedback latency:** 30 seconds (jq validation is instant; pipeline stages are longer)

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 10-01-01 | 01 | 1 | PIPE-01 | smoke | `jq 'has("expectations") and has("summary")' grading.json` | Wave 0 | pending |
| 10-01-02 | 01 | 1 | PIPE-01 | integration | `jq '.run_summary.delta.pass_rate' benchmark.json` | Wave 0 | pending |
| 10-01-03 | 01 | 1 | PIPE-01 | integration | `jq '.run_summary.delta.pass_rate' benchmark.json` | Wave 0 | pending |
| 10-01-04 | 01 | 1 | PIPE-01 | smoke | Full pipeline completes without manual intervention | Wave 0 | pending |

*Status: pending / green / red / flaky*

---

## Wave 0 Requirements

- [ ] Workspace directories created before first run (`mkdir -p` full tree)
- [ ] eval_metadata.json template populated per skill
- [ ] grading.json schema validation command prepared
- [ ] benchmark.json schema validation command prepared

*Existing infrastructure covers environment setup/teardown and subagent definitions.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Pipeline runs without user prompts | PIPE-01 | Requires observing full pipeline execution | Watch orchestration for any user prompts or hangs |
| Delta magnitudes are reasonable | PIPE-01 | Requires human judgment on thresholds | Compare deltas to v1.0 baselines; flag if outside expected range |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 30s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

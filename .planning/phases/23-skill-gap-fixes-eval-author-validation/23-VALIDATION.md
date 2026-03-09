---
phase: 23
slug: skill-gap-fixes-eval-author-validation
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-09
---

# Phase 23 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Manual review + eval pipeline |
| **Config file** | eval/v4/phase-18-evals.json (gold-standard reference) |
| **Quick run command** | `wc -l skills/drupal-*/SKILL.md` |
| **Full suite command** | Invoke eval-author with Phase 18 inputs, compare against gold-standard |
| **Estimated runtime** | ~60 seconds (line counts) / ~5 min (eval-author validation) |

---

## Sampling Rate

- **After every task commit:** Run `wc -l skills/drupal-*/SKILL.md` (verify no skill exceeds 500 lines)
- **After every plan wave:** Eval-author validation pass/fail against gold-standard
- **Before `/gsd:verify-work`:** All three skill gaps closed + eval-author validation passed
- **Max feedback latency:** 60 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 23-01-01 | 01 | 1 | TOOL-06 | static | `grep -c 'bundled-entities' skills/drupal-entities-fields/SKILL.md` | ❌ W0 | ⬜ pending |
| 23-01-02 | 01 | 1 | TOOL-06 | static | `wc -l skills/drupal-entities-fields/references/bundled-entities.md` | ❌ W0 | ⬜ pending |
| 23-01-03 | 01 | 1 | TOOL-07 | static | `grep -c 'addCacheableDependency' skills/drupal-caching/SKILL.md` | ✅ partial | ⬜ pending |
| 23-01-04 | 01 | 1 | TOOL-07 | static | `grep -c '#ajax' skills/drupal-forms-api/SKILL.md` | ❌ W0 | ⬜ pending |
| 23-02-01 | 02 | 2 | TOOL-05 | manual | N/A - qualitative review of eval-author output | N/A | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `skills/drupal-entities-fields/references/bundled-entities.md` — bundle_of + hook_update_N content
- [ ] CacheableMetadata bubbling section in `skills/drupal-caching/SKILL.md`
- [ ] `#ajax` section in `skills/drupal-forms-api/SKILL.md`
- [ ] Eval-author validation output directory (temporary, for comparison)

*These are the files being CREATED by this phase, not pre-existing test infrastructure.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Eval-author assertion quality matches Phase 18 gold-standard | TOOL-05 | Qualitative comparison of assertion patterns, not text equality | Invoke eval-author with Phase 18 inputs, compare against eval/v4/phase-18-evals.json: check count (12-22), distribution (60/20/20), 4/5 core differentiators covered, rationales present, no tautologies |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 60s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

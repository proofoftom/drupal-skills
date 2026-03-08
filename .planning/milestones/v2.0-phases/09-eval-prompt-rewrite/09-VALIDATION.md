---
phase: 9
slug: eval-prompt-rewrite
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-07
---

# Phase 9 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | bash + jq (JSON schema validation on evals.json files) |
| **Config file** | none — inline validation commands |
| **Quick run command** | `for f in skills/drupal-*/evals/evals.json; do jq -e '.prompt' "$f" > /dev/null && echo "OK: $f"; done` |
| **Full suite command** | `for f in skills/drupal-*/evals/evals.json; do jq -e '.prompt' "$f" > /dev/null && ! grep -qi 'os-kg\|os-knowledge-garden\|open.social' "$f" && echo "PASS: $f" || echo "FAIL: $f"; done` |
| **Estimated runtime** | ~2 seconds |

---

## Sampling Rate

- **After every task commit:** Run quick validation on modified evals.json files
- **After every plan wave:** Run full suite (all 13 evals.json)
- **Before `/gsd:verify-work`:** Full suite must show 0 os-kg references
- **Max feedback latency:** 2 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 09-01-01 | 01 | 1 | EVAL-01 | schema | `jq -e '.prompt' skills/drupal-*/evals/evals.json` | ✅ | ⬜ pending |
| 09-01-02 | 01 | 1 | EVAL-02 | grep | `! grep -rqi 'os-kg\|open.social' skills/drupal-*/evals/evals.json` | ✅ | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

*Existing infrastructure covers all phase requirements. evals.json files already exist for all 13 skills.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Prompts don't leak hints | EVAL-02 | Semantic judgment | Read each prompt, verify no implementation hints that teach without-skill agent |
| Prompts are realistic tasks | EVAL-01 | Quality judgment | Verify prompts describe plausible Drupal module development tasks |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 2s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

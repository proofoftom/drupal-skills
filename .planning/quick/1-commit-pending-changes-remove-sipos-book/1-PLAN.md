---
phase: quick
plan: 1
type: execute
wave: 1
depends_on: []
files_modified:
  - skills/drupal-routing-controllers/SKILL.md
  - .gitignore
  - .planning/config.json
  - eval/v3/phase-16-evals.json
  - eval/v3/phase-16-runtime-assertions.json
  - eval/v4/phase-18-evals.json
  - eval/v4/phase-18-results-without.json
  - eval/v4/phase-18-with-results.json
  - eval/v4/phase-18-with-v2-results.json
  - eval/v4/phase-19-evals.json
  - eval/v4/phase-19-results-without.json
  - eval/v4/phase-19-with-results.json
  - eval/v4/phase-20-evals.json
  - eval/v4/phase-20-results-with.json
  - eval/v4/phase-20-results-without.json
  - modules/group_ai_pm/css/dashboard.css
  - modules/group_ai_pm/js/**
  - modules/group_ai_pm/src/Controller/ProjectApiController.php
  - modules/group_ai_pm/src/Form/TaskStatusForm.php
  - modules/group_ai_pm/templates/group-ai-pm-dashboard.html.twig
  - modules/group_ai_pm/templates/group-ai-pm-kanban.html.twig
autonomous: true
requirements: []
must_haves:
  truths:
    - "No Sipos book references remain in any skill file"
    - "All pending work (staged, modified, untracked) is committed"
    - "Public GitHub repo exists at proofoftom/drupal-skills"
    - "All commits are pushed to remote"
  artifacts:
    - path: "skills/drupal-routing-controllers/SKILL.md"
      provides: "DI guidance without external book attribution"
      contains: "acceptable in .module files"
  key_links:
    - from: "local repo"
      to: "github.com/proofoftom/drupal-skills"
      via: "git remote origin"
      pattern: "github.com.*proofoftom/drupal-skills"
---

<objective>
Commit all pending changes, remove the Sipos book reference from skills, create a public GitHub repo, and push.

Purpose: Prepare the repo for public release by cleaning up attribution to copyrighted material and committing all accumulated v4.0/v5.0 work.
Output: Clean repo pushed to public GitHub remote.
</objective>

<execution_context>
@/home/proofoftom/.claude/get-shit-done/workflows/execute-plan.md
@/home/proofoftom/.claude/get-shit-done/templates/summary.md
</execution_context>

<context>
@skills/drupal-routing-controllers/SKILL.md (line 350 — Sipos reference to remove)
</context>

<tasks>

<task type="auto">
  <name>Task 1: Remove Sipos book reference and commit all pending changes</name>
  <files>skills/drupal-routing-controllers/SKILL.md, .gitignore, .planning/config.json, eval/v3/*, eval/v4/*, modules/group_ai_pm/**</files>
  <action>
1. Edit `skills/drupal-routing-controllers/SKILL.md` line 350: replace
   `(Sipos, Ch. 2)` with `(these are procedural contexts where the service container is not available)`.
   The full line should read:
   `> Static \Drupal:: calls are acceptable in .module files and procedural hooks where constructor injection is unavailable (these are procedural contexts where the service container is not available).`

2. Stage and commit in logical groups:
   a. First commit — the Sipos cleanup:
      - `git add skills/drupal-routing-controllers/SKILL.md`
      - Commit: `fix: remove copyrighted book reference from routing-controllers skill`

   b. Second commit — all remaining pending work:
      - `git add .gitignore .planning/config.json`
      - `git add eval/v3/ eval/v4/`
      - `git add modules/group_ai_pm/css/ modules/group_ai_pm/js/ modules/group_ai_pm/src/Controller/ProjectApiController.php modules/group_ai_pm/src/Form/TaskStatusForm.php modules/group_ai_pm/templates/`
      - Commit: `feat: add v3/v4 eval results, Vue kanban board, and module enhancements`

3. Verify no Sipos references remain: `grep -r "Sipos" skills/`
4. Verify working tree is clean: `git status`
  </action>
  <verify>
    <automated>cd /home/proofoftom/Code/drupal-skills && grep -r "Sipos" skills/ ; echo "exit: $?" && git status --porcelain | head -5</automated>
  </verify>
  <done>No Sipos references in skills/. Working tree is clean (all changes committed).</done>
</task>

<task type="auto">
  <name>Task 2: Create public GitHub repo and push</name>
  <files></files>
  <action>
1. Create a public GitHub repository:
   `gh repo create drupal-skills --public --source=. --description "Claude Code skills for Drupal module development"`

2. Push all branches:
   `git push -u origin main`

3. Verify the remote is set and push succeeded:
   `git remote -v`
   `gh repo view proofoftom/drupal-skills --json url,visibility`
  </action>
  <verify>
    <automated>cd /home/proofoftom/Code/drupal-skills && gh repo view --json url,visibility --jq '.url + " " + .visibility'</automated>
  </verify>
  <done>Public repo exists at github.com/proofoftom/drupal-skills, all commits pushed, remote origin configured.</done>
</task>

</tasks>

<verification>
- `grep -r "Sipos" skills/` returns no matches
- `git status` shows clean working tree
- `gh repo view proofoftom/drupal-skills --json visibility` shows "PUBLIC"
- `git log --oneline -5` shows both new commits
</verification>

<success_criteria>
1. Zero Sipos/book references in any skill file
2. All 36+ previously untracked files are committed
3. Public GitHub repo at proofoftom/drupal-skills with all commits pushed
</success_criteria>

<output>
After completion, create `.planning/quick/1-commit-pending-changes-remove-sipos-book/1-SUMMARY.md`
</output>

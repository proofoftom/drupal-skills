# Contributing to drupal-skills

Workflow for iterating on the plugins in this marketplace.

## Single source of truth

Edit skills only in `plugins/<plugin>/skills/<skill>/SKILL.md`. Never edit installed copies in `~/.claude/skills/` or `~/.claude/plugins/cache/` — those get overwritten on `/plugin marketplace update`.

## Local dev loop

Iterate on a feature branch, install from the branch ref to test:

```bash
git checkout -b improve-<skill>
# edit plugins/<plugin>/skills/<skill>/SKILL.md
git push -u origin improve-<skill>
```

In a Claude Code session in any project:

```
/plugin marketplace add proofoftom/drupal-skills@improve-<skill>
/plugin install drupal-skills@drupal-skills
/reload-plugins
```

Trigger the skill in a representative prompt, observe whether auto-trigger fires and whether the output matches the patterns. Iterate the skill description and body until both behave correctly.

## Eval before publishing

For non-trivial changes, run the eval suite for the affected skill (`eval/`) before opening a PR. Auto-trigger and output quality regress silently otherwise — evals are the only signal.

## Versioning + release

Each plugin versions independently:

1. Bump `plugins/<plugin>/.claude-plugin/plugin.json` `version`
2. Bump the matching entry in `.claude-plugin/marketplace.json`
3. PR -> merge to `main`
4. `git tag <plugin>-vX.Y.Z && git push --tags`
5. Optional: GitHub release with notes

## Sources and attribution

Skills are distilled from Drupal core documentation, publicly available community knowledge, and refined through eval-driven iteration. The eval rounds are where skill quality actually comes from -- a pattern from any single source is just a starting point.

Do not name specific copyrighted books, courses, or paid resources in published skill content, README, or marketplace descriptions. The skills describe Drupal APIs (factual, public information); naming a paid source in our marketing copy implies a derivative-work relationship we don't have permission to claim. Use neutral framing: "Drupal community publications," "test-first patterns established by the Drupal TDD community," etc.

When a skill genuinely originates from a specific public, free resource (e.g. a drupal.org documentation page), attribute it inline near the relevant pattern with a link -- this is "further reading," not "this is derived from."

## Capturing ideas

File a GitHub issue immediately when you spot a candidate skill, gap, or refinement during real project work. Don't trust "I'll remember" -- a fresh session reads the issue cold, no context needed.

The distillation pipeline this repo uses:
- **Documentation distillation**: skills derived from publicly available Drupal core docs and community resources
- **Project observation**: when a homunculus-evolved project skill contains a universal subset, file an issue to lift the universal portion into the published plugin (see issue #2 for an example)

Don't republish project-specific noise; do mine project work for generalizable rules.

## Typical full iteration

```
1. Notice a Drupal pattern Claude keeps getting wrong
2. Open an issue on this repo
3. Branch, edit SKILL.md, push
4. /plugin marketplace add proofoftom/drupal-skills@<branch>
5. Trigger the relevant prompt, observe behavior
6. Iterate description / body
7. Run eval/ suite for the affected skill
8. PR -> merge -> bump plugin.json + marketplace.json -> tag
9. Close issue with link to release
```

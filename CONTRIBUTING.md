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

## How skills are built

Each skill goes through the same pipeline: draft from publicly available Drupal documentation and community knowledge, then refine through eval rounds against representative prompts until auto-trigger and output quality both clear the bar. The eval rounds are where the actual quality comes from -- a draft is just a starting point.

Sources we draw on regularly:
- [drupal.org API documentation](https://api.drupal.org/api/drupal/) -- the canonical reference
- [drupal.org Documentation](https://www.drupal.org/docs) -- handbook-style guides
- Public Drupal community blogs, talks, and screencasts

If a skill captures a specific public Drupal pattern that has a clear canonical write-up online, link it inline as "further reading" near the relevant pattern.

## Capturing ideas

File a GitHub issue immediately when you spot a candidate skill, gap, or refinement during real project work. Don't trust "I'll remember" -- a fresh session reads the issue cold, no context needed.

Two ways skill candidates surface:
- **Documentation distillation**: pattern that's clearly documented but Claude keeps getting wrong without the skill loaded
- **Project observation**: a project-scoped skill (or a homunculus-evolved one) contains a universal Drupal subset worth lifting into the public plugin -- see issue #2 for an example

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

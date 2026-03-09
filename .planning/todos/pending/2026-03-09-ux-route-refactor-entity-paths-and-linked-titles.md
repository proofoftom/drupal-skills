---
created: 2026-03-09T14:10:43.420Z
title: UX route refactor - entity paths and linked titles
area: ui
files:
  - modules/group_ai_pm/src/Entity/Project.php
  - modules/group_ai_pm/src/Entity/Task.php
  - modules/group_ai_pm/group_ai_pm.routing.yml
  - modules/group_ai_pm/group_ai_pm.links.menu.yml
  - modules/group_ai_pm/src/ProjectListBuilder.php
  - modules/group_ai_pm/src/TaskListBuilder.php
---

## Problem

Entity routes are under `/admin/content/project/{id}` and `/admin/content/task/{id}` but they aren't local tabs on the Content page, making the URL structure misleading. The dashboard is buried under `/admin/content/project-dashboard` instead of having its own top-level admin presence. List builders render entity titles as plain text instead of linking to the canonical view page.

Three issues:
1. **Entity paths**: Project and Task link templates use `AdminHtmlRouteProvider` under `/admin/content/` which is superfluous — should be `/project/{id}` and `/task/{id}`
2. **Projects top-level admin**: Dashboard should be at `/admin/projects` with its own menu item, not a child of `system.admin_content`
3. **Linked titles in list builders**: `ProjectListBuilder::buildRow()` and `TaskListBuilder::buildRow()` use `$entity->getTitle()` as plain text — should use `$entity->toLink()` or `Link::fromTextAndUrl()` to link to the canonical page

## Solution

- Update entity annotations in Project.php and Task.php to change link templates (canonical, add-form, edit-form, delete-form, collection)
- Switch from `AdminHtmlRouteProvider` to `DefaultHtmlRouteProvider` (or keep Admin but update paths)
- Update `group_ai_pm.routing.yml` — move dashboard route to `/admin/projects`
- Update `group_ai_pm.links.menu.yml` — make Projects a top-level admin menu item instead of child of `system.admin_content`
- Update list builders to render titles as links to canonical entity pages
- Update any hardcoded path references in controllers, templates, or JS

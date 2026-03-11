import { createApp } from 'vue';
import KanbanBoard from './components/KanbanBoard.vue';

/**
 * Initialize Kanban board with Drupal.behaviors.
 * Uses Drupal.once() to prevent double-mounting during behavior re-attachment.
 */
Drupal.behaviors.groupAiPmKanban = {
  attach(context, settings) {
    // Get the mount point
    const mountPoint = context.querySelector('#kanban-app');
    if (!mountPoint) {
      return;
    }

    // Use Drupal.once() to ensure this only runs once per element
    once('kanban-board', '#kanban-app', context).forEach(() => {
      const app = createApp(KanbanBoard, {
        settings: settings.groupAiPm.kanban,
      });

      app.mount('#kanban-app');
    });
  },
};

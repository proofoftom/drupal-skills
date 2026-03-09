import { ref, computed } from 'vue';
import { fetchKanban, patchKanban, postKanban, deleteKanban } from '../api/drupal.js';
import { useToast } from './useToast.js';

/**
 * Composable for managing Kanban board state and API interactions.
 *
 * @param {Object} settings
 *   The drupalSettings.groupAiPm.kanban configuration.
 *
 * @returns {Object}
 *   Board state and action methods.
 */
export function useKanban(settings) {
  const columns = ref(settings.tasks || {
    todo: [],
    in_progress: [],
    review: [],
    done: [],
  });
  const error = ref(null);
  const loading = ref(false);
  const { addToast } = useToast();

  /**
   * Updates task status via API, with optimistic update and rollback.
   */
  const updateTaskStatus = async (taskId, newStatus) => {
    // Find the task for optimistic update.
    let taskData = null;
    let oldStatus = null;
    let oldColumnKey = null;

    for (const [columnKey, tasks] of Object.entries(columns.value)) {
      const index = tasks.findIndex((t) => t.id === taskId);
      if (index !== -1) {
        taskData = tasks[index];
        oldStatus = columnKey;
        oldColumnKey = columnKey;
        break;
      }
    }

    if (!taskData) {
      error.value = 'Task not found';
      return;
    }

    // Optimistic update: remove from old column, add to new column.
    columns.value[oldColumnKey] = columns.value[oldColumnKey].filter(
      (t) => t.id !== taskId
    );
    taskData.status = newStatus;
    columns.value[newStatus].push(taskData);

    try {
      // Update via API.
      const url = `/api/kanban/task/${taskId}/status`;
      await patchKanban(url, { status: newStatus });
      error.value = null;
      addToast('Task status updated', 'success');
    } catch (err) {
      // Rollback on error.
      columns.value[newStatus] = columns.value[newStatus].filter(
        (t) => t.id !== taskId
      );
      columns.value[oldColumnKey].push(taskData);
      taskData.status = oldStatus;
      error.value = err.message;
      addToast(err.message, 'error');
    }
  };

  /**
   * Updates task fields (title, priority, assignee).
   */
  const updateTask = async (taskId, updates) => {
    // Find the task for optimistic update.
    let taskData = null;
    const oldData = {};

    for (const tasks of Object.values(columns.value)) {
      const found = tasks.find((t) => t.id === taskId);
      if (found) {
        taskData = found;
        break;
      }
    }

    if (!taskData) {
      error.value = 'Task not found';
      return;
    }

    // Store old values for rollback.
    Object.assign(oldData, taskData);

    // Optimistic update.
    Object.assign(taskData, updates);

    try {
      const url = `/api/kanban/task/${taskId}`;
      const result = await patchKanban(url, updates);
      Object.assign(taskData, result);
      error.value = null;
      addToast('Task updated', 'success');
    } catch (err) {
      // Rollback on error.
      Object.assign(taskData, oldData);
      error.value = err.message;
      addToast(err.message, 'error');
    }
  };

  /**
   * Creates a new task.
   */
  const createTask = async (title, status = 'todo') => {
    if (!title.trim()) {
      error.value = 'Title cannot be empty';
      addToast('Title cannot be empty', 'error');
      return null;
    }

    try {
      const url = `/api/kanban/project/${settings.projectId}/task`;
      const newTask = await postKanban(url, { title: title.trim(), status });
      const targetColumn = newTask.status || status;
      columns.value[targetColumn].unshift(newTask);
      error.value = null;
      addToast('Task created', 'success');
      return newTask;
    } catch (err) {
      error.value = err.message;
      addToast(err.message, 'error');
      return null;
    }
  };

  /**
   * Deletes a task with optimistic removal and rollback.
   */
  const deleteTask = async (taskId) => {
    // Find the task for optimistic removal.
    let taskData = null;
    let taskColumn = null;
    let taskIndex = -1;

    for (const [columnKey, tasks] of Object.entries(columns.value)) {
      const index = tasks.findIndex((t) => t.id === taskId);
      if (index !== -1) {
        taskData = tasks[index];
        taskColumn = columnKey;
        taskIndex = index;
        break;
      }
    }

    if (!taskData) {
      error.value = 'Task not found';
      addToast('Task not found', 'error');
      return;
    }

    // Optimistic removal.
    columns.value[taskColumn].splice(taskIndex, 1);

    try {
      const url = `/api/kanban/task/${taskId}`;
      await deleteKanban(url);
      error.value = null;
      addToast('Task deleted', 'success');
    } catch (err) {
      // Rollback on error.
      columns.value[taskColumn].splice(taskIndex, 0, taskData);
      error.value = err.message;
      addToast(err.message, 'error');
    }
  };

  /**
   * Handles drag-and-drop reordering between columns.
   */
  const reorderTasks = (fromColumn, toColumn, fromIndex, toIndex) => {
    if (fromColumn === toColumn) {
      // Reorder within same column.
      const [task] = columns.value[fromColumn].splice(fromIndex, 1);
      columns.value[toColumn].splice(toIndex, 0, task);
    } else {
      // Move task between columns.
      const [task] = columns.value[fromColumn].splice(fromIndex, 1);
      columns.value[toColumn].splice(toIndex, 0, task);

      // Update status if moving to a different status column.
      if (task.status !== toColumn) {
        updateTaskStatus(task.id, toColumn).catch(() => {
          // On error, reorder is already rolled back by updateTaskStatus.
        });
      }
    }
  };

  /**
   * Refreshes board data from API.
   */
  const refresh = async () => {
    loading.value = true;
    try {
      const url = `/api/kanban/project/${settings.projectId}`;
      const response = await fetchKanban(url);
      columns.value = response.columns;
      error.value = null;
    } catch (err) {
      error.value = err.message;
    } finally {
      loading.value = false;
    }
  };

  return {
    columns,
    error,
    loading,
    updateTaskStatus,
    updateTask,
    createTask,
    deleteTask,
    reorderTasks,
    refresh,
  };
}

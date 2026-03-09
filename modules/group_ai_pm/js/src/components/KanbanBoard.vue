<template>
  <div class="gapm-kanban">
    <!-- Toast container -->
    <ToastContainer />

    <!-- Context menu -->
    <ContextMenu
      :status-labels="settings.statusLabels"
      :priority-labels="settings.priorityLabels"
      @status-change="handleContextMenuStatusChange"
      @priority-change="handleContextMenuPriorityChange"
      @edit="openDetailPanel"
      @delete="handleDelete"
    />

    <!-- Task detail panel -->
    <TaskDetailPanel
      :visible="detailPanelVisible"
      :task="selectedTask"
      :members="settings.members"
      @close="detailPanelVisible = false"
      @update-task="handlePanelUpdate"
      @delete-task="handleDelete"
    />

    <!-- Filter bar -->
    <FilterBar :members="settings.members" />

    <!-- Display options toolbar -->
    <div class="gapm-kanban__toolbar">
      <DisplayOptions :project-id="settings.projectId" />
    </div>

    <!-- Error display -->
    <div v-if="error" class="gapm-kanban__error" role="alert">
      <p>{{ error }}</p>
      <button @click="error = null">Dismiss</button>
    </div>

    <!-- Loading indicator -->
    <div v-if="loading" class="gapm-kanban__loading">
      Loading...
    </div>

    <!-- Board columns -->
    <div v-show="!loading" class="gapm-kanban__board">
      <KanbanColumn
        v-for="(columnKey, index) in columnOrder"
        :key="`col-${columnKey}`"
        :status="columnKey"
        :column-config="settings.columns[columnKey]"
        :tasks="filteredColumns[columnKey]"
        :status-labels="settings.statusLabels"
        :can-create="settings.permissions.createTask"
        :display-options="displayOptions"
        @task-status-change="handleStatusChange"
        @task-create="handleTaskCreate"
        @open-panel="openDetailPanel"
        @update-task="handleTaskUpdate"
        @context-menu="handleContextMenu"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import KanbanColumn from './KanbanColumn.vue';
import TaskDetailPanel from './TaskDetailPanel.vue';
import ContextMenu from './ContextMenu.vue';
import ToastContainer from './ToastContainer.vue';
import FilterBar from './FilterBar.vue';
import DisplayOptions from './DisplayOptions.vue';
import { useKanban } from '../composables/useKanban.js';
import { useFilters } from '../composables/useFilters.js';
import { useDisplayOptions } from '../composables/useDisplayOptions.js';
import { useContextMenu } from '../composables/useContextMenu.js';

const props = defineProps({
  settings: {
    type: Object,
    required: true,
  },
});

const {
  columns,
  error,
  loading,
  updateTaskStatus,
  updateTask,
  createTask,
  deleteTask,
} = useKanban(props.settings);

const { filters } = useFilters();
const { options: displayOptions } = useDisplayOptions(props.settings.projectId);
const contextMenu = useContextMenu();

const columnOrder = ['todo', 'in_progress', 'review', 'done'];
const detailPanelVisible = ref(false);
const selectedTask = ref(null);

const filteredColumns = computed(() => {
  const filtered = {
    todo: [],
    in_progress: [],
    review: [],
    done: [],
  };

  for (const [columnKey, tasks] of Object.entries(columns.value)) {
    filtered[columnKey] = tasks.filter((task) => {
      if (filters.value.assignee !== null) {
        const taskAssigneeId = task.assignee?.id || null;
        if (taskAssigneeId !== filters.value.assignee) {
          return false;
        }
      }

      if (filters.value.priority !== null) {
        if (task.priority !== filters.value.priority) {
          return false;
        }
      }

      return true;
    });
  }

  return filtered;
});

const handleStatusChange = async ({ taskId, newStatus }) => {
  await updateTaskStatus(taskId, newStatus);
};

const handleTaskCreate = async ({ title, status }) => {
  await createTask(title, status);
};

const handleTaskUpdate = async ({ taskId, updates }) => {
  await updateTask(taskId, updates);
};

const handleDelete = async (taskId) => {
  await deleteTask(taskId);
  detailPanelVisible.value = false;
  selectedTask.value = null;
};

const openDetailPanel = (taskId) => {
  const task = findTaskById(taskId);
  if (task) {
    selectedTask.value = task;
    detailPanelVisible.value = true;
  }
};

const handlePanelUpdate = async ({ taskId, updates }) => {
  // Status uses a dedicated endpoint — handle separately.
  if (updates.status) {
    const task = findTaskById(taskId);
    if (task && task.status !== updates.status) {
      await updateTaskStatus(taskId, updates.status);
    }
  }

  // Send remaining fields to the general update endpoint.
  const { status, ...fieldUpdates } = updates;
  if (Object.keys(fieldUpdates).length > 0) {
    await updateTask(taskId, fieldUpdates);
  }

  // Refresh selected task.
  const refreshed = findTaskById(taskId);
  if (refreshed) {
    selectedTask.value = refreshed;
  }
};

const handleContextMenu = ({ event, task }) => {
  contextMenu.show(event, task);
};

const handleContextMenuStatusChange = async ({ taskId, status }) => {
  await updateTaskStatus(taskId, status);
};

const handleContextMenuPriorityChange = async ({ taskId, priority }) => {
  await updateTask(taskId, { priority });
};

const findTaskById = (taskId) => {
  for (const tasks of Object.values(columns.value)) {
    const task = tasks.find((t) => t.id === taskId);
    if (task) return task;
  }
  return null;
};

onMounted(async () => {
  // Data is already in drupalSettings.
});
</script>

<style scoped>
.gapm-kanban {
  padding: 20px;
  background: white;
  border-radius: 4px;
}

.gapm-kanban__toolbar {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 12px;
  gap: 12px;
}

.gapm-kanban__error {
  padding: 12px 16px;
  background: #ffebee;
  border: 1px solid #ef5350;
  border-radius: 4px;
  color: #c62828;
  margin-bottom: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.gapm-kanban__error p {
  margin: 0;
  flex: 1;
}

.gapm-kanban__error button {
  padding: 4px 12px;
  border: none;
  background: #c62828;
  color: white;
  border-radius: 3px;
  cursor: pointer;
  font-size: 12px;
  margin-left: 12px;
}

.gapm-kanban__error button:hover {
  background: #b71c1c;
}

.gapm-kanban__loading {
  text-align: center;
  padding: 40px 20px;
  color: #999;
}

.gapm-kanban__board {
  display: flex;
  gap: 12px;
  overflow-x: auto;
  padding-bottom: 12px;
  margin-bottom: -12px;
}

@media (max-width: 1200px) {
  .gapm-kanban__board {
    flex-wrap: wrap;
  }
}

@media (max-width: 768px) {
  .gapm-kanban {
    padding: 12px;
  }

  .gapm-kanban__board {
    gap: 8px;
  }

  .gapm-kanban__toolbar {
    margin-bottom: 8px;
  }
}
</style>

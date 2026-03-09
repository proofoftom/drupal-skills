<template>
  <div class="gapm-kanban-column">
    <!-- Column header -->
    <div class="gapm-kanban-column__header">
      <h2 class="gapm-kanban-column__title">
        {{ columnConfig.label }}
      </h2>
      <span class="gapm-kanban-column__count">
        {{ tasks.length }}
      </span>
    </div>

    <!-- Draggable task list -->
    <VueDraggable
      v-model="localTasks"
      tag="div"
      class="gapm-kanban-column__tasks"
      group="kanban-tasks"
      @add="handleDragAdd"
      @start="emit('drag-start')"
      @end="emit('drag-end')"
      ghost-class="gapm-task-card--ghost"
      drag-class="gapm-task-card--chosen"
      chosen-class="gapm-task-card--chosen"
      :animation="200"
      item-key="id"
    >
      <TaskCard
        v-for="task in localTasks"
        :key="`task-${task.id}`"
        :task="task"
        :status-labels="statusLabels"
        :display-options="displayOptions"
        @status-change="updateTaskStatus(task.id, $event)"
        @open-panel="emit('open-panel', $event)"
        @update-task="emit('update-task', $event)"
        @context-menu="emit('context-menu', $event)"
      />
    </VueDraggable>

    <!-- Quick create form -->
    <QuickCreateForm
      v-if="canCreate"
      :status="status"
      @create="handleTaskCreate"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import TaskCard from './TaskCard.vue';
import QuickCreateForm from './QuickCreateForm.vue';

const props = defineProps({
  status: String,
  columnConfig: Object,
  tasks: Array,
  statusLabels: Object,
  canCreate: Boolean,
  displayOptions: {
    type: Object,
    default: () => ({
      showPriority: true,
      showAssignee: true,
      showDueDate: true,
      compactMode: false,
    }),
  },
});

const emit = defineEmits([
  'task-status-change',
  'task-create',
  'open-panel',
  'update-task',
  'context-menu',
  'drag-start',
  'drag-end',
]);

const localTasks = computed({
  get: () => props.tasks,
  set: () => { /* Parent handles state update via drag events */ },
});

const handleDragAdd = (evt) => {
  // SortableJS @add fires on the destination column when a task is dropped in.
  // Read task ID from the data-task-id attribute on the dragged DOM element.
  const taskId = Number(evt.item.dataset.taskId);
  if (taskId) {
    emit('task-status-change', {
      taskId,
      newStatus: props.status,
    });
  }
};

const updateTaskStatus = (taskId, newStatus) => {
  emit('task-status-change', {
    taskId,
    newStatus,
  });
};

const handleTaskCreate = async (title) => {
  emit('task-create', {
    title,
    status: props.status,
  });
};
</script>

<style scoped>
.gapm-kanban-column {
  display: flex;
  flex-direction: column;
  background: #f9f9f9;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  min-height: 400px;
  flex: 0 0 calc(25% - 9px);
}

.gapm-kanban-column__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  border-bottom: 2px solid #d0d0d0;
  background: #f5f5f5;
  border-radius: 4px 4px 0 0;
}

.gapm-kanban-column__title {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
  color: #333;
}

.gapm-kanban-column__count {
  font-size: 12px;
  background: #e0e0e0;
  color: #666;
  padding: 2px 8px;
  border-radius: 12px;
}

.gapm-kanban-column__tasks {
  flex: 1;
  padding: 12px;
  overflow-y: auto;
  list-style: none;
  margin: 0;
}

.gapm-kanban-column__tasks:empty::before {
  content: 'Drop tasks here';
  display: block;
  text-align: center;
  color: #999;
  font-size: 13px;
  padding: 20px 0;
}

/* Drag states */
.gapm-task-card--ghost {
  opacity: 0.5;
  background: #e3f2fd;
}

.gapm-task-card--dragging {
  opacity: 0.7;
  transform: rotate(2deg);
}

/* Responsive: 2 columns on tablets, 1 on mobile */
@media (max-width: 1200px) {
  .gapm-kanban-column {
    flex: 0 0 calc(50% - 6px);
  }
}

@media (max-width: 768px) {
  .gapm-kanban-column {
    flex: 0 0 100%;
  }
}
</style>

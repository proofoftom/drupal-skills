<template>
  <div
    class="gapm-task-card"
    :data-task-id="task.id"
    :class="[`gapm-task-card--${task.priority}`, dueClass]"
    @click="handleCardClick"
    @dblclick="startEditingTitle"
    @contextmenu="handleContextMenu"
    role="article"
    tabindex="0"
  >
    <!-- Priority-colored left border -->
    <div class="gapm-task-card__priority-bar"></div>

    <div class="gapm-task-card__content">
      <h3 v-if="!editingTitle" class="gapm-task-card__title">
        {{ task.title }}
      </h3>

      <input
        v-else
        v-model="editingTitleValue"
        class="gapm-task-card__title-input"
        @keydown.enter="saveTitle"
        @keydown.escape="cancelEditingTitle"
        @blur="saveTitle"
        autofocus
      />

      <div class="gapm-task-card__meta">
        <AssigneeAvatar
          v-if="task.assignee && displayOptions.showAssignee"
          :name="task.assignee.name"
          :picture-url="task.assignee.pictureUrl"
          :user-id="task.assignee.id"
        />

        <span
          v-if="displayOptions.showPriority"
          class="gapm-task-card__priority-label"
        >
          {{ priorityLabel }}
        </span>

        <span
          v-if="task.dueDate && displayOptions.showDueDate"
          class="gapm-task-card__due-date"
        >
          📅 {{ formatDate(task.dueDate) }}
        </span>
      </div>

      <!-- Keyboard-accessible status menu (WCAG 2.5.7 alternative to drag-and-drop) -->
      <div class="gapm-task-card__menu">
        <button
          class="gapm-task-card__menu-trigger"
          @click.stop="showMenu = !showMenu"
          :aria-expanded="showMenu"
          aria-label="Move task to different status"
        >
          {{ statusLabel }} ▼
        </button>

        <div v-show="showMenu" class="gapm-task-card__menu-items">
          <button
            v-for="(label, status) in statusOptions"
            :key="status"
            class="gapm-task-card__menu-item"
            :aria-label="`Move to ${label}`"
            @click.stop="moveToStatus(status)"
          >
            {{ label }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import AssigneeAvatar from './AssigneeAvatar.vue';

const props = defineProps({
  task: {
    type: Object,
    required: true,
  },
  statusLabels: {
    type: Object,
    required: true,
  },
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

const emit = defineEmits(['status-change', 'open-panel', 'update-task', 'context-menu']);

const showMenu = ref(false);
const editingTitle = ref(false);
const editingTitleValue = ref('');
let clickTimeout = null;

const statusOptions = computed(() => {
  const options = { ...props.statusLabels };
  delete options[props.task.status];
  return options;
});

const statusLabel = computed(
  () => props.statusLabels[props.task.status] || props.task.status
);

const priorityLabel = computed(() => {
  const labels = {
    low: 'Low',
    medium: 'Med',
    high: 'High',
  };
  return labels[props.task.priority] || props.task.priority;
});

const dueClass = computed(() => {
  if (!props.task.dueDate) return null;

  const dueDate = new Date(props.task.dueDate);
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const dueTime = dueDate.getTime();
  const todayTime = today.getTime();
  const diffDays = Math.floor((dueTime - todayTime) / (1000 * 60 * 60 * 24));

  if (diffDays < 0) {
    return 'gapm-task-card--overdue';
  }
  if (diffDays === 0) {
    return 'gapm-task-card--due-today';
  }
  if (diffDays <= 3) {
    return 'gapm-task-card--due-soon';
  }

  return null;
});

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
  });
};

const startEditingTitle = () => {
  editingTitle.value = true;
  editingTitleValue.value = props.task.title;
};

const saveTitle = async () => {
  editingTitle.value = false;
  const newTitle = editingTitleValue.value.trim();

  if (newTitle && newTitle !== props.task.title) {
    emit('update-task', {
      taskId: props.task.id,
      updates: { title: newTitle },
    });
  }
};

const cancelEditingTitle = () => {
  editingTitle.value = false;
};

const handleCardClick = () => {
  // Prevent double-click from also triggering single-click.
  if (clickTimeout) {
    clearTimeout(clickTimeout);
    clickTimeout = null;
    return;
  }

  clickTimeout = setTimeout(() => {
    clickTimeout = null;
    if (!editingTitle.value) {
      emit('open-panel', props.task.id);
    }
  }, 200);
};

const moveToStatus = (newStatus) => {
  emit('status-change', newStatus);
  showMenu.value = false;
};

const handleContextMenu = (event) => {
  emit('context-menu', {
    event,
    task: props.task,
  });
};
</script>

<style scoped>
.gapm-task-card {
  background: white;
  border: 1px solid #ddd;
  border-left: 3px solid #ddd;
  border-radius: 4px;
  padding: 12px;
  margin-bottom: 12px;
  display: flex;
  gap: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: box-shadow 0.2s, background 0.2s;
  cursor: pointer;
  position: relative;
}

.gapm-task-card:hover {
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.gapm-task-card:focus {
  outline: 2px solid #0066cc;
  outline-offset: -1px;
}

.gapm-task-card--overdue {
  border-left-color: #f44336;
}

.gapm-task-card--due-today {
  border-left-color: #ff9800;
}

.gapm-task-card--due-soon {
  border-left-color: #ffc107;
}

.gapm-task-card--low .gapm-task-card__priority-bar {
  background: #4caf50;
}

.gapm-task-card--medium .gapm-task-card__priority-bar {
  background: #ff9800;
}

.gapm-task-card--high .gapm-task-card__priority-bar {
  background: #f44336;
}

.gapm-task-card__priority-bar {
  width: 4px;
  border-radius: 2px;
  flex-shrink: 0;
  background: #d0d0d0;
  display: none;
}

.gapm-task-card__content {
  flex: 1;
  min-width: 0;
}

.gapm-task-card__title,
.gapm-task-card__title-input {
  margin: 0 0 8px 0;
  font-size: 14px;
  font-weight: 500;
  line-height: 1.4;
}

.gapm-task-card__title {
  word-break: break-word;
  color: #333;
}

.gapm-task-card__title-input {
  width: 100%;
  padding: 4px;
  border: 1px solid #0066cc;
  border-radius: 3px;
  font-family: inherit;
}

.gapm-task-card__title-input:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(0, 102, 204, 0.1);
}

.gapm-task-card__meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #666;
  margin-bottom: 8px;
  line-height: 1.4;
}

.gapm-task-card__priority-label,
.gapm-task-card__due-date {
  white-space: nowrap;
}

.gapm-task-card__priority-label {
  display: inline-block;
  padding: 2px 6px;
  background: #f5f5f5;
  border-radius: 2px;
  font-size: 11px;
}

.gapm-task-card__menu {
  position: relative;
}

.gapm-task-card__menu-trigger {
  padding: 4px 8px;
  font-size: 12px;
  background: #f5f5f5;
  border: 1px solid #ddd;
  border-radius: 3px;
  cursor: pointer;
  color: #333;
  width: 100%;
  text-align: left;
  transition: background 0.2s;
}

.gapm-task-card__menu-trigger:hover {
  background: #ebebeb;
}

.gapm-task-card__menu-items {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #ddd;
  border-radius: 3px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  z-index: 10;
  min-width: 120px;
}

.gapm-task-card__menu-item {
  display: block;
  width: 100%;
  padding: 8px 12px;
  border: none;
  background: white;
  cursor: pointer;
  font-size: 13px;
  color: #333;
  text-align: left;
  transition: background 0.15s;
}

.gapm-task-card__menu-item:hover {
  background: #f5f5f5;
}

.gapm-task-card__menu-item:first-child {
  border-radius: 2px 2px 0 0;
}

.gapm-task-card__menu-item:last-child {
  border-radius: 0 0 2px 2px;
}
</style>

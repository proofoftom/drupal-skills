<template>
  <Teleport to="body">
    <div v-if="visible" class="gapm-panel-overlay" @click="handleOverlayClick">
      <div
        ref="panelElement"
        class="gapm-panel"
        @click.stop
        role="dialog"
        aria-labelledby="panel-title"
      >
        <!-- Close button -->
        <button
          class="gapm-panel__close"
          @click="emit('close')"
          aria-label="Close panel"
        >
          ✕
        </button>

        <!-- Content -->
        <div class="gapm-panel__content">
          <h2 id="panel-title" class="gapm-panel__title">{{ task.title }}</h2>

          <div v-if="task.description" class="gapm-panel__section">
            <h3 class="gapm-panel__section-title">Description</h3>
            <p class="gapm-panel__description">{{ task.description }}</p>
          </div>

          <!-- Status dropdown -->
          <div class="gapm-panel__section">
            <label class="gapm-panel__label">Status</label>
            <select
              v-model="editData.status"
              class="gapm-panel__select"
            >
              <option value="todo">To Do</option>
              <option value="in_progress">In Progress</option>
              <option value="review">Review</option>
              <option value="done">Done</option>
            </select>
          </div>

          <!-- Priority dropdown -->
          <div class="gapm-panel__section">
            <label class="gapm-panel__label">Priority</label>
            <select
              v-model="editData.priority"
              class="gapm-panel__select"
            >
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
            </select>
          </div>

          <!-- Assignee dropdown -->
          <div class="gapm-panel__section">
            <label class="gapm-panel__label">Assignee</label>
            <select
              v-model="editData.assignee"
              class="gapm-panel__select"
            >
              <option :value="null">Unassigned</option>
              <option
                v-for="member in members"
                :key="member.id"
                :value="member.id"
              >
                {{ member.name }}
              </option>
            </select>
          </div>

          <!-- Metadata -->
          <div class="gapm-panel__meta">
            <div v-if="task.dueDate" class="gapm-panel__meta-item">
              <span class="gapm-panel__meta-label">Due:</span>
              <span class="gapm-panel__meta-value">{{ formatDate(task.dueDate) }}</span>
            </div>
            <div class="gapm-panel__meta-item">
              <span class="gapm-panel__meta-label">Created:</span>
              <span class="gapm-panel__meta-value">{{ formatDate(task.created) }}</span>
            </div>
          </div>

          <!-- Actions -->
          <div class="gapm-panel__actions">
            <button
              class="gapm-panel__action gapm-panel__action--primary"
              @click="handleSave"
            >
              Save
            </button>
            <button
              class="gapm-panel__action gapm-panel__action--danger"
              @click="handleDelete"
            >
              Delete
            </button>
            <button
              class="gapm-panel__action gapm-panel__action--secondary"
              @click="emit('close')"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';

const props = defineProps({
  visible: {
    type: Boolean,
    required: true,
  },
  task: {
    type: Object,
    default: null,
  },
  members: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['close', 'update-task', 'delete-task']);

const panelElement = ref(null);
const editData = reactive({
  status: null,
  priority: null,
  assignee: null,
});

watch(() => props.task, (newTask) => {
  if (newTask) {
    editData.status = newTask.status;
    editData.priority = newTask.priority;
    editData.assignee = newTask.assignee?.id || null;
  }
}, { immediate: true, deep: true });

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

const handleOverlayClick = () => {
  emit('close');
};

const handleSave = async () => {
  if (!props.task) return;

  const updates = {
    status: editData.status,
    priority: editData.priority,
    assignee: editData.assignee,
  };

  emit('update-task', {
    taskId: props.task.id,
    updates,
  });
  emit('close');
};

const handleDelete = () => {
  if (!props.task) return;

  if (confirm('Are you sure you want to delete this task?')) {
    emit('delete-task', props.task.id);
  }
};
</script>

<style scoped>
.gapm-panel-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: flex-end;
  z-index: 9999;
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.gapm-panel {
  background: white;
  width: 100%;
  max-width: 400px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: -2px 0 8px rgba(0, 0, 0, 0.15);
  animation: slideUp 0.3s ease-out;
  position: relative;
}

@keyframes slideUp {
  from {
    transform: translateY(100%);
  }
  to {
    transform: translateY(0);
  }
}

.gapm-panel__close {
  position: absolute;
  top: 12px;
  right: 12px;
  background: transparent;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #999;
  z-index: 10;
}

.gapm-panel__close:hover {
  color: #333;
}

.gapm-panel__content {
  padding: 40px 20px 20px 20px;
}

.gapm-panel__title {
  margin: 0 0 16px 0;
  font-size: 18px;
  font-weight: 600;
  color: #333;
}

.gapm-panel__section {
  margin-bottom: 20px;
}

.gapm-panel__section-title {
  margin: 0 0 8px 0;
  font-size: 14px;
  font-weight: 600;
  color: #666;
}

.gapm-panel__label {
  display: block;
  margin-bottom: 8px;
  font-size: 14px;
  font-weight: 500;
  color: #333;
}

.gapm-panel__select {
  width: 100%;
  padding: 8px 12px;
  font-size: 14px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-family: inherit;
}

.gapm-panel__select:focus {
  outline: none;
  border-color: #0066cc;
  box-shadow: 0 0 0 2px rgba(0, 102, 204, 0.1);
}

.gapm-panel__description {
  margin: 0;
  font-size: 14px;
  line-height: 1.6;
  color: #666;
}

.gapm-panel__meta {
  background: #f9f9f9;
  padding: 12px;
  border-radius: 4px;
  margin-bottom: 20px;
}

.gapm-panel__meta-item {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  color: #666;
  margin-bottom: 6px;
}

.gapm-panel__meta-item:last-child {
  margin-bottom: 0;
}

.gapm-panel__meta-label {
  font-weight: 500;
}

.gapm-panel__actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.gapm-panel__action {
  padding: 10px 16px;
  font-size: 14px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  transition: background 0.2s;
}

.gapm-panel__action--primary {
  background: #0066cc;
  color: white;
}

.gapm-panel__action--primary:hover {
  background: #0052a3;
}

.gapm-panel__action--secondary {
  background: #f0f0f0;
  color: #333;
}

.gapm-panel__action--secondary:hover {
  background: #e8e8e8;
}

.gapm-panel__action--danger {
  background: #f44336;
  color: white;
}

.gapm-panel__action--danger:hover {
  background: #da190b;
}
</style>

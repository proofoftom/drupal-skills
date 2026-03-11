<template>
  <Teleport to="body">
    <div
      v-if="visible"
      ref="menuElement"
      class="gapm-context-menu"
      :style="{ top: position.y + 'px', left: position.x + 'px' }"
      role="menu"
    >
      <!-- Status submenu -->
      <div class="gapm-context-menu__submenu">
        <button
          class="gapm-context-menu__item gapm-context-menu__item--parent"
          @click="toggleSubmenu('status')"
        >
          Change Status →
        </button>
        <div v-if="activeSubmenu === 'status'" class="gapm-context-menu__submenu-items">
          <button
            v-for="(label, status) in statusOptions"
            :key="status"
            class="gapm-context-menu__item"
            @click="handleStatusChange(status)"
            role="menuitem"
          >
            {{ label }}
          </button>
        </div>
      </div>

      <!-- Priority submenu -->
      <div class="gapm-context-menu__submenu">
        <button
          class="gapm-context-menu__item gapm-context-menu__item--parent"
          @click="toggleSubmenu('priority')"
        >
          Change Priority →
        </button>
        <div v-if="activeSubmenu === 'priority'" class="gapm-context-menu__submenu-items">
          <button
            v-for="(label, priority) in priorityOptions"
            :key="priority"
            class="gapm-context-menu__item"
            @click="handlePriorityChange(priority)"
            role="menuitem"
          >
            {{ label }}
          </button>
        </div>
      </div>

      <hr class="gapm-context-menu__divider" />

      <!-- Edit -->
      <button
        class="gapm-context-menu__item"
        @click="handleEdit"
        role="menuitem"
      >
        Edit Details
      </button>

      <!-- Delete -->
      <button
        class="gapm-context-menu__item gapm-context-menu__item--danger"
        @click="handleDelete"
        role="menuitem"
      >
        Delete
      </button>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useContextMenu } from '../composables/useContextMenu.js';

const props = defineProps({
  statusLabels: {
    type: Object,
    default: () => ({}),
  },
  priorityLabels: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits([
  'status-change',
  'priority-change',
  'edit',
  'delete',
]);

const { visible, position, task, menuElement, hide } = useContextMenu();

const activeSubmenu = ref(null);

const statusOptions = computed(() => {
  const options = { ...props.statusLabels };
  if (task.value) {
    delete options[task.value.status];
  }
  return options;
});

const priorityOptions = computed(() => props.priorityLabels);

const toggleSubmenu = (submenu) => {
  activeSubmenu.value = activeSubmenu.value === submenu ? null : submenu;
};

const handleStatusChange = (status) => {
  if (task.value) {
    emit('status-change', {
      taskId: task.value.id,
      status,
    });
  }
  hide();
};

const handlePriorityChange = (priority) => {
  if (task.value) {
    emit('priority-change', {
      taskId: task.value.id,
      priority,
    });
  }
  hide();
};

const handleEdit = () => {
  if (task.value) {
    emit('edit', task.value.id);
  }
  hide();
};

const handleDelete = () => {
  if (task.value) {
    emit('delete', task.value.id);
  }
  hide();
};
</script>

<style scoped>
.gapm-context-menu {
  position: fixed;
  background: white;
  border: 1px solid #ddd;
  border-radius: 4px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
  z-index: 10000;
  min-width: 200px;
  animation: popIn 0.15s ease-out;
}

@keyframes popIn {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.gapm-context-menu__submenu {
  position: relative;
}

.gapm-context-menu__item {
  display: block;
  width: 100%;
  padding: 10px 16px;
  border: none;
  background: transparent;
  cursor: pointer;
  font-size: 14px;
  color: #333;
  text-align: left;
  transition: background 0.15s;
}

.gapm-context-menu__item:hover {
  background: #f5f5f5;
}

.gapm-context-menu__item--parent {
  color: #0066cc;
}

.gapm-context-menu__item--danger {
  color: #f44336;
}

.gapm-context-menu__item--danger:hover {
  background: #ffebee;
}

.gapm-context-menu__submenu-items {
  background: #fafafa;
  border-left: 4px solid #0066cc;
}

.gapm-context-menu__divider {
  border: none;
  border-top: 1px solid #e0e0e0;
  margin: 4px 0;
}
</style>

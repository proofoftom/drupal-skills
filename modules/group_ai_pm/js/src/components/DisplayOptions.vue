<template>
  <div class="gapm-display-options">
    <button
      class="gapm-display-options__trigger"
      @click="showDropdown = !showDropdown"
      :aria-expanded="showDropdown"
      aria-label="Display options"
    >
      ⚙ Options
    </button>

    <div v-show="showDropdown" class="gapm-display-options__dropdown">
      <label class="gapm-display-options__checkbox">
        <input
          type="checkbox"
          :checked="options.showPriority"
          @change="toggleOption('showPriority')"
        />
        Show Priority
      </label>

      <label class="gapm-display-options__checkbox">
        <input
          type="checkbox"
          :checked="options.showAssignee"
          @change="toggleOption('showAssignee')"
        />
        Show Assignee
      </label>

      <label class="gapm-display-options__checkbox">
        <input
          type="checkbox"
          :checked="options.showDueDate"
          @change="toggleOption('showDueDate')"
        />
        Show Due Date
      </label>

      <hr class="gapm-display-options__divider" />

      <label class="gapm-display-options__checkbox">
        <input
          type="checkbox"
          :checked="options.compactMode"
          @change="toggleOption('compactMode')"
        />
        Compact Mode
      </label>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useDisplayOptions } from '../composables/useDisplayOptions.js';

const props = defineProps({
  projectId: {
    type: Number,
    required: true,
  },
});

const showDropdown = ref(false);
const { options, toggleOption } = useDisplayOptions(props.projectId);
</script>

<style scoped>
.gapm-display-options {
  position: relative;
}

.gapm-display-options__trigger {
  padding: 6px 12px;
  background: #f5f5f5;
  border: 1px solid #ddd;
  border-radius: 3px;
  cursor: pointer;
  font-size: 13px;
  color: #333;
  transition: background 0.2s;
}

.gapm-display-options__trigger:hover {
  background: #ebebeb;
}

.gapm-display-options__dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  background: white;
  border: 1px solid #ddd;
  border-radius: 3px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  z-index: 100;
  min-width: 150px;
  margin-top: 4px;
}

.gapm-display-options__checkbox {
  display: block;
  padding: 8px 12px;
  cursor: pointer;
  font-size: 13px;
  color: #333;
  user-select: none;
  transition: background 0.15s;
}

.gapm-display-options__checkbox:hover {
  background: #f5f5f5;
}

.gapm-display-options__checkbox input {
  margin-right: 6px;
  cursor: pointer;
}

.gapm-display-options__divider {
  border: none;
  border-top: 1px solid #e0e0e0;
  margin: 4px 0;
}
</style>

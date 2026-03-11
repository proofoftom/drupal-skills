<template>
  <div class="gapm-filter-bar">
    <div class="gapm-filter-bar__section">
      <!-- Assignee filter -->
      <div class="gapm-filter-bar__group">
        <label class="gapm-filter-bar__label">Assignee:</label>
        <select
          v-model="localFilters.assignee"
          class="gapm-filter-bar__select"
        >
          <option :value="null">All</option>
          <option
            v-for="member in members"
            :key="member.id"
            :value="member.id"
          >
            {{ member.name }}
          </option>
        </select>
      </div>

      <!-- Priority filter -->
      <div class="gapm-filter-bar__group">
        <label class="gapm-filter-bar__label">Priority:</label>
        <select
          v-model="localFilters.priority"
          class="gapm-filter-bar__select"
        >
          <option :value="null">All</option>
          <option value="low">Low</option>
          <option value="medium">Medium</option>
          <option value="high">High</option>
        </select>
      </div>
    </div>

    <!-- Active filter pills -->
    <div v-if="hasActiveFilters" class="gapm-filter-bar__pills">
      <div
        v-if="localFilters.assignee"
        class="gapm-filter-bar__pill"
      >
        {{ assigneeLabel }}
        <button
          class="gapm-filter-bar__pill-close"
          @click="clearFilter('assignee')"
          aria-label="Clear assignee filter"
        >
          ✕
        </button>
      </div>

      <div
        v-if="localFilters.priority"
        class="gapm-filter-bar__pill"
      >
        {{ localFilters.priority }}
        <button
          class="gapm-filter-bar__pill-close"
          @click="clearFilter('priority')"
          aria-label="Clear priority filter"
        >
          ✕
        </button>
      </div>

      <button
        class="gapm-filter-bar__clear-all"
        @click="clearAll"
      >
        Clear all
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed, watch } from 'vue';
import { useFilters } from '../composables/useFilters.js';

const props = defineProps({
  members: {
    type: Array,
    default: () => [],
  },
});

const { filters, clearFilter, clearAll, hasActiveFilters } = useFilters();

const localFilters = reactive({
  assignee: null,
  priority: null,
});

// Sync to parent filters.
watch(localFilters, (newFilters) => {
  filters.value = { ...newFilters };
}, { deep: true });

// Sync from parent filters.
watch(filters, (newFilters) => {
  localFilters.assignee = newFilters.assignee;
  localFilters.priority = newFilters.priority;
}, { deep: true });

const assigneeLabel = computed(() => {
  const member = props.members.find((m) => m.id === localFilters.assignee);
  return member ? member.name : 'Unknown';
});
</script>

<style scoped>
.gapm-filter-bar {
  background: #f9f9f9;
  border-bottom: 1px solid #e0e0e0;
  padding: 12px;
  margin-bottom: 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
}

.gapm-filter-bar__section {
  display: flex;
  gap: 16px;
}

.gapm-filter-bar__group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.gapm-filter-bar__label {
  font-size: 13px;
  font-weight: 500;
  color: #333;
  white-space: nowrap;
}

.gapm-filter-bar__select {
  padding: 6px 8px;
  font-size: 13px;
  border: 1px solid #ddd;
  border-radius: 3px;
  font-family: inherit;
  background: white;
}

.gapm-filter-bar__select:focus {
  outline: none;
  border-color: #0066cc;
  box-shadow: 0 0 0 2px rgba(0, 102, 204, 0.1);
}

.gapm-filter-bar__pills {
  display: flex;
  gap: 8px;
  align-items: center;
}

.gapm-filter-bar__pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #e3f2fd;
  border: 1px solid #90caf9;
  border-radius: 16px;
  padding: 4px 12px;
  font-size: 12px;
  color: #1565c0;
}

.gapm-filter-bar__pill-close {
  background: transparent;
  border: none;
  color: #1565c0;
  cursor: pointer;
  padding: 0;
  font-size: 14px;
}

.gapm-filter-bar__pill-close:hover {
  color: #0d47a1;
}

.gapm-filter-bar__clear-all {
  padding: 4px 12px;
  font-size: 12px;
  background: transparent;
  border: 1px solid #ddd;
  border-radius: 3px;
  cursor: pointer;
  color: #666;
  transition: background 0.2s;
}

.gapm-filter-bar__clear-all:hover {
  background: #f0f0f0;
}
</style>

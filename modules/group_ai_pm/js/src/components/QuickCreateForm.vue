<template>
  <div v-if="!isCreating" class="gapm-quick-create">
    <button
      class="gapm-quick-create__button"
      @click="startCreating"
    >
      + Add task
    </button>
  </div>

  <div v-else class="gapm-quick-create__form">
    <input
      ref="titleInput"
      v-model="title"
      type="text"
      class="gapm-quick-create__input"
      placeholder="Task title..."
      @keydown.enter="handleCreate"
      @keydown.escape="handleCancel"
    />
    <div class="gapm-quick-create__actions">
      <button
        class="gapm-quick-create__submit"
        @click="handleCreate"
      >
        Create
      </button>
      <button
        class="gapm-quick-create__cancel"
        @click="handleCancel"
      >
        Cancel
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick } from 'vue';

const props = defineProps({
  status: String,
});

const emit = defineEmits(['create']);

const isCreating = ref(false);
const title = ref('');
const titleInput = ref(null);

const startCreating = () => {
  isCreating.value = true;
  nextTick(() => {
    titleInput.value?.focus();
  });
};

const handleCreate = async () => {
  if (title.value.trim()) {
    emit('create', title.value);
    title.value = '';
    isCreating.value = false;
  }
};

const handleCancel = () => {
  title.value = '';
  isCreating.value = false;
};
</script>

<style scoped>
.gapm-quick-create {
  display: flex;
  justify-content: center;
}

.gapm-quick-create__button {
  padding: 8px 12px;
  background: #f0f0f0;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
  cursor: pointer;
  color: #666;
  width: 100%;
}

.gapm-quick-create__button:hover {
  background: #e8e8e8;
  color: #333;
}

.gapm-quick-create__form {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.gapm-quick-create__input {
  padding: 8px 12px;
  font-size: 14px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-family: inherit;
}

.gapm-quick-create__input:focus {
  outline: none;
  border-color: #0066cc;
  box-shadow: 0 0 0 2px rgba(0, 102, 204, 0.1);
}

.gapm-quick-create__actions {
  display: flex;
  gap: 8px;
}

.gapm-quick-create__submit,
.gapm-quick-create__cancel {
  padding: 6px 12px;
  font-size: 13px;
  border-radius: 3px;
  border: none;
  cursor: pointer;
  flex: 1;
  font-weight: 500;
}

.gapm-quick-create__submit {
  background: #0066cc;
  color: white;
}

.gapm-quick-create__submit:hover {
  background: #0052a3;
}

.gapm-quick-create__cancel {
  background: #f0f0f0;
  color: #666;
}

.gapm-quick-create__cancel:hover {
  background: #e8e8e8;
}
</style>

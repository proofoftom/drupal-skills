<template>
  <div class="gapm-avatar" :title="name">
    <img
      v-if="pictureUrl"
      :src="pictureUrl"
      :alt="name"
      class="gapm-avatar__image"
    />
    <div
      v-else
      class="gapm-avatar__initials"
      :style="{ backgroundColor: backgroundColor }"
    >
      {{ initials }}
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  name: {
    type: String,
    required: true,
  },
  pictureUrl: {
    type: String,
    default: null,
  },
  userId: {
    type: Number,
    required: true,
  },
});

const COLORS = [
  '#FF6B6B',
  '#4ECDC4',
  '#45B7D1',
  '#FFA07A',
  '#98D8C8',
  '#F7DC6F',
  '#BB8FCE',
  '#85C1E9',
];

const initials = computed(() => {
  return props.name
    .split(' ')
    .map((n) => n.charAt(0))
    .join('')
    .toUpperCase()
    .slice(0, 2);
});

const backgroundColor = computed(() => {
  return COLORS[props.userId % COLORS.length];
});
</script>

<style scoped>
.gapm-avatar {
  display: inline-block;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
}

.gapm-avatar__image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.gapm-avatar__initials {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 14px;
  font-weight: bold;
}
</style>

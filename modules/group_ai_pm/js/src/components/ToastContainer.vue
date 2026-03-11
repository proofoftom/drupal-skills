<template>
  <Teleport to="body">
    <div class="gapm-toast-container">
      <TransitionGroup name="gapm-toast" tag="div">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="gapm-toast"
          :class="`gapm-toast--${toast.type}`"
          role="alert"
        >
          <p class="gapm-toast__message">{{ toast.message }}</p>
          <button
            class="gapm-toast__close"
            @click="removeToast(toast.id)"
            :aria-label="`Dismiss ${toast.type} message`"
          >
            ✕
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { useToast } from '../composables/useToast.js';

const { toasts, removeToast } = useToast();
</script>

<style>
.gapm-toast-container {
  position: fixed;
  top: 70px;
  right: 20px;
  z-index: 10000;
  pointer-events: none;
}

.gapm-toast {
  background: white;
  border-left: 4px solid #2196F3;
  border-radius: 4px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  padding: 16px 12px;
  margin-bottom: 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  min-width: 300px;
  pointer-events: auto;
  animation: slideIn 0.3s ease-out;
}

.gapm-toast--success {
  border-left-color: #4CAF50;
}

.gapm-toast--error {
  border-left-color: #F44336;
}

.gapm-toast--info {
  border-left-color: #2196F3;
}

.gapm-toast__message {
  margin: 0;
  flex: 1;
  font-size: 14px;
  color: #333;
}

.gapm-toast__close {
  background: transparent;
  border: none;
  color: #999;
  cursor: pointer;
  font-size: 18px;
  padding: 0;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.2s;
}

.gapm-toast__close:hover {
  color: #333;
}

@keyframes slideIn {
  from {
    transform: translateX(400px);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.gapm-toast-enter-active,
.gapm-toast-leave-active {
  transition: all 0.3s ease;
}

.gapm-toast-enter-from {
  opacity: 0;
  transform: translateX(400px);
}

.gapm-toast-leave-to {
  opacity: 0;
  transform: translateX(400px);
}
</style>

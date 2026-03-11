import { ref } from 'vue';

/**
 * Toast notification queue management.
 *
 * Provides addToast(message, type, duration) and removeToast(id).
 * Module-level singleton via ref().
 */

const toasts = ref([]);
const toastId = ref(0);
const timeoutMap = new Map();

/**
 * Default durations by toast type (in milliseconds).
 */
const DEFAULT_DURATIONS = {
  success: 3000,
  error: 5000,
  info: 4000,
};

/**
 * Adds a toast notification to the queue.
 *
 * @param {string} message
 *   The toast message.
 * @param {string} type
 *   Toast type: 'success', 'error', 'info'.
 * @param {number} duration
 *   Optional duration in ms. Uses default for type if not provided.
 *
 * @returns {number}
 *   The toast ID.
 */
export function addToast(message, type = 'info', duration = null) {
  const id = toastId.value++;
  const actualDuration = duration !== null ? duration : DEFAULT_DURATIONS[type];

  toasts.value.push({
    id,
    message,
    type,
  });

  // Auto-dismiss after duration.
  const timeoutId = setTimeout(() => {
    removeToast(id);
  }, actualDuration);

  timeoutMap.set(id, timeoutId);

  return id;
}

/**
 * Removes a toast by ID, cleaning up any pending timeouts.
 *
 * @param {number} id
 *   The toast ID.
 */
export function removeToast(id) {
  const timeoutId = timeoutMap.get(id);
  if (timeoutId !== undefined) {
    clearTimeout(timeoutId);
    timeoutMap.delete(id);
  }

  toasts.value = toasts.value.filter((t) => t.id !== id);
}

/**
 * Composable for consuming toast notifications.
 *
 * @returns {Object}
 *   { toasts: ref, addToast, removeToast }
 */
export function useToast() {
  return {
    toasts,
    addToast,
    removeToast,
  };
}

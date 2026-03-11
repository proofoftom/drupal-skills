import { ref, watch } from 'vue';

/**
 * Display preferences persisted in localStorage.
 *
 * Accepts projectId parameter. Returns options ref (showPriority, showAssignee,
 * showDueDate, compactMode) and toggleOption(key). Reads from localStorage key
 * `gapm-display-${projectId}` on creation, merges with defaults. Watches and
 * writes changes back.
 */
export function useDisplayOptions(projectId) {
  const DEFAULT_OPTIONS = {
    showPriority: true,
    showAssignee: true,
    showDueDate: true,
    compactMode: false,
  };

  const storageKey = `gapm-display-${projectId}`;

  /**
   * Loads options from localStorage.
   */
  function loadOptions() {
    try {
      const stored = localStorage.getItem(storageKey);
      if (stored) {
        const parsed = JSON.parse(stored);
        return { ...DEFAULT_OPTIONS, ...parsed };
      }
    } catch (e) {
      // Ignore JSON parse errors.
    }
    return { ...DEFAULT_OPTIONS };
  }

  const options = ref(loadOptions());

  /**
   * Toggles a display option.
   *
   * @param {string} key
   *   Option key.
   */
  function toggleOption(key) {
    if (key in options.value) {
      options.value[key] = !options.value[key];
    }
  }

  /**
   * Saves options to localStorage when they change.
   */
  watch(options, (newOptions) => {
    try {
      localStorage.setItem(storageKey, JSON.stringify(newOptions));
    } catch (e) {
      // Ignore localStorage errors (quota exceeded, etc).
    }
  }, { deep: true });

  return {
    options,
    toggleOption,
  };
}

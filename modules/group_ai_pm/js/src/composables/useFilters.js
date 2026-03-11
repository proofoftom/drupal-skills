import { ref, computed, watch } from 'vue';

/**
 * Filter state with URL sync.
 *
 * Returns reactive filters (assignee, priority), clearFilter(key), clearAll(),
 * hasActiveFilters computed. Reads URL query params on mount via URLSearchParams.
 * Watches filter changes and syncs back to URL via history.replaceState().
 */
export function useFilters() {
  const filters = ref({
    assignee: null,
    priority: null,
  });

  /**
   * Reads filters from URL query parameters.
   */
  function initializeFromUrl() {
    const params = new URLSearchParams(window.location.search);
    if (params.has('assignee')) {
      filters.value.assignee = parseInt(params.get('assignee'), 10);
    }
    if (params.has('priority')) {
      filters.value.priority = params.get('priority');
    }
  }

  /**
   * Syncs filters to URL query parameters.
   */
  function syncToUrl() {
    const params = new URLSearchParams();
    if (filters.value.assignee !== null) {
      params.set('assignee', filters.value.assignee);
    }
    if (filters.value.priority !== null) {
      params.set('priority', filters.value.priority);
    }

    const newUrl = params.toString()
      ? `${window.location.pathname}?${params.toString()}`
      : window.location.pathname;

    window.history.replaceState({}, '', newUrl);
  }

  /**
   * Clears a specific filter.
   *
   * @param {string} key
   *   Filter key (assignee or priority).
   */
  function clearFilter(key) {
    if (key in filters.value) {
      filters.value[key] = null;
    }
  }

  /**
   * Clears all filters.
   */
  function clearAll() {
    filters.value.assignee = null;
    filters.value.priority = null;
  }

  /**
   * Computed property for whether any filters are active.
   */
  const hasActiveFilters = computed(
    () => filters.value.assignee !== null || filters.value.priority !== null
  );

  // Initialize from URL and watch for changes.
  initializeFromUrl();
  watch(filters, syncToUrl, { deep: true });

  return {
    filters,
    clearFilter,
    clearAll,
    hasActiveFilters,
  };
}

import { ref, nextTick } from 'vue';

/**
 * Context menu positioning.
 *
 * Returns visible, position, task, show(event, taskData), hide().
 * Calculates position from event.clientX/clientY. Adjusts for viewport edge
 * overflow via nextTick + getBoundingClientRect. Closes on document click
 * and Escape key.
 */
export function useContextMenu() {
  const visible = ref(false);
  const position = ref({ x: 0, y: 0 });
  const task = ref(null);
  const menuElement = ref(null);

  /**
   * Shows context menu at event coordinates.
   *
   * @param {MouseEvent} event
   *   The right-click event.
   * @param {Object} taskData
   *   The task object.
   */
  async function show(event, taskData) {
    event.preventDefault();

    task.value = taskData;
    position.value = {
      x: event.clientX,
      y: event.clientY,
    };

    visible.value = true;

    // Wait for rendering, then adjust position for viewport edges.
    await nextTick();

    if (menuElement.value) {
      const rect = menuElement.value.getBoundingClientRect();
      const viewportWidth = window.innerWidth;
      const viewportHeight = window.innerHeight;

      if (rect.right > viewportWidth) {
        position.value.x = viewportWidth - rect.width - 10;
      }
      if (rect.bottom > viewportHeight) {
        position.value.y = viewportHeight - rect.height - 10;
      }
    }
  }

  /**
   * Hides the context menu.
   */
  function hide() {
    visible.value = false;
    task.value = null;
  }

  /**
   * Closes menu on document click.
   */
  function handleDocumentClick(e) {
    if (visible.value && menuElement.value && !menuElement.value.contains(e.target)) {
      hide();
    }
  }

  /**
   * Closes menu on Escape key.
   */
  function handleKeyDown(e) {
    if (visible.value && e.key === 'Escape') {
      hide();
    }
  }

  // Set up event listeners when visible changes.
  if (typeof window !== 'undefined') {
    document.addEventListener('click', handleDocumentClick);
    document.addEventListener('keydown', handleKeyDown);
  }

  return {
    visible,
    position,
    task,
    menuElement,
    show,
    hide,
  };
}

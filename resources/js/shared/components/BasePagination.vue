<script setup lang="ts">
// Reusable pagination controls driven by API pagination metadata

interface Props {
  currentPage: number
  lastPage: number
  disabled?: boolean
}

withDefaults(defineProps<Props>(), {
  disabled: false,
})

// Emitted when the user requests a different page
const emit = defineEmits<{
  'update:page': [page: number]
}>()

function goToPage(page: number, currentPage: number, lastPage: number): void {
  if (page < 1 || page > lastPage || page === currentPage) {
    return
  }

  emit('update:page', page)
}
</script>

<template>
  <div class="base-pagination">
    <!-- Previous page -->
    <button
      class="base-pagination__button"
      type="button"
      :disabled="disabled || currentPage <= 1"
      @click="goToPage(currentPage - 1, currentPage, lastPage)"
    >
      Previous
    </button>

    <span class="base-pagination__status">
      Page {{ currentPage }} / {{ lastPage }}
    </span>

    <!-- Next page -->
    <button
      class="base-pagination__button"
      type="button"
      :disabled="disabled || currentPage >= lastPage"
      @click="goToPage(currentPage + 1, currentPage, lastPage)"
    >
      Next
    </button>
  </div>
</template>

<style scoped>
.base-pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  padding-top: 1rem;
}

.base-pagination__button {
  padding: 0.4rem 0.9rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  background-color: #ffffff;
  cursor: pointer;
  font-size: 0.85rem;
}

.base-pagination__button:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.base-pagination__button:not(:disabled):hover {
  background-color: #f3f4f6;
}

.base-pagination__status {
  font-size: 0.85rem;
  color: #374151;
}
</style>

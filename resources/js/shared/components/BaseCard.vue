<script setup lang="ts">
// Reusable card component with optional title and footer slots

interface Props {
  title?: string
  // Removes internal padding, useful when the slot content manages its own layout (e.g. tables)
  noPadding?: boolean
}

withDefaults(defineProps<Props>(), {
  title: '',
  noPadding: false,
})
</script>

<template>
  <div class="base-card">
    <!-- Header rendered only if a title is provided or the header slot is used -->
    <div v-if="title || $slots.header" class="base-card__header">
      <slot name="header">
        <h2 class="base-card__title">{{ title }}</h2>
      </slot>
    </div>

    <div class="base-card__body" :class="{ 'base-card__body--no-padding': noPadding }">
      <slot />
    </div>

    <!-- Footer rendered only if the footer slot is used -->
    <div v-if="$slots.footer" class="base-card__footer">
      <slot name="footer" />
    </div>
  </div>
</template>

<style scoped>
.base-card {
  background-color: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
}

.base-card__header {
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #e5e7eb;
}

.base-card__title {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
}

.base-card__body {
  padding: 1.25rem;
}

.base-card__body--no-padding {
  padding: 0;
}

.base-card__footer {
  padding: 1rem 1.25rem;
  border-top: 1px solid #e5e7eb;
  background-color: #f9fafb;
}
</style>

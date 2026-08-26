<script setup lang="ts">
// Reusable button component with variants, sizes and a loading state

// Supported visual variants
type ButtonVariant = 'primary' | 'secondary' | 'danger' | 'ghost'

// Supported sizes
type ButtonSize = 'sm' | 'md' | 'lg'

interface Props {
  variant?: ButtonVariant
  size?: ButtonSize
  type?: 'button' | 'submit' | 'reset'
  disabled?: boolean
  loading?: boolean
}

// Default prop values
withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md',
  type: 'button',
  disabled: false,
  loading: false,
})

// Emitted when the button is clicked (and not disabled/loading)
const emit = defineEmits<{
  click: [event: MouseEvent]
}>()

function handleClick(event: MouseEvent): void {
  emit('click', event)
}
</script>

<template>
  <button
    class="base-button"
    :class="[`base-button--${variant}`, `base-button--${size}`]"
    :type="type"
    :disabled="disabled || loading"
    @click="handleClick"
  >
    <!-- Simple loading indicator, replaces the label while loading -->
    <span v-if="loading" class="base-button__spinner" aria-hidden="true" />
    <span class="base-button__label">
      <slot />
    </span>
  </button>
</template>

<style scoped>
/* Base button reset and shared styles */
.base-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  border: 1px solid transparent;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.15s ease, opacity 0.15s ease;
}

.base-button:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

/* Sizes */
.base-button--sm {
  padding: 0.35rem 0.75rem;
  font-size: 0.8rem;
}
.base-button--md {
  padding: 0.55rem 1rem;
  font-size: 0.9rem;
}
.base-button--lg {
  padding: 0.75rem 1.5rem;
  font-size: 1rem;
}

/* Variants */
.base-button--primary {
  background-color: #2563eb;
  color: #ffffff;
}
.base-button--primary:not(:disabled):hover {
  background-color: #1d4ed8;
}

.base-button--secondary {
  background-color: #e5e7eb;
  color: #111827;
}
.base-button--secondary:not(:disabled):hover {
  background-color: #d1d5db;
}

.base-button--danger {
  background-color: #dc2626;
  color: #ffffff;
}
.base-button--danger:not(:disabled):hover {
  background-color: #b91c1c;
}

.base-button--ghost {
  background-color: transparent;
  color: #111827;
  border-color: #d1d5db;
}
.base-button--ghost:not(:disabled):hover {
  background-color: #f3f4f6;
}

/* Loading spinner */
.base-button__spinner {
  width: 0.9em;
  height: 0.9em;
  border: 2px solid currentColor;
  border-right-color: transparent;
  border-radius: 50%;
  animation: base-button-spin 0.6s linear infinite;
}

@keyframes base-button-spin {
  to {
    transform: rotate(360deg);
  }
}
</style>

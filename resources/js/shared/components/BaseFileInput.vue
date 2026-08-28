<script setup lang="ts">
// Reusable file input with a visible selected filename
import { ref } from 'vue'

interface Props {
  accept?: string
  disabled?: boolean
}

withDefaults(defineProps<Props>(), {
  accept: 'image/*',
  disabled: false,
})

// Emits the selected File, or null if the selection was cleared
const emit = defineEmits<{
  'update:modelValue': [file: File | null]
}>()

// Name of the currently selected file, shown next to the input
const selectedFileName = ref<string | null>(null)

function handleChange(event: Event): void {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0] ?? null

  selectedFileName.value = file?.name ?? null
  emit('update:modelValue', file)
}
</script>

<template>
  <div class="base-file-input">
    <input
      type="file"
      :accept="accept"
      :disabled="disabled"
      @change="handleChange"
    />
    <span v-if="selectedFileName" class="base-file-input__name">
      {{ selectedFileName }}
    </span>
  </div>
</template>

<style scoped>
.base-file-input {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.base-file-input__name {
  font-size: 0.85rem;
  color: #374151;
}
</style>

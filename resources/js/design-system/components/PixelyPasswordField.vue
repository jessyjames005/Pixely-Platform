<script setup lang="ts">
withDefaults(
  defineProps<{
    label?: string
    modelValue?: string | null
    errorMessages?: string | string[]
    autocomplete?: string
  }>(),
  {
    label: 'Password',
    modelValue: '',
    autocomplete: 'current-password',
  },
)

defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>

<template>
  <v-text-field
    :model-value="modelValue ?? ''"
    :label="label"
    :autocomplete="autocomplete"
    :error-messages="errorMessages"
    type="password"
    append-inner-icon="mdi-eye-outline"
    @update:model-value="$emit('update:modelValue', $event)"
  />
</template>

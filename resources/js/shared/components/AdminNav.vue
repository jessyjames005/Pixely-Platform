<script setup lang="ts">
import { ref } from 'vue'
import { useVisibleNav } from '@shared/navigation/useVisibleNav'
import SidebarItem from './SidebarItem.vue'

const props = defineProps<{ rail: boolean }>()
defineEmits<{
  (e: 'toggle-rail'): void
}>()

const { visibleItems } = useVisibleNav()

const openGroup = ref<string | null>(null)

function handleToggle(item: string): void {
  openGroup.value = openGroup.value === item ? null : item
}
</script>

<template>
  <v-list nav>
    <sidebar-item
      v-for="item in visibleItems"
      :key="item.to"
      :item="item"
      :rail="props.rail"
      :open="props.rail ? false : openGroup === item.to"
      :on-toggle="props.rail ? undefined : () => handleToggle(item.to)"
    />
  </v-list>

  <v-divider class="my-2" />

  <div class="pa-2 d-flex align-center">
    <v-btn
      icon
      :title="props.rail ? 'Expand navigation' : 'Collapse navigation'"
      :aria-label="props.rail ? 'Expand navigation' : 'Collapse navigation'"
      @click="$emit('toggle-rail')"
    >
      <v-icon :icon="props.rail ? 'mdi-chevron-right' : 'mdi-chevron-left'" />
    </v-btn>
    <span class="ml-2 text-medium-emphasis small">{{ props.rail ? 'Expand' : 'Collapse' }}</span>
  </div>
</template>

<style scoped>
/* Align the footer toggle with the rail/expanded width. */
</style>

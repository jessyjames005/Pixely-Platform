<script setup lang="ts">
import { ref } from 'vue'
import { useVisibleNav } from '@shared/navigation/useVisibleNav'
import SidebarItem from './SidebarItem.vue'

const props = defineProps<{ rail: boolean }>()
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
</template>

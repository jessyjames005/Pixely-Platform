<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { navLabel } from '@shared/navigation/types'
import type { NavItem } from '@shared/navigation/types'

interface Props {
  item: NavItem
  depth?: number
  rail?: boolean
  open?: boolean
  onToggle?: () => void
}

const props = withDefaults(defineProps<Props>(), {
  depth: 0,
  rail: false,
  open: undefined,
  onToggle: undefined,
})

const route = useRoute()
const localOpen = ref(false)

const isOpen = computed(() => props.open ?? localOpen.value)
const isGroup = computed(() => Array.isArray(props.item.children) && props.item.children.length > 0)

const isActive = computed(() => {
  if (isGroup.value && props.item.children) {
    return props.item.children.some((child) => child.to === route.path)
  }
  return props.item.to === route.path
})

function toggle(): void {
  if (props.onToggle) {
    props.onToggle()
  } else {
    localOpen.value = !localOpen.value
  }
}

function itemClasses(): Record<string, unknown> {
  return {
    'sidebar-item': true,
    active: isActive.value,
    'py-1': true,
  }
}

function childClasses(): Record<string, unknown> {
  return {
    'sidebar-child': true,
    active: props.item.to === route.path,
  }
}
</script>

<template>
  <!-- Group header: flyout on hover when collapsed, inline toggle when expanded -->
  <template v-if="isGroup">
    <v-menu
      v-if="props.rail"
      location="end"
      :offset="8"
      open-on-hover
      transition="scale-transition"
    >
      <template #activator="{ props: menuProps }">
        <v-list-item
          v-bind="menuProps"
          :class="itemClasses()"
          :prepend-icon="item.icon"
          :title="navLabel(item)"
          :to="item.to"
        >
          <v-list-item-content>
            <v-list-item-title>{{ navLabel(item) }}</v-list-item-title>
            <v-list-item-action>
              <v-icon
                icon="mdi-chevron-right"
                size="18"
              />
            </v-list-item-action>
          </v-list-item-content>
        </v-list-item>
      </template>

      <v-list
        density="compact"
        class="sidebar-flyout"
      >
        <sidebar-item
          v-for="child in item.children!"
          :key="child.to"
          :item="child"
          :depth="depth + 1"
          :rail="false"
        />
      </v-list>
    </v-menu>

    <div
      v-else
      class="sidebar-group"
    >
      <v-list-item
        :class="itemClasses()"
        :prepend-icon="item.icon"
        :title="navLabel(item)"
        tabindex="0"
        role="button"
        :aria-expanded="String(isOpen)"
        @click="toggle"
        @keydown.enter.prevent="toggle"
        @keydown.space.prevent="toggle"
      >
        <v-list-item-content>
          <v-list-item-title>{{ navLabel(item) }}</v-list-item-title>
          <v-list-item-action>
            <v-icon
              :icon="isOpen ? 'mdi-chevron-down' : 'mdi-chevron-right'"
              size="18"
            />
          </v-list-item-action>
        </v-list-item-content>
      </v-list-item>

      <v-list
        v-if="isOpen"
        density="compact"
        class="sidebar-children"
      >
        <sidebar-item
          v-for="child in item.children!"
          :key="child.to"
          :item="child"
          :depth="depth + 1"
          :rail="false"
        />
      </v-list>
    </div>
  </template>

  <!-- Leaf -->
  <v-list-item
    v-else
    :class="childClasses()"
    :prepend-icon="item.icon"
    :title="rail ? navLabel(item) : undefined"
    :to="item.to"
  >
    <v-list-item-content>
      <v-list-item-title>{{ navLabel(item) }}</v-list-item-title>
    </v-list-item-content>
  </v-list-item>
</template>

<style scoped>
.sidebar-item {
  border-radius: 4px;
}
.sidebar-item.active {
  background-color: rgba(var(--v-theme-primary), 0.12);
  border-right: 2px solid rgb(var(--v-theme-primary));
}
.sidebar-group .sidebar-item {
  font-weight: 600;
}
.sidebar-child {
  border-radius: 0 4px 4px 0;
  font-weight: 400;
}
.sidebar-child.active {
  background-color: rgba(var(--v-theme-primary), 0.08);
}
.sidebar-flyout {
  background-color: rgb(var(--v-theme-surface));
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  min-width: 200px;
}
</style>

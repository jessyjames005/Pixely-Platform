<script setup lang="ts">
// Overlapping initials avatars for a list of assignees, capped at 3
// visible with a "+N" overflow badge — used throughout the alerts list.
import type { ArtifactAvatar } from '../models/tuleap'

const props = defineProps<{ assignees?: ArtifactAvatar[] }>()

function initials(name: string): string {
  const parts = (name || '').replace(/\s*\(.*\)/, '').trim().split(/\s+/)
  return parts
    .map((w) => w[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
}

function avatarColor(name: string): string {
  let hash = 0
  for (const c of name || '') hash = c.charCodeAt(0) + ((hash << 5) - hash)
  const h = Math.abs(hash) % 360
  return `hsl(${h}, 45%, 40%)`
}

const visible = () => (props.assignees ?? []).slice(0, 3)
const overflowCount = () => Math.max((props.assignees?.length ?? 0) - 3, 0)
</script>

<template>
  <div v-if="assignees?.length" class="d-flex flex-row-reverse align-center">
    <v-avatar
      v-for="(a, i) in visible()"
      :key="i"
      size="24"
      :color="avatarColor(a.name)"
      :title="a.name"
      class="ml-n2"
      style="border: 2px solid rgb(var(--v-theme-surface))"
    >
      <span class="text-caption font-weight-bold" style="color: white; font-size: 9px">{{ initials(a.name) }}</span>
    </v-avatar>
    <v-avatar
      v-if="overflowCount() > 0"
      size="24"
      color="surface-variant"
      :title="`+${overflowCount()} autres`"
      class="ml-n2"
      style="border: 2px solid rgb(var(--v-theme-surface))"
    >
      <span class="text-caption font-weight-bold" style="font-size: 9px">+{{ overflowCount() }}</span>
    </v-avatar>
  </div>
</template>

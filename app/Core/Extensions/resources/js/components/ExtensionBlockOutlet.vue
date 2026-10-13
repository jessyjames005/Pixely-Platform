<script setup lang="ts">
import { computed } from 'vue'
import type { Surface } from '@shared/surface'
import type { ExtensionBlockDefinition } from '../models/ExtensionCapability'
import {
  resolveTrustedBlockRenderer,
  trustedBlockRenderers,
} from '../renderers/TrustedBlockRenderer'

const props = withDefaults(defineProps<{
  definition: ExtensionBlockDefinition
  value?: Record<string, unknown>
  surface: Surface
}>(), {
  value: () => ({}),
})

/** Resolve a renderer from the local allowlist, never from API-provided code or paths. */
const renderer = computed(() =>
  resolveTrustedBlockRenderer(props.definition, props.surface, trustedBlockRenderers),
)
</script>

<template>
  <component
    :is="renderer.component"
    v-if="renderer"
    :definition="definition"
    :value="value"
    :surface="surface"
  />
  <div
    v-else
    class="extension-block-outlet__unavailable"
    role="status"
    :data-block-id="definition.qualified_id"
  >
    This block is not available on this surface.
  </div>
</template>

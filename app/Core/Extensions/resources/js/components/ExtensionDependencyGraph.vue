<script setup lang="ts">
// Renders a simple layered dependency graph (DAG) for installed
// extensions, using plain SVG — no charting library needed for the
// small number of nodes expected here.
import { computed } from 'vue'
import type { ExtensionSummary } from '../models/Extension'

const props = defineProps<{
  extensions: ExtensionSummary[]
}>()

const NODE_WIDTH = 160
const NODE_HEIGHT = 48
const LAYER_GAP_X = 220
const NODE_GAP_Y = 70
const PADDING = 40

interface PositionedNode {
  id: string
  name: string
  enabled: boolean
  layer: number
  x: number
  y: number
}

// Computes each extension's layer: an extension with no dependencies
// is layer 0; otherwise it's one more than the deepest dependency.
// Missing dependencies (shouldn't happen — enable() validates this)
// are simply ignored rather than crashing the graph.
function computeLayers(extensions: ExtensionSummary[]): Map<string, number> {
  const byId = new Map(extensions.map((ext) => [ext.id, ext]))
  const layers = new Map<string, number>()
  const visiting = new Set<string>()

  function layerOf(id: string): number {
    if (layers.has(id)) return layers.get(id)!
    if (visiting.has(id)) return 0 // cycle guard, should not occur

    visiting.add(id)
    const extension = byId.get(id)
    const deps = extension?.dependencies ?? []

    const layer = deps.length === 0
      ? 0
      : 1 + Math.max(...deps.filter((dep) => byId.has(dep)).map(layerOf), -1)

    visiting.delete(id)
    layers.set(id, layer)
    return layer
  }

  for (const ext of extensions) {
    layerOf(ext.id)
  }

  return layers
}

const positionedNodes = computed<PositionedNode[]>(() => {
  const layers = computeLayers(props.extensions)
  const countPerLayer = new Map<number, number>()

  return props.extensions.map((ext) => {
    const layer = layers.get(ext.id) ?? 0
    const indexInLayer = countPerLayer.get(layer) ?? 0
    countPerLayer.set(layer, indexInLayer + 1)

    return {
      id: ext.id,
      name: ext.name,
      enabled: ext.enabled,
      layer,
      x: PADDING + layer * LAYER_GAP_X,
      y: PADDING + indexInLayer * NODE_GAP_Y,
    }
  })
})

const nodesById = computed(() => new Map(positionedNodes.value.map((n) => [n.id, n])))

// One line per (dependent -> dependency) edge, with arrow pointing
// at the dependency (the thing that must exist/be enabled first).
const edges = computed(() => {
  const lines: { x1: number; y1: number; x2: number; y2: number; key: string }[] = []

  for (const ext of props.extensions) {
    const from = nodesById.value.get(ext.id)
    if (!from) continue

    for (const depId of ext.dependencies) {
      const to = nodesById.value.get(depId)
      if (!to) continue

      lines.push({
        key: `${ext.id}->${depId}`,
        x1: from.x,
        y1: from.y + NODE_HEIGHT / 2,
        x2: to.x + NODE_WIDTH,
        y2: to.y + NODE_HEIGHT / 2,
      })
    }
  }

  return lines
})

const svgWidth = computed(() => {
  const maxLayer = Math.max(0, ...positionedNodes.value.map((n) => n.layer))
  return PADDING * 2 + (maxLayer + 1) * LAYER_GAP_X
})

const svgHeight = computed(() => {
  const maxY = Math.max(0, ...positionedNodes.value.map((n) => n.y))
  return PADDING * 2 + maxY + NODE_HEIGHT
})
</script>

<template>
  <div class="dependency-graph-wrapper">
    <svg :width="svgWidth" :height="svgHeight" :viewBox="`0 0 ${svgWidth} ${svgHeight}`">
      <defs>
        <marker id="arrow" markerWidth="8" markerHeight="8" refX="7" refY="4" orient="auto">
          <path d="M0,0 L8,4 L0,8 Z" fill="#9e9e9e" />
        </marker>
      </defs>

      <!-- Edges (dependency lines), drawn under nodes -->
      <line
        v-for="edge in edges"
        :key="edge.key"
        :x1="edge.x1"
        :y1="edge.y1"
        :x2="edge.x2"
        :y2="edge.y2"
        stroke="#9e9e9e"
        stroke-width="2"
        marker-end="url(#arrow)"
      />

      <!-- Nodes -->
      <g v-for="node in positionedNodes" :key="node.id">
        <rect
          :x="node.x"
          :y="node.y"
          :width="NODE_WIDTH"
          :height="NODE_HEIGHT"
          rx="8"
          :fill="node.enabled ? '#e8f5e9' : '#f5f5f5'"
          :stroke="node.enabled ? '#43a047' : '#bdbdbd'"
          stroke-width="2"
        />
        <text
          :x="node.x + NODE_WIDTH / 2"
          :y="node.y + NODE_HEIGHT / 2 + 5"
          text-anchor="middle"
          font-size="13"
          font-weight="600"
          fill="#212121"
        >
          {{ node.name }}
        </text>
      </g>
    </svg>

    <div class="dependency-graph-legend">
      <span class="legend-item"><span class="legend-swatch legend-swatch--enabled" /> Enabled</span>
      <span class="legend-item"><span class="legend-swatch legend-swatch--disabled" /> Disabled</span>
      <span class="legend-item">Arrow points from a dependent extension to the extension it requires</span>
    </div>
  </div>
</template>

<style scoped>
.dependency-graph-wrapper {
  overflow-x: auto;
}

.dependency-graph-legend {
  display: flex;
  gap: 1.5rem;
  margin-top: 0.75rem;
  font-size: 0.8rem;
  color: #616161;
  align-items: center;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.legend-swatch {
  width: 12px;
  height: 12px;
  border-radius: 3px;
  display: inline-block;
}

.legend-swatch--enabled {
  background: #e8f5e9;
  border: 2px solid #43a047;
}

.legend-swatch--disabled {
  background: #f5f5f5;
  border: 2px solid #bdbdbd;
}
</style>

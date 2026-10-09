<script setup lang="ts">
// Renders text as a QR code (inline SVG, dark on a white square so it stays
// scannable in dark mode). Renders nothing when the text cannot be encoded.
import { computed } from 'vue'
import { generateQrMatrix, qrToPath } from '../utils/qrcode'

const props = withDefaults(defineProps<{ value: string; size?: number; label?: string }>(), {
  size: 192,
  label: 'QR code',
})

const MARGIN = 4

const qr = computed(() => {
  try {
    const matrix = generateQrMatrix(props.value)
    return { path: qrToPath(matrix, MARGIN), dimension: matrix.length + MARGIN * 2 }
  } catch {
    return null
  }
})
</script>

<template>
  <svg
    v-if="qr"
    :width="size"
    :height="size"
    :viewBox="`0 0 ${qr.dimension} ${qr.dimension}`"
    shape-rendering="crispEdges"
    role="img"
    :aria-label="label"
    data-testid="qr-code"
  >
    <rect :width="qr.dimension" :height="qr.dimension" fill="#fff" />
    <path :d="qr.path" fill="#000" />
  </svg>
</template>

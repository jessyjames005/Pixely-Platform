<script setup lang="ts">
import { ref } from 'vue'

withDefaults(
  defineProps<{
    label: string
    value: string | number
    subtitle?: string
    icon?: string
    iconColor?: string
    iconBackground?: string
    trend?: string
    trendDirection?: 'up' | 'down' | 'flat'
    pointColor?: string
  }>(),
  {
    label: 'Metric',
    value: '0',
    icon: 'mdi-chart-line',
    iconColor: '#5B5BF5',
    iconBackground: '#EDEDFE',
    trendDirection: 'up',
    pointColor: '#4CAF50',
  },
)
</script>

<template>
  <v-card
    class="d-flex flex-column align-start pa-4"
    :style="{ borderRadius: '16px', padding: '16px', backgroundColor: '#FFFFFF' }"
    elevation="0"
    border
  >
    <div class="d-flex align-center justify-space-between mb-2">
      <span class="text-caption text-medium-emphasis">{{ label }}</span>
      <v-chip
        v-if="trend"
        size="x-small"
        variant="flat"
        :style="{ backgroundColor: `${'#4CAF50'}1A`, color: '#4CAF50', fontWeight: 600 }"
      >
        {{ trend }}
      </v-chip>
    </div>

    <div class="text-h5 font-weight-bold mb-2">{{ value }}</div>

    <div v-if="subtitle" class="text-body-2 text-medium-emphasis mb-2">
      {{ subtitle }}
    </div>

    <canvas
      ref="canvasRef"
      class="w-full h-16"
      :style="{ borderRadius: '8px' }"
    />
  </v-card>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'

const canvasRef = ref<HTMLCanvasElement | null>(null)

onMounted(() => {
  const canvas = canvasRef.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  if (!ctx) return
  const width = canvas.parentElement!.clientWidth
  const height = 96
  canvas.width = width
  canvas.height = height

  const points = [15, 45, 25, 65, 35, 40, 55, 20, 70, 30, 85, 15]
  ctx.clearRect(0, 0, width, height)
  ctx.beginPath()
  ctx.strokeStyle = '#4CAF50'
  ctx.lineWidth = 2.5
  ctx.lineCap = 'round'
  ctx.lineJoin = 'round'
  const step = width / (points.length - 1)
  points.forEach((y, i) => {
    const x = i * step
    const py = height - y * 1.2
    if (i === 0) ctx.moveTo(x, py)
    else ctx.lineTo(x, py)
  })
  ctx.stroke()

  points.forEach((y, i) => {
    const x = i * step
    const py = height - y * 1.2
    ctx.beginPath()
    ctx.fillStyle = '#4CAF50'
    ctx.arc(x, py, 3.5, 0, Math.PI * 2)
    ctx.fill()
  })
})
</script>
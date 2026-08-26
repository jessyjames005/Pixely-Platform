<script setup lang="ts">
// Temporary smoke test for the new reusable UI components
import BaseCard from '../components/ui/BaseCard.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseTable, { type TableColumn } from '../components/ui/BaseTable.vue'

const columns: TableColumn[] = [
  { key: 'name', label: 'Name' },
  { key: 'status', label: 'Status', align: 'center' },
]

const rows = [
  { name: 'Gallery', status: 'Enabled' },
  { name: 'Sample Cinema', status: 'Disabled' },
]
</script>

<template>
  <section>
    <h1>Dashboard</h1>
    <BaseCard title="Extensions">
      <BaseTable :columns="columns" :rows="rows" />
      <template #footer>
        <BaseButton variant="primary">Refresh</BaseButton>
      </template>
    </BaseCard>
  </section>
</template>

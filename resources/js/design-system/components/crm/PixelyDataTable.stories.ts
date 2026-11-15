import type { Meta, StoryObj } from '@storybook/vue3'
import PixelyDataTable from './PixelyDataTable.vue'

const meta: Meta<typeof PixelyDataTable> = {
  title: 'CRM / PixelyDataTable',
  component: PixelyDataTable,
  tags: ['autodocs'],
}

export default meta
type Story = StoryObj<typeof PixelyDataTable>

export const Default: Story = {
  render: () => ({
    components: { PixelyDataTable },
    template: `
      <PixelyDataTable
        title="Recent Activity"
        subtitle="Latest CRM records"
        :items="items"
        :columns="columns"
      />
    `,
    data() {
      return {
        items: [
          { id: 'CRM-1024', name: 'Acme Corp', status: 'Active', date: '15 Nov 2026' },
          { id: 'CRM-1025', name: 'Globex Inc', status: 'Pending', date: '14 Nov 2026' },
          { id: 'CRM-1026', name: 'Umbrella Ltd', status: 'Active', date: '13 Nov 2026' },
        ],
        columns: ['id', 'name', 'status', 'date'],
      }
    },
  }),
}

export const Empty: Story = {
  render: () => ({
    components: { PixelyDataTable },
    template: `
      <PixelyDataTable
        title="Recent Activity"
        subtitle="Latest CRM records"
        :items="[]"
        :columns="columns"
        emptyMessage="No CRM records found."
      />
    `,
    data() {
      return {
        columns: ['id', 'name', 'status', 'date'],
      }
    },
  }),
}
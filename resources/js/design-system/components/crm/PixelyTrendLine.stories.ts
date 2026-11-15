import type { Meta, StoryObj } from '@storybook/vue3'
import PixelyTrendLine from './PixelyTrendLine.vue'

const meta: Meta<typeof PixelyTrendLine> = {
  title: 'CRM / PixelyTrendLine',
  component: PixelyTrendLine,
  tags: ['autodocs'],
}

export default meta
type Story = StoryObj<typeof PixelyTrendLine>

export const Default: Story = {
  render: () => ({
    components: { PixelyTrendLine },
    template: `
      <PixelyTrendLine
        label="Organic Sessions"
        value="89k"
        subtitle="2022"
        trend="+12%"
        trendDirection="up"
        icon="mdi-chart-line"
        iconColor="#4CAF50"
        iconBackground="#E8F5E9"
      />
    `,
  }),
}
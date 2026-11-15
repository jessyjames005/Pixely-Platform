import type { Meta, StoryObj } from '@storybook/vue3'
import PixelyStatCard from './PixelyStatCard.vue'

const meta: Meta<typeof PixelyStatCard> = {
  title: 'Design System / PixelyStatCard',
  component: PixelyStatCard,
  tags: ['autodocs'],
}

export default meta
type Story = StoryObj<typeof PixelyStatCard>

export const Default: Story = {
  render: () => ({
    components: { PixelyStatCard },
    template: `
      <PixelyStatCard
        label="Total Orders"
        value="155k"
        trend="+22%"
        trendDirection="up"
        hint="Last 4 Month"
        icon="mdi-cart-outline"
        iconColor="#5B5BF5"
        iconBackground="#EDEDFE"
      />
    `,
  }),
}

export const Declining: Story = {
  render: () => ({
    components: { PixelyStatCard },
    template: `
      <PixelyStatCard
        label="New Customers"
        value="2,856"
        trend="-8.1%"
        trendDirection="down"
        icon="mdi-account-group-outline"
        iconColor="#EF5350"
        iconBackground="#FFEBEE"
      />
    `,
  }),
}
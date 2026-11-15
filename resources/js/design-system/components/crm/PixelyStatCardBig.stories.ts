import type { Meta, StoryObj } from '@storybook/vue3'
import PixelyStatCardBig from './PixelyStatCardBig.vue'

const meta: Meta<typeof PixelyStatCardBig> = {
  title: 'CRM / PixelyStatCardBig',
  component: PixelyStatCardBig,
  tags: ['autodocs'],
}

export default meta
type Story = StoryObj<typeof PixelyStatCardBig>

export const Default: Story = {
  render: () => ({
    components: { PixelyStatCardBig },
    template: `
      <PixelyStatCardBig
        label="Total Orders"
        value="155k"
        subtitle="Last 4 Month"
        trend="+22%"
        trendDirection="up"
        icon="mdi-cart-outline"
        iconColor="#5B5BF5"
        iconBackground="#EDEDFE"
      />
    `,
  }),
}

export const Declining: Story = {
  render: () => ({
    components: { PixelyStatCardBig },
    template: `
      <PixelyStatCardBig
        label="New Customers"
        value="2,856"
        subtitle="Down from last week"
        trend="-8.1%"
        trendDirection="down"
        icon="mdi-account-group-outline"
        iconColor="#EF5350"
        iconBackground="#FFEBEE"
      />
    `,
  }),
}
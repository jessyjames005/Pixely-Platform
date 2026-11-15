import type { Meta, StoryObj } from '@storybook/vue3'
import PixelyDashboardCard from './PixelyDashboardCard.vue'

const meta: Meta<typeof PixelyDashboardCard> = {
  title: 'CRM / PixelyDashboardCard',
  component: PixelyDashboardCard,
  tags: ['autodocs'],
}

export default meta
type Story = StoryObj<typeof PixelyDashboardCard>

export const Default: Story = {
  render: () => ({
    components: { PixelyDashboardCard },
    template: `
      <PixelyDashboardCard
        title="Overview"
        description="Summary of your CRM dashboard metrics."
      >
        <div class="row no-gutters mb-8">
          <PixelyStatCardBig
            label="Total Orders"
            value="155k"
            trend="+22%"
            trendDirection="up"
            icon="mdi-cart-outline"
          />
          <PixelyStatCardBig
            label="New Customers"
            value="2,856"
            trend="-8.1%"
            trendDirection="down"
            icon="mdi-account-group-outline"
          />
        </div>
      </PixelyDashboardCard>
    `,
  }),
}
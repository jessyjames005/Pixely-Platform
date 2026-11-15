import type { Meta, StoryObj } from '@storybook/vue3'
import PixelyEmptyStateCentered from './PixelyEmptyStateCentered.vue'

const meta: Meta<typeof PixelyEmptyStateCentered> = {
  title: 'CRM / PixelyEmptyStateCentered',
  component: PixelyEmptyStateCentered,
  tags: ['autodocs'],
}

export default meta
type Story = StoryObj<typeof PixelyEmptyStateCentered>

export const Default: Story = {
  render: () => ({
    components: { PixelyEmptyStateCentered },
    template: `
      <PixelyEmptyStateCentered
        title="No records found"
        subtitle="There are no CRM records to display at the moment."
        icon="mdi-inbox-outline"
      />
    `,
  }),
}
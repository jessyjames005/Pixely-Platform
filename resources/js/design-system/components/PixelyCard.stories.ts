import type { Meta, StoryObj } from '@storybook/vue3'
import PixelyCard from './PixelyCard.vue'

const meta: Meta<typeof PixelyCard> = {
  title: 'Design System / PixelyCard',
  component: PixelyCard,
  tags: ['autodocs'],
}

export default meta
type Story = StoryObj<typeof PixelyCard>

export const Default: Story = {
  render: () => ({
    components: { PixelyCard },
    template: `
      <PixelyCard title="Card Title" subtitle="Subtitle text" heading="Section Header" description="This is a standard Pixely design system card.">
        <p class="text-body-2 text-medium-emphasis">Card body content goes here. Use this container to group related information.</p>
      </PixelyCard>
    `,
  }),
}
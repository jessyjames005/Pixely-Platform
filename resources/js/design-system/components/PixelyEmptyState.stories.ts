import type { Meta, StoryObj } from '@storybook/vue3'
import PixelyEmptyState from './PixelyEmptyState.vue'

const meta: Meta<typeof PixelyEmptyState> = {
  title: 'Design System / PixelyEmptyState',
  component: PixelyEmptyState,
  tags: ['autodocs'],
}

export default meta
type Story = StoryObj<typeof PixelyEmptyState>

export const Default: Story = {
  render: () => ({
    components: { PixelyEmptyState },
    template: `
      <PixelyEmptyState
        title="No movies yet"
        description="Create your first movie and start building your catalogue."
        actionLabel="Add movie"
      />
    `,
  }),
}
import type { Meta, StoryObj } from '@storybook/vue3'
import PixelyCodeBlock from './PixelyCodeBlock.vue'

const meta: Meta<typeof PixelyCodeBlock> = {
  title: 'Design System / PixelyCodeBlock',
  component: PixelyCodeBlock,
  tags: ['autodocs'],
}

export default meta
type Story = StoryObj<typeof PixelyCodeBlock>

export const Default: Story = {
  render: () => ({
    components: { PixelyCodeBlock },
    template: `
      <PixelyCodeBlock
        code="const hello = 'Pixely Design System'"
        language="javascript"
      />
    `,
  }),
}
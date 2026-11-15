import type { Meta, StoryObj } from '@storybook/vue3'
import PixelyPageHeader from './PixelyPageHeader.vue'

const meta: Meta<typeof PixelyPageHeader> = {
  title: 'Design System / PixelyPageHeader',
  component: PixelyPageHeader,
  tags: ['autodocs'],
}

export default meta
type Story = StoryObj<typeof PixelyPageHeader>

export const Default: Story = {
  render: () => ({
    components: { PixelyPageHeader },
    template: `
      <PixelyPageHeader
        title="Movie Catalogue"
        subtitle="Manage your movies, genres, and translations"
        icon="mdi-movie-open-outline"
        actionLabel="Add Movie"
      />
    `,
  }),
}
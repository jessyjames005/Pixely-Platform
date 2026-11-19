import type { Meta, StoryObj } from '@storybook/vue3'
import CinemaCalendarAgenda from './CinemaCalendarAgenda.vue'

const meta: Meta<typeof CinemaCalendarAgenda> = {
  title: 'Extensions/CinemaMovie/CinemaCalendarAgenda',
  component: CinemaCalendarAgenda,
  tags: ['autodocs'],
}

export default meta
type Story = StoryObj<typeof CinemaCalendarAgenda>

export const Default: Story = {
  render: () => ({
    components: { CinemaCalendarAgenda },
    template: '<CinemaCalendarAgenda />',
  }),
}

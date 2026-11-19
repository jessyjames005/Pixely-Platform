import type { Meta, StoryObj } from '@storybook/vue3'
import PixelyCalendar from './PixelyCalendar.vue'

const meta: Meta<typeof PixelyCalendar> = {
  title: 'Design System / PixelyCalendar',
  component: PixelyCalendar,
  tags: ['autodocs'],
}

export default meta
type Story = StoryObj<typeof PixelyCalendar>

export const Default: Story = {
  render: (args) => ({
    components: { PixelyCalendar },
    setup: () => ({ args }),
    template: '<PixelyCalendar v-bind="args" />',
  }),
  parameters: {
    controls: { sortBy: 'required' },
  },
  args: {
    initialView: 'month',
    initialDate: '2024-01-15',
    events: [
      {
        id: '1',
        title: 'Team Meeting',
        start: '2024-01-15T09:00:00',
        end: '2024-01-15T10:00:00',
        category: 'business',
      },
      {
        id: '2',
        title: 'Lunch',
        start: '2024-01-15T12:00:00',
        allDay: true,
        category: 'personal',
      },
    ],
    categories: [
      { id: 'business', label: 'Business', color: '#5B5BF5' },
      { id: 'personal', label: 'Personal', color: '#4CAF50' },
    ],
  },
}

export const WeekView: Story = {
  args: {
    initialView: 'week',
    initialDate: '2024-01-15',
    events: [
      {
        id: '1',
        title: 'Team Meeting',
        start: '2024-01-15T09:00:00',
        end: '2024-01-15T10:00:00',
        category: 'business',
      },
    ],
    categories: [
      { id: 'business', label: 'Business', color: '#5B5BF5' },
      { id: 'personal', label: 'Personal', color: '#4CAF50' },
    ],
  },
}

export const DayView: Story = {
  args: {
    initialView: 'day',
    initialDate: '2024-01-15',
    events: [
      {
        id: '1',
        title: 'Team Meeting',
        start: '2024-01-15T09:00:00',
        end: '2024-01-15T10:00:00',
        category: 'business',
      },
    ],
    categories: [
      { id: 'business', label: 'Business', color: '#5B5BF5' },
      { id: 'personal', label: 'Personal', color: '#4CAF50' },
    ],
  },
}

export const AgendaView: Story = {
  args: {
    initialView: 'agenda',
    initialDate: '2024-01-15',
    events: [
      {
        id: '1',
        title: 'Team Meeting',
        start: '2024-01-15T09:00:00',
        end: '2024-01-15T10:00:00',
        category: 'business',
      },
      {
        id: '2',
        title: 'Client Call',
        start: '2024-01-15T14:00:00',
        end: '2024-01-15T15:00:00',
        category: 'personal',
      },
    ],
    categories: [
      { id: 'business', label: 'Business', color: '#5B5BF5' },
      { id: 'personal', label: 'Personal', color: '#4CAF50' },
      { id: 'family', label: 'Family', color: '#03A9F4' },
    ],
  },
}
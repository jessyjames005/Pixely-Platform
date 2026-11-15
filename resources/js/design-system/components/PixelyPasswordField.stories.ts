import type { Meta, StoryObj } from '@storybook/vue3'
import PixelyPasswordField from './PixelyPasswordField.vue'

const meta: Meta<typeof PixelyPasswordField> = {
  title: 'Design System / PixelyPasswordField',
  component: PixelyPasswordField,
  tags: ['autodocs'],
}

export default meta
type Story = StoryObj<typeof PixelyPasswordField>

export const Default: Story = {
  render: () => ({
    components: { PixelyPasswordField },
    template: `
      <PixelyPasswordField label="Password" modelValue="Secret123" />
    `,
  }),
}

export const WithError: Story = {
  render: () => ({
    components: { PixelyPasswordField },
    template: `
      <PixelyPasswordField
        label="Password"
        modelValue=""
        :error-messages="['Password is required.']"
      />
    `,
  }),
}
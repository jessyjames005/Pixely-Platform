import type { Meta, StoryObj } from '@storybook/vue3'
import PixelyForgotPasswordPage from './PixelyForgotPasswordPage.vue'

const meta: Meta<typeof PixelyForgotPasswordPage> = {
  title: 'Auth / PixelyForgotPasswordPage',
  component: PixelyForgotPasswordPage,
  tags: ['autodocs'],
}

export default meta
type Story = StoryObj<typeof PixelyForgotPasswordPage>

export const Default: Story = {
  render: () => ({
    components: { PixelyForgotPasswordPage },
    template: `<PixelyForgotPasswordPage />`,
  }),
}
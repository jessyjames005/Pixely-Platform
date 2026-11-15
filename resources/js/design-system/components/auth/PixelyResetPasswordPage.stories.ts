import type { Meta, StoryObj } from '@storybook/vue3'
import PixelyResetPasswordPage from './PixelyResetPasswordPage.vue'

const meta: Meta<typeof PixelyResetPasswordPage> = {
  title: 'Auth / PixelyResetPasswordPage',
  component: PixelyResetPasswordPage,
  tags: ['autodocs'],
}

export default meta
type Story = StoryObj<typeof PixelyResetPasswordPage>

export const Default: Story = {
  render: () => ({
    components: { PixelyResetPasswordPage },
    template: `<PixelyResetPasswordPage />`,
  }),
}
import type { Meta, StoryObj } from '@storybook/vue3'
import PixelyVerifyEmailPage from './PixelyVerifyEmailPage.vue'

const meta: Meta<typeof PixelyVerifyEmailPage> = {
  title: 'Auth / PixelyVerifyEmailPage',
  component: PixelyVerifyEmailPage,
  tags: ['autodocs'],
}

export default meta
type Story = StoryObj<typeof PixelyVerifyEmailPage>

export const Default: Story = {
  render: () => ({
    components: { PixelyVerifyEmailPage },
    template: `<PixelyVerifyEmailPage email="hello@example.com" />`,
  }),
}
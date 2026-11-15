import type { Meta, StoryObj } from '@storybook/vue3'
import PixelyLoginPage from './PixelyLoginPage.vue'

const meta: Meta<typeof PixelyLoginPage> = {
  title: 'Auth / PixelyLoginPage',
  component: PixelyLoginPage,
  tags: ['autodocs'],
}

export default meta
type Story = StoryObj<typeof PixelyLoginPage>

export const Default: Story = {
  render: () => ({
    components: { PixelyLoginPage },
    template: `<PixelyLoginPage title="Login" subtitle="Please sign-in to your account and start the adventure" email="johndoe@email.com" />`,
  }),
}
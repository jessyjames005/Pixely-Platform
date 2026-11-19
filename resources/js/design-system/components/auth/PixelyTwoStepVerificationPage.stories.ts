import type { Meta, StoryObj } from '@storybook/vue3'
import PixelyTwoStepVerificationPage from './PixelyTwoStepVerificationPage.vue'

const meta: Meta<typeof PixelyTwoStepVerificationPage> = {
  title: 'Auth / PixelyTwoStepVerificationPage',
  component: PixelyTwoStepVerificationPage,
  tags: ['autodocs'],
}

export default meta
type Story = StoryObj<typeof PixelyTwoStepVerificationPage>

export const Default: Story = {
  render: () => ({
    components: { PixelyTwoStepVerificationPage },
    template: `<PixelyTwoStepVerificationPage email="*******1234" />`,
  }),
}
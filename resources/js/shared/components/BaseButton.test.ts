// Unit tests for BaseButton
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseButton from './BaseButton.vue'

describe('BaseButton', () => {
  it('renders its slot content', () => {
    const wrapper = mount(BaseButton, { slots: { default: 'Click me' } })
    expect(wrapper.text()).toBe('Click me')
  })

  it('emits click when clicked', async () => {
    const wrapper = mount(BaseButton)
    await wrapper.trigger('click')
    expect(wrapper.emitted('click')).toHaveLength(1)
  })

  it('does not emit click when disabled', async () => {
    const wrapper = mount(BaseButton, { props: { disabled: true } })
    await wrapper.trigger('click')
    expect(wrapper.emitted('click')).toBeUndefined()
  })

  it('does not emit click when loading', async () => {
    const wrapper = mount(BaseButton, { props: { loading: true } })
    await wrapper.trigger('click')
    expect(wrapper.emitted('click')).toBeUndefined()
  })

  it('applies the correct variant and size classes', () => {
    const wrapper = mount(BaseButton, { props: { variant: 'danger', size: 'lg' } })
    expect(wrapper.classes()).toContain('base-button--danger')
    expect(wrapper.classes()).toContain('base-button--lg')
  })

  it('defaults to type="button"', () => {
    const wrapper = mount(BaseButton)
    expect(wrapper.attributes('type')).toBe('button')
  })
})

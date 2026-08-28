// Unit tests for BasePagination
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BasePagination from './BasePagination.vue'

describe('BasePagination', () => {
  it('displays the current and last page', () => {
    const wrapper = mount(BasePagination, { props: { currentPage: 2, lastPage: 5 } })
    expect(wrapper.text()).toContain('Page 2 / 5')
  })

  it('disables Previous on the first page', () => {
    const wrapper = mount(BasePagination, { props: { currentPage: 1, lastPage: 5 } })
    const [previous] = wrapper.findAll('button')
    expect(previous.attributes('disabled')).toBeDefined()
  })

  it('disables Next on the last page', () => {
    const wrapper = mount(BasePagination, { props: { currentPage: 5, lastPage: 5 } })
    const [, next] = wrapper.findAll('button')
    expect(next.attributes('disabled')).toBeDefined()
  })

  it('emits update:page with the next page number', async () => {
    const wrapper = mount(BasePagination, { props: { currentPage: 2, lastPage: 5 } })
    const [, next] = wrapper.findAll('button')
    await next.trigger('click')
    expect(wrapper.emitted('update:page')).toEqual([[3]])
  })

  it('does not emit when disabled', async () => {
    const wrapper = mount(BasePagination, {
      props: { currentPage: 2, lastPage: 5, disabled: true },
    })
    const [, next] = wrapper.findAll('button')
    await next.trigger('click')
    expect(wrapper.emitted('update:page')).toBeUndefined()
  })
})

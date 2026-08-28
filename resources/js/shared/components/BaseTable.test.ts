// Unit tests for BaseTable
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseTable, { type TableColumn } from './BaseTable.vue'

const columns: TableColumn[] = [
  { key: 'name', label: 'Name' },
  { key: 'status', label: 'Status', align: 'center' },
]

describe('BaseTable', () => {
  it('renders column headers', () => {
    const wrapper = mount(BaseTable, { props: { columns, rows: [] } })
    expect(wrapper.text()).toContain('Name')
    expect(wrapper.text()).toContain('Status')
  })

  it('shows the empty state text when there are no rows', () => {
    const wrapper = mount(BaseTable, {
      props: { columns, rows: [], emptyText: 'Nothing here.' },
    })
    expect(wrapper.text()).toContain('Nothing here.')
  })

  it('shows a loading state instead of rows', () => {
    const wrapper = mount(BaseTable, {
      props: { columns, rows: [{ name: 'Gallery', status: 'Enabled' }], loading: true },
    })
    expect(wrapper.text()).toContain('Loading…')
    expect(wrapper.text()).not.toContain('Gallery')
  })

  it('renders row data matching each column key', () => {
    const wrapper = mount(BaseTable, {
      props: { columns, rows: [{ name: 'Gallery', status: 'Enabled' }] },
    })
    expect(wrapper.text()).toContain('Gallery')
    expect(wrapper.text()).toContain('Enabled')
  })

  it('renders a named slot for a column instead of the raw value', () => {
    const wrapper = mount(BaseTable, {
      props: { columns, rows: [{ name: 'Gallery', status: 'Enabled' }] },
      slots: {
        status: '<template #status="{ value }"><span class="badge">{{ value }}</span></template>',
      },
    })
    expect(wrapper.find('.badge').exists()).toBe(true)
  })
})

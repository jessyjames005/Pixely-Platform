import { describe, expect, it } from 'vitest'
import { deserializeCollection, deserializeResource, type JsonApiResource } from './api'

describe('deserializeResource', () => {
  it('spreads the attributes of a JSON:API resource', () => {
    const model = deserializeResource({
      type: 'users',
      id: '1',
      attributes: { name: 'Jane', enabled: true },
    } as JsonApiResource<{ name: string; enabled: boolean }>)

    expect(model).toMatchObject({ id: '1', type: 'users', name: 'Jane', enabled: true })
  })

  it('keeps the fields of a flat resource such as /extensions entries', () => {
    const flat = {
      id: 'gallery',
      name: 'Gallery',
      version: '1.0.0',
      dependencies: [],
      enabled: true,
    } as unknown as JsonApiResource<{ name: string; version: string; dependencies: string[]; enabled: boolean }>

    const { resources } = deserializeCollection({ data: [flat] })

    expect(resources[0]).toMatchObject({ id: 'gallery', name: 'Gallery', enabled: true })
  })
})

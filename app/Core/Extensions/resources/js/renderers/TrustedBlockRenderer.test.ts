import { defineComponent } from 'vue'
import { describe, expect, it } from 'vitest'
import type { ExtensionBlockDefinition } from '../models/ExtensionCapability'
import {
  createTrustedBlockRendererRegistry,
  resolveTrustedBlockRenderer,
} from './TrustedBlockRenderer'

const component = defineComponent({ name: 'TrustedTestBlock' })
const renderer = {
  qualifiedId: 'website.hero',
  surfaces: ['public', 'admin'] as const,
  component,
}
const definition: ExtensionBlockDefinition = {
  id: 'hero',
  label: 'Hero',
  extension_id: 'website',
  qualified_id: 'website.hero',
  surfaces: ['public', 'admin'],
  schema: { title: { type: 'string' } },
}

describe('trusted block renderer contract', () => {
  it('resolves a local allowlisted renderer when both declarations permit the surface', () => {
    const registry = createTrustedBlockRendererRegistry([renderer])

    expect(resolveTrustedBlockRenderer(definition, 'public', registry)).toBe(renderer)
    expect(resolveTrustedBlockRenderer(definition, 'user', registry)).toBeNull()
  })

  it('does not resolve a renderer based on metadata alone', () => {
    const registry = createTrustedBlockRendererRegistry([])

    expect(resolveTrustedBlockRenderer(definition, 'public', registry)).toBeNull()
  })

  it('rejects renderers with malformed qualified IDs', () => {
    expect(() => createTrustedBlockRendererRegistry([{
      ...renderer,
      qualifiedId: '../remote-component',
    }])).toThrow('Invalid trusted block renderer ID')
  })

  it('rejects duplicate renderer IDs', () => {
    expect(() => createTrustedBlockRendererRegistry([renderer, renderer]))
      .toThrow('Duplicate trusted block renderer ID')
  })

  it('rejects empty, duplicated, or unsupported surface declarations', () => {
    expect(() => createTrustedBlockRendererRegistry([{ ...renderer, surfaces: [] }]))
      .toThrow('Invalid surfaces')
    expect(() => createTrustedBlockRendererRegistry([{ ...renderer, surfaces: ['public', 'public'] }]))
      .toThrow('Invalid surfaces')
    expect(() => createTrustedBlockRendererRegistry([{ ...renderer, surfaces: ['private'] as never }]))
      .toThrow('Invalid surfaces')
  })
})

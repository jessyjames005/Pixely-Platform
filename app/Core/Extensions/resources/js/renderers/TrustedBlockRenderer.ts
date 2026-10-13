import type { Component } from 'vue'
import type { Surface } from '@shared/surface'
import type { ExtensionBlockDefinition } from '../models/ExtensionCapability'

/** Props every trusted extension block renderer must accept. */
export interface TrustedBlockRendererProps {
  /** Validated, non-executable metadata returned by the Core API. */
  definition: ExtensionBlockDefinition
  /** User/page configuration for this block instance. */
  value: Record<string, unknown>
  /** Surface where the block is about to be rendered. */
  surface: Surface
}

/** A renderer bundled with Pixely's frontend and explicitly allowlisted by Core. */
export interface TrustedBlockRendererDefinition {
  /** Stable namespaced identifier, for example `website.hero`. */
  qualifiedId: string
  /** Surfaces on which this local component is approved to render. */
  surfaces: readonly Surface[]
  /** A trusted local Vue component; never resolved from API-provided paths. */
  component: Component
}

export type TrustedBlockRendererRegistry = ReadonlyMap<string, TrustedBlockRendererDefinition>

const VALID_SURFACES: readonly Surface[] = ['public', 'user', 'admin', 'api']
const QUALIFIED_ID_PATTERN = /^[a-z][a-z0-9-]*\.[a-z][a-z0-9-]*$/

/**
 * Build an immutable-by-contract renderer lookup from locally imported components.
 * Invalid IDs, invalid surfaces, and duplicate IDs fail fast during development/build.
 */
export function createTrustedBlockRendererRegistry(
  definitions: readonly TrustedBlockRendererDefinition[],
): TrustedBlockRendererRegistry {
  const registry = new Map<string, TrustedBlockRendererDefinition>()

  for (const definition of definitions) {
    if (!QUALIFIED_ID_PATTERN.test(definition.qualifiedId)) {
      throw new Error(`Invalid trusted block renderer ID: ${definition.qualifiedId}`)
    }

    if (registry.has(definition.qualifiedId)) {
      throw new Error(`Duplicate trusted block renderer ID: ${definition.qualifiedId}`)
    }

    if (
      definition.surfaces.length === 0 ||
      definition.surfaces.some((surface) => !VALID_SURFACES.includes(surface)) ||
      new Set(definition.surfaces).size !== definition.surfaces.length
    ) {
      throw new Error(`Invalid surfaces for trusted block renderer: ${definition.qualifiedId}`)
    }

    registry.set(definition.qualifiedId, definition)
  }

  return registry
}

/**
 * Resolve only an explicitly allowlisted local renderer and require both the
 * declaration and the renderer to permit the requested surface.
 */
export function resolveTrustedBlockRenderer(
  definition: ExtensionBlockDefinition,
  surface: Surface,
  registry: TrustedBlockRendererRegistry,
): TrustedBlockRendererDefinition | null {
  const renderer = registry.get(definition.qualified_id)

  if (!renderer || !definition.surfaces.includes(surface) || !renderer.surfaces.includes(surface)) {
    return null
  }

  return renderer
}

/**
 * Renderer registrations are intentionally empty until a block ships with a
 * reviewed local Vue component. API metadata alone can never register a renderer.
 */
export const trustedBlockRenderers = createTrustedBlockRendererRegistry([])

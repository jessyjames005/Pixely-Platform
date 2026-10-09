import type { Surface } from '@shared/surface'
import type { NavItem } from '@shared/navigation/types'

/** Public, non-executable metadata declared by an extension capability. */
export interface ExtensionBlockDefinition {
  id: string
  label: string
  extension_id: string
  qualified_id: string
  surfaces: Surface[]
  schema: Record<string, unknown>
}

/** Raw response envelope used by Core capability endpoints. */
export interface CapabilityCollection<T> {
  data: T[]
  meta?: { total?: number }
}

const VALID_SURFACES: Surface[] = ['public', 'user', 'admin', 'api']

/** Narrow untrusted API values into the navigation contract used by the UI. */
export function parseNavigationItems(value: unknown): NavItem[] {
  if (!Array.isArray(value)) return []

  return value.flatMap((candidate): NavItem[] => {
    if (!candidate || typeof candidate !== 'object') return []
    const item = candidate as Record<string, unknown>
    if (typeof item.label !== 'string' || typeof item.to !== 'string' || typeof item.icon !== 'string') return []

    const children = parseNavigationItems(item.children)
    const declaredSurfaces = Array.isArray(item.surfaces)
      ? item.surfaces
      : (typeof item.surface === 'string' ? [item.surface] : undefined)
    const surfaces = declaredSurfaces
      ? declaredSurfaces.filter((surface): surface is Surface => typeof surface === 'string' && VALID_SURFACES.includes(surface as Surface))
      : undefined

    return [{
      label: item.label,
      to: item.to,
      icon: item.icon,
      ...(typeof item.permission === 'string' ? { permission: item.permission } : {}),
      ...(typeof item.extensionId === 'string' ? { extensionId: item.extensionId } : {}),
      ...(typeof item.order === 'number' ? { order: item.order } : {}),
      ...(surfaces?.length ? { surfaces } : {}),
      ...(children.length ? { children } : {}),
    }]
  })
}

/** Keep only valid block declarations intended for the requested surface. */
export function filterBlocksForSurface(value: unknown, surface: Surface): ExtensionBlockDefinition[] {
  if (!Array.isArray(value)) return []

  return value.flatMap((candidate): ExtensionBlockDefinition[] => {
    if (!candidate || typeof candidate !== 'object') return []
    const block = candidate as Record<string, unknown>
    if (
      typeof block.id !== 'string' ||
      typeof block.label !== 'string' ||
      typeof block.extension_id !== 'string' ||
      typeof block.qualified_id !== 'string' ||
      !Array.isArray(block.surfaces) ||
      !block.surfaces.includes(surface) ||
      !block.schema || typeof block.schema !== 'object' || Array.isArray(block.schema)
    ) return []

    const surfaces = block.surfaces.filter(
      (candidateSurface): candidateSurface is Surface =>
        typeof candidateSurface === 'string' && VALID_SURFACES.includes(candidateSurface as Surface),
    )

    return [{
      id: block.id,
      label: block.label,
      extension_id: block.extension_id,
      qualified_id: block.qualified_id,
      surfaces,
      schema: block.schema as Record<string, unknown>,
    }]
  })
}

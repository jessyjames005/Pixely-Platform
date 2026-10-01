// Shared TypeScript types matching the Pixely Platform API response contracts

// Pagination metadata returned alongside collection responses
export interface JsonApiResourceIdentifier {
  type: string
  id: string
  meta?: Record<string, unknown>
}

export interface JsonApiResource<TAttributes extends object = Record<string, unknown>>
  extends JsonApiResourceIdentifier {
  attributes: TAttributes
  relationships?: Record<string, unknown>
  links?: Record<string, unknown>
}

export interface JsonApiDocument<TData> {
  data: TData
  included?: JsonApiResource[]
  links?: Record<string, unknown>
  meta?: Record<string, unknown>
}

export interface JsonApiErrorSource {
  pointer?: string
  parameter?: string
  header?: string
}

export interface JsonApiError {
  id?: string
  links?: Record<string, unknown>
  status?: string
  code?: string
  title?: string
  detail?: string
  source?: JsonApiErrorSource
  meta?: Record<string, unknown>
}

export interface JsonApiErrorDocument {
  errors: JsonApiError[]
  jsonapi?: { version?: string; meta?: Record<string, unknown> }
}

export type JsonApiModel<TAttributes extends object> = TAttributes &
  JsonApiResourceIdentifier & {
    relationships?: Record<string, unknown>
    resourceMeta?: Record<string, unknown>
    resourceLinks?: Record<string, unknown>
    documentMeta?: Record<string, unknown>
    documentLinks?: Record<string, unknown>
    included?: JsonApiResource[]
  }

export interface JsonApiCollectionResult<TResource> {
  resources: TResource[]
  meta?: Record<string, unknown>
  links?: Record<string, unknown>
  included?: JsonApiResource[]
}

export interface PaginationMeta extends Record<string, unknown> {
  current_page?: number
  last_page?: number
  per_page?: number
  total?: number
}

export function serializeResource<TAttributes extends object>(
  type: string,
  attributes: TAttributes,
  id?: string | number,
): JsonApiDocument<JsonApiResource<TAttributes>> {
  return {
    data: {
      type,
      ...(id === undefined ? {} : { id: String(id) }),
      attributes,
    },
  }
}

export function serializeRelationship(
  resources: JsonApiResourceIdentifier[],
): JsonApiDocument<JsonApiResourceIdentifier[]> {
  return { data: resources.map(({ type, id }) => ({ type, id })) }
}

export function deserializeResource<TAttributes extends object>(
  resource: JsonApiResource<TAttributes>,
): JsonApiModel<TAttributes> {
  return {
    ...resource.attributes,
    id: resource.id,
    type: resource.type,
    ...(resource.relationships ? { relationships: resource.relationships } : {}),
    ...(resource.meta ? { resourceMeta: resource.meta } : {}),
    ...(resource.links ? { resourceLinks: resource.links } : {}),
  }
}

export function deserializeDocument<TAttributes extends object>(
  document: JsonApiDocument<JsonApiResource<TAttributes> | null>,
): JsonApiModel<TAttributes> | null {
  if (document.data === null) return null

  return {
    ...deserializeResource(document.data),
    ...(document.meta ? { documentMeta: document.meta } : {}),
    ...(document.links ? { documentLinks: document.links } : {}),
    ...(document.included ? { included: document.included } : {}),
  }
}

export function deserializeCollection<TAttributes extends object>(
  document: JsonApiDocument<JsonApiResource<TAttributes>[]>,
): JsonApiCollectionResult<JsonApiModel<TAttributes>> {
  return {
    resources: document.data.map(deserializeResource),
    ...(document.meta ? { meta: document.meta } : {}),
    ...(document.links ? { links: document.links } : {}),
    ...(document.included ? { included: document.included } : {}),
  }
}

export function decodeJsonApiId(id: string): string[] | null {
  try {
    const base64 = id.replace(/-/g, '+').replace(/_/g, '/')
    const padded = base64.padEnd(Math.ceil(base64.length / 4) * 4, '=')
    const bytes = Uint8Array.from(atob(padded), (character) => character.charCodeAt(0))
    const parsed: unknown = JSON.parse(new TextDecoder().decode(bytes))
    if (!Array.isArray(parsed) || parsed.some((part) => typeof part !== 'string')) return null
    return parsed as string[]
  } catch {
    return null
  }
}

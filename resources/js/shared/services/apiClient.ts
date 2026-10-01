// Centralized HTTP client for the Pixely Platform API
import {
  deserializeCollection,
  deserializeDocument,
  serializeResource,
  type JsonApiCollectionResult,
  type JsonApiDocument,
  type JsonApiError,
  type JsonApiErrorDocument,
  type JsonApiModel,
  type JsonApiResource,
} from '../types/api'

// Base path for all Platform API requests
const API_BASE_URL = '/api/v1'

// Error thrown for any non-2xx API response, carrying the API error payload
export class ApiClientError extends Error {
  public readonly code: string
  public readonly status: number
  public readonly errors: JsonApiError[]
  public readonly title: string
  public readonly detail: string
  public readonly source: JsonApiError['source'] | null

  constructor(
    status: number,
    code: string,
    message: string,
    errors: JsonApiError[] = [],
  ) {
    super(message)
    this.name = 'ApiClientError'
    this.status = status
    this.code = code
    this.errors = errors
    this.title = errors[0]?.title ?? message
    this.detail = errors[0]?.detail ?? message
    this.source = errors[0]?.source ?? null
  }
}

// Query parameters accepted by GET requests
type QueryParams = Record<string, string | number | boolean | undefined | null>

// Builds a query string from a plain object, skipping null/undefined values
function buildQueryString(params?: QueryParams): string {
  if (!params) return ''

  const searchParams = new URLSearchParams()
  for (const [key, value] of Object.entries(params)) {
    if (value !== undefined && value !== null) searchParams.append(key, String(value))
  }

  const query = searchParams.toString()
  return query ? `?${query}` : ''
}

// Builds the fetch body/headers depending on the payload type.
// FormData is sent as-is (browser sets the multipart boundary),
// Plain objects are JSON:API documents serialized with the required media type.
function buildBody(body?: unknown): Pick<RequestInit, 'body' | 'headers'> {
  if (body === undefined) return {}
  if (body instanceof FormData) return { body }

  return {
    body: JSON.stringify(body),
    headers: {
      'Content-Type': 'application/vnd.api+json',
    },
  }
}

// Reads a cookie value by name (used to read Laravel's XSRF-TOKEN cookie)
function readCookie(name: string): string | null {
  const match = document.cookie.split('; ').find((row) => row.startsWith(`${name}=`))
  return match ? decodeURIComponent(match.split('=').slice(1).join('=')) : null
}

// Performs a fetch call and normalizes success/error handling.
// Always sends credentials (session cookie) and, when available,
// the XSRF token required by Laravel/Sanctum for stateful requests.
async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
  const headers = new Headers(options.headers)
  headers.set('Accept', 'application/vnd.api+json')

  if (options.body instanceof FormData) {
    headers.delete('Content-Type')
  } else if (options.body !== undefined) {
    headers.set('Content-Type', 'application/vnd.api+json')
  }

  const xsrfToken = readCookie('XSRF-TOKEN')
  if (xsrfToken) headers.set('X-XSRF-TOKEN', xsrfToken)

  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    credentials: 'include',
    headers,
  })

  if (response.status === 204) return undefined as T

  const payload: unknown = await response.json()
  if (!response.ok) {
    const errorDocument = payload as JsonApiErrorDocument
    const errors = Array.isArray(errorDocument.errors) ? errorDocument.errors : []
    const firstError = errors[0]
    throw new ApiClientError(
      response.status,
      firstError?.code ?? 'UNKNOWN_ERROR',
      firstError?.detail ?? firstError?.title ?? 'An unexpected error occurred.',
      errors,
    )
  }

  return payload as T
}

// Sends a GET request with optional query parameters
function get<T>(path: string, params?: QueryParams): Promise<T> {
  return request<T>(`${path}${buildQueryString(params)}`, {
    method: 'GET',
  })
}

// Sends a POST request with a JSON or FormData body
function post<T>(path: string, body?: unknown): Promise<T> {
  return request<T>(path, {
    method: 'POST',
    ...buildBody(body),
  })
}
// Sends a PUT request with a JSON or FormData body
function put<T>(path: string, body?: unknown): Promise<T> {
  return request<T>(path, {
    method: 'PUT',
    ...buildBody(body),
  })
}

// Sends a DELETE request
function del<T>(path: string): Promise<T> {
  return request<T>(path, {
    method: 'DELETE',
  })
}

// Fetches the CSRF cookie required by Sanctum before any stateful
// (session-authenticated) request, typically before login.
export function fetchCsrfCookie(): Promise<void> {
  return fetch('/sanctum/csrf-cookie', { credentials: 'include' }).then(() => undefined)
}

async function getResource<TAttributes extends object>(
  path: string,
  params?: QueryParams,
): Promise<JsonApiModel<TAttributes> | null> {
  const document = await get<JsonApiDocument<JsonApiResource<TAttributes> | null>>(path, params)
  return deserializeDocument(document)
}

async function getCollection<TAttributes extends object>(
  path: string,
  params?: QueryParams,
): Promise<JsonApiCollectionResult<JsonApiModel<TAttributes>>> {
  const document = await get<JsonApiDocument<JsonApiResource<TAttributes>[]>>(path, params)
  return deserializeCollection(document)
}

async function postResource<TAttributes extends object>(
  path: string,
  type: string,
  attributes: Partial<TAttributes>,
  id?: string | number,
): Promise<JsonApiModel<TAttributes> | null> {
  const document = await post<JsonApiDocument<JsonApiResource<TAttributes>>>(
    path,
    serializeResource(type, attributes, id),
  )
  return deserializeDocument(document)
}

async function postFormResource<TAttributes extends object>(
  path: string,
  formData: FormData,
): Promise<JsonApiModel<TAttributes> | null> {
  const document = await post<JsonApiDocument<JsonApiResource<TAttributes>>>(path, formData)
  return deserializeDocument(document)
}

async function putResource<TAttributes extends object>(
  path: string,
  type: string,
  attributes: Partial<TAttributes>,
  id?: string | number,
): Promise<JsonApiModel<TAttributes> | null> {
  const document = await put<JsonApiDocument<JsonApiResource<TAttributes>>>(
    path,
    serializeResource(type, attributes, id),
  )
  return deserializeDocument(document)
}

function patch<T>(path: string, body: unknown): Promise<T> {
  return request<T>(path, { method: 'PATCH', ...buildBody(body) })
}

async function patchResource<TAttributes extends object>(
  path: string,
  type: string,
  attributes: Partial<TAttributes>,
  id?: string | number,
): Promise<JsonApiModel<TAttributes> | null> {
  const document = await patch<JsonApiDocument<JsonApiResource<TAttributes>>>(
    path,
    serializeResource(type, attributes, id),
  )
  return deserializeDocument(document)
}

export const apiClient = {
  get,
  getResource,
  getCollection,
  post,
  postResource,
  postFormResource,
  put,
  putResource,
  patch,
  patchResource,
  delete: del,
}

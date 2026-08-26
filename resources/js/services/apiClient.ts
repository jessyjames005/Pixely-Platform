// Centralized HTTP client for the Pixely Platform API
import type { ApiErrorResponse } from '../types/api'

// Base path for all Platform API requests
const API_BASE_URL = '/api/v1'

// Error thrown for any non-2xx API response, carrying the API error payload
export class ApiClientError extends Error {
  public readonly code: string
  public readonly status: number
  public readonly details: Record<string, unknown> | null

  constructor(
    status: number,
    code: string,
    message: string,
    details: Record<string, unknown> | null = null,
  ) {
    super(message)
    this.name = 'ApiClientError'
    this.status = status
    this.code = code
    this.details = details
  }
}

// Query parameters accepted by GET requests
type QueryParams = Record<string, string | number | boolean | undefined | null>

// Builds a query string from a plain object, skipping null/undefined values
function buildQueryString(params?: QueryParams): string {
  if (!params) {
    return ''
  }

  const searchParams = new URLSearchParams()

  for (const [key, value] of Object.entries(params)) {
    if (value !== undefined && value !== null) {
      searchParams.append(key, String(value))
    }
  }

  const query = searchParams.toString()
  return query ? `?${query}` : ''
}

// Builds the fetch body/headers depending on the payload type.
// FormData is sent as-is (browser sets the multipart boundary),
// plain objects are serialized to JSON.
function buildBody(body?: unknown): Pick<RequestInit, 'body' | 'headers'> {
  if (body === undefined) {
    return {}
  }

  if (body instanceof FormData) {
    return { body }
  }

  return {
    body: JSON.stringify(body),
    headers: {
      'Content-Type': 'application/json',
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
  const xsrfToken = readCookie('XSRF-TOKEN')

  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      ...(xsrfToken ? { 'X-XSRF-TOKEN': xsrfToken } : {}),
      ...options.headers,
    },
  })

  // No content: nothing to parse (e.g. DELETE/logout responses)
  if (response.status === 204) {
    return undefined as T
  }

  const payload = await response.json()

  if (!response.ok) {
    const errorPayload = (payload as ApiErrorResponse).error
    throw new ApiClientError(
      response.status,
      errorPayload?.code ?? 'UNKNOWN_ERROR',
      errorPayload?.message ?? 'An unexpected error occurred.',
      errorPayload?.details ?? null,
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

// Public API client used across the administration frontend
export const apiClient = {
  get,
  post,
  put,
  delete: del,
}

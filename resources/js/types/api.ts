// Shared TypeScript types matching the Pixely Platform API response contracts

// Pagination metadata returned alongside collection responses
export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

// Envelope for a single-resource API response: { data: T }
export interface ApiResponse<T> {
  data: T
}

// Envelope for a collection API response: { data: T[], meta: PaginationMeta }
export interface ApiCollectionResponse<T> {
  data: T[]
  meta: PaginationMeta
}

// Shape of the "error" object inside an API error payload
export interface ApiErrorPayload {
  code: string
  message: string
  details?: Record<string, unknown> | null
}

// Full API error envelope: { error: { code, message, details? } }
export interface ApiErrorResponse {
  error: ApiErrorPayload
}

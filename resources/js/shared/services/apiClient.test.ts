// Unit tests for the centralized API client
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { apiClient, ApiClientError, fetchCsrfCookie } from './apiClient'
import {
  deserializeCollection,
  deserializeDocument,
  decodeJsonApiId,
  serializeRelationship,
  serializeResource,
} from '../types/api'
import type { JsonApiDocument, JsonApiResource } from '../types/api'

describe('apiClient', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', vi.fn())
  })

  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it('sends a GET request with the correct URL and query params', async () => {
    const mockResponse: JsonApiDocument<JsonApiResource<{ title: string }>[]> = {
      data: [{ id: '1', type: 'photos', attributes: { title: 'Sunset' } }],
      meta: { page: { currentPage: 1 } },
    }
    vi.mocked(fetch).mockResolvedValueOnce(
      new Response(JSON.stringify(mockResponse), { status: 200 }),
    )

    const result = await apiClient.get('/photos', { 'page[number]': 2, 'page[size]': 10 })

    expect(fetch).toHaveBeenCalledWith(
      '/api/v1/photos?page%5Bnumber%5D=2&page%5Bsize%5D=10',
      expect.objectContaining({ method: 'GET', credentials: 'include' }),
    )
    const [, options] = vi.mocked(fetch).mock.calls[0]
    expect(new Headers(options?.headers).get('Accept')).toBe('application/vnd.api+json')
    expect(result).toEqual(mockResponse)
  })

  it('sends a POST request with a JSON body', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(
      new Response(JSON.stringify({ data: { id: '1', type: 'photos', attributes: { title: 'Sunset' } } }), { status: 201 }),
    )

    await apiClient.post('/photos', {
      data: { type: 'photos', attributes: { title: 'Sunset' } },
    })

    expect(fetch).toHaveBeenCalledWith(
      '/api/v1/photos',
      expect.objectContaining({
        method: 'POST',
        body: JSON.stringify({ data: { type: 'photos', attributes: { title: 'Sunset' } } }),
      }),
    )
    const [, options] = vi.mocked(fetch).mock.calls[0]
    expect(new Headers(options?.headers).get('Content-Type')).toBe('application/vnd.api+json')
  })

  it('sends FormData bodies without a Content-Type header override', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(
      new Response(JSON.stringify({ data: { id: '2', type: 'files', attributes: {} } }), { status: 201 }),
    )

    const formData = new FormData()
    formData.append('image', new Blob(['fake']))

    await apiClient.post('/files', formData)

    const [, options] = vi.mocked(fetch).mock.calls[0]
    expect(options?.body).toBe(formData)
    expect(new Headers(options?.headers).get('Content-Type')).toBeNull()
    expect(new Headers(options?.headers).get('Accept')).toBe('application/vnd.api+json')
  })

  it('returns undefined for a 204 No Content response', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(new Response(null, { status: 204 }))

    const result = await apiClient.delete('/gallery/1')

    expect(result).toBeUndefined()
  })

  it('throws an ApiClientError with the API error payload on failure', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(
      new Response(
        JSON.stringify({
          errors: [
            {
              status: '404',
              code: 'RESOURCE_NOT_FOUND',
              title: 'Not Found',
              detail: 'Not found.',
              source: { pointer: '/data/id' },
            },
            { status: '404', code: 'RELATED_RESOURCE_NOT_FOUND', detail: 'Related resource missing.' },
          ],
        }),
        { status: 404 },
      ),
    )

    await expect(apiClient.get('/gallery/999')).rejects.toMatchObject({
      status: 404,
      code: 'RESOURCE_NOT_FOUND',
      message: 'Not found.',
      title: 'Not Found',
      source: { pointer: '/data/id' },
      errors: expect.arrayContaining([
        expect.objectContaining({ code: 'RELATED_RESOURCE_NOT_FOUND' }),
      ]),
    })
  })

  it('throws an ApiClientError instance', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(
      new Response(JSON.stringify({ errors: [{ code: 'X', detail: 'Y' }] }), { status: 500 }),
    )

    await expect(apiClient.get('/x')).rejects.toBeInstanceOf(ApiClientError)
  })
})

describe('fetchCsrfCookie', () => {
  it('requests the Sanctum CSRF cookie endpoint with credentials', async () => {
    const fetchMock = vi.fn().mockResolvedValueOnce(new Response(null, { status: 204 }))
    vi.stubGlobal('fetch', fetchMock)

    await fetchCsrfCookie()

    expect(fetchMock).toHaveBeenCalledWith('/sanctum/csrf-cookie', { credentials: 'include' })

    vi.unstubAllGlobals()
  })
})

describe('JSON:API resource helpers', () => {
  it('flattens a single resource while retaining resource and document fields', () => {
    const result = deserializeDocument({
      data: {
        id: '42',
        type: 'users',
        attributes: { name: 'Ada', email: 'ada@example.test' },
        relationships: { roles: { data: [{ type: 'roles', id: 'admin' }] } },
        links: { self: '/users/42' },
      },
      meta: { requestId: 'req-1' },
      included: [{ id: 'admin', type: 'roles', attributes: { name: 'admin' } }],
    })

    expect(result).toMatchObject({
      id: '42',
      type: 'users',
      name: 'Ada',
      email: 'ada@example.test',
      relationships: { roles: { data: [{ type: 'roles', id: 'admin' }] } },
      resourceLinks: { self: '/users/42' },
      documentMeta: { requestId: 'req-1' },
      included: [{ id: 'admin', type: 'roles', attributes: { name: 'admin' } }],
    })
  })

  it('normalizes collections without dropping pagination metadata or included resources', () => {
    const result = deserializeCollection({
      data: [{ id: 'p1', type: 'photos', attributes: { title: 'Sunset' } }],
      meta: { page: { currentPage: 1 }, total: 1 },
      links: { next: null },
      included: [{ id: 'u1', type: 'users', attributes: { name: 'Ada' } }],
    })

    expect(result).toEqual({
      resources: [{ id: 'p1', type: 'photos', title: 'Sunset' }],
      meta: { page: { currentPage: 1 }, total: 1 },
      links: { next: null },
      included: [{ id: 'u1', type: 'users', attributes: { name: 'Ada' } }],
    })
  })

  it('serializes resource and relationship documents using JSON:API identifiers', () => {
    expect(serializeResource('users', { name: 'Ada' }, '42')).toEqual({
      data: { type: 'users', id: '42', attributes: { name: 'Ada' } },
    })
    expect(serializeRelationship([{ type: 'roles', id: 'admin' }])).toEqual({
      data: [{ type: 'roles', id: 'admin' }],
    })
    expect(serializeResource('users', { name: 'Ada' })).toEqual({
      data: { type: 'users', attributes: { name: 'Ada' } },
    })
  })

  it('decodes canonical DocumentIds only when their expected part count matches', () => {
    const projectId = 'WyJ0dWxlYXAtcHJvamVjdHMiLCI0MiJd'

    expect(decodeJsonApiId(projectId, 2)).toEqual(['tuleap-projects', '42'])
    expect(decodeJsonApiId(projectId, 3)).toBeNull()
    expect(decodeJsonApiId(btoa('["tuleap-projects", "42"]'), 2)).toBeNull()
    expect(decodeJsonApiId(btoa('["tuleap-projects", ""]'), 2)).toBeNull()

    const pathId = btoa(String.raw`["tuleap-projects","a\/b"]`)
      .replace(/\+/g, '-')
      .replace(/\//g, '_')
      .replace(/=+$/, '')
    expect(decodeJsonApiId(pathId, 2)).toEqual(['tuleap-projects', 'a/b'])
  })
})

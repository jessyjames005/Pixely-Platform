// Unit tests for the centralized API client
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { apiClient, ApiClientError, fetchCsrfCookie } from './apiClient'

describe('apiClient', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', vi.fn())
  })

  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it('sends a GET request with the correct URL and query params', async () => {
    const mockResponse = { data: [{ id: 1 }], meta: { current_page: 1 } }
    vi.mocked(fetch).mockResolvedValueOnce(
      new Response(JSON.stringify(mockResponse), { status: 200 }),
    )

    const result = await apiClient.get('/gallery', { page: 2, per_page: 10 })

    expect(fetch).toHaveBeenCalledWith(
      '/api/v1/gallery?page=2&per_page=10',
      expect.objectContaining({ method: 'GET', credentials: 'include' }),
    )
    expect(result).toEqual(mockResponse)
  })

  it('sends a POST request with a JSON body', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(
      new Response(JSON.stringify({ data: { id: 1 } }), { status: 201 }),
    )

    await apiClient.post('/gallery/upload', { title: 'Sunset' })

    expect(fetch).toHaveBeenCalledWith(
      '/api/v1/gallery/upload',
      expect.objectContaining({
        method: 'POST',
        body: JSON.stringify({ title: 'Sunset' }),
        headers: expect.objectContaining({ 'Content-Type': 'application/json' }),
      }),
    )
  })

  it('sends FormData bodies without a Content-Type header override', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(
      new Response(JSON.stringify({ data: {} }), { status: 201 }),
    )

    const formData = new FormData()
    formData.append('image', new Blob(['fake']))

    await apiClient.post('/gallery/upload', formData)

    const [, options] = vi.mocked(fetch).mock.calls[0]
    expect(options?.body).toBe(formData)
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
          error: { code: 'RESOURCE_NOT_FOUND', message: 'Not found.' },
        }),
        { status: 404 },
      ),
    )

    await expect(apiClient.get('/gallery/999')).rejects.toMatchObject({
      status: 404,
      code: 'RESOURCE_NOT_FOUND',
      message: 'Not found.',
    })
  })

  it('throws an ApiClientError instance', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(
      new Response(JSON.stringify({ error: { code: 'X', message: 'Y' } }), { status: 500 }),
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

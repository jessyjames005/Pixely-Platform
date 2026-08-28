// Unit tests for the useApi composable
import { describe, it, expect, vi } from 'vitest'
import { useApi } from './useApi'
import { ApiClientError } from '../services/apiClient'

describe('useApi', () => {
  it('sets loading during execution and data on success', async () => {
    const apiCall = vi.fn().mockResolvedValue({ id: 1, name: 'Test' })
    const { data, loading, error, execute } = useApi(apiCall)

    const promise = execute()
    expect(loading.value).toBe(true)

    const result = await promise

    expect(loading.value).toBe(false)
    expect(error.value).toBeNull()
    expect(data.value).toEqual({ id: 1, name: 'Test' })
    expect(result).toEqual({ id: 1, name: 'Test' })
  })

  it('sets error and returns null on ApiClientError', async () => {
    const apiCall = vi.fn().mockRejectedValue(
      new ApiClientError(404, 'RESOURCE_NOT_FOUND', 'Not found.'),
    )
    const { data, loading, error, execute } = useApi(apiCall)

    const result = await execute()

    expect(loading.value).toBe(false)
    expect(result).toBeNull()
    expect(data.value).toBeNull()
    expect(error.value).toBeInstanceOf(ApiClientError)
    expect(error.value?.code).toBe('RESOURCE_NOT_FOUND')
  })

  it('wraps a non-ApiClientError failure into a network error', async () => {
    const apiCall = vi.fn().mockRejectedValue(new Error('boom'))
    const { error, execute } = useApi(apiCall)

    await execute()

    expect(error.value?.code).toBe('NETWORK_ERROR')
  })

  it('passes arguments through to the wrapped call', async () => {
    const apiCall = vi.fn().mockResolvedValue('ok')
    const { execute } = useApi(apiCall)

    await execute(1, 'x', true)

    expect(apiCall).toHaveBeenCalledWith(1, 'x', true)
  })
})

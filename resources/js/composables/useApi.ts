// Vue composable providing loading/error/data state around an API call
import { ref, type Ref } from 'vue'
import { ApiClientError } from '../services/apiClient'

// Wraps an async API call and tracks its loading/error/data state
export function useApi<T, Args extends unknown[] = []>(
  apiCall: (...args: Args) => Promise<T>,
) {
  const data = ref<T | null>(null) as Ref<T | null>
  const loading = ref(false)
  const error = ref<ApiClientError | null>(null)

  // Executes the wrapped API call, updating state as it progresses
  async function execute(...args: Args): Promise<T | null> {
    loading.value = true
    error.value = null

    try {
      const result = await apiCall(...args)
      data.value = result
      return result
    } catch (caught) {
      error.value =
        caught instanceof ApiClientError
          ? caught
          : new ApiClientError(0, 'NETWORK_ERROR', 'Network request failed.')
      return null
    } finally {
      loading.value = false
    }
  }

  return { data, loading, error, execute }
}

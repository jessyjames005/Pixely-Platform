import { ref } from 'vue'
import { apiClient, ApiClientError } from '@shared/services/apiClient'
import type { WebsitePage } from '../models/website'

interface PageResponse {
  data: WebsitePage
}

export function useWebsitePublicPage() {
  const page = ref<WebsitePage | null>(null)
  const loading = ref(false)
  const notFound = ref(false)

  async function load(slug: string): Promise<void> {
    loading.value = true
    notFound.value = false
    page.value = null

    try {
      const response = await apiClient.get<PageResponse>(`/website/public/pages/${encodeURIComponent(slug)}`)
      page.value = response.data
    } catch (error) {
      if (error instanceof ApiClientError && error.status === 404) {
        notFound.value = true
      } else {
        notFound.value = true
      }
    } finally {
      loading.value = false
    }
  }

  return { page, loading, notFound, load }
}

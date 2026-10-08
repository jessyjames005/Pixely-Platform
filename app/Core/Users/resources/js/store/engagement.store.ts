import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

export interface UserFavorite {
    id: number
    resource_type: string
    resource_id: string
    metadata: Record<string, unknown> | null
}

export interface UserHistoryEntry {
    id: number
    resource_type: string
    resource_id: string
    action: string
    metadata: Record<string, unknown> | null
    occurred_at: string
}

interface Paginated<T> {
    data: T[]
    current_page: number
    last_page: number
    per_page: number
    total: number
}

export const useEngagementStore = defineStore('user-engagement', () => {
    const favorites = ref<UserFavorite[]>([])
    const history = ref<UserHistoryEntry[]>([])
    const loading = ref(false)
    const error = ref<string | null>(null)
    const favoritePages = ref(1)
    const historyPages = ref(1)

    async function loadFavorites(resourceType?: string, page = 1): Promise<void> {
        loading.value = true
        error.value = null
        try {
            const { data } = await axios.get<Paginated<UserFavorite>>('/api/v1/me/favorites', {
                params: { ...(resourceType ? { resource_type: resourceType } : {}), page },
            })
            favorites.value = data.data
            favoritePages.value = data.last_page
        } catch (exception) {
            error.value = exception instanceof Error ? exception.message : 'Unable to load favorites.'
        } finally {
            loading.value = false
        }
    }

    async function removeFavorite(resourceType: string, resourceId: string): Promise<void> {
        error.value = null
        await axios.delete(`/api/v1/me/favorites/${encodeURIComponent(resourceType)}/${encodeURIComponent(resourceId)}`)
        favorites.value = favorites.value.filter(
            (favorite) => !(favorite.resource_type === resourceType && favorite.resource_id === resourceId),
        )
    }

    async function loadHistory(resourceType?: string, page = 1): Promise<void> {
        loading.value = true
        error.value = null
        try {
            const { data } = await axios.get<Paginated<UserHistoryEntry>>('/api/v1/me/history', {
                params: { ...(resourceType ? { resource_type: resourceType } : {}), page },
            })
            history.value = data.data
            historyPages.value = data.last_page
        } catch (exception) {
            error.value = exception instanceof Error ? exception.message : 'Unable to load history.'
        } finally {
            loading.value = false
        }
    }

    return {
        favorites,
        history,
        loading,
        error,
        favoritePages,
        historyPages,
        loadFavorites,
        removeFavorite,
        loadHistory,
    }
})

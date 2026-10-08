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

    async function loadFavorites(resourceType?: string): Promise<void> {
        loading.value = true
        try {
            const { data } = await axios.get<Paginated<UserFavorite>>('/api/v1/me/favorites', {
                params: resourceType ? { resource_type: resourceType } : undefined,
            })
            favorites.value = data.data
        } finally {
            loading.value = false
        }
    }

    async function removeFavorite(resourceType: string, resourceId: string): Promise<void> {
        await axios.delete(`/api/v1/me/favorites/${encodeURIComponent(resourceType)}/${encodeURIComponent(resourceId)}`)
        favorites.value = favorites.value.filter(
            (favorite) => !(favorite.resource_type === resourceType && favorite.resource_id === resourceId),
        )
    }

    async function loadHistory(resourceType?: string): Promise<void> {
        loading.value = true
        try {
            const { data } = await axios.get<Paginated<UserHistoryEntry>>('/api/v1/me/history', {
                params: resourceType ? { resource_type: resourceType } : undefined,
            })
            history.value = data.data
        } finally {
            loading.value = false
        }
    }

    return {
        favorites,
        history,
        loading,
        loadFavorites,
        removeFavorite,
        loadHistory,
    }
})

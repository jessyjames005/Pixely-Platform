import { defineStore } from 'pinia'
import { apiClient } from '@shared/services/apiClient'
import type { ApiResponse } from '@shared/types/api'
import type { WebsiteMenu, WebsitePage } from '../models/website'

interface PageListResponse {
  data: WebsitePage[]
  meta: { total: number; page: number; per_page: number }
}

interface WebsiteState {
  pages: WebsitePage[]
  menus: WebsiteMenu[]
  loadingPages: boolean
  loadingMenus: boolean
}

export const useWebsiteStore = defineStore('website', {
  state: (): WebsiteState => ({
    pages: [],
    menus: [],
    loadingPages: false,
    loadingMenus: false,
  }),

  actions: {
    async fetchPages(search = ''): Promise<void> {
      this.loadingPages = true
      try {
        const result = await apiClient.get<PageListResponse>('/website/pages', search ? { search } : undefined)
        this.pages = result.data
      } finally {
        this.loadingPages = false
      }
    },

    async createPage(payload: Partial<WebsitePage>): Promise<WebsitePage> {
      const result = await apiClient.post<ApiResponse<WebsitePage>>('/website/pages', payload)
      await this.fetchPages()
      return result.data
    },

    async updatePage(id: string, payload: Partial<WebsitePage>): Promise<WebsitePage> {
      const result = await apiClient.put<ApiResponse<WebsitePage>>(`/website/pages/${id}`, payload)
      await this.fetchPages()
      return result.data
    },

    async deletePage(id: string): Promise<void> {
      await apiClient.delete(`/website/pages/${id}`)
      await this.fetchPages()
    },

    async fetchMenus(): Promise<void> {
      this.loadingMenus = true
      try {
        const result = await apiClient.get<ApiResponse<WebsiteMenu[]>>('/website/menus')
        this.menus = result.data
      } finally {
        this.loadingMenus = false
      }
    },

    async createMenu(payload: Partial<WebsiteMenu>): Promise<WebsiteMenu> {
      const result = await apiClient.post<ApiResponse<WebsiteMenu>>('/website/menus', payload)
      await this.fetchMenus()
      return result.data
    },

    async updateMenu(id: string, payload: Partial<WebsiteMenu>): Promise<WebsiteMenu> {
      const result = await apiClient.put<ApiResponse<WebsiteMenu>>(`/website/menus/${id}`, payload)
      await this.fetchMenus()
      return result.data
    },

    async deleteMenu(id: string): Promise<void> {
      await apiClient.delete(`/website/menus/${id}`)
      await this.fetchMenus()
    },
  },
})

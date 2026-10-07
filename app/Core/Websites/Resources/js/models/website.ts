export type PageStatus = 'draft' | 'published' | 'archived'

export interface WebsitePage {
  id: string
  slug: string
  title: string
  status: PageStatus
  template: string
  seo: Record<string, unknown>
  blocks: WebsiteBlock[]
}

export interface WebsiteBlock {
  type: string
  text?: string
  href?: string
  [key: string]: unknown
}

export type MenuItemType = 'page' | 'extension' | 'external'

export interface WebsiteMenuItem {
  id: string
  type: MenuItemType
  title: string
  targetUrl: string | null
  pageId: string | null
  extensionId: string | null
  slug: string | null
  sortOrder: number
  active: boolean
}

export interface WebsiteMenu {
  id: string
  name: string
  code: string
  items: WebsiteMenuItem[]
}

// resources/js/shared/navigation/types.ts
// Contract every domain's navigation declaration follows.
export interface NavItem {
  // A function form re-evaluates on every render (needed for a
  // translated label to update when the locale changes) — AdminNav
  // calls navLabel(item) rather than reading `.label` directly.
  label: string | (() => string)
  to: string
  icon: string
  // Omitted = always visible to any authenticated user (e.g. Dashboard, Settings)
  permission?: string
  // Set only for domains backed by a real backend Extension (app/Extensions/*)
  // that can be enabled/disabled — the item is hidden when disabled,
  // regardless of permission. Core modules (Users, Roles...) omit this.
  extensionId?: string
  // Nested navigation items for submenus
  children?: NavItem[]
}

export function navLabel(item: NavItem): string {
  return typeof item.label === 'function' ? item.label() : item.label
}

// resources/js/shared/navigation/types.ts
// Contract every domain's navigation declaration follows.
export interface NavItem {
  label: string
  to: string
  icon: string
  // Omitted = always visible to any authenticated user (e.g. Dashboard, Settings)
  permission?: string
  // Set only for domains backed by a real backend Extension (app/Extensions/*)
  // that can be enabled/disabled — the item is hidden when disabled,
  // regardless of permission. Core modules (Users, Roles...) omit this.
  extensionId?: string
}

// Lightweight translation helper — no vue-i18n dependency, since a
// flat key lookup with locale switching is all this needs.
//
// Registered as a global property so every template can call
// $t('module.group.key') directly with no per-component import; the
// same `translate` function is also exported for use inside <script
// setup> blocks (computed labels, dynamic keys, etc.) as `t(...)`.
import type { App } from 'vue'
import { useI18nStore } from '../store/i18n.store'

export function translate(
  key: string,
  fallback?: string,
  replacements?: Record<string, string | number>,
): string {
  const store = useI18nStore()

  let node: unknown = store.catalog
  for (const segment of key.split('.')) {
    if (node == null || typeof node !== 'object') {
      node = undefined
      break
    }
    node = (node as Record<string, unknown>)[segment]
  }

  let result = typeof node === 'string' ? node : (fallback ?? key)

  if (replacements) {
    for (const [name, value] of Object.entries(replacements)) {
      result = result.replaceAll(`:${name}`, String(value))
    }
  }

  return result
}

export const i18nPlugin = {
  install(app: App): void {
    app.config.globalProperties.$t = translate
  },
}

declare module '@vue/runtime-core' {
  interface ComponentCustomProperties {
    $t: typeof translate
  }
}

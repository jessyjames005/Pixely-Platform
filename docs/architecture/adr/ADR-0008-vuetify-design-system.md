# ADR-0008: Vuetify as the Design System Foundation

## Status

Accepted

## Context

The administration interface needed a consistent visual design (colours, typography, spacing, component styling) rather than hand-built components with ad-hoc scoped CSS. The developer maintaining Pixely has no design background and needs a system that produces a coherent, professional result without requiring design judgement on every screen.

Tailwind CSS was present in the project scaffold from the start but never actually used in any component — all styling was plain scoped CSS.

## Decision

Pixely adopts **Vuetify** as its component library and design token layer, and **removes Tailwind**.

* A Pixely theme (`pixelyLight` / `pixelyDark`) is defined once in `resources/js/shared/plugins/vuetify.ts` and registered globally.
* All hand-built `Base*` components (`BaseButton`, `BaseCard`, `BaseTable`, `BasePagination`, `BaseFileInput`) are removed and replaced by their Vuetify equivalents (`v-btn`, `v-card`, `v-data-table`, `v-file-input`, etc.).
* Material Design Icons (`@mdi/font`) provide the icon set.

## Alternatives Considered

### PrimeVue

Rejected. PrimeVue's licensing model changed in 2026: future development is commercial-only, with a free "Community License" restricted by revenue/team-size eligibility conditions that must be monitored over time. For a project whose future scale is unknown, this introduces avoidable licensing risk that Vuetify (permissive MIT, no eligibility conditions) does not carry.

### shadcn-vue

Rejected. It ships unstyled primitives (via Reka UI) assembled with Tailwind utility classes — it requires design judgement to produce a coherent result, which conflicts directly with the project's stated need for sensible defaults without design expertise. It would also have required reintroducing Tailwind, reversing the decision to avoid two parallel design systems.

### Nuxt UI

Rejected, despite becoming fully MIT-licensed (v4, mid-2026). It is built for and best documented within the Nuxt ecosystem; Pixely is a plain Vite + Vue application (no Nuxt), making Nuxt UI a less-trodden integration path. It also requires Tailwind, for the same reason as shadcn-vue above.

### Keep Tailwind + hand-built components

Rejected as the status quo. Tailwind was already installed but unused; continuing to hand-build and hand-style every component (`Base*`) does not scale as the admin grows, and running Tailwind alongside a future component library would mean two competing styling systems and duplicated design decisions (spacing, colour, typography defined twice).

## Consequences

### Positive

* A single design system: Vuetify's theme is simultaneously the component library and the design tokens layer, with no separate token system to keep in sync.
* `v-data-table` replaces both `BaseTable` and `BasePagination` at once, including built-in loading states.
* No design expertise required to produce a consistent, professional-looking admin.
* No licensing risk as the project scales (fully permissive, no eligibility thresholds).

### Negative

* Vuetify's Material Design aesthetic is immediately recognizable and, without further theme customization, can read as generic. Mitigated by the custom `pixelyLight`/`pixelyDark` theme and `defaults` overrides, with room for deeper customization later.
* Adopting Vuetify's own layout components (`v-app`, `v-navigation-drawer`, `v-app-bar`) means the app's structural shell is now coupled to Vuetify, rather than framework-agnostic markup.

## Testing

Vitest coverage for the removed `Base*` components was deleted along with the components themselves. Vuetify's own components are not unit-tested directly (they are a third-party, already-tested dependency); coverage focuses on Pinia store logic and `apiClient`/`useApi`, which remain framework-agnostic.

# Pixely Platform Design System

## Purpose

The Pixely Design System provides shared visual foundations for the administration interface and future extension UIs. Vuetify remains the component implementation layer; do not introduce a second component library without an Architecture Decision Record.

## Theme foundations

Theme definitions live in `resources/js/shared/plugins/vuetify.ts`:

- `pixelyLight` is the default light theme.
- `pixelyDark` is the dark theme.
- Vuetify owns theme-aware semantic colours such as `background`, `surface`, `primary`, `on-surface`, and `outline`.
- User theme preferences continue to be applied by `AdminLayout.vue`.

The administration visual identity uses a neutral dark/white foundation with electric blue as the primary interaction colour. The public website retains its own styling and must not inherit administration-specific colour choices.

## Shared CSS tokens

The global token layer is `resources/js/shared/styles/design-system.scss`, imported by `resources/js/app.ts`.

Token families currently provided:

| Family | Prefix | Examples |
| --- | --- | --- |
| Typography | `--px-font-*` | font family, type scale, line heights |
| Spacing | `--px-space-*` | 4px-based spacing scale |
| Shape | `--px-radius-*` | small to extra-large radii, pill |
| Elevation | `--px-elevation-*` | three restrained shadow levels |
| Interaction | `--px-focus-*` | visible keyboard focus ring |
| Layout | `--px-content-*`, `--px-layout-*` | content width and responsive gutters |
| Admin semantic aliases | `--px-admin-*` | theme-aware background, surface, text, primary and border |

Use these tokens for new platform-owned UI rather than adding arbitrary one-off values. Prefer Vuetify semantic colours and spacing utilities where they already express the design intent.

## Component conventions

1. Prefer Vuetify components and existing shared components over duplicated controls.
2. Keep domain-specific components inside their owning Core domain or extension.
3. Keep shared components genuinely cross-domain.
4. Keep component logic and markup in Vue SFCs; put reusable styling in SCSS under the relevant `styles/` directory.
5. Ensure keyboard focus is visible, controls have accessible names, and colour is not the only way to convey state.
6. Respect the user's reduced-motion preference.
7. Test responsive behaviour at narrow mobile widths as well as desktop widths.
8. Preserve light/dark theme support when adding colours or surfaces.

## Responsive baseline

- Small screens: up to 600px.
- Medium screens: 601px–960px.
- Large screens: above 960px.

These are project guidance values, not a replacement for Vuetify's responsive display utilities. Prefer `useDisplay()` and Vuetify grid/layout components where possible.

## Scope and next steps

This first foundation establishes tokens, theme semantics, baseline focus styling, and reduced-motion support. It does not yet claim that all existing pages/components are fully migrated, that Storybook is configured, or that a complete accessibility audit has passed. Subsequent S5 work should migrate existing UI incrementally and add component specifications and visual examples.

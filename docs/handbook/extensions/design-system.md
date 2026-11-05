# Extension Design System Guidelines

Extension administration interfaces use Pixely's shared Vuetify-based Design
System. The extension owns its domain behavior; the platform owns shared
interaction, theme, and accessibility foundations.

The canonical token and theme reference is the
[Pixely Platform Design System](../../design-system/DESIGN_SYSTEM.md). This
guide describes how an extension should use it.

## Reuse before adding

1. Prefer Vuetify components and existing shared Pixely components.
2. Keep a domain-specific component under that extension's
   `resources/js/components/`.
3. Put a component in `resources/js/shared/components/` only when it is
   generic and useful across domains.
4. Do not introduce another component library or a parallel token system
   without an approved architecture decision.

Avoid one-off controls and domain-local replacements for common buttons,
dialogs, cards, tables, alerts, and navigation. Use Vuetify's established
components and the application's global defaults.

## Theme and tokens

Use the application-provided light and dark themes. Prefer Vuetify semantic
colors (`primary`, `surface`, `background`, `error`, and related theme values)
over hard-coded colors so extension views remain legible across themes.

Use shared CSS custom properties from
`resources/js/shared/styles/design-system.scss` when a design value cannot be
expressed with Vuetify's theme or utilities. Do not introduce a second
extension-specific spacing, typography, color, or elevation scale.

Keep extension styles local under `resources/js/styles/`. Vue SFCs contain
markup and logic, not `<style>` blocks; import the required SCSS from the
component or view. Prefer Vuetify layout components and utilities for standard
spacing and responsive behavior.

## Interaction patterns

- Keep page structure predictable: a clear page title and primary action
  followed by the page's main content.
- Use dialogs for create/edit forms rather than placing permanent forms above a
  list.
- Use the shared confirmation dialog for destructive actions; never use
  `window.confirm()`.
- Use the shared notification composable for action success/failure. Keep
  validation feedback inline with the form being corrected.
- Give tables and collections clear loading, empty, and error states.
- Use consistent icon and semantic color treatment for primary, secondary,
  and destructive actions.

## Accessibility and responsive behavior

- Give every interactive control an accessible name and a clear focus state.
- Do not rely on color alone to communicate status or errors.
- Preserve readable contrast in both light and dark themes.
- Respect reduced-motion preferences.
- Test narrow mobile and desktop widths; prefer Vuetify's responsive grid and
  display utilities.

Do not claim accessibility conformance based solely on following these
guidelines. Components still require appropriate review and testing.

## Component examples

Keep examples focused on reusable UI states and variants, not complete
extension workflows. Storybook is not currently installed or configured in
this repository; see [Extension Storybook Guidelines](storybook.md) for the
current limitation and the intended future story practices.

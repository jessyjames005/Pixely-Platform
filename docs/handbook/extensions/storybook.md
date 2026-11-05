# Extension Storybook Guidelines

## Current status

Storybook is **not installed or configured** in this repository. There are no
Storybook scripts or runtime commands to run today. Do not add a Storybook
dependency, configuration, or CI job as part of ordinary extension work;
integration requires a separate scoped decision.

These guidelines describe how extensions should document reusable components
once the platform adopts Storybook. They are not a claim that stories are
currently runnable.

## What belongs in a story

Storybook should focus on reusable components and design-system primitives,
not full business workflows or route-level application behavior.

When Storybook support is introduced:

- Keep a story beside its component or in a colocated `stories/` directory,
  following the eventual repository configuration.
- Use typed Component Story Format (CSF) and the Vue 3 renderer selected by
  the platform integration.
- Reuse the platform's Vuetify/theme setup so examples match the application.
- Keep stories deterministic: use representative local props and fixtures,
  not live API requests or production user data.
- Document component purpose, meaningful props, slots, and interaction
  expectations.

Show the states relevant to each component, such as default, loading, empty,
error, disabled, validation, and responsive variants. Not every component
needs every state. Include keyboard behavior and accessible labels when they
are part of the component contract.

## Extension ownership

An extension owns components used only by its frontend. Shared components
belong to Core only when multiple domains need the same generic behavior.
Stories must not become an alternative API, authorization boundary, or
extension discovery mechanism.

Do not import components from API-provided paths or execute templates from
untrusted data. Any future Storybook integration must remain a development
tool and must not change the production bundle unless explicitly designed and
reviewed.

## Related guides

- [Extension Frontend Development Guide](frontend-development.md)
- [Extension Design System Guidelines](design-system.md)
- [Pixely Platform Design System](../../design-system/DESIGN_SYSTEM.md)

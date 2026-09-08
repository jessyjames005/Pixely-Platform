# Frontend Architecture

## Vue 3 + TypeScript + Vuetify

Pixely's administration interface follows Material Design 3 (M3) principles and uses Vuetify as the implementation layer.

## Project Structure
```
resources/js/
├── extensions/<domain>/
│   ├── components/    # Domain-specific components
│   ├── composables/   # Reactive helpers specific to this domain
│   ├── docs/          # Domain-specific developer notes
│   ├── entities/      # Domain entity classes/factories
│   ├── enums/         # Domain enums
│   ├── models/        # Domain data shapes returned by the API
│   ├── store/         # Pinia store(s)
│   ├── styles/        # .scss files
│   ├── tests/         # Vitest unit tests
│   ├── types/         # Domain-specific TypeScript types
│   ├── utils/         # Pure helper functions
│   └── views/         # Route-level page components
└── shared/            # Cross-domain code
```

## Design System
- Pixely must have a reusable Design System based on Material Design 3 and Vuetify
- Establish Pixely-specific theme tokens on top of Vuetify
- Align Pixely theme tokens with Material 3 tokens where applicable
- Reuse spacing, typography, colours, elevation, forms, tables, dialogs, alerts, and navigation patterns consistently
- Accessibility must be considered for reusable components

## Component Rules
- Prefer reusable components over duplicated UI code
- Do not create one-off visual components when the Design System should provide the component
- Keep business-specific UI inside the relevant extension
- Shared components belong in the platform frontend/design-system layer
- API communication should be separated from presentation components

## State Management
- Pinia for state management
- Each domain defines its own store(s) under `store/`
- No module-level singleton composables for state

## Styling
- Styling lives in `.scss` files under `styles/`
- No `<style>` blocks in `.vue` files
- Component file contains markup and logic only

## Storybook
- Reusable UI components should have Storybook stories
- Stories should demonstrate: default, loading, empty, error, disabled, validation, responsive variations
- Storybook must remain focused on reusable UI components, not complete business workflows

## Design Workflow
- Design major administration workflows before implementing complex UI when practical
- Designs should follow the Pixely UI/UX Kit and Material Design 3
- Do not design components that cannot reasonably be represented by the chosen frontend architecture
- Design decisions should be documented when they affect the reusable platform UI
- External templates may be used as visual references, but must be adapted to the Pixely Design System
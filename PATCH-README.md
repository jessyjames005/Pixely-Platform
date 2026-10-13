# Pixely Platform — S4 trusted block renderer contract

## Included changes

- Adds a typed contract for locally bundled Vue components that render SDK v2 extension blocks.
- Adds a Core-owned allowlist keyed by namespaced `qualified_id` values.
- Requires both API block metadata and the local renderer registration to allow the requested surface.
- Rejects malformed or duplicate renderer IDs and invalid surface declarations.
- Adds `ExtensionBlockOutlet.vue` with a safe fallback for unregistered blocks.
- Documents the security boundary and updates the S4 roadmap and changelog.

The allowlist intentionally contains no concrete renderer yet: no existing extension currently declares a concrete block renderer. This change establishes the contract without pretending that unreviewed extension UI can execute.

## Apply

From the repository root, review `git status --short`, extract the ZIP while preserving paths, then inspect the diff:

```powershell
Expand-Archive -LiteralPath .\Pixely-Platform-S4-trusted-block-renderer.zip -DestinationPath . -Force
git diff --check
npm run test -- app/Core/Extensions/resources/js/renderers/TrustedBlockRenderer.test.ts
npm run typecheck
npm run build:front
```

No commit or push has been performed.

## Validation status

The patch was reviewed for TypeScript structure and file consistency in the patch environment. Vitest, Vue type-checking, and Vite build still need to be run in the project's Docker/dev environment.

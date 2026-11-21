# Website Engine Extension Contracts

Extensions expose public content through the Website Engine without direct coupling.

## Capabilities
- `pages`: extension-declared public page definitions.
- `blocks`: namespaced block types with schema.
- `navigation`: menu contributions.
- `seo`: metadata providers.

## Rendering
- Server-side permissions remain authoritative.
- Frontend renders only allowlisted blocks.
- Errors are isolated per block.

## Assets
- Extensions provide asset URLs; platform serves them.

## Route compatibility
- Extension routes are namespaced and versioned.

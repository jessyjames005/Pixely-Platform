> _This page is generated from [`docs/architecture/diagrams/contracts.md`](https://github.com/jessyjames005/Pixely-Platform/blob/develop/docs/architecture/diagrams/contracts.md). Edit it in the repository, not in the wiki: wiki edits are overwritten._

# Contracts

```mermaid
classDiagram

KernelInterface --> ExtensionManagerInterface

ExtensionManagerInterface --> ExtensionInterface
ExtensionManagerInterface --> ManifestInterface
ExtensionManagerInterface --> InstallerInterface
ExtensionManagerInterface --> UpdaterInterface
ExtensionManagerInterface --> UninstallerInterface
```
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
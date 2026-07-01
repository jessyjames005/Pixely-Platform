# Contracts

## Purpose

Contracts define the public interfaces of Pixely Platform.

Every major component must depend on Contracts instead of concrete implementations.

## Core Contracts

### KernelInterface

Responsible for booting Pixely Platform.

### ExtensionManagerInterface

Responsible for discovering and managing extensions.

### ExtensionInterface

Represents a generic extension.

### ManifestInterface

Represents an extension manifest.

### InstallerInterface

Responsible for installing an extension.

### UpdaterInterface

Responsible for updating an extension.

### UninstallerInterface

Responsible for removing an extension.

## Principles

- Depend on interfaces.
- One responsibility per contract.
- Contracts are stable.
- Implementations may evolve.
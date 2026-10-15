> _This page is generated from [`docs/architecture/extension-manager.md`](https://github.com/jessyjames005/Pixely-Platform/blob/develop/docs/architecture/extension-manager.md). Edit it in the repository, not in the wiki: wiki edits are overwritten._

# Extension Manager

## Purpose

The Extension Manager is responsible for managing the complete lifecycle of every extension.

## Responsibilities

- Discover extensions
- Register extensions
- Validate dependencies
- Install extensions
- Update extensions
- Enable and disable extensions
- Uninstall extensions
- Load configuration
- Publish assets
- Execute migrations

## Principles

- Everything is an Extension.
- Extensions are isolated.
- Extensions communicate through Contracts and Events.
- The Kernel never knows specific extension types.

## Lifecycle

Discover
↓
Register
↓
Validate
↓
Install
↓
Enable
↓
Boot
↓
Disable
↓
Uninstall
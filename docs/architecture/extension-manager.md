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
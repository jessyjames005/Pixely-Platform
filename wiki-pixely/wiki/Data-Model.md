> _This page is generated from [`docs/architecture/data-model.md`](https://github.com/jessyjames005/Pixely-Platform/blob/develop/docs/architecture/data-model.md). Edit it in the repository, not in the wiki: wiki edits are overwritten._

# Platform Data Model

## Purpose

The Core database only stores platform data.

Business data belongs to Extensions.

---

## Core Tables

### users

Platform users.

### roles

User roles.

### permissions

Platform permissions.

### settings

Global application settings.

### extensions

Installed extensions.

### extension_versions

Installed extension versions.

### languages

Available languages.

### jobs

Queued jobs.

### cache

Application cache.

### sessions

User sessions.

---

## Principles

- The Core owns only platform data.
- Extensions own their business data.
- Extensions never modify Core tables directly.
- Every extension manages its own migrations.
# Pixely Architecture Charter

> **"Build for the next developer."**

Pixely is built with a simple philosophy:

Software should be easy to understand, easy to extend, easy to test, and enjoyable to maintain.

Every architectural decision should improve the long-term quality of the platform rather than optimize only for short-term development speed.

When faced with multiple valid solutions, choose the one that:

* improves readability;
* reduces coupling;
* increases testability;
* favors explicitness over magic;
* keeps the Core small and focused.

## Our Mission

Build a modern, modular and Laravel-native platform that developers enjoy using and contributing to.

Pixely should be:

* elegant to read;
* predictable to use;
* extensible by design;
* pleasant to maintain.

## Our Vision

Pixely is not just another Laravel application.

Pixely is a platform built on top of Laravel.

Laravel provides the foundation.

Pixely provides the modular architecture.

Every new feature should reinforce this vision.

## Our Commitment

Before writing code, we ask ourselves:

* Is this responsibility in the right place?
* Does this improve the architecture?
* Can it be tested easily?
* Will another developer understand it in six months?
* Does it respect our architectural principles?

If the answer is "no", we improve the design before writing more code.

---

> **"Code is temporary. Architecture is long-term."**


# Architecture Principles

## Purpose

This document defines the architectural principles of the Pixely Platform.

Every contribution, feature, refactoring, and design decision should follow these principles to ensure consistency, maintainability, and long-term scalability.

---

# 1. Contract First

Define interfaces before implementations.

Every major component must expose a clear contract that describes its responsibilities without exposing implementation details.

**Example**

* `KernelInterface`
* `ExtensionInterface`

---

# 2. Single Responsibility Principle

A class must have one responsibility and one reason to change.

Examples:

* `ExtensionRegistry` stores extensions.
* `ExtensionManager` manages the extension lifecycle.
* `Kernel` orchestrates the platform.

---

# 3. Composition Over Inheritance

Prefer composing objects rather than creating deep inheritance hierarchies.

Services collaborate through interfaces and dependency injection.

---

# 4. Dependency Inversion

High-level components must depend on abstractions, not concrete implementations.

Laravel's Service Container should resolve dependencies whenever possible.

---

# 5. Laravel Native

Pixely extends Laravel instead of replacing it.

Whenever Laravel already provides a robust solution, it should be preferred.

Examples include:

* Service Providers
* Dependency Injection
* Events
* Queues
* Artisan Commands
* Cache
* Filesystem
* Configuration

---

# 6. Convention Over Configuration

The platform should provide sensible defaults.

Developers should configure only what differs from the standard behavior.

---

# 7. Modular Architecture

Every feature should be designed as an independent module whenever possible.

Modules should be isolated, reusable, and loosely coupled.

---

# 8. Explicit Lifecycle

Every platform component must have a clearly defined lifecycle.

Typical lifecycle:

1. Register
2. Boot
3. Shutdown (when applicable)

---

# 9. Testability First

Every component should be testable in isolation.

New features should include automated tests before being considered complete.

---

# 10. Clean Public APIs

Public APIs should be expressive, intuitive, and easy to discover.

Code should read naturally.

Example:

```php
Pixely::boot();

Pixely::extensions()->register($extension);
```

---

# 11. Separation of Responsibilities

Responsibilities should be clearly divided.

Examples:

* Managers perform actions.
* Registries store objects.
* Contracts define behavior.
* Providers integrate with Laravel.
* Support contains reusable utilities.

---

# 12. Documentation as Code

Architecture documentation is part of the project.

Whenever the architecture evolves, the documentation must evolve with it.

Documentation should never become outdated.

---

# 13. Backward Compatibility

Public APIs should evolve carefully.

Breaking changes must be intentional, documented, and minimized.

---

# 14. Readability Over Cleverness

Code is written for humans first.

Prefer explicit, readable solutions over clever or overly complex implementations.

---

# 15. Continuous Improvement

Architecture is never finished.

Refactoring is encouraged when it improves clarity, maintainability, or extensibility without introducing unnecessary complexity.

---

# Core Values

Pixely aims to be:

* Simple
* Modular
* Predictable
* Extensible
* Testable
* Maintainable
* Laravel-native

These values should guide every architectural decision made throughout the project.

# Additional Principles

## 16. SOLID by Default

All core components should follow the SOLID principles whenever appropriate.

The objective is to build software that is easy to extend without modifying existing code.

---

## 17. Open for Extension, Closed for Modification

Core components should expose extension points instead of requiring direct modification.

New functionality should be added through extensions, modules, events, or contracts.

---

## 18. Fail Fast

Errors should be detected as early as possible.

Invalid states should never remain unnoticed.

The platform should provide clear and meaningful exceptions.

---

## 19. Keep the Core Small

The Core should remain lightweight.

Business features belong in modules or extensions, not in the Core.

The Core provides infrastructure, not application features.

---

## 20. Explicit Naming

Names should clearly express intent.

Avoid generic names such as:

* Helper
* Utils
* ManagerHelper
* Common

Prefer names that describe responsibility.

Examples:

* ExtensionRegistry
* ModuleLoader
* EventDispatcher

---

## 21. Stable Public Contracts

Public contracts should evolve carefully.

Interfaces are commitments to developers and should remain stable whenever possible.

---

## 22. Developer Experience (DX)

Developer experience is a first-class concern.

The API should be:

* discoverable
* predictable
* expressive
* enjoyable to use

Writing Pixely code should feel natural.

---

## 23. Security by Design

Security is considered from the beginning of the design process.

Every component should follow the principle of least privilege and avoid exposing unnecessary functionality.

---

## 24. Performance Without Sacrificing Readability

Readable code comes first.

Performance optimizations should be introduced only when measurable and necessary.

---

## 25. Consistency Over Preference

Project consistency is more important than individual coding preferences.

The architecture should feel uniform regardless of the author of a component.


# Pixely Platform Vision

## Our Mission

Pixely Platform is an open-source modular platform built to help developers create scalable and maintainable applications.

Instead of building one large monolithic application, Pixely provides a powerful Core that can be extended through independent modules.

Our mission is to offer a solid foundation where every business feature can evolve independently while sharing common platform services.

---

## Our Philosophy

We believe that software should be easy to understand, easy to extend and enjoyable to maintain.

Architecture always comes before complexity.

Documentation is part of the product.

Quality is more important than quantity.

---

## Core Principles

### The Core provides services

The Core is responsible for shared platform capabilities such as:

* Authentication
* Users
* Roles & Permissions
* Settings
* Localization
* Notifications
* Dashboard
* Events
* Logging
* API

The Core never contains business-specific features.

---

### Modules provide business features

Every business capability belongs to an independent module.

Examples include:

* Gallery
* Blog
* Shop
* Reviews
* Statistics
* Calendar

Modules extend the platform without modifying the Core.

---

### Documentation First

Every important decision must be documented.

Architecture Decision Records (ADR) are used to explain why technical choices were made.

Documentation evolves together with the project.

---

### Developer Experience

Pixely Platform should be enjoyable to develop.

We value:

* Clear architecture
* Predictable APIs
* Consistent coding standards
* High code quality
* Comprehensive documentation

---

## Long-Term Vision

Pixely Platform is designed as a long-term project.

Every architectural decision should help the platform evolve without introducing unnecessary complexity.

We aim to build a platform that developers will enjoy using, extending and contributing to.

---

## Multi-Surface Platform

Pixely Platform is evolving toward a **Multi-Surface Platform**:

- **Website (public)** — public-facing pages, SEO, menus
- **User Space** — authenticated user dashboard (`/account`)
- **Administration** — platform management (`/admin`)
- **API** — JSON:API backend

Each extension declares its supported surfaces in its manifest.
The Core provides surface-agnostic contracts; extensions
implement surface-specific behavior through optional interfaces.

**Evolution, not rewrite** — `/admin` is preserved during migration.

---

## Our Motto

**Build once. Extend forever.**

# Pixely Platform Vision

## Our Mission

Pixely Platform is an open-source modular platform for creating, publishing and administering complete websites through a stable Core, a shared Website Engine and independent Extensions.

Instead of building one large monolithic application, Pixely provides a stable Core and Website Engine that can be extended through independent Extensions.

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

### Extensions provide business features

Every business capability belongs to an independent Extension.

Examples include:

* Gallery
* Blog
* Shop
* Reviews
* Statistics
* Calendar

Extensions extend the platform without modifying the Core.

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

## Deployment model: one website per installation

One Pixely installation represents one independent website. This keeps the data model, authorization boundaries, configuration, cache, storage and extension contracts understandable and avoids introducing tenant isolation before a real business need exists.

Multi-site / multi-tenant hosting inside one installation is deferred. It may be reconsidered only after a confirmed requirement (for example, an agency managing multiple client sites or a SaaS multi-space offer) and a dedicated ADR covering data isolation, authorization, domains, sessions, cache, migrations and extension compatibility.

This decision does not remove multi-language support or the multi-surface model.

## Multi-Surface Platform

A single installation exposes several **application surfaces**:

- **Website (public)** — public-facing pages, SEO, menus
- **User Space** — authenticated user dashboard (`/account`)
- **Administration** — platform management (`/admin`)
- **API** — JSON:API backend

Each extension may declare its supported surfaces in its manifest.
The Core provides surface-agnostic contracts; extensions
implement surface-specific behavior through optional interfaces.

**Evolution, not rewrite** — `/admin` is preserved during migration.

---

## Our Motto

**Build once. Extend forever.**

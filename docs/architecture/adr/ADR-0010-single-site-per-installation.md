# ADR-0010: One Website per Pixely Installation

- **Status:** Accepted
- **Date:** 2026-11-17
- **Decision owners:** Pixely Platform maintainers
- **Supersedes:** The multi-site / multi-tenant direction previously described as a future platform capability where it conflicts with this decision.

## Context

Pixely's long-term product goal is a modular platform for creating, publishing and administering a complete website. The platform already has several application surfaces (`public`, `user`, `admin`, `api`), multi-language requirements and independently installable Extensions.

Treating those surfaces or languages as separate sites would introduce unnecessary tenant complexity. A generalised multi-tenant model would affect nearly every persistence and runtime boundary: data ownership, authorization, domains, sessions, cache keys, storage, background jobs, migrations, extension contracts and operational tooling.

The current product requirement is to build and operate a website using one Pixely installation. No confirmed business requirement currently justifies the cost and risk of managing multiple independent websites inside the same installation.

## Decision

**One Pixely installation represents one independent website.**

1. Do not introduce a generalised `site_id` or `tenant_id` across the data model.
2. Do not add Spatie Teams or another tenant-isolation layer for the purpose of hosting multiple websites.
3. Keep `public`, `user`, `admin` and `api` as application surfaces of the same installation; a surface is not a tenant.
4. Keep multi-language interface and content support in scope. Language selection and content localisation are not tenant boundaries.
5. Keep the Core, Website Engine and Extensions separated by explicit contracts. The Website Engine owns shared website mechanisms (pages, menus, blocks, templates/layouts, preview, publication, URLs and SEO); Extensions contribute domain-specific content and capabilities.
6. Keep configuration and operational services scoped to the installation unless a later decision explicitly defines a narrower scope.
7. Treat multi-site / multi-tenant hosting as deferred, not as an implicit requirement for the current roadmap.

## Reopening criteria

Reopen this decision only when a concrete business requirement is confirmed, such as an agency managing multiple client websites in one control plane or a SaaS offer with multiple isolated spaces.

A new architecture proposal must assess, at minimum:

- tenant identity and data isolation, including existing-data migration;
- authorization, role/permission scope and cross-tenant access tests;
- custom domains, routing and TLS;
- session and authentication boundaries;
- cache, queues, scheduled jobs, storage and media isolation;
- platform/site configuration and secrets;
- extension compatibility, lifecycle and migration ownership;
- backup, restore, observability and operational support;
- performance, cost and migration/rollback strategy.

No partial `site_id` implementation should be introduced as speculative preparation: it creates a second ownership model without delivering tenant isolation.

## Consequences

### Positive

- A simpler and more testable data model and authorization boundary.
- No tenant filtering burden on every query, policy, cache key and extension.
- A clear product promise: each installation manages one website.
- Multi-language and multi-surface capabilities can evolve independently.
- Engineering effort stays focused on the Website Engine, Extensions, media, security and release quality.

### Trade-offs

- Operators needing several websites must run separate Pixely installations for now.
- Cross-site central administration, shared users and centralised billing are not provided by this decision.
- A future multi-tenant product would require a dedicated design and migration; it must not be assumed to be a configuration-only change.

## Related decisions

- ADR-0090: Multi-Surface Platform Architecture.
- ADR-0001: Platform, Not Application.
- Planning Global V4.0 (17 November 2026).

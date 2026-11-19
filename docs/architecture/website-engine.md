# Website Engine — Architecture Boundary

## Purpose

The Website Engine provides the shared mechanisms required to create, organise, preview and publish the public website operated by one Pixely installation.

It is distinct from both the Core and business Extensions:

- **Core** owns platform-wide infrastructure and stable cross-domain contracts.
- **Website Engine** owns common website publishing concepts and rendering workflows.
- **Extensions** own domain-specific content and behavior, and integrate through public extension contracts.

## Deployment scope

One installation represents one independent website (ADR-0010). The Website Engine must not introduce a generalised tenant/site ownership model or speculative `site_id` columns.

## Responsibilities

The target scope includes:

- Pages, slugs, draft/published states and publication rules.
- Menus, hierarchy, ordering and internal/external links.
- Typed content blocks and safe rendering.
- Reusable layouts and templates.
- Preview and controlled publication.
- Public URLs, redirects and error pages.
- SEO metadata, sitemap and robots policy.
- Render caching and invalidation.
- Language-aware content and explicit locale fallback.

## Extension integration

Extensions may contribute public routes, pages, blocks, navigation and SEO metadata through documented SDK contracts. The Website Engine must not depend on CinemaMovie or another specific extension. CinemaMovie is the pilot integration, not a source of generic website abstractions.

The backend remains authoritative for validation, authentication and permissions. Frontend route guards and hidden navigation are usability features only.

## Delivery sequence

1. Audit existing website/page/menu capabilities.
2. Complete the shared Website Engine foundations (S3).
3. Define and document public extension integration contracts (S4).
4. Integrate CinemaMovie as the reference pilot.
5. Add Gallery public templates only after shared mechanisms are validated.

## Non-goals

- Multi-tenant site isolation.
- A central control plane for unrelated websites.
- An in-house payment processor.
- Extension-specific page/rendering logic in the Core.

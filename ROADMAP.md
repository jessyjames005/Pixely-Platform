# Pixely Platform Roadmap

This roadmap defines the planned evolution of Pixely Platform from the initial foundation to the first stable Gallery release.

---

## v0.1.0 - Foundation

### Project & Documentation

* [x] Project vision
* [x] Project README
* [x] Project roadmap
* [x] Project changelog
* [x] Architecture documentation
* [x] Architecture Decision Records (ADR)
* [x] Documentation structure
* [x] Sprint documentation
* [x] GitHub technical documentation

### Development Environment

* [x] Windows development environment
* [x] PHP development environment
* [x] Composer
* [x] Laravel
* [x] Node.js / NPM
* [x] VS Code development environment
* [x] Docker
* [x] Docker Compose
* [x] WSL / Linux development support

### Git Workflow

* [x] Git repository
* [x] GitHub repository
* [x] `develop` branch
* [x] Feature branch workflow
* [x] SSH authentication
* [x] Remote repository configuration
* [x] Commit workflow
* [x] Push workflow

### CI/CD

* [ ] Continuous integration
* [ ] Automated test execution
* [ ] Static analysis
* [ ] Code style checks
* [ ] Continuous deployment

---

## v0.2.0 - Kernel

### Extension Architecture

The original Module concept evolved into the Pixely Extension architecture.

* [x] Extension contract
* [x] Extension manifest
* [x] Extension registry
* [x] Extension discovery
* [x] Extension repository
* [x] Extension manager
* [x] Extension state
* [x] Extension status
* [x] Extension registration
* [x] Extension boot process
* [x] Extension enable / disable lifecycle
* [x] Extension dependency declaration
* [x] Extension dependency validation
* [x] Circular dependency detection
* [x] Extension state persistence

### Extension Configuration

* [x] `ExtensionConfigurableInterface`
* [x] Default extension configuration
* [x] Configuration access
* [x] Configuration overrides
* [x] Configuration persistence
* [x] Configuration existence checks
* [x] Configuration removal
* [x] Configuration tests

### Extension Commands

* [x] Extension enable command
* [x] Extension disable command
* [x] Persistent extension state
* [x] Extension command tests

### Kernel Tests

* [x] Unit tests for extension contracts
* [x] Unit tests for extension configuration
* [x] Unit tests for extension persistence
* [x] Feature tests for extension lifecycle
* [x] Feature tests for extension commands
* [x] Dependency validation tests

---

## v0.3.0 - Core

### Authentication

* [ ] Authentication
* [ ] Login
* [ ] Logout
* [ ] Password management
* [ ] Authentication API

### Users

* [ ] User model
* [ ] User management
* [ ] User profile
* [ ] User preferences

### Roles & Permissions

* [ ] Role system
* [ ] Permission system
* [ ] Role assignment
* [ ] Permission checks
* [ ] Extension permissions

### Settings

* [ ] Platform settings
* [ ] User settings
* [ ] Extension settings
* [ ] Persistent settings storage

### Localization

* [ ] Translation system
* [ ] Locale management
* [ ] Language selection
* [ ] Extension translations

---

## v0.4.0 - Administration

### Dashboard

* [ ] Administration dashboard
* [ ] System status
* [ ] Extension status
* [ ] Platform statistics

### Extension Manager

* [x] Extension registration
* [x] Extension discovery
* [x] Extension state management
* [x] Enable extension
* [x] Disable extension
* [ ] Install extension
* [ ] Uninstall extension
* [ ] Update extension
* [ ] Extension version management
* [ ] Dependency visualization

### User Management

* [ ] User listing
* [ ] User creation
* [ ] User editing
* [ ] User deletion
* [ ] Role management

### Settings

* [ ] Administration settings
* [ ] Extension configuration UI
* [ ] Platform configuration UI

---

## v0.5.0 - Extension SDK

The Extension SDK provides a stable foundation for building independent Pixely extensions.

### Extension Structure

* [x] Extension manifest
* [x] Extension contract
* [x] Extension service providers
* [x] Extension routes
* [x] Extension API routes
* [x] Extension models
* [x] Extension controllers

### Extension Lifecycle

* [x] Registration
* [x] Discovery
* [x] Boot
* [x] Enable
* [x] Disable
* [x] Dependency validation
* [x] State persistence

### Configuration

* [x] Default configuration
* [x] Configuration overrides
* [x] Configuration persistence
* [x] Configuration access
* [x] Configuration removal

### Database

* [ ] Extension migrations
* [ ] Migration versioning
* [ ] Extension database isolation
* [ ] Migration rollback

### Assets

* [ ] Extension assets
* [ ] Asset publishing
* [ ] Frontend assets
* [ ] Extension views

### Versioning

* [x] Extension manifest version
* [ ] Extension version compatibility
* [ ] Platform / extension compatibility rules
* [ ] Extension upgrade mechanism

### Developer Experience

* [ ] Extension generator
* [ ] Extension development template
* [ ] Extension testing helpers
* [ ] Extension SDK documentation

---

## v0.6.0 - Platform API

### API Foundation

* [x] API route architecture
* [x] Extension-owned API routes
* [x] API middleware
* [x] JSON API responses
* [ ] API versioning
* [ ] API error format
* [ ] API documentation

### REST API

* [x] Gallery listing endpoint
* [x] Gallery detail endpoint
* [x] Gallery upload endpoint
* [x] Gallery update endpoint
* [x] Gallery delete endpoint
* [ ] Generic extension API conventions
* [ ] API pagination
* [ ] API filtering
* [ ] API sorting

### API Authentication

* [ ] API authentication
* [ ] API tokens
* [ ] Token permissions
* [ ] Extension API permissions

### API Documentation

* [x] API route documentation structure
* [ ] OpenAPI specification
* [ ] OpenAPI schemas
* [ ] API examples
* [ ] API documentation generation

### Public SDK

* [ ] Public API SDK
* [ ] PHP SDK
* [ ] JavaScript / TypeScript SDK
* [ ] SDK authentication
* [ ] SDK documentation

---

## v1.0.0 - Gallery Extension

The Gallery Extension is the first complete Pixely Platform extension and the first major functional module.

### Gallery Foundation

* [x] Gallery extension
* [x] Gallery manifest
* [x] Gallery service provider
* [x] Gallery web routes
* [x] Gallery API routes
* [x] Gallery photo model
* [x] Gallery database structure

### Gallery Web

* [x] Gallery web route
* [ ] Gallery listing page
* [ ] Photo detail page
* [ ] Photo upload interface
* [ ] Photo editing interface
* [ ] Photo deletion interface

### Gallery API

* [x] `GET /api/gallery`
* [x] `GET /api/gallery/{photo}`
* [x] `POST /api/gallery/upload`
* [x] `PUT /api/gallery/{photo}`
* [x] `DELETE /api/gallery/{photo}`

### Gallery Upload

* [x] Image validation
* [x] Image storage
* [x] Photo database persistence
* [x] Stored file verification
* [x] Stored file deletion
* [x] Upload API tests
* [ ] Image resizing
* [ ] Thumbnail generation
* [ ] Image optimization

### Albums

* [ ] Album model
* [ ] Album creation
* [ ] Album editing
* [ ] Album deletion
* [ ] Album / photo relationship
* [ ] Album API
* [ ] Album interface

### Photos

* [x] Photo model
* [x] Photo creation
* [x] Photo retrieval
* [x] Photo update
* [x] Photo deletion
* [ ] Photo metadata
* [ ] Photo visibility
* [ ] Photo ordering

### Comments

* [ ] Comment model
* [ ] Comment creation
* [ ] Comment editing
* [ ] Comment deletion
* [ ] Comment moderation
* [ ] Comment API

### Tags

* [ ] Tag model
* [ ] Photo tagging
* [ ] Tag management
* [ ] Tag API

### Search

* [ ] Photo search
* [ ] Album search
* [ ] Tag search
* [ ] Search API
* [ ] Search filters

### EXIF

* [ ] EXIF extraction
* [ ] EXIF storage
* [ ] Camera information
* [ ] GPS metadata
* [ ] EXIF privacy controls

---

# Future Extensions

Pixely Platform is designed to support multiple independent extensions.

## Blog Extension

* [ ] Articles
* [ ] Categories
* [ ] Tags
* [ ] Comments
* [ ] Publishing workflow
* [ ] Blog API

## Shop Extension

* [ ] Products
* [ ] Categories
* [ ] Cart
* [ ] Orders
* [ ] Payments
* [ ] Shop API

## Media Extension

* [ ] Media library
* [ ] File management
* [ ] Storage abstraction
* [ ] Image processing
* [ ] File metadata

---

# Long-Term Platform Goals

### Extension Ecosystem

* [ ] Extension marketplace
* [ ] Extension installation
* [ ] Extension updates
* [ ] Extension dependency resolution
* [ ] Extension compatibility checks
* [ ] Extension security validation

### Platform

* [ ] Multi-language platform
* [ ] Multi-site support
* [ ] Configuration management
* [ ] Event system
* [ ] Job / queue system
* [ ] Notification system
* [ ] Caching
* [ ] Logging and monitoring

### Developer Platform

* [ ] Complete Extension SDK
* [ ] Extension generator
* [ ] Developer documentation
* [ ] API documentation
* [ ] CLI tooling
* [ ] Extension testing framework

---

# Current Progress

The Pixely Platform currently has a functional extension foundation with:

* Extension contracts and manifests
* Extension discovery and registry
* Extension lifecycle management
* Dependency management
* Extension state persistence
* Extension configuration and persistence
* Extension commands
* Gallery extension
* Gallery photo model
* Gallery web routes
* Gallery API
* Gallery CRUD operations
* Image upload and storage
* Automatic stored-file deletion
* Automated tests

The next development focus is to consolidate the **Gallery API**, improve API documentation with OpenAPI, then continue the Gallery feature set with albums, metadata, thumbnails and search.

# Pixely Platform Architecture

## Core + Extensions Pattern

Pixely Platform is a modular application platform built on Laravel.
Its architecture is based on a small Core and an extensible system where all business features are implemented as Extensions.

### Core Responsibilities
- Authentication
- Users
- Roles & Permissions
- Settings
- Localization
- Dashboard
- Notifications
- Events
- Scheduler
- API
- Module Engine

### Extension Types
- Module
- Theme
- Widget
- Integration
- Language Pack

### Extension Lifecycle
```
Discover
    ↓
Register
    ↓
Install
    ↓
Enable
    ↓
Boot
    ↓
Run
    ↓
Disable
    ↓
Uninstall
```

### Core Principles
- Follow Laravel conventions
- Keep the Core as small as possible
- Everything is an Extension
- Extensions communicate through Contracts and Events
- Documentation is part of the product

## API Architecture
- Versioned HTTP API: `/api/v1/...`
- OpenAPI documentation via Scramble (`dedoc/scramble`)
- Swagger UI at `/docs/api`

## Authentication
Laravel Sanctum in SPA mode:
- Session-cookie authentication
- CSRF handled by Sanctum's stateful-domain handling
- Protected routes use `auth:sanctum` middleware
- Frontend calls `GET /sanctum/csrf-cookie` before login
- Sends `credentials: 'include'` plus `X-XSRF-TOKEN` header

## Testing
- PHPUnit / Pest for backend
- Vitest for frontend
- PHPStan for static analysis
- ESLint for TypeScript/Vue
- Stylelint for SCSS

## Technology Stack
### Backend
- Laravel
- PHP 8.3+
- MySQL
- Redis
- Spatie Laravel Permission
- Laravel Debugbar (dev only)
- Laravel Telescope (dev only, permission-gated)

### Frontend
- Vue 3
- TypeScript
- Vite
- Tailwind CSS
- Vuetify

### Infrastructure
- Docker
- GitHub Actions
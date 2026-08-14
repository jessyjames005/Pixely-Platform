# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog.
Versioning follows Semantic Versioning.

## [Unreleased]

### Added
- Initial project structure
- Project vision
- Roadmap
- Architecture Decision Records

## [0.1.0] - In Progress

### Added
- Repository initialization
- Documentation foundation

### Added

- Added Gallery API endpoints for listing, viewing, creating, updating and deleting photos.
- Added Gallery image upload through the API.
- Added Gallery upload validation.
- Added public storage handling for uploaded gallery images.
- Added automatic deletion of the stored image when a photo is deleted.
- Added feature tests covering the Gallery API.

### Added

- Added pagination support to the Gallery API.
- Added configurable `page` and `per_page` query parameters.
- Limited Gallery API page size to a maximum of 100 photos.
- Added pagination metadata to Gallery API responses.
- Added automated tests for Gallery API pagination.
- Documented Gallery API pagination in OpenAPI.

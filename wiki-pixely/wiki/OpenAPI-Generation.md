> _This page is generated from [`docs/api/openapi-generation.md`](https://github.com/jessyjames005/Pixely-Platform/blob/develop/docs/api/openapi-generation.md). Edit it in the repository, not in the wiki: wiki edits are overwritten._

# OpenAPI Specification Generation

## Source of truth

PHP OpenAPI Attributes in application and extension source code are the source of truth for the API specification.

The generated artifact is:

```text
docs/api/openapi.yml
```

Generate it with the repository's configured `zircote/swagger-php` command (see `composer.json` scripts and the CI workflow). Do not edit `openapi.yml` manually: regenerate it after changing API attributes and review the generated diff.

Swagger UI is the official interactive interface for browsing the generated specification. Do not introduce Redoc as a competing official API UI.

## Extension requirements

An extension that exposes API endpoints must declare the relevant OpenAPI metadata in its PHP source. Keep schemas, operations, parameters, responses, security requirements and error formats aligned with the actual API implementation.

## Review checklist

- [ ] PHP Attributes describe the implemented request/response behavior.
- [ ] Extension endpoints are included in generation.
- [ ] The generated YAML is refreshed and committed when the source changes.
- [ ] The specification generation check passes in CI.
- [ ] Swagger UI displays the generated document.

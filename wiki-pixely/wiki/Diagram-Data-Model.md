> _This page is generated from [`docs/architecture/diagrams/data-model.md`](https://github.com/jessyjames005/Pixely-Platform/blob/develop/docs/architecture/diagrams/data-model.md). Edit it in the repository, not in the wiki: wiki edits are overwritten._

# Platform Data Model

```mermaid
erDiagram

users
roles
permissions
settings
extensions
extension_versions
languages

users ||--o{ roles : has
roles ||--o{ permissions : grants
extensions ||--|| extension_versions : version
```
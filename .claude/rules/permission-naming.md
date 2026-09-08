# Permission Naming Convention Rules

Path-scoped rules for permission definitions in `app/**/Providers/*.php` and any code registering permissions.

## Format
Permissions follow a fixed pattern: `<domain>.<object>.<action>`.

### CRUD Objects
Use exactly these three action suffixes — never `create`/`update`/`edit` as separate permissions:

- `view` — read access
- `manage` — create + update (deliberately merged; if a role needs to create/edit but never delete, grant `manage` without `delete`)
- `delete` — separate and revocable independently of `manage`, since deletion is irreversible

A role with no permission at all for an object is implicitly forbidden — there is no explicit "forbidden" permission to create.

### Examples
```
gallery.photos.view
gallery.photos.manage
gallery.photos.delete

blog.posts.view
blog.posts.manage
blog.posts.delete

shop.products.view
shop.products.manage
shop.products.delete
```

### Non-CRUD Tools
Platform tools that aren't CRUD objects use explicit action names:

```
system.logs.view
system.cache.clear
system.sql.query
system.extensions.install
system.extensions.manage
system.telescope.view
```

## Rules
- Always set `guard_name: 'web'` explicitly when creating Role/Permission (see section on Spatie guard drift with Sanctum)
- New extensions must declare their own permissions using this convention
- Extension-owned permission registration (synced on enable) is planned but not yet implemented — see `ROADMAP.md`
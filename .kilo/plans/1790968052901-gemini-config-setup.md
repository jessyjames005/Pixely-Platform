# Plan: Adapting Roadmap to New Target Architecture (Modular Platform)

## 1. Context & Diagnosis of the Existing

The target architecture document formalizes the vision of Pixely Platform:
- **Core**: Shared generic capabilities (Auth/ACL, Extensions, Events, Jobs, Settings, API, Logs, Audit).
- **Extensions**: Independent business functionalities (`Files`, `Gallery`, `Converter`, `Translations`).
- **Applications**: Separate user-facing experiences (`Admin`, `Gallery public`, `Converter public`).
- **Key Dependency**: `Core` does not depend on any business extension; `Gallery` and `Converter` both depend on `Files`.

### Current state in the repository:

1. **Kernel & Extensions**: Already mature (~80% compliant). Discovery, lifecycle (Discover, Register, Enable, Disable, Install, Uninstall, Incremental upgrade by steps), dependency management between extensions with circular dependency detection, and automatic synchronization of declared permissions (`ExtensionPermissionSynchronizer`).

2. **Files Extension**: Already exists with `FileUploadValidator`, `FileUploadService`, `files` table, and standalone API/UI (`/admin/files`). *Target gap*: The current model is still flat (no variant table yet, and Gallery photos are not yet linked to `File`).

3. **Gallery Extension**: Exists and works, but the `Photo` model stores its own `filename` and `thumbnail_filename` columns. *Target gap*: Should be refactored to use `Album → Media → File` without duplicating storage fields.

4. **Translations (Translets)**: Already aligned with vision. The Core provides the loading service and the public unauthenticated endpoint (`/api/v1/locales/{locale}`), while each extension has its own `lang/{fr,en}` catalogs and the `Translations` extension provides the administration interface.

5. **Jobs & Queues**: Redis is configured in Docker, but heavy processing (resizing, conversions) are not yet asynchronous via dedicated queues (`media`, `documents`).

6. **Converter Extension**: Planned in the roadmap under the name "Media Conversion Extension", but not implemented.

---

## 2. Assessment of the Scope of the Project ("Is it too big?")

### Conclusion: **No, the project is not too big**, on two conditions imperative:

1. **Do not succumb to premature over-development**:
   - The target document lists 89 points including multi-tenancy SaaS, CDN, WebSocket, SSR/SSG for the public Gallery, and microservices.
   - The document itself advocates for evolution on a Modular Monolith (section 53) and classifies multi-tenancy/microservices as low priority ★★☆☆☆ (section 86).
   - These complex aspects should be deferred to later phases.

2. **Capitalize on the 60% already built**:
   - The routing JSON:API core, the Extension Manager, the role system, and the Vuetify 4 UI are already in place and functional.
   - The real work immediately consists of model adjustments (`File` enriched, `Gallery` branched onto `Files`, architecture of `Jobs/Queues`), which are manageable incremental evolutions.

---

## 3. Breakdown of the Project into 5 Pragmatic Phases

To adapt the roadmap without blocking development:

### Phase 1 — Files Foundation & Storage (Pivot foundation)
- Enrich the `File` model in the `Files` extension:
   - Columns: `uuid`, `checksum`, `visibility` (`private`, `public`, `unlisted`, `shared`), `status` (`uploaded`, `validating`, `ready`, `processing`, `available`, `rejected`, `deleted`).
   - Metadata system (`FileMetadata` or typed JSON column: dimensions, EXIF, duration, etc.).
   - Variant management (`FileVariant`: original, thumbnail, medium, large, webp, avif).
   - Separate directories: `quarantine`, `originals`, `derivatives`.
- Maintain backward compatibility for avatars and existing files.

### Phase 2 — Gallery Refactoring (Strict dependence on Files)
- Refactor the Gallery model:
   - `Album` (id, title, slug, description, visibility, cover_media_id, settings).
   - `Media` (id, file_id [FK to `files`], album_id, title, description, position, metadata, published_at).
   - Eliminate the `Photo` table duplication.
- Complete delegation of upload and derivatives to `Files`.

### Phase 3 — Core Events & Asynchronous Jobs
- Core event system:
   - `FileUploaded`, `FileDeleted`, `ConversionStarted`, `ConversionCompleted`.
- Redis queues specialized:
   - `default`, `media`, `documents`, `maintenance`.
- Move heavy image processing (thumbnails, WebP, EXIF stripping on public derivatives) into asynchronous jobs (no more blocking in the HTTP upload request).

### Phase 4 — Extension Converter (Media & Documents)
- Create the `Converter` extension dependent on `Files`.
- `Conversion` model (id, source_file_id, target_format, status, progress, output_file_id).
- `ProcessorInterface` contract and implementations (`FFmpegProcessor`, `ImageProcessor`, `PdfProcessor`).
- Dynamic capabilities endpoint: `GET /api/v1/converters/capabilities`.
- Asynchronous conversion endpoint: `POST /api/v1/conversions` (returns HTTP 202 Accepted + Job ID).
- Vue 3 administration interface for tracking conversions.

### Phase 5 — Frontends Decoupling (Long-term)
- Keep the current Vue 3 Admin as the central SPA.
- Share generic bricks (`@platform/api`, `@platform/types`, `@platform/ui`).
- Prepare for the emergence of light public frontends (`photos.*`, `convert.*`).

---

## 4. Specific Modifications to Apply to `ROADMAP.md`

1. **Section `Files Extension` (in ROADMAP.md)**:
   - Add the following subsections: *Variant File Model*, *File Metadata*, *Storage Pipeline & Quarantine*.

2. **Section `v1.0.0 - Gallery Extension`**:
   - Replace the specifications of `Photo` by `Media → File` and integrate `Albums`.
   - Add asynchronous processing via Jobs and EXIF stripping on publication.

3. **Section `Future Extensions > Media Conversion Extension`**:
   - Rename it to `Converter Extension` and structure it according to points 22 to 26 of the target document (contract `ProcessorInterface`, dynamic capabilities, HTTP 202 response, dedicated queues).

4. **Section Core Platform**:
   - Explicitly add the events Core (`Event-Driven Architecture`) and the queue management (`Queue System`).

5. **Section `Current Execution Order`**:
   - Update the execution order to place the strengthening of `Files` and the refactoring of `Gallery (Album → Media → File)` in the logical sequence before the Converter extension.

---

## 5. Validation Criteria for the Plan

- [ ] The roadmap `ROADMAP.md` reflects exactly the principles of the target architecture without contradiction.
- [ ] The features already completed (marked `[x]`) are strictly preserved.
- [ ] The project is segmented into progressive steps avoiding a development halt.
- [ ] The immediate execution order (`Current Execution Order`) is clarified.

(End of file - total 98 lines)
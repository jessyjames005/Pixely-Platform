# Plan : Adaptation de la Roadmap à la Nouvelle Architecture Cible (Plateforme Modulaire)

## 1. Contexte & Diagnostic de l'Existant

Le document d'architecture cible formalise la vision de **Pixely Platform** :
- **Core** : Capacités génériques transverses (Auth/ACL, Extensions, Events, Jobs, Settings, API, Logs, Audit).
- **Extensions** : Fonctionnalités métier indépendantes (`Files`, `Gallery`, `Converter`, `Translations`).
- **Applications** : Expériences utilisateurs séparées (`Admin`, `Gallery publique`, `Converter public`).
- **Dépendance clé** : `Core` ne dépend d'aucune extension métier ; `Gallery` et `Converter` dépendent tous deux de `Files`.

### État de l'existant dans le dépôt :
1. **Kernel & Extensions** : Déjà mature (~80% conforme). Découverte, cycle de vie (Discover, Register, Enable, Disable, Install, Uninstall, Upgrade incrémental par steps), dépendances entre extensions avec détection de cycles, et synchronisation automatique des permissions déclarées (`ExtensionPermissionSynchronizer`).
2. **Files Extension** : Existe déjà avec `FileUploadValidator`, `FileUploadService`, table `files`, et API/UI standalone (`/admin/files`). *Écart cible* : le modèle actuel est encore plat (pas encore de table de variantes, pas de cycle de vie formel `uploaded -> validating -> ready`, et les photos de Gallery ne sont pas encore reliées à `File`).
3. **Gallery Extension** : Existe et fonctionne, mais avec un modèle `Photo` stockant ses propres colonnes `filename` et `thumbnail_filename`. *Écart cible* : doit être refactorisée pour utiliser `Album -> Media -> File` sans dupliquer les métadonnées de fichier.
4. **Translations (Translets)** : Déjà aligné avec la vision. Le Core fournit le service de chargement et l'endpoint public unauthenticated (`/api/v1/locales/{locale}`), tandis que chaque extension possède ses catalogues `lang/{fr,en}` et que l'extension `Translations` fournit l'interface d'administration.
5. **Jobs & Queues** : Redis est configuré dans Docker, mais les traitements lourds (redimensionnement, conversions) ne sont pas encore asynchrones via des files dédiées (`media`, `documents`).
6. **Converter Extension** : Prévue dans la roadmap sous le nom "Media Conversion Extension", mais non implémentée.

---

## 2. Évaluation de l'Ampleur du Chantier ("Est-il trop gros ?")

### Conclusion : **Non, le chantier n'est pas trop gros**, à 2 conditions impératives :

1. **Ne pas céder au sur-développement prématuré** :
   - Le document cible liste 89 points incluant le multi-tenancy SaaS, CDN, WebSockets, SSR/SSG pour la galerie publique, et microservices.
   - Le document lui-même préconise de **rester sur un Monolithe Modulaire** (section 53) et classe le multi-tenancy/microservices en priorité basse ★★☆☆☆ (section 86).
   - Ces aspects complexes doivent être repoussés en phases ultérieures.

2. **Capitaliser sur les 60% déjà construits** :
   - Le socle de routing JSON:API, l'Extension Manager, le système de rôles, et l'UI Vuetify 4 sont déjà en place et fonctionnels.
   - Le chantier réel immédiat consiste en des ajustements de modélisation (`File` enrichi, `Gallery` branchée sur `Files`, architecture de `Jobs/Queues`), ce qui représente des évolutions incrémentales maîtrisables.

---

## 3. Découpage du Chantier en 5 Phases Pragmatiques

Pour adapter la roadmap sans bloquer le développement courant :

### Phase 1 — Socle Files & Storage (Fondation pivot)
- Enrichir le modèle `File` dans l'extension `Files` :
  - Colonnes : `uuid`, `checksum`, `visibility` (`private`, `public`, `unlisted`, `shared`), `status` (`uploaded`, `validating`, `ready`, `processing`, `available`, `rejected`, `deleted`).
  - Système de métadonnées (`FileMetadata` ou colonne JSON typée : dimensions, EXIF, durée, etc.).
  - Gestion des variantes (`FileVariant` : original, thumbnail, medium, large, webp, avif).
  - Séparation des disques/répertoires de stockage : `quarantine`, `originals`, `derivatives`.
- Maintien de la rétrocompatibilité pour les avatars et fichiers existants.

### Phase 2 — Refactorisation Gallery (Dépendance stricte à Files)
- Refondre le modèle Gallery :
  - `Album` (id, title, slug, description, visibility, cover_media_id, settings).
  - `Media` (id, file_id [FK vers files], album_id, title, description, position, metadata, published_at).
  - Élimination de la table/duplication `Photo.filename`.
- Délégation complète de l'upload et des dérivés à `Files`.

### Phase 3 — Core Events & Jobs Asynchrones
- Système d'événements Core :
  - `FileUploaded`, `FileDeleted`, `ConversionStarted`, `ConversionCompleted`.
- Queues Redis spécialisées :
  - `default`, `media`, `documents`, `maintenance`.
- Déportation des tâches d'image (vignettes, WebP, nettoyage EXIF public) dans des Jobs asynchrones (plus de blocage dans la requête HTTP d'upload).

### Phase 4 — Extension Converter (Media & Documents)
- Création de l'extension `Converter` dépendant de `Files`.
- Modèle `Conversion` (id, source_file_id, target_format, status, progress, output_file_id).
- Contrat `ProcessorInterface` et implémentations (`FFmpegProcessor`, `ImageProcessor`, `PdfProcessor`).
- Endpoint dynamique de capacités : `GET /api/v1/converters/capabilities`.
- Endpoint de conversion asynchrone : `POST /api/v1/conversions` (retourne HTTP 202 Accepted + Job ID).
- Interface Vue 3 d'administration pour suivre les conversions.

### Phase 5 — Découplage des Applications Frontends (Long terme)
- Conserver l'Admin Vue 3 actuelle comme SPA centrale.
- Partager les briques génériques (`@platform/api`, `@platform/types`, `@platform/ui`).
- Préparer l'émergence des frontends publics légers (`photos.*`, `convert.*`).

---

## 4. Modifications Précises à apporter à `ROADMAP.md`

1. **Section `Files Extension` (dans ROADMAP.md)** :
   - Ajouter les sous-sections : *Modèle File pivot*, *File Variants*, *File Metadata*, *Pipeline de Sécurité & Quarantaine*.
2. **Section `v1.0.0 - Gallery Extension`** :
   - Remplacer les spécifications de `Photo` par `Media -> File` et intégrer `Albums`.
   - Ajouter le traitement asynchrone via Jobs et le nettoyage EXIF à la publication.
3. **Section `Future Extensions > Media Conversion Extension`** :
   - Renommer en `Converter Extension` et la structurer selon les points 22 à 26 du document cible (contrat `ProcessorInterface`, dynamic capabilities, réponse HTTP 202, queues dédiées).
4. **Section Core Platform** :
   - Ajouter explicitement les événements Core (`Event-Driven Architecture`) et la gestion des files de travail (`Queue System`).
5. **Section `Current Execution Order`** :
   - Mettre à jour l'ordonnancement pour placer le renforcement de `Files` et la refonte `Gallery (Album -> Media -> File)` dans la séquence logique avant l'extension Converter.

---

## 5. Critères de Validation du Plan

- [ ] La roadmap `ROADMAP.md` reflète exactement les principes de l'architecture cible sans contradiction.
- [ ] Les fonctionnalités déjà terminées (marquées `[x]`) sont strictement préservées.
- [ ] Le chantier est segmenté en étapes progressives évitant un arrêt de développement.
- [ ] L'ordre d'exécution immédiat (`Current Execution Order`) est clarifié.

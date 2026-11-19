<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useApi } from '@shared/composables/useApi'
import { useConfirmDialog } from '@shared/composables/useConfirmDialog'
import { useNotify } from '@shared/composables/useNotify'
import { translate as t } from '@shared/plugins/i18n'
import { useAuthStore } from '@core/auth/store/auth.store'
import { useCinemaMovieStore } from '../store/cinema-movie.store'
import type { CinemaMovieItem, MovieStatus, PublicationStatus } from '../models/CinemaMovie'

interface MovieForm {
  originalTitle: string
  releaseDate: string
  runtimeMinutes: number | null
  ageRating: string
  productionStatus: MovieStatus
  publicationStatus: PublicationStatus
  englishTitle: string
  englishOverview: string
  frenchTitle: string
  frenchOverview: string
  posterId: string | null
  backdropId: string | null
}

const headers = [
  { title: t('cinema-movie.object.movie.title', 'Title'), key: 'title' },
  { title: t('cinema-movie.object.movie.release_date', 'Release date'), key: 'releaseDate' },
  { title: t('cinema-movie.object.movie.production_status.label', 'Production'), key: 'productionStatus' },
  { title: t('cinema-movie.object.movie.publication_status.label', 'Publication'), key: 'publicationStatus' },
  { title: '', key: 'actions', align: 'end' as const, sortable: false },
]
const genreHeaders = [
  { title: t('cinema-movie.object.genre.name', 'Genre'), key: 'name' },
  { title: t('cinema-movie.object.genre.slug', 'Slug'), key: 'slug' },
  { title: '', key: 'actions', align: 'end' as const, sortable: false },
]
const productionStatuses: MovieStatus[] = [
  'rumored', 'planned', 'in_production', 'post_production', 'released', 'canceled',
]
const publicationStatuses: PublicationStatus[] = ['draft', 'published', 'archived']
const productionStatusItems = computed(() => productionStatuses.map((value) => ({
  title: t(`cinema-movie.object.movie.production_status.${value}`, value.replaceAll('_', ' ')),
  value,
})))
const publicationStatusItems = computed(() => publicationStatuses.map((value) => ({
  title: t(`cinema-movie.object.movie.publication_status.${value}`, value),
  value,
})))
const perPage = 20

const authStore = useAuthStore()
const store = useCinemaMovieStore()
const notify = useNotify()
const { confirm } = useConfirmDialog()
const { loading, error, execute: fetchItems } = useApi(store.fetchItems)
const { loading: saving, error: saveError, execute: submitSave } = useApi(store.saveMovie)
const { loading: deleting, error: deleteError, execute: submitDelete } = useApi(store.deleteMovie)
const { loading: genresLoading, error: genresError, execute: fetchGenres } = useApi(store.fetchGenres)
const { loading: mediaLoading, error: mediaError, execute: fetchMediaFiles } = useApi(store.fetchMediaFiles)
const { loading: savingGenre, error: genreSaveError, execute: submitGenreSave } = useApi(store.saveGenre)
const { loading: deletingGenre, error: genreDeleteError, execute: submitGenreDelete } = useApi(store.deleteGenre)

const dialogOpen = ref(false)
const genreDialogOpen = ref(false)
const editingMovie = ref<CinemaMovieItem | null>(null)
const editingGenre = ref<string | null>(null)
const currentPage = ref(1)
const form = reactive<MovieForm>(emptyForm())
const selectedGenreIds = ref<string[]>([])
const englishGenreName = ref('')
const frenchGenreName = ref('')
const isEditing = computed(() => editingMovie.value !== null)

function emptyForm(): MovieForm {
  return {
    originalTitle: '',
    releaseDate: '',
    runtimeMinutes: null,
    ageRating: '',
    productionStatus: 'rumored',
    publicationStatus: 'draft',
    englishTitle: '',
    englishOverview: '',
    frenchTitle: '',
    frenchOverview: '',
    posterId: null,
    backdropId: null,
  }
}

onMounted(() => Promise.all([fetchItems(currentPage.value, perPage), fetchGenres(), fetchMediaFiles()]))

function openCreateDialog(): void {
  editingMovie.value = null
  selectedGenreIds.value = []
  Object.assign(form, emptyForm())
  dialogOpen.value = true
}

function openEditDialog(movie: CinemaMovieItem): void {
  editingMovie.value = movie
  selectedGenreIds.value = movie.genres.map((genre) => genre.id)
  const english = movie.translations.find((translation) => translation.locale === 'en')
  const french = movie.translations.find((translation) => translation.locale === 'fr')
  Object.assign(form, {
    ...emptyForm(),
    originalTitle: movie.originalTitle ?? english?.title ?? '',
    releaseDate: movie.releaseDate ?? '',
    runtimeMinutes: movie.runtimeMinutes,
    ageRating: movie.ageRating ?? '',
    productionStatus: movie.productionStatus,
    publicationStatus: movie.publicationStatus,
    englishTitle: english?.title ?? '',
    englishOverview: english?.overview ?? '',
    frenchTitle: french?.title ?? '',
    frenchOverview: french?.overview ?? '',
    posterId: movie.posterId,
    backdropId: movie.backdropId,
  })
  dialogOpen.value = true
}

async function save(): Promise<void> {
  const attributes = {
    originalTitle: form.originalTitle.trim(),
    releaseDate: form.releaseDate || null,
    runtimeMinutes: form.runtimeMinutes,
    ageRating: form.ageRating || null,
    productionStatus: form.productionStatus,
    publicationStatus: form.publicationStatus,
  }
  const translations = [
    { locale: 'en', title: form.englishTitle.trim(), overview: form.englishOverview || null, tagline: null },
    { locale: 'fr', title: form.frenchTitle.trim(), overview: form.frenchOverview || null, tagline: null },
  ]
  const result = await submitSave(
    editingMovie.value?.id ?? null,
    attributes,
    translations,
    selectedGenreIds.value,
    form.posterId,
    form.backdropId,
  )
  if (!result) return

  notify.success(t('cinema-movie.msg.movie_saved', 'Movie saved.'))
  dialogOpen.value = false
  await fetchItems(currentPage.value, perPage)
}

async function remove(movie: CinemaMovieItem): Promise<void> {
  const title = displayTitle(movie)
  if (!await confirm({
    title: t('cinema-movie.title.delete_movie', 'Delete movie'),
    message: t('cinema-movie.msg.confirm_delete_movie', 'Delete “:title”? This cannot be undone.', { title }),
    confirmText: t('common.action.delete', 'Delete'),
  })) return

  await submitDelete(movie.id)
  if (deleteError.value) return
  notify.success(t('cinema-movie.msg.movie_deleted', 'Movie deleted.'))
  await fetchItems(currentPage.value, perPage)
}

function displayTitle(movie: CinemaMovieItem): string {
  return movie.translations.find((translation) => translation.locale === 'en')?.title
    || movie.originalTitle
    || movie.slug
}

async function changePage(page: number): Promise<void> {
  currentPage.value = page
  await fetchItems(page, perPage)
}

function displayGenre(genre: (typeof store.genres)[number]): string {
  return genre.translations.find((translation) => translation.locale === 'en')?.name
    || genre.translations.find((translation) => translation.locale === 'fr')?.name
    || genre.slug
}

function openCreateGenreDialog(): void {
  editingGenre.value = null
  englishGenreName.value = ''
  frenchGenreName.value = ''
  genreDialogOpen.value = true
}

function openEditGenreDialog(genre: (typeof store.genres)[number]): void {
  editingGenre.value = genre.id
  englishGenreName.value = genre.translations.find((translation) => translation.locale === 'en')?.name ?? ''
  frenchGenreName.value = genre.translations.find((translation) => translation.locale === 'fr')?.name ?? ''
  genreDialogOpen.value = true
}

async function saveGenre(): Promise<void> {
  const result = await submitGenreSave(editingGenre.value, [
    { locale: 'en', name: englishGenreName.value.trim() },
    { locale: 'fr', name: frenchGenreName.value.trim() },
  ])
  if (!result) return

  notify.success(t('cinema-movie.msg.genre_saved', 'Genre saved.'))
  genreDialogOpen.value = false
  await Promise.all([fetchGenres(), fetchItems(currentPage.value, perPage)])
}

async function removeGenre(genre: (typeof store.genres)[number]): Promise<void> {
  if (!await confirm({
    title: t('cinema-movie.title.delete_genre', 'Delete genre'),
    message: t('cinema-movie.msg.confirm_delete_genre', 'Delete “:name”? Movies will lose this genre.', { name: displayGenre(genre) }),
    confirmText: t('common.action.delete', 'Delete'),
  })) return

  await submitGenreDelete(genre.id)
  if (genreDeleteError.value) return
  notify.success(t('cinema-movie.msg.genre_deleted', 'Genre deleted.'))
  await Promise.all([fetchGenres(), fetchItems(currentPage.value, perPage)])
}
</script>

<template>
  <section>
    <div class="d-flex align-center justify-space-between flex-wrap ga-3 mb-4">
      <h1 class="text-h5">
        {{ t('cinema-movie.title.admin', 'Cinema movies') }}
      </h1>
      <v-btn
        v-if="authStore.can('cinema-movie.movies.manage')"
        color="primary"
        prepend-icon="mdi-plus"
        @click="openCreateDialog"
      >
        {{ t('cinema-movie.action.add_movie', 'Add movie') }}
      </v-btn>
    </div>

    <v-card>
      <v-card-title class="d-flex align-center justify-space-between">
        {{ t('cinema-movie.title.movie_catalogue', 'Movie catalogue') }}
        <v-btn
          size="small"
          variant="tonal"
          :loading="loading"
          @click="fetchItems(currentPage, perPage)"
        >
          {{ t('common.action.refresh', 'Refresh') }}
        </v-btn>
      </v-card-title>

      <v-alert
        v-if="error"
        type="error"
        density="compact"
        class="mx-4"
      >
        {{ error.message }}
      </v-alert>
      <v-alert
        v-if="deleteError"
        type="error"
        density="compact"
        class="mx-4"
      >
        {{ deleteError.message }}
      </v-alert>

      <v-data-table
        :headers="headers"
        :items="store.items"
        :loading="loading"
        item-value="id"
        :items-per-page="perPage"
        hide-default-footer
      >
        <template #no-data>
          <div class="py-8 text-center text-medium-emphasis">
            {{ loading ? t('cinema-movie.state.loading', 'Loading movies…') : t('cinema-movie.state.empty', 'No movies yet.') }}
          </div>
        </template>
        <template #item.title="{ item }">
          {{ displayTitle(item) }}
        </template>
        <template #item.releaseDate="{ item }">
          {{ item.releaseDate || '—' }}
        </template>
        <template #item.productionStatus="{ item }">
          {{ t(`cinema-movie.object.movie.production_status.${item.productionStatus}`, item.productionStatus.replaceAll('_', ' ')) }}
        </template>
        <template #item.publicationStatus="{ item }">
          <v-chip
            size="small"
            :color="item.publicationStatus === 'published' ? 'success' : undefined"
          >
            {{ t(`cinema-movie.object.movie.publication_status.${item.publicationStatus}`, item.publicationStatus) }}
          </v-chip>
        </template>
        <template #item.actions="{ item }">
          <v-btn
            v-if="authStore.can('cinema-movie.movies.manage')"
            icon="mdi-pencil"
            size="small"
            variant="text"
            :aria-label="t('cinema-movie.action.edit_movie', 'Edit movie')"
            @click="openEditDialog(item)"
          />
          <v-btn
            v-if="authStore.can('cinema-movie.movies.delete')"
            icon="mdi-delete"
            size="small"
            variant="text"
            color="error"
            :loading="deleting"
            :aria-label="t('cinema-movie.action.delete_movie', 'Delete movie')"
            @click="remove(item)"
          />
        </template>
      </v-data-table>

      <v-card-actions
        v-if="store.meta && store.meta.last_page && store.meta.last_page > 1"
        class="justify-center"
      >
        <v-pagination
          :model-value="store.meta.current_page ?? currentPage"
          :length="store.meta.last_page"
          :disabled="loading"
          @update:model-value="changePage"
        />
      </v-card-actions>
    </v-card>

    <v-dialog
      v-model="dialogOpen"
      max-width="760"
      persistent
      scrollable
    >
      <v-card :title="isEditing ? t('cinema-movie.title.edit_movie', 'Edit movie') : t('cinema-movie.title.add_movie', 'Add movie')">
        <v-card-text>
          <v-form @submit.prevent="save">
            <div class="text-subtitle-1 mb-2">
              {{ t('cinema-movie.title.movie_details', 'Movie details') }}
            </div>
            <v-text-field
              v-model="form.originalTitle"
              :label="t('cinema-movie.object.movie.original_title', 'Original title')"
              required
              maxlength="255"
            />
            <div class="d-sm-flex ga-3">
              <v-text-field
                v-model="form.releaseDate"
                type="date"
                :label="t('cinema-movie.object.movie.release_date', 'Release date')"
              />
              <v-text-field
                v-model.number="form.runtimeMinutes"
                type="number"
                min="1"
                max="1000"
                :label="t('cinema-movie.object.movie.runtime_minutes', 'Runtime (minutes)')"
              />
              <v-text-field
                v-model="form.ageRating"
                :label="t('cinema-movie.object.movie.age_rating', 'Age rating')"
                maxlength="10"
              />
            </div>
            <div class="d-sm-flex ga-3">
              <v-select
                v-model="form.productionStatus"
                :items="productionStatusItems"
                :label="t('cinema-movie.object.movie.production_status.label', 'Production status')"
              />
              <v-select
                v-model="form.publicationStatus"
                :items="publicationStatusItems"
                :label="t('cinema-movie.object.movie.publication_status.label', 'Publication status')"
              />
            </div>
            <v-select
              v-model="selectedGenreIds"
              :items="store.genres"
              :item-title="displayGenre"
              item-value="id"
              :label="t('cinema-movie.object.movie.genres', 'Genres')"
              multiple
              chips
              closable-chips
              :loading="genresLoading"
              :error-messages="genresError?.message"
            />
            <div class="d-sm-flex ga-3">
              <v-select
                v-model="form.posterId"
                :items="store.mediaFiles"
                item-title="original_name"
                item-value="id"
                clearable
                :loading="mediaLoading"
                :error-messages="mediaError?.message"
                :label="t('cinema-movie.object.movie.poster', 'Poster image')"
                :hint="t('cinema-movie.msg.media_files_hint', 'Select an image already uploaded in Files.')"
                persistent-hint
              />
              <v-select
                v-model="form.backdropId"
                :items="store.mediaFiles"
                item-title="original_name"
                item-value="id"
                clearable
                :loading="mediaLoading"
                :error-messages="mediaError?.message"
                :label="t('cinema-movie.object.movie.backdrop', 'Backdrop image')"
                persistent-hint
                :hint="t('cinema-movie.msg.media_files_hint', 'Select an image already uploaded in Files.')"
              />
            </div>

            <v-divider class="my-4" />
            <div class="text-subtitle-1 mb-2">
              {{ t('cinema-movie.title.translations', 'Titles and descriptions') }}
            </div>
            <v-text-field
              v-model="form.englishTitle"
              :label="t('cinema-movie.object.movie.english_title', 'English title')"
              :hint="t('cinema-movie.msg.default_locale_required', 'An English title is required before publication.')"
              persistent-hint
            />
            <v-textarea
              v-model="form.englishOverview"
              :label="t('cinema-movie.object.movie.english_overview', 'English overview')"
              rows="2"
              auto-grow
            />
            <v-text-field
              v-model="form.frenchTitle"
              :label="t('cinema-movie.object.movie.french_title', 'French title')"
            />
            <v-textarea
              v-model="form.frenchOverview"
              :label="t('cinema-movie.object.movie.french_overview', 'French overview')"
              rows="2"
              auto-grow
            />

            <v-alert
              v-if="saveError"
              type="error"
              density="compact"
              class="mt-3"
            >
              {{ saveError.message }}
            </v-alert>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn
            variant="text"
            :disabled="saving"
            @click="dialogOpen = false"
          >
            {{ t('common.action.cancel', 'Cancel') }}
          </v-btn>
          <v-btn
            color="primary"
            :loading="saving"
            :disabled="!form.originalTitle.trim() || (form.publicationStatus === 'published' && !form.englishTitle.trim())"
            @click="save"
          >
            {{ t('common.action.save', 'Save') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-card class="mt-6">
      <v-card-title
        data-testid="cinema-movie-genres-heading"
        class="d-flex align-center justify-space-between flex-wrap ga-3"
      >
        {{ t('cinema-movie.title.genres', 'Genres') }}
        <v-btn
          v-if="authStore.can('cinema-movie.movies.manage')"
          size="small"
          color="primary"
          prepend-icon="mdi-plus"
          @click="openCreateGenreDialog"
        >
          {{ t('cinema-movie.action.add_genre', 'Add genre') }}
        </v-btn>
      </v-card-title>
      <v-alert
        v-if="genresError"
        type="error"
        density="compact"
        class="mx-4"
      >
        {{ genresError.message }}
      </v-alert>
      <v-alert
        v-if="genreDeleteError"
        type="error"
        density="compact"
        class="mx-4"
      >
        {{ genreDeleteError.message }}
      </v-alert>
      <v-data-table
        :headers="genreHeaders"
        :items="store.genres"
        :loading="genresLoading"
        item-value="id"
        :items-per-page="100"
        hide-default-footer
      >
        <template #no-data>
          <div class="py-6 text-center text-medium-emphasis">
            {{ genresLoading ? t('cinema-movie.state.loading_genres', 'Loading genres…') : t('cinema-movie.state.empty_genres', 'No genres yet.') }}
          </div>
        </template>
        <template #item.name="{ item }">
          {{ displayGenre(item) }}
        </template>
        <template #item.actions="{ item }">
          <v-btn
            v-if="authStore.can('cinema-movie.movies.manage')"
            icon="mdi-pencil"
            size="small"
            variant="text"
            :aria-label="t('cinema-movie.action.edit_genre', 'Edit genre')"
            @click="openEditGenreDialog(item)"
          />
          <v-btn
            v-if="authStore.can('cinema-movie.movies.delete')"
            icon="mdi-delete"
            size="small"
            variant="text"
            color="error"
            :loading="deletingGenre"
            :aria-label="t('cinema-movie.action.delete_genre', 'Delete genre')"
            @click="removeGenre(item)"
          />
        </template>
      </v-data-table>
    </v-card>

    <v-dialog
      v-model="genreDialogOpen"
      max-width="560"
      persistent
    >
      <v-card :title="editingGenre ? t('cinema-movie.title.edit_genre', 'Edit genre') : t('cinema-movie.title.add_genre', 'Add genre')">
        <v-card-text>
          <v-text-field
            v-model="englishGenreName"
            :label="t('cinema-movie.object.genre.english_name', 'English name')"
            required
          />
          <v-text-field
            v-model="frenchGenreName"
            :label="t('cinema-movie.object.genre.french_name', 'French name')"
          />
          <v-alert
            v-if="genreSaveError"
            type="error"
            density="compact"
            class="mt-3"
          >
            {{ genreSaveError.message }}
          </v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn
            variant="text"
            :disabled="savingGenre"
            @click="genreDialogOpen = false"
          >
            {{ t('common.action.cancel', 'Cancel') }}
          </v-btn>
          <v-btn
            color="primary"
            :loading="savingGenre"
            :disabled="!englishGenreName.trim()"
            @click="saveGenre"
          >
            {{ t('common.action.save', 'Save') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </section>
</template>

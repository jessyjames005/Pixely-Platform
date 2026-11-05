// Pinia store for the CinemaMovie extension catalogue.
import { defineStore } from 'pinia'
import { apiClient } from '@shared/services/apiClient'
import type { JsonApiResource, PaginationMeta } from '@shared/types/api'
import type {
  CinemaGenre,
  CinemaMovieItem,
  GenreAttributes,
  GenreTranslation,
  GenreTranslationAttributes,
  MovieAttributes,
  MovieTranslation,
  MovieTranslationAttributes,
} from '../models/CinemaMovie'

interface MovieInput extends Partial<MovieAttributes> {
  originalTitle: string
}

type TranslationInput = MovieTranslationAttributes
type GenreTranslationInput = GenreTranslationAttributes

interface CinemaMovieState {
  items: CinemaMovieItem[]
  genres: CinemaGenre[]
  meta: PaginationMeta | null
}

type Relationship = { data?: Array<{ id: string; type: string }> | null }

function slugify(value: string): string {
  return value
    .normalize('NFKD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '')
}

export const useCinemaMovieStore = defineStore('cinemaMovie', {
  state: (): CinemaMovieState => ({
    items: [],
    genres: [],
    meta: null,
  }),

  actions: {
    async fetchItems(page = 1, perPage = 20): Promise<void> {
      const result = await apiClient.getCollection<MovieAttributes>('/cinema-movies', {
        include: 'translations,genres,genres.translations',
        'page[number]': page,
        'page[size]': perPage,
      })
      const includedById = new Map(
        (result.included ?? []).map((resource) => [`${resource.type}:${resource.id}`, resource]),
      )

      this.items = result.resources.map((movie) => {
        const relationship = movie.relationships?.translations as Relationship | undefined
        const translations = (relationship?.data ?? []).flatMap((identifier) => {
          const resource = includedById.get(`${identifier.type}:${identifier.id}`)
          return resource
            ? [{ ...resource.attributes, id: resource.id, type: resource.type } as CinemaMovieItem['translations'][number]]
            : []
        })
        const genreRelationship = movie.relationships?.genres as Relationship | undefined
        const genres = (genreRelationship?.data ?? []).flatMap((identifier) => {
          const resource = includedById.get(`${identifier.type}:${identifier.id}`)
          if (!resource || resource.type !== 'cinema-genres') return []
          const translationRelationship = resource.relationships?.translations as Relationship | undefined
          const genreTranslations = (translationRelationship?.data ?? []).flatMap((translationIdentifier) => {
            const translation = includedById.get(`${translationIdentifier.type}:${translationIdentifier.id}`)
            return translation && translation.type === 'cinema-genre-translations'
              ? [{ ...translation.attributes, id: translation.id, type: translation.type } as GenreTranslation]
              : []
          })
          return [{ ...resource.attributes, id: resource.id, type: resource.type, translations: genreTranslations } as CinemaGenre]
        })
        return { ...movie, translations, genres }
      })
      this.meta = (result.meta as PaginationMeta | undefined) ?? null
    },

    async fetchGenres(): Promise<void> {
      const result = await apiClient.getCollection<GenreAttributes>('/cinema-genres', {
        include: 'translations',
        'page[size]': 100,
      })
      const includedById = new Map(
        (result.included ?? [])
          .filter((resource) => resource.type === 'cinema-genre-translations')
          .map((resource) => [resource.id, resource]),
      )
      this.genres = result.resources.map((genre) => {
        const relationship = genre.relationships?.translations as Relationship | undefined
        const translations = (relationship?.data ?? []).flatMap((identifier) => {
          const resource = includedById.get(identifier.id)
          return resource
            ? [{ ...resource.attributes, id: resource.id, type: resource.type } as GenreTranslation]
            : []
        })
        return { ...genre, translations }
      })
    },

    async saveMovie(
      movieId: string | null,
      attributes: MovieInput,
      translations: TranslationInput[],
      genreIds: string[],
    ): Promise<CinemaMovieItem> {
      const requestedPublicationStatus = attributes.publicationStatus ?? 'draft'
      const movieAttributes: Partial<MovieAttributes> = { ...attributes }
      if (!movieId) movieAttributes.publicationStatus = 'draft'
      else delete movieAttributes.publicationStatus

      const saved = movieId
        ? await apiClient.patchResource<MovieAttributes>(`/cinema-movies/${movieId}`, 'cinema-movies', movieAttributes, movieId)
        : await apiClient.postResource<MovieAttributes>('/cinema-movies', 'cinema-movies', movieAttributes)
      if (!saved) throw new Error('The movie save response did not include a resource.')

      const existing = this.items.find((item) => item.id === saved.id)
      const updated: CinemaMovieItem = {
        ...saved,
        translations: [...(existing?.translations ?? [])],
        genres: [...(existing?.genres ?? [])],
      }
      const existingIndex = this.items.findIndex((item) => item.id === saved.id)
      if (existingIndex < 0) this.items.unshift(updated)
      else this.items.splice(existingIndex, 1, updated)

      for (const translation of translations) {
        if (!translation.title.trim()) continue
        const current = updated.translations.find((item) => item.locale === translation.locale)
        if (current) {
          const result = await apiClient.patchResource<MovieTranslationAttributes>(
            `/cinema-movie-translations/${current.id}`,
            'cinema-movie-translations',
            translation,
            current.id,
          )
          if (!result) throw new Error('The translation update response did not include a resource.')
          updated.translations.splice(updated.translations.indexOf(current), 1, result as MovieTranslation)
        } else {
          const document = await apiClient.post<{ data: JsonApiResource<MovieTranslationAttributes> }>('/cinema-movie-translations', {
            data: {
              type: 'cinema-movie-translations',
              attributes: translation,
              relationships: { movie: { data: { type: 'cinema-movies', id: saved.id } } },
            },
          })
          updated.translations.push({ ...document.data.attributes, id: document.data.id, type: document.data.type })
        }
      }

      await apiClient.patch(`/cinema-movies/${saved.id}`, {
        data: {
          type: 'cinema-movies',
          id: saved.id,
          relationships: {
            genres: { data: genreIds.map((id) => ({ type: 'cinema-genres', id })) },
          },
        },
      })
      updated.genres = this.genres.filter((genre) => genreIds.includes(genre.id))

      if (movieId || requestedPublicationStatus !== 'draft') {
        const publishResult = await apiClient.patchResource<MovieAttributes>(
          `/cinema-movies/${saved.id}`,
          'cinema-movies',
          { publicationStatus: requestedPublicationStatus },
          saved.id,
        )
        if (!publishResult) throw new Error('The movie publication response did not include a resource.')
      }

      return updated
    },

    async saveGenre(
      genreId: string | null,
      translations: GenreTranslationInput[],
    ): Promise<CinemaGenre> {
      const saved = genreId
        ? this.genres.find((genre) => genre.id === genreId)
        : await apiClient.postResource<GenreAttributes>('/cinema-genres', 'cinema-genres', {
          slug: slugify(translations.find((translation) => translation.locale === 'en')?.name ?? '') || 'genre',
        })
      if (!saved) throw new Error('The genre save response did not include a resource.')

      const existing = this.genres.find((genre) => genre.id === saved.id)
      const updated: CinemaGenre = { ...saved, translations: [...(existing?.translations ?? [])] }
      const existingIndex = this.genres.findIndex((genre) => genre.id === saved.id)
      if (existingIndex < 0) this.genres.unshift(updated)
      else this.genres.splice(existingIndex, 1, updated)

      for (const translation of translations) {
        if (!translation.name.trim()) continue
        const current = updated.translations.find((item) => item.locale === translation.locale)
        if (current) {
          const result = await apiClient.patchResource<GenreTranslationAttributes>(
            `/cinema-genre-translations/${current.id}`,
            'cinema-genre-translations',
            translation,
            current.id,
          )
          if (!result) throw new Error('The genre translation update response did not include a resource.')
          updated.translations.splice(updated.translations.indexOf(current), 1, result as GenreTranslation)
        } else {
          const document = await apiClient.post<{ data: JsonApiResource<GenreTranslationAttributes> }>('/cinema-genre-translations', {
            data: {
              type: 'cinema-genre-translations',
              attributes: translation,
              relationships: { genre: { data: { type: 'cinema-genres', id: saved.id } } },
            },
          })
          updated.translations.push({ ...document.data.attributes, id: document.data.id, type: document.data.type })
        }
      }

      return updated
    },

    async deleteGenre(genreId: string): Promise<void> {
      await apiClient.delete<void>(`/cinema-genres/${genreId}`)
    },

    async deleteMovie(movieId: string): Promise<void> {
      await apiClient.delete<void>(`/cinema-movies/${movieId}`)
    },
  },
})

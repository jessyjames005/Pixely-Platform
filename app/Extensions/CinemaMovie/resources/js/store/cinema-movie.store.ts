// Pinia store for the CinemaMovie extension catalogue.
import { defineStore } from 'pinia'
import { apiClient } from '@shared/services/apiClient'
import type { JsonApiResource, PaginationMeta } from '@shared/types/api'
import type { CinemaMovieItem, MovieAttributes, MovieTranslation, MovieTranslationAttributes } from '../models/CinemaMovie'

interface MovieInput extends Partial<MovieAttributes> {
  originalTitle: string
}

type TranslationInput = MovieTranslationAttributes

interface CinemaMovieState {
  items: CinemaMovieItem[]
  meta: PaginationMeta | null
}

type Relationship = { data?: Array<{ id: string; type: string }> | null }

export const useCinemaMovieStore = defineStore('cinemaMovie', {
  state: (): CinemaMovieState => ({
    items: [],
    meta: null,
  }),

  actions: {
    async fetchItems(page = 1, perPage = 20): Promise<void> {
      const result = await apiClient.getCollection<MovieAttributes>('/cinema-movies', {
        include: 'translations',
        'page[number]': page,
        'page[size]': perPage,
      })
      const includedById = new Map(
        (result.included ?? [])
          .filter((resource) => resource.type === 'cinema-movie-translations')
          .map((resource) => [resource.id, resource]),
      )

      this.items = result.resources.map((movie) => {
        const relationship = movie.relationships?.translations as Relationship | undefined
        const translations = (relationship?.data ?? []).flatMap((identifier) => {
          const resource = includedById.get(identifier.id)
          return resource
            ? [{ ...resource.attributes, id: resource.id, type: resource.type } as CinemaMovieItem['translations'][number]]
            : []
        })
        return { ...movie, translations }
      })
      this.meta = (result.meta as PaginationMeta | undefined) ?? null
    },

    async saveMovie(
      movieId: string | null,
      attributes: MovieInput,
      translations: TranslationInput[],
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
      const updated: CinemaMovieItem = { ...saved, translations: [...(existing?.translations ?? [])] }
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

    async deleteMovie(movieId: string): Promise<void> {
      await apiClient.delete<void>(`/cinema-movies/${movieId}`)
    },
  },
})

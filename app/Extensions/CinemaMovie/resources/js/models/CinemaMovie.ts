import type { JsonApiModel } from '@shared/types/api'

export type MovieStatus = 'rumored' | 'planned' | 'in_production' | 'post_production' | 'released' | 'canceled'
export type PublicationStatus = 'draft' | 'published' | 'archived'

export interface MovieAttributes {
  slug: string
  originalTitle: string | null
  originalLanguage: string | null
  releaseDate: string | null
  runtimeMinutes: number | null
  ageRating: string | null
  productionStatus: MovieStatus
  publicationStatus: PublicationStatus
  budget: number | null
  revenue: number | null
  voteAverage: number | null
  voteCount: number
}

export interface CinemaMediaFile {
  id: string
  original_name: string
  mime_type: string
  url: string
  thumbnail_url: string | null
}

export interface MovieTranslationAttributes {
  locale: string
  title: string
  overview: string | null
  tagline: string | null
}

export interface GenreAttributes {
  slug: string
}

export interface GenreTranslationAttributes {
  locale: string
  name: string
}

export type MovieTranslation = JsonApiModel<MovieTranslationAttributes>
export type GenreTranslation = JsonApiModel<GenreTranslationAttributes>
export type CinemaGenre = JsonApiModel<GenreAttributes> & {
  translations: GenreTranslation[]
}
export type CinemaMovieItem = JsonApiModel<MovieAttributes> & {
  translations: MovieTranslation[]
  genres: CinemaGenre[]
  posterId: string | null
  backdropId: string | null
}

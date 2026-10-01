import type { JsonApiModel } from '@shared/types/api'

export type CinemaMovieItem = JsonApiModel<object> & {
  type: 'cinema-movie-items'
}

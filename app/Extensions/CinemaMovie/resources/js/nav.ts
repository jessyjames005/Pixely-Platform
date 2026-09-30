import type { NavItem } from '@shared/navigation/types'

export const cinemaMovieNavItem: NavItem = {
  label: 'CinemaMovie',
  to: '/admin/cinema-movie',
  icon: 'mdi-puzzle-outline',
  permission: 'cinema-movie.items.view',
  extensionId: 'cinema-movie',
}

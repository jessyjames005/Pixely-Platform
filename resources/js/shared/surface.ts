export const SURFACES = ['public', 'user', 'admin', 'api'] as const

export type Surface = (typeof SURFACES)[number]

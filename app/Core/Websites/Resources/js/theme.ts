/**
 * Minimal public website design tokens.
 *
 * The theme is intentionally small until the platform Theme/Blocks sprint.
 * Components consume CSS variables so a future theme can replace the values
 * without changing website components.
 */
export const websiteTheme = {
  maxWidth: '1120px',
  radius: '10px',
  spacing: '1rem',
} as const

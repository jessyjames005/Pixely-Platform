import type { Locator, Page } from '@playwright/test'
import { BasePage } from './BasePage.pom'

/** Page object for the CinemaMovie extension administration workflows. */
export class CinemaMoviePage extends BasePage {
  readonly pageHeading: Locator
  readonly catalogueHeading: Locator
  readonly genresHeading: Locator
  readonly addMovieButton: Locator
  readonly addGenreButton: Locator
  readonly movieDialog: Locator
  readonly genreDialog: Locator
  readonly confirmationDialog: Locator

  constructor(page: Page) {
    super(page)
    this.pageHeading = page.getByRole('heading', { name: /Cinema movies|Films/ })
    this.catalogueHeading = page.getByText(/Movie catalogue|Catalogue des films/, { exact: true })
    this.genresHeading = page.getByTestId('cinema-movie-genres-heading')
    this.addMovieButton = page.getByRole('button', { name: /Add movie|Ajouter un film/ })
    this.addGenreButton = page.getByRole('button', { name: /Add genre|Ajouter un genre/ })
    this.movieDialog = page.getByRole('dialog').filter({
      has: page.getByText(/Movie details|Détails du film/),
    })
    this.genreDialog = page.getByRole('dialog').filter({
      has: page.getByText(/English name|Nom anglais/),
    })
    this.confirmationDialog = page.getByRole('dialog').filter({
      has: page.getByRole('button', { name: /Delete|Supprimer/ }),
    })
  }

  async goto(): Promise<void> {
    await this.open('/admin/cinema-movie')
  }

  /** Returns the table row containing the supplied movie title. */
  movieRow(title: string): Locator {
    return this.page.getByRole('row').filter({ hasText: title })
  }

  /** Returns the table row containing the supplied genre name. */
  genreRow(name: string): Locator {
    return this.page.getByRole('row').filter({ hasText: name })
  }

  async createMovie(data: {
    originalTitle: string
    englishTitle: string
    frenchTitle: string
  }): Promise<void> {
    await this.addMovieButton.click()
    await this.movieDialog.getByLabel(/Original title|Titre original/).fill(data.originalTitle)
    await this.movieDialog.getByLabel(/English title|Titre anglais/).fill(data.englishTitle)
    await this.movieDialog.getByLabel(/French title|Titre français/).fill(data.frenchTitle)
    await this.movieDialog.getByRole('button', { name: /^Save$|^Enregistrer$/ }).click()
  }

  async editMovieTitle(currentTitle: string, updatedTitle: string): Promise<void> {
    const row = this.movieRow(currentTitle)
    await row.getByRole('button', { name: /Edit movie|Modifier le film/ }).click()
    await this.movieDialog.getByLabel(/English title|Titre anglais/).fill(updatedTitle)
    await this.movieDialog.getByRole('button', { name: /^Save$|^Enregistrer$/ }).click()
  }

  async deleteMovie(title: string): Promise<void> {
    await this.movieRow(title)
      .getByRole('button', { name: /Delete movie|Supprimer le film/ })
      .click()
    await this.confirmDeletion()
  }

  async createGenre(englishName: string, frenchName: string): Promise<void> {
    await this.addGenreButton.click()
    await this.genreDialog.getByLabel(/English name|Nom anglais/).fill(englishName)
    await this.genreDialog.getByLabel(/French name|Nom français/).fill(frenchName)
    await this.genreDialog.getByRole('button', { name: /^Save$|^Enregistrer$/ }).click()
  }

  async deleteGenre(name: string): Promise<void> {
    await this.genreRow(name)
      .getByRole('button', { name: /Delete genre|Supprimer le genre/ })
      .click()
    await this.confirmDeletion()
  }

  private async confirmDeletion(): Promise<void> {
    await this.confirmationDialog.getByRole('button', { name: /Delete|Supprimer/ }).click()
  }
}

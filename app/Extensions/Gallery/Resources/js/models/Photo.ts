// Photo resource shape as returned by the API
export interface Photo {
  id: string
  type: 'photos'
  title: string | null
  filename: string
  thumbnailFilename: string | null
}

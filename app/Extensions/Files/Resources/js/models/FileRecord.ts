// Domain type for the standalone Files API.
// Named FileRecord (not "File") to avoid shadowing the browser's
// native File type, which the upload form also needs.
export interface FileRecord {
  id: number
  disk: string
  path: string
  thumbnail_path: string | null
  original_name: string
  mime_type: string
  size: number
  uploaded_by: number | null
  url: string
  thumbnail_url: string | null
  created_at: string
  updated_at: string
}

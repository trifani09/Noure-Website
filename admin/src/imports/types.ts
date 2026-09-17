export type ImportError = { field: string | null; code: string; message: string }
export type ImportPreviewRow = { reference: string; row: number; name: string; slug: string; variants: number; images: number; errors: ImportError[] }
export type ImportPreview = { summary: { detected: number; valid: number; invalid: number }; rows: ImportPreviewRow[] }
export type ImportResult = { summary: { detected: number; created_products: number; created_variants: number; imported_images: number; failed: number }; rows: Array<{ reference: string; status: string; errors: ImportError[] }> }

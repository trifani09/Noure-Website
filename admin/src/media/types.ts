export type ProductMedia = { public_id: string; url: string; alt_text: string | null; width: number | null; height: number | null; mime_type: string | null; sort_order: number; is_primary: boolean; variant_public_id: string | null }
export type MediaResponse = { data: ProductMedia[]; meta: Record<string, never>; message: null }
export type MediaItemResponse = { data: ProductMedia; meta: Record<string, never>; message: null }

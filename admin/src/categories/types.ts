export type CategoryParent = { public_id: string; name: string; slug: string }

export type Category = {
  public_id: string
  parent: CategoryParent | null
  name: string
  slug: string
  description: string | null
  image_path: string | null
  image_url: string | null
  sort_order: number
  is_active: boolean
  direct_product_count: number
  children_count: number
  created_at: string
  updated_at: string
}

export type Pagination = { total: number; per_page: number; current_page: number; last_page: number }
export type CategoryListResponse = { data: Category[]; meta: { pagination: Pagination }; message: string | null }
export type CategoryResponse = { data: Category; meta: Record<string, never>; message: string | null }

export type CategoryInput = {
  name: string
  slug?: string
  description: string | null
  parent_public_id: string | null
  sort_order: number
  is_active: boolean
  image_path: string | null
}

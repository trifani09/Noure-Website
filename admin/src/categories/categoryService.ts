import { apiRequest } from '../api/client'
import type { CategoryInput, CategoryListResponse, CategoryResponse } from './types'

export type CategoryFilters = {
  page?: number
  per_page?: number
  search?: string
  parent?: string
  is_active?: string
  sort?: string
}

export function listCategories(filters: CategoryFilters = {}): Promise<CategoryListResponse> {
  const query = new URLSearchParams()
  Object.entries(filters).forEach(([key, value]) => {
    if (value !== undefined && value !== '') query.set(key, String(value))
  })
  return apiRequest(`/api/v1/admin/categories?${query.toString()}`)
}

export function createCategory(input: CategoryInput): Promise<CategoryResponse> {
  return apiRequest('/api/v1/admin/categories', { method: 'POST', body: JSON.stringify(input) })
}

export function updateCategory(publicId: string, input: CategoryInput): Promise<CategoryResponse> {
  return apiRequest(`/api/v1/admin/categories/${publicId}`, { method: 'PUT', body: JSON.stringify(input) })
}

export function deleteCategory(publicId: string): Promise<null> {
  return apiRequest(`/api/v1/admin/categories/${publicId}`, { method: 'DELETE' })
}

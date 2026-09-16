import { apiRequest } from '../api/client'
import type { ProductInput, ProductListResponse, ProductResponse } from './types'

export function listProducts(filters: Record<string, string | number> = {}): Promise<ProductListResponse> {
  const query = new URLSearchParams()
  Object.entries(filters).forEach(([key, value]) => { if (value !== '') query.set(key, String(value)) })
  return apiRequest(`/api/v1/admin/products?${query}`)
}
export function getProduct(id: string): Promise<ProductResponse> { return apiRequest(`/api/v1/admin/products/${id}`) }
export function createProduct(input: ProductInput): Promise<ProductResponse> { return apiRequest('/api/v1/admin/products', { method: 'POST', body: JSON.stringify(input) }) }
export function updateProduct(id: string, input: ProductInput): Promise<ProductResponse> { return apiRequest(`/api/v1/admin/products/${id}`, { method: 'PUT', body: JSON.stringify(input) }) }
export function deleteProduct(id: string): Promise<null> { return apiRequest(`/api/v1/admin/products/${id}`, { method: 'DELETE' }) }

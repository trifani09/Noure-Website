import { apiRequest } from '../api/client'
import type { GenerateVariantsInput, GenerateVariantsResponse, ProductInput, ProductListResponse, ProductOption, ProductOptionValue, ProductResponse, ProductVariant, VariantResponse } from './types'

export function listProducts(filters: Record<string, string | number> = {}): Promise<ProductListResponse> {
  const query = new URLSearchParams()
  Object.entries(filters).forEach(([key, value]) => { if (value !== '') query.set(key, String(value)) })
  return apiRequest(`/api/v1/admin/products?${query}`)
}
export function getProduct(id: string): Promise<ProductResponse> { return apiRequest(`/api/v1/admin/products/${id}`) }
export function createProduct(input: ProductInput): Promise<ProductResponse> { return apiRequest('/api/v1/admin/products', { method: 'POST', body: JSON.stringify(input) }) }
export function updateProduct(id: string, input: ProductInput): Promise<ProductResponse> { return apiRequest(`/api/v1/admin/products/${id}`, { method: 'PUT', body: JSON.stringify(input) }) }
export function deleteProduct(id: string): Promise<null> { return apiRequest(`/api/v1/admin/products/${id}`, { method: 'DELETE' }) }
export function createOption(productId: string, input: ProductOption): Promise<{ data: ProductOption }> { return apiRequest(`/api/v1/admin/products/${productId}/options`, { method: 'POST', body: JSON.stringify(input) }) }
export function addOptionValue(productId: string, optionCode: string, input: ProductOptionValue): Promise<{ data: ProductOptionValue }> { return apiRequest(`/api/v1/admin/products/${productId}/options/${optionCode}/values`, { method: 'POST', body: JSON.stringify(input) }) }
export function generateVariants(productId: string, input: GenerateVariantsInput): Promise<GenerateVariantsResponse> { return apiRequest(`/api/v1/admin/products/${productId}/variants/generate`, { method: 'POST', body: JSON.stringify(input) }) }
export function updateVariant(productId: string, variantId: string, input: Omit<ProductVariant, 'public_id' | 'is_default' | 'available'>): Promise<VariantResponse> { return apiRequest(`/api/v1/admin/products/${productId}/variants/${variantId}`, { method: 'PUT', body: JSON.stringify(input) }) }
export function setDefaultVariant(productId: string, variantId: string): Promise<VariantResponse> { return apiRequest(`/api/v1/admin/products/${productId}/variants/${variantId}/default`, { method: 'PUT' }) }

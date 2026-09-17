import { ApiError, apiRequest } from '../api/client'
import type { MediaItemResponse, MediaResponse, ProductMedia } from './types'

const API_URL = (import.meta.env.VITE_API_URL ?? 'http://localhost:8000').replace(/\/$/, '')
function csrfToken(): string | null { const cookie = document.cookie.split('; ').find((entry) => entry.startsWith('XSRF-TOKEN=')); return cookie ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)) : null }

export function listProductMedia(productId: string): Promise<MediaResponse> { return apiRequest(`/api/v1/admin/products/${productId}/images`) }
export function updateProductMedia(productId: string, imageId: string, input: Partial<Pick<ProductMedia, 'alt_text' | 'sort_order' | 'is_primary' | 'variant_public_id'>>): Promise<MediaItemResponse> { return apiRequest(`/api/v1/admin/products/${productId}/images/${imageId}`, { method: 'PUT', body: JSON.stringify(input) }) }
export function deleteProductMedia(productId: string, imageId: string): Promise<null> { return apiRequest(`/api/v1/admin/products/${productId}/images/${imageId}`, { method: 'DELETE' }) }
export function uploadProductMedia(productId: string, form: FormData, onProgress: (percent: number) => void): Promise<MediaItemResponse> {
  return new Promise((resolve, reject) => {
    const request = new XMLHttpRequest(); request.open('POST', `${API_URL}/api/v1/admin/products/${productId}/images`); request.withCredentials = true; request.setRequestHeader('Accept', 'application/json')
    const token = csrfToken(); if (token) request.setRequestHeader('X-XSRF-TOKEN', token)
    request.upload.onprogress = (event) => { if (event.lengthComputable) onProgress(Math.round((event.loaded / event.total) * 100)) }
    request.onerror = () => reject(new ApiError(0, null, 'The upload could not be completed.'))
    request.onload = () => { let payload: unknown = null; try { payload = JSON.parse(request.responseText) } catch { /* empty response */ } if (request.status >= 200 && request.status < 300) resolve(payload as MediaItemResponse); else { const body = payload as { message?: string; meta?: { errors?: { code?: string; message?: string }[] } } | null; reject(new ApiError(request.status, body?.meta?.errors?.[0]?.code ?? null, body?.meta?.errors?.[0]?.message ?? body?.message ?? 'The upload could not be completed.', body?.meta?.errors ?? [])) } }
    request.send(form)
  })
}

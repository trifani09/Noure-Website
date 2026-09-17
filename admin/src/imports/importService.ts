import { ApiError } from '../api/client'
import type { ImportPreview, ImportResult } from './types'

const API_URL = (import.meta.env.VITE_API_URL ?? 'http://localhost:8000').replace(/\/$/, '')
function csrfToken(): string | null { const cookie = document.cookie.split('; ').find((entry) => entry.startsWith('XSRF-TOKEN=')); return cookie ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)) : null }
async function submit<T>(path: string, form: FormData): Promise<T> { const headers = new Headers({ Accept: 'application/json' }); const token = csrfToken(); if (token) headers.set('X-XSRF-TOKEN', token); const response = await fetch(`${API_URL}${path}`, { method: 'POST', body: form, headers, credentials: 'include' }); const payload = await response.json(); if (!response.ok) { const errors = payload?.meta?.errors ?? []; throw new ApiError(response.status, errors[0]?.code ?? null, errors[0]?.message ?? payload?.message ?? 'Import request failed.', errors) } return payload.data as T }
export function previewProductImport(form: FormData): Promise<ImportPreview> { return submit('/api/v1/admin/product-imports/preview', form) }
export function runProductImport(form: FormData): Promise<ImportResult> { return submit('/api/v1/admin/product-imports', form) }
export function importTemplateUrl(type: 'products' | 'variants' | 'images'): string { return `${API_URL}/api/v1/admin/product-imports/templates/${type}` }

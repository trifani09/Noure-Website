import { ApiError, apiRequest } from '../api/client'
import type { Banner, HomepageSection, SectionInput } from './types'

const API_URL = (import.meta.env.VITE_API_URL ?? 'http://localhost:8000').replace(/\/$/, '')
function csrfToken(): string | null { const cookie = document.cookie.split('; ').find((entry) => entry.startsWith('XSRF-TOKEN=')); return cookie ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)) : null }
export function listBanners(): Promise<{ data: Banner[] }> { return apiRequest('/api/v1/admin/banners') }
export function deleteBanner(id: string): Promise<null> { return apiRequest(`/api/v1/admin/banners/${id}`, { method: 'DELETE' }) }
export function listSections(): Promise<{ data: HomepageSection[] }> { return apiRequest('/api/v1/admin/homepage-sections') }
export function saveSection(input: SectionInput, id?: string): Promise<{ data: HomepageSection }> { return apiRequest(`/api/v1/admin/homepage-sections${id ? `/${id}` : ''}`, { method: id ? 'PUT' : 'POST', body: JSON.stringify(input) }) }
export function deleteSection(id: string): Promise<null> { return apiRequest(`/api/v1/admin/homepage-sections/${id}`, { method: 'DELETE' }) }
export async function saveBanner(form: FormData, id?: string): Promise<{ data: Banner }> { if (id) form.append('_method', 'PUT'); const headers = new Headers({ Accept: 'application/json' }); const token = csrfToken(); if (token) headers.set('X-XSRF-TOKEN', token); const response = await fetch(`${API_URL}/api/v1/admin/banners${id ? `/${id}` : ''}`, { method: 'POST', body: form, credentials: 'include', headers }); const payload = await response.json(); if (!response.ok) { const errors = payload?.meta?.errors ?? []; throw new ApiError(response.status, errors[0]?.code ?? null, errors.map((item: { message?: string }) => item.message).filter(Boolean).join(' ') || payload?.message || 'Banner could not be saved.', errors) } return payload }

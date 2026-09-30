import { apiRequest, initializeCsrf } from '../api/client'
import type { Customer, Dashboard, Discount, Pagination, StoreSettings } from './types'
type Envelope<T> = { data: T; meta: { pagination?: Pagination }; message: string | null }
function query(params: Record<string, string | number>) { const value = new URLSearchParams(); Object.entries(params).forEach(([key, item]) => { if (item !== '') value.set(key, String(item)) }); return value.toString() }
export const getDashboard = () => apiRequest<Envelope<Dashboard>>('/api/v1/admin/dashboard')
export const listCustomers = (params: Record<string, string | number>) => apiRequest<Envelope<Customer[]>>(`/api/v1/admin/customers?${query(params)}`)
export async function updateCustomerStatus(id: string, status: Customer['status']) { await initializeCsrf(); return apiRequest<Envelope<Customer>>(`/api/v1/admin/customers/${id}/status`, { method: 'PUT', body: JSON.stringify({ status }) }) }
export const listDiscounts = (params: Record<string, string | number>) => apiRequest<Envelope<Discount[]>>(`/api/v1/admin/discounts?${query(params)}`)
export async function saveDiscount(discount: Partial<Discount> & { code: string; name: string; type: Discount['type']; value: number; is_active: boolean }) { await initializeCsrf(); return apiRequest<Envelope<Discount>>(discount.public_id ? `/api/v1/admin/discounts/${discount.public_id}` : '/api/v1/admin/discounts', { method: discount.public_id ? 'PUT' : 'POST', body: JSON.stringify(discount) }) }
export async function deleteDiscount(id: string) { await initializeCsrf(); return apiRequest<void>(`/api/v1/admin/discounts/${id}`, { method: 'DELETE' }) }
export const getSettings = () => apiRequest<Envelope<StoreSettings>>('/api/v1/admin/settings')
export async function saveSettings(settings: StoreSettings) { await initializeCsrf(); return apiRequest<Envelope<StoreSettings>>('/api/v1/admin/settings', { method: 'PUT', body: JSON.stringify(settings) }) }

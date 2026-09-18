import { apiRequest, initializeCsrf } from '../api/client'
import type { OrderDetail, OrderStatus, OrderSummary, PaginationData } from './types'
type Envelope<T> = { data: T; meta: { pagination: PaginationData }; message: string | null }
export async function listOrders(params: Record<string, string | number>) {
  const query = new URLSearchParams()
  Object.entries(params).forEach(([key, value]) => { if (value !== '') query.set(key, String(value)) })
  return apiRequest<Envelope<OrderSummary[]>>(`/api/v1/admin/orders?${query}`)
}
export const getOrder = (publicId: string) => apiRequest<{ data: OrderDetail; meta: object; message: string | null }>(`/api/v1/admin/orders/${publicId}`)
export async function updateOrderStatus(publicId: string, status: OrderStatus) {
  await initializeCsrf()
  return apiRequest<{ data: OrderDetail; meta: object; message: string | null }>(`/api/v1/admin/orders/${publicId}/status`, { method: 'PUT', body: JSON.stringify({ status }) })
}

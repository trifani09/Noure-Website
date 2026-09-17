import { apiRequest } from '../api/client'
import type { AdjustmentInput, AdjustmentResponse, InventoryResponse, MovementResponse } from './types'

export function getVariantInventory(variantId: string): Promise<InventoryResponse> { return apiRequest(`/api/v1/admin/variants/${variantId}/inventory`) }
export function adjustInventory(variantId: string, input: AdjustmentInput): Promise<AdjustmentResponse> { return apiRequest(`/api/v1/admin/variants/${variantId}/inventory/adjustments`, { method: 'POST', body: JSON.stringify(input) }) }
export function getInventoryMovements(variantId: string, filters: Record<string, string | number> = {}): Promise<MovementResponse> { const query = new URLSearchParams(); Object.entries(filters).forEach(([key, value]) => query.set(key, String(value))); return apiRequest(`/api/v1/admin/variants/${variantId}/inventory/movements?${query}`) }

export type InventoryLevel = { variant_public_id: string; location: { code: string; name: string; is_active: boolean }; on_hand: number; reserved: number; safety_stock: number; available: number; version: number; updated_at: string }
export type InventoryMovement = { variant_public_id: string; location: { code: string; name: string }; quantity_delta: number; movement_type: string; reference_type: string | null; reference_id: string | null; reason: string | null; actor: { name: string; email: string } | null; created_at: string }
export type InventoryResponse = { data: InventoryLevel[]; meta: Record<string, never>; message: null }
export type MovementResponse = { data: InventoryMovement[]; meta: { pagination: { total: number; per_page: number; current_page: number; last_page: number } }; message: null }
export type AdjustmentInput = { location_code: string; quantity_delta: number; reason: string; reference_type: string | null; reference_id: string | null; expected_version: number }
export type AdjustmentResponse = { data: { level: InventoryLevel; movement: InventoryMovement }; meta: Record<string, never>; message: null }

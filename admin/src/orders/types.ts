export type OrderStatus = 'pending' | 'processing' | 'shipped' | 'completed' | 'cancelled'
export type PaginationData = { total: number; per_page: number; current_page: number; last_page: number }
export type OrderSummary = {
  public_id: string; order_number: string; customer: { name: string; email: string; phone: string | null }
  grand_total_amount: number; currency: string; payment_status: string; fulfillment_status: string
  status: OrderStatus; created_at: string
}
export type AddressSnapshot = { recipient_name?: string; phone?: string; line1: string; line2?: string | null; city: string; province?: string | null; postal_code: string; country_code: string }
export type OrderDetail = OrderSummary & {
  subtotal_amount: number; discount_amount: number; shipping_amount: number; tax_amount: number
  shipping_address: AddressSnapshot; billing_address: AddressSnapshot
  items: Array<{ product_name: string; variant_name: string | null; sku: string; option_values: Record<string, string>; quantity: number; unit_price_amount: number; total_amount: number; currency: string }>
  payment: { status: string; provider: string | null; method_type: string | null; amount: number | null; currency: string; provider_reference: string | null; paid_at: string | null; transactions: Array<{ type: string; status: string; amount: number; currency: string; processed_at: string }> }
  status_history: Array<{ from: OrderStatus | null; to: OrderStatus; changed_at: string; actor: { name: string; email: string } | null }>
}

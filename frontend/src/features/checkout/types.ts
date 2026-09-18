import type { Cart } from "@/features/cart/types";

export type Address = {
  public_id: string;
  label: string | null;
  recipient_name: string;
  phone: string;
  line1: string;
  line2: string | null;
  city: string;
  province: string | null;
  postal_code: string;
  country_code: string;
  is_default_shipping: boolean;
};
export type Totals = {
  subtotal_amount: number;
  discount_amount: number;
  shipping_amount: number;
  grand_total_amount: number;
  currency: string;
};
export type Checkout = {
  cart: Cart;
  customer: { name: string; email: string; phone: string } | null;
  addresses: Address[];
  totals: Totals;
};
export type ShippingAddress = {
  line1: string;
  line2?: string;
  city: string;
  province?: string;
  postal_code: string;
  country_code: string;
};
export type Order = {
  public_id: string;
  order_number: string;
  status: string;
  payment_status: string;
  fulfillment_status: string;
  email: string;
  items: Array<{
    product_name: string;
    variant_name: string | null;
    sku: string;
    quantity: number;
    unit_price_amount: number;
    total_amount: number;
    currency: string;
  }>;
  subtotal_amount: number;
  discount_amount: number;
  shipping_amount: number;
  grand_total_amount: number;
  currency: string;
};

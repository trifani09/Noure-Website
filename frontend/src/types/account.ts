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
  is_default_billing: boolean;
};

export type OrderSummary = {
  public_id: string;
  order_number: string;
  created_at: string;
  item_count: number;
  grand_total_amount: number;
  currency: string;
  status: string;
  payment_status: string;
  fulfillment_status: string;
};

export type OrderDetail = OrderSummary & {
  placed_at: string;
  cancelled_at: string | null;
  customer: { email: string; phone: string | null };
  shipping_address: AddressSnapshot;
  billing_address: AddressSnapshot;
  items: OrderItem[];
  subtotal_amount: number;
  discount_amount: number;
  shipping_amount: number;
  tax_amount: number;
};

export type AddressSnapshot = Record<string, string | null>;

export type OrderItem = {
  product_name: string;
  variant_name: string | null;
  sku: string;
  option_values: Record<string, string>;
  quantity: number;
  unit_price_amount: number;
  subtotal_amount: number;
  discount_amount: number;
  tax_amount: number;
  total_amount: number;
  currency: string;
};

export type AddressInput = Omit<Address, "public_id">;
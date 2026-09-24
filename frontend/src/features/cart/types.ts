export type CartOption = {
  option_code: string;
  option_name: string;
  value_code: string;
  value_label: string;
};
export type CartItem = {
  id: number;
  quantity: number;
  unit_price_amount: number;
  subtotal_amount: number;
  currency: string;
  product: { public_id: string; name: string; slug: string };
  variant: {
    public_id: string;
    sku: string;
    title: string | null;
    selected_options: CartOption[];
  };
  image: { url: string; alt_text: string | null } | null;
};
export type Cart = {
  public_id: string;
  items: CartItem[];
  item_count: number;
  subtotal_amount: number;
  discount_amount: number;
  total_amount: number;
  applied_discount: { code: string; name: string; amount: number } | null;
  currency: string;
};

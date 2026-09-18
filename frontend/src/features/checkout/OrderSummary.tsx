import { formatMoney } from "@/lib/format";
import type { Checkout } from "./types";
export function OrderSummary({ checkout }: { checkout: Checkout }) {
  return <aside className="h-fit bg-ivory p-6 lg:sticky lg:top-24">
    <h2 className="editorial-title text-3xl">Order summary</h2>
    <div className="mt-6 divide-y divide-line border-y border-line">
      {checkout.cart.items.map((item) => <div key={item.id} className="flex justify-between gap-4 py-4 text-sm"><div><p>{item.product.name}</p><p className="mt-1 text-xs text-muted">{item.variant.title ?? item.variant.sku} · Qty {item.quantity}</p></div><span>{formatMoney(item.subtotal_amount, item.currency)}</span></div>)}
    </div>
    <dl className="mt-5 space-y-3 text-sm"><div className="flex justify-between"><dt>Subtotal</dt><dd>{formatMoney(checkout.totals.subtotal_amount, checkout.totals.currency)}</dd></div><div className="flex justify-between"><dt>Discount</dt><dd>{formatMoney(checkout.totals.discount_amount, checkout.totals.currency)}</dd></div><div className="flex justify-between"><dt>Shipping</dt><dd>{checkout.totals.shipping_amount === 0 ? "Free" : formatMoney(checkout.totals.shipping_amount, checkout.totals.currency)}</dd></div><div className="flex justify-between border-t border-line pt-4 font-semibold"><dt>Total</dt><dd>{formatMoney(checkout.totals.grand_total_amount, checkout.totals.currency)}</dd></div></dl>
  </aside>;
}

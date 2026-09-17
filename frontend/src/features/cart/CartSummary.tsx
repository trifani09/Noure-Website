import Link from "next/link";
import { formatMoney } from "@/lib/format";
import type { Cart } from "./types";
export function CartSummary({ cart }: { cart: Cart }) {
  return (
    <aside className="border-t border-line pt-6">
      <div className="flex justify-between text-sm">
        <span>Subtotal</span>
        <span>{formatMoney(cart.subtotal_amount, cart.currency)}</span>
      </div>
      <p className="mt-3 text-xs leading-6 text-muted">
        Shipping and taxes are calculated at checkout.
      </p>
      <button type="button" className="button-primary mt-6 w-full">
        Checkout
      </button>
      <Link
        href="/products"
        className="mt-4 block text-center text-xs font-semibold uppercase tracking-widest underline underline-offset-4"
      >
        Continue shopping
      </Link>
    </aside>
  );
}

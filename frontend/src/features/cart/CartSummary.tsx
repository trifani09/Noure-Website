import Link from "next/link";
import { formatMoney } from "@/lib/format";
import type { Cart } from "./types";
import { DiscountCodeForm } from "./DiscountCodeForm";
export function CartSummary({ cart, onCheckout }: { cart: Cart; onCheckout?: () => void }) {
  return (
    <aside className="border-t border-line pt-6">
      <div className="flex justify-between text-sm">
        <span>Subtotal</span>
        <span>{formatMoney(cart.subtotal_amount, cart.currency)}</span>
      </div>
      {cart.discount_amount > 0 && (
        <div className="mt-3 flex justify-between text-sm text-plum">
          <span>Discount</span>
          <span>-{formatMoney(cart.discount_amount, cart.currency)}</span>
        </div>
      )}
      <div className="mt-4 flex justify-between border-t border-line pt-4 font-semibold">
        <span>Total</span>
        <span>{formatMoney(cart.total_amount, cart.currency)}</span>
      </div>
      <DiscountCodeForm />
      <p className="mt-3 text-xs leading-6 text-muted">
        Shipping and taxes are calculated at checkout.
      </p>
      <Link href="/checkout" onClick={onCheckout} className="button-primary mt-6 w-full">
        Checkout
      </Link>
      <Link
        href="/products"
        className="mt-4 block text-center text-xs font-semibold uppercase tracking-widest underline underline-offset-4"
      >
        Continue shopping
      </Link>
    </aside>
  );
}

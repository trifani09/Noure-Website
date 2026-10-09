"use client";

import { useEffect } from "react";
import Link from "next/link";
import { useCart } from "./CartContext";
import { CartItem } from "./CartItem";
import { CartSummary } from "./CartSummary";

export function CartDrawer() {
  const { cart, loading, error, drawerOpen, closeDrawer, updateItem, removeItem } = useCart();

  useEffect(() => {
    if (!drawerOpen) return;
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    const closeOnEscape = (event: KeyboardEvent) => event.key === "Escape" && closeDrawer();
    window.addEventListener("keydown", closeOnEscape);
    return () => {
      document.body.style.overflow = previousOverflow;
      window.removeEventListener("keydown", closeOnEscape);
    };
  }, [drawerOpen, closeDrawer]);

  if (!drawerOpen) return null;
  return (
    <div className="fixed inset-0 z-50">
      <button type="button" aria-label="Tutup keranjang" onClick={closeDrawer} className="absolute inset-0 bg-ink/35" />
      <aside role="dialog" aria-modal="true" aria-labelledby="cart-drawer-title" className="absolute right-0 top-0 flex h-full w-full max-w-md flex-col bg-paper p-6 shadow-2xl">
        <div className="flex items-center justify-between border-b border-line pb-5">
          <h2 id="cart-drawer-title" className="editorial-title text-3xl">Keranjang</h2>
          <button type="button" onClick={closeDrawer} className="focus-ring text-2xl" aria-label="Tutup keranjang">×</button>
        </div>
        <div className="flex-1 overflow-y-auto">
          {loading && <p className="py-10 text-sm text-muted">Memuat keranjang…</p>}
          {error && <p className="py-10 text-sm text-red-700">{error}</p>}
          {!loading && !error && cart?.items.length === 0 && (
            <div className="py-14 text-center">
              <p className="editorial-title text-2xl">Keranjang masih kosong</p>
              <Link href="/products" onClick={closeDrawer} className="mt-5 inline-block text-xs font-semibold uppercase tracking-widest underline underline-offset-4">Mulai belanja</Link>
            </div>
          )}
          {cart?.items.map((item) => <CartItem key={item.id} item={item} onUpdate={(quantity) => updateItem(item.id, quantity)} onRemove={() => removeItem(item.id)} />)}
        </div>
        {cart && cart.items.length > 0 && <CartSummary cart={cart} onCheckout={closeDrawer} />}
        <Link href="/cart" onClick={closeDrawer} className="mt-5 text-center text-xs uppercase tracking-widest text-muted">Lihat keranjang lengkap</Link>
      </aside>
    </div>
  );
}

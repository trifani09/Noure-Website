"use client";

import Link from "next/link";
import { CartItem } from "./CartItem";
import { CartSummary } from "./CartSummary";
import { useCart } from "./CartContext";

export function CartPageClient() {
  const { cart, loading, error, updateItem, removeItem, refresh } = useCart();
  if (loading) return <div className="page-shell py-24 text-sm text-muted">Memuat keranjang…</div>;
  if (error) return (
    <div className="page-shell py-24 text-center text-sm text-red-700">
      <p>{error}</p>
      <button type="button" onClick={() => void refresh()} className="focus-ring mt-5 font-semibold uppercase tracking-widest underline underline-offset-4">Coba lagi</button>
    </div>
  );
  if (!cart || cart.items.length === 0) return (
    <div className="page-shell py-24 text-center">
      <h1 className="editorial-title text-5xl">Keranjang masih kosong</h1>
      <Link href="/products" className="mt-7 inline-block text-xs font-semibold uppercase tracking-widest underline underline-offset-4">Mulai belanja</Link>
    </div>
  );
  return (
    <div className="page-shell py-12 md:py-20">
      <div className="flex items-end justify-between border-b border-line pb-6">
        <div><p className="eyebrow text-plum">Noure</p><h1 className="editorial-title mt-2 text-5xl">Keranjang</h1></div>
        <span className="text-sm text-muted">{cart.item_count} item</span>
      </div>
      <div className="mt-8 grid gap-12 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div>{cart.items.map((item) => <CartItem key={item.id} item={item} onUpdate={(quantity) => updateItem(item.id, quantity)} onRemove={() => removeItem(item.id)} />)}</div>
        <CartSummary cart={cart} />
      </div>
    </div>
  );
}

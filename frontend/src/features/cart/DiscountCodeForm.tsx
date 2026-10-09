"use client";

import { useState } from "react";
import { useCart } from "./CartContext";

export function DiscountCodeForm() {
  const { cart, applyDiscount, removeDiscount } = useCart();
  const [code, setCode] = useState("");
  const [pending, setPending] = useState(false);
  const [message, setMessage] = useState<string | null>(null);

  async function submit(event: React.FormEvent) {
    event.preventDefault();
    if (!code.trim()) return;
    setPending(true);
    setMessage(null);
    try {
      await applyDiscount(code.trim());
      setCode("");
      setMessage("Kode promo berhasil digunakan.");
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Kode promo tidak dapat digunakan.");
    } finally {
      setPending(false);
    }
  }

  async function remove() {
    setPending(true);
    setMessage(null);
    try {
      await removeDiscount();
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Kode promo tidak dapat dihapus.");
    } finally {
      setPending(false);
    }
  }

  if (cart?.applied_discount) return (
    <div className="mt-5">
      <div className="flex items-center justify-between border border-line bg-ivory p-3 text-xs">
        <div><span className="font-semibold uppercase tracking-wider">{cart.applied_discount.code}</span><span className="ml-2 text-muted">{cart.applied_discount.name}</span></div>
        <button type="button" disabled={pending} onClick={() => void remove()} className="focus-ring underline underline-offset-4 disabled:opacity-40">{pending ? "Menghapus…" : "Hapus"}</button>
      </div>
      {message && <p role="status" className="mt-2 text-xs text-plum">{message}</p>}
    </div>
  );

  return (
    <form onSubmit={submit} className="mt-5">
      <label htmlFor="discount-code" className="text-[10px] font-semibold uppercase tracking-widest text-muted">Kode promo</label>
      <div className="mt-2 flex border border-line">
        <input id="discount-code" value={code} onChange={(event) => setCode(event.target.value.toUpperCase())} maxLength={100} placeholder="Masukkan kode" className="min-w-0 flex-1 bg-transparent px-3 py-2.5 text-sm outline-none" />
        <button disabled={pending || !code.trim()} className="focus-ring border-l border-line px-3 text-[10px] font-semibold uppercase tracking-widest disabled:opacity-40">{pending ? "Memproses…" : "Gunakan"}</button>
      </div>
      {message && <p role="status" className="mt-2 text-xs text-plum">{message}</p>}
    </form>
  );
}

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
      setMessage("Discount applied.");
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Discount could not be applied.");
    } finally {
      setPending(false);
    }
  }

  if (cart?.applied_discount) {
    return (
      <div className="mt-5 flex items-center justify-between border border-line bg-ivory p-3 text-xs">
        <div>
          <span className="font-semibold uppercase tracking-wider">{cart.applied_discount.code}</span>
          <span className="ml-2 text-muted">{cart.applied_discount.name}</span>
        </div>
        <button type="button" disabled={pending} onClick={() => void removeDiscount()} className="focus-ring underline underline-offset-4">Remove</button>
      </div>
    );
  }

  return (
    <form onSubmit={submit} className="mt-5">
      <label htmlFor="discount-code" className="text-[10px] font-semibold uppercase tracking-widest text-muted">Discount code</label>
      <div className="mt-2 flex border border-line">
        <input id="discount-code" value={code} onChange={(event) => setCode(event.target.value.toUpperCase())} maxLength={100} placeholder="Enter code" className="min-w-0 flex-1 bg-transparent px-3 py-2.5 text-sm outline-none" />
        <button disabled={pending || !code.trim()} className="focus-ring border-l border-line px-3 text-[10px] font-semibold uppercase tracking-widest disabled:opacity-40">{pending ? "Applying…" : "Apply"}</button>
      </div>
      {message && <p role="status" className="mt-2 text-xs text-plum">{message}</p>}
    </form>
  );
}

"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { formatMoney } from "@/lib/format";
import type { Order } from "./types";

export function OrderSuccess() {
  const [order, setOrder] = useState<Order | null | undefined>(undefined);
  const [wasGuest, setWasGuest] = useState(false);

  useEffect(() => {
    const stored = sessionStorage.getItem("noure:last-order");
    queueMicrotask(() => {
      setOrder(stored ? (JSON.parse(stored) as Order) : null);
      setWasGuest(sessionStorage.getItem("noure:last-order-was-guest") === "true");
    });
  }, []);

  if (order === undefined) return <div className="page-shell py-24 text-sm text-muted">Loading your order...</div>;
  if (!order) return <div className="page-shell py-24 text-center"><h1 className="editorial-title text-5xl">Order details unavailable</h1><p className="mt-4 text-sm text-muted">This confirmation is only available in the browser session where the order was placed.</p><Link href="/products" className="button-primary mt-8">Continue shopping</Link></div>;

  return (
    <div className="page-shell max-w-3xl py-16 text-center md:py-24">
      <p className="eyebrow text-plum">Thank you</p>
      <h1 className="editorial-title mt-3 text-5xl">Your order is confirmed</h1>
      <div className="mt-4 flex justify-center gap-6 text-sm text-muted"><p>Order number <strong className="text-ink">{order.order_number}</strong></p><p>Status <strong className="capitalize text-ink">{order.status}</strong></p></div>
      <div className="mx-auto mt-10 max-w-xl border-y border-line text-left">
        {order.items.map((item) => <div key={`${item.sku}-${item.variant_name}`} className="flex justify-between gap-4 border-b border-line py-4 last:border-0"><div><p>{item.product_name}</p><p className="mt-1 text-xs text-muted">{item.variant_name} · Qty {item.quantity}</p></div><span>{formatMoney(item.total_amount, item.currency)}</span></div>)}
      </div>
      <div className="mx-auto mt-5 flex max-w-xl justify-between font-semibold"><span>Total</span><span>{formatMoney(order.grand_total_amount, order.currency)}</span></div>
      {wasGuest && <div className="mx-auto mt-10 max-w-xl bg-ivory p-6"><h2 className="editorial-title text-2xl">Save time on your next order</h2><p className="mt-2 text-sm text-muted">Create an optional account to save your details and manage future purchases.</p><Link href={`/register?email=${encodeURIComponent(order.email)}`} className="mt-5 inline-block text-xs font-semibold uppercase tracking-widest underline underline-offset-4">Create an account</Link></div>}
      <Link href="/products" className="button-primary mt-10">Continue shopping</Link>
    </div>
  );
}

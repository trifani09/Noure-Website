"use client";
import Link from "next/link";
import { useCallback, useEffect, useState } from "react";
import { formatMoney } from "@/lib/format";
import { createPayment, getPayment } from "./payment-api";
import type { Payment } from "./types";
export function PaymentPage({ orderId }: { orderId: string }) {
  const [payment, setPayment] = useState<Payment | null>(null); const [loading, setLoading] = useState(true); const [error, setError] = useState<string | null>(null);
  const refresh = useCallback(async () => { setError(null); try { setPayment(await getPayment(orderId)); } catch (caught) { setError(caught instanceof Error ? caught.message : "Payment status could not be refreshed."); } finally { setLoading(false); } }, [orderId]);
  useEffect(() => { let active = true; const returned = window.location.search.length > 0; (returned ? getPayment(orderId) : createPayment(orderId)).then((result) => { if (!active) return; setPayment(result); if (!returned && result.redirect_url) window.location.assign(result.redirect_url); }).catch((caught) => { if (active) setError(caught instanceof Error ? caught.message : "Payment could not be created."); }).finally(() => { if (active) setLoading(false); }); return () => { active = false; }; }, [orderId]);
  if (loading) return <div className="page-shell py-24 text-center text-sm text-muted">Preparing secure payment…</div>;
  if (error && !payment) return <div className="page-shell py-24 text-center"><h1 className="editorial-title text-5xl">Payment unavailable</h1><p className="mt-4 text-sm text-red-700">{error}</p><button className="button-primary mt-8" onClick={() => window.location.reload()}>Try again</button></div>;
  if (!payment) return null;
  const failed = payment.status === "failed" || payment.status === "expired" || payment.status === "cancelled";
  return <div className="page-shell max-w-2xl py-16 text-center md:py-24"><p className="eyebrow text-plum">Midtrans secure payment</p><h1 className="editorial-title mt-3 text-5xl">{payment.status === "paid" ? "Payment received" : failed ? "Payment was not completed" : "Payment pending"}</h1><div className="mx-auto mt-8 max-w-md border-y border-line py-6 text-sm"><div className="flex justify-between"><span>Order</span><strong>{payment.order_number}</strong></div><div className="mt-3 flex justify-between"><span>Amount</span><strong>{formatMoney(payment.amount, payment.currency)}</strong></div><div className="mt-3 flex justify-between"><span>Status</span><strong className="capitalize">{payment.status}</strong></div>{payment.expires_at && <div className="mt-3 flex justify-between"><span>Expires</span><strong>{new Date(payment.expires_at).toLocaleString()}</strong></div>}</div>{error && <p className="mt-5 text-sm text-red-700">{error}</p>}<div className="mt-8 flex flex-wrap justify-center gap-3">{payment.status === "paid" ? <Link href="/checkout/success" className="button-primary">View order confirmation</Link> : <><button className="button-primary" onClick={() => void refresh()}>Refresh status</button>{payment.redirect_url && !failed && <a className="button-primary" href={payment.redirect_url}>Continue payment</a>}</>}</div></div>;
}

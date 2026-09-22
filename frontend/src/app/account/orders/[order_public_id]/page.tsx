"use client";

import Link from "next/link";
import { useParams } from "next/navigation";
import { useEffect, useState } from "react";
import { AccountNav } from "@/components/account/AccountGuard";
import { getOrder } from "@/services/account-api";
import { createPayment } from "@/features/checkout/payment-api";
import type { OrderDetail } from "@/types/account";

export default function OrderDetailPage() {
  const params = useParams<{ order_public_id: string }>();
  const [order, setOrder] = useState<OrderDetail | null>(null); const [error, setError] = useState(""); const [retrying, setRetrying] = useState(false); const [paymentUrl, setPaymentUrl] = useState("");
  useEffect(() => { getOrder(params.order_public_id).then(setOrder).catch(() => setError('This order could not be found.')); }, [params.order_public_id]);
  const money = (amount: number, currency: string) => new Intl.NumberFormat('en-US', { style: 'currency', currency, maximumFractionDigits: 0 }).format(amount);
  async function retryPayment() { if (!order) return; setRetrying(true); try { const payment = await createPayment(order.public_id); if (payment.redirect_url) setPaymentUrl(payment.redirect_url); else setError('Payment is not available for this order yet.'); } catch { setError('Unable to start payment. Please try again.'); } finally { setRetrying(false); } }
  if (error) return <div className="page-shell section-space"><AccountNav /><p className="mt-14 text-sm text-muted">{error}</p><Link href="/account/orders" className="mt-6 inline-block border-b border-ink text-xs font-semibold uppercase tracking-[.14em]">Back to orders</Link></div>;
  if (!order) return <div className="page-shell section-space"><AccountNav /><p className="mt-14 text-sm text-muted">Loading your order...</p></div>;
  return <div className="page-shell section-space"><AccountNav /><div className="mt-14"><Link href="/account/orders" className="text-xs font-semibold uppercase tracking-[.14em] text-muted hover:text-ink">← Order history</Link><div className="mt-8 flex flex-wrap items-end justify-between gap-5"><div><p className="eyebrow text-plum">{new Date(order.created_at).toLocaleDateString()}</p><h1 className="editorial-title mt-3 text-5xl">{order.order_number}</h1></div><div className="text-right text-xs uppercase tracking-[.12em] text-muted"><p>{order.status}</p><p>Payment: {order.payment_status}</p><p>Fulfillment: {order.fulfillment_status}</p></div></div>{order.payment_status === 'pending' || order.payment_status === 'unpaid' ? <div className="mt-8 border border-line bg-ivory p-5"><p className="text-sm">Payment is still pending for this order.</p>{paymentUrl ? <a href={paymentUrl} className="mt-4 inline-block button-primary">Continue payment</a> : <button onClick={() => void retryPayment()} disabled={retrying} className="mt-4 button-primary disabled:opacity-50">{retrying ? 'Starting...' : 'Retry payment'}</button>}</div> : null}<div className="mt-12 grid gap-12 lg:grid-cols-[1.4fr_1fr]"><section><h2 className="editorial-title text-3xl">Items</h2><div className="mt-5 divide-y divide-line border-y border-line">{order.items.map((item) => <div key={item.sku} className="flex justify-between gap-5 py-5 text-sm"><div><p className="font-medium">{item.product_name}</p><p className="mt-1 text-muted">{item.variant_name} · Qty {item.quantity}</p>{Object.entries(item.option_values).map(([name, value]) => <p key={name} className="text-xs text-muted">{name}: {value}</p>)}</div><p>{money(item.total_amount, item.currency)}</p></div>)}</div></section><aside className="space-y-8"><AddressBlock title="Shipping address" address={order.shipping_address} /><AddressBlock title="Billing address" address={order.billing_address} /><div className="border-t border-line pt-5 text-sm"><Total label="Subtotal" amount={order.subtotal_amount} currency={order.currency} /><Total label="Shipping" amount={order.shipping_amount} currency={order.currency} /><Total label="Tax" amount={order.tax_amount} currency={order.currency} /><Total label="Total" amount={order.grand_total_amount} currency={order.currency} strong /></div></aside></div></div></div>;
}

function AddressBlock({ title, address }: { title: string; address: Record<string, string | null> }) {
  return <div><h2 className="eyebrow text-plum">{title}</h2><p className="mt-3 text-sm leading-7 text-muted">{address.recipient_name}<br />{address.line1}{address.line2 && `, ${address.line2}`}<br />{address.city}{address.province && `, ${address.province}`} {address.postal_code}<br />{address.country_code}</p></div>;
}
function Total({ label, amount, currency, strong = false }: { label: string; amount: number; currency: string; strong?: boolean }) {
  return <div className={`flex justify-between py-1 ${strong ? 'mt-3 border-t border-line pt-4 font-semibold' : ''}`}><span>{label}</span><span>{new Intl.NumberFormat('en-US', { style: 'currency', currency, maximumFractionDigits: 0 }).format(amount)}</span></div>;
}
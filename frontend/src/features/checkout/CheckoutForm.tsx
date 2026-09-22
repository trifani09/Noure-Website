"use client";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useCallback, useEffect, useState } from "react";
import { useCart } from "@/features/cart";
import { CheckoutApiError, getCheckout, getShippingMethods, placeOrder } from "./api";
import { CustomerInformation } from "./CustomerInformation";
import { OrderSummary } from "./OrderSummary";
import { PlaceOrderButton } from "./PlaceOrderButton";
import { ShippingAddressForm } from "./ShippingAddressForm";
import { ShippingMethodSelector } from "./ShippingMethodSelector";
import type { Checkout, ShippingAddress, ShippingMethod } from "./types";

const emptyAddress: ShippingAddress = { line1: "", line2: "", city: "", province: "", postal_code: "", country_code: "ID" };
export function CheckoutForm() {
  const router = useRouter();
  const { refresh } = useCart();
  const [checkout, setCheckout] = useState<Checkout | null>(null);
  const [customer, setCustomer] = useState({ name: "", email: "", phone: "" });
  const [address, setAddress] = useState(emptyAddress);
  const [selectedAddress, setSelectedAddress] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [pending, setPending] = useState(false);
  const [shippingMethods, setShippingMethods] = useState<ShippingMethod[]>([]);
  const [selectedShipping, setSelectedShipping] = useState("");
  const [shippingLoading, setShippingLoading] = useState(false);
  const loadShipping = useCallback(async (input: { address_public_id?: string; shipping_address?: ShippingAddress }) => {
    setShippingLoading(true);
    try {
      const methods = await getShippingMethods(input);
      setShippingMethods(methods);
      const method = methods[0];
      if (method) { setSelectedShipping(method.code); setCheckout((current) => current ? { ...current, totals: { ...current.totals, shipping_amount: method.amount, grand_total_amount: current.totals.subtotal_amount - current.totals.discount_amount + method.amount + 0 } } : current); }
    } catch (caught) { setShippingMethods([]); setSelectedShipping(""); setError(caught instanceof Error ? caught.message : "Unable to calculate shipping."); }
    finally { setShippingLoading(false); }
  }, []);
  useEffect(() => { getCheckout().then((data) => { setCheckout(data); if (data.customer) setCustomer(data.customer); const saved = data.addresses[0]?.public_id ?? ""; setSelectedAddress(saved); if (saved) void loadShipping({ address_public_id: saved }); }).catch((caught) => setError(caught instanceof Error ? caught.message : "Unable to load checkout." )).finally(() => setLoading(false)); }, [loadShipping]);
  if (loading) return <div className="page-shell py-24 text-sm text-muted">Loading checkout...</div>;
  if (error && !checkout) return <div className="page-shell py-24"><p className="text-sm text-red-700">{error}</p><Link href="/cart" className="mt-5 inline-block underline">Return to cart</Link></div>;
  if (!checkout || checkout.cart.items.length === 0) return <div className="page-shell py-24 text-center"><h1 className="editorial-title text-5xl">Your cart is empty</h1><Link href="/products" className="mt-7 inline-block text-xs font-semibold uppercase tracking-widest underline underline-offset-4">Continue shopping</Link></div>;
  async function submit(event: React.FormEvent) {
    event.preventDefault(); setPending(true); setError(null);
    try {
      const order = await placeOrder({ ...(checkout!.customer ? {} : customer), ...(selectedAddress ? { address_public_id: selectedAddress } : { shipping_address: address }), shipping_method_code: selectedShipping });
      sessionStorage.setItem("noure:last-order", JSON.stringify(order));
      await refresh();
      router.push(`/payment/${order.public_id}`);
    } catch (caught) {
      if (caught instanceof CheckoutApiError && caught.status === 401) setError("Your session expired. Please sign in again or continue as a guest.");
      else setError(caught instanceof Error ? caught.message : "We could not place your order. Please try again.");
    } finally { setPending(false); }
  }
  return <div className="page-shell py-12 md:py-20"><div className="mb-10"><p className="eyebrow text-plum">Secure checkout</p><h1 className="editorial-title mt-2 text-5xl">Complete your order</h1></div><form onSubmit={submit} className="grid gap-12 lg:grid-cols-[minmax(0,1fr)_24rem]"><div><CustomerInformation authenticated={Boolean(checkout.customer)} {...customer} onChange={(field, value) => setCustomer((current) => ({ ...current, [field]: value }))} /><ShippingAddressForm addresses={checkout.addresses} selectedAddress={selectedAddress} address={address} onSelect={(value) => { setSelectedAddress(value); if (value) void loadShipping({ address_public_id: value }); }} onChange={(field, value) => { const next = { ...address, [field]: value }; setAddress(next); if (["city", "province", "country_code", "postal_code"].includes(field) && next.city && next.country_code) void loadShipping({ shipping_address: next }); }} /><ShippingMethodSelector methods={shippingMethods} selected={selectedShipping} loading={shippingLoading} onSelect={(code) => { const method = shippingMethods.find((item) => item.code === code); setSelectedShipping(code); if (method) setCheckout((current) => current ? { ...current, totals: { ...current.totals, shipping_amount: method.amount, grand_total_amount: current.totals.subtotal_amount - current.totals.discount_amount + method.amount + 0 } } : current); }} />{error && <p role="alert" className="mt-6 border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</p>}<PlaceOrderButton pending={pending} /></div><OrderSummary checkout={checkout} /></form></div>;
}

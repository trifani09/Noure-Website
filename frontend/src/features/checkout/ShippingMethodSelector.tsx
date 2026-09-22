import { formatMoney } from "@/lib/format";
import type { ShippingMethod } from "./types";

export function ShippingMethodSelector({ methods, selected, onSelect, loading }: { methods: ShippingMethod[]; selected: string; onSelect: (code: string) => void; loading: boolean }) {
  return <section className="mt-8 border-t border-line pt-8">
    <h2 className="editorial-title text-3xl">Shipping method</h2>
    {loading && <p className="mt-4 text-sm text-muted">Calculating delivery options...</p>}
    {!loading && <div className="mt-5 grid gap-3">{methods.map((method) => <label key={method.code} className="flex cursor-pointer gap-3 border border-line p-4 text-sm"><input type="radio" name="shipping-method" checked={selected === method.code} onChange={() => onSelect(method.code)} /><span className="flex-1"><strong>{method.name}</strong><br /><span className="text-xs text-muted">{method.estimate}</span></span><strong>{method.amount === 0 ? "Free" : formatMoney(method.amount, method.currency)}</strong></label>)}</div>}
  </section>;
}
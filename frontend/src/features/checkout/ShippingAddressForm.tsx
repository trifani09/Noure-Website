import type { Address, ShippingAddress } from "./types";
type Props = { addresses: Address[]; selectedAddress: string; address: ShippingAddress; onSelect: (value: string) => void; onChange: (field: keyof ShippingAddress, value: string) => void };
const fieldClass = "focus-ring w-full border border-line bg-paper px-4 py-3 text-sm";
export function ShippingAddressForm({ addresses, selectedAddress, address, onSelect, onChange }: Props) {
  const manual = !selectedAddress;
  return <section className="border-t border-line pt-8">
    <h2 className="editorial-title text-3xl">Shipping address</h2>
    {addresses.length > 0 && <div className="mt-5 grid gap-3">
      {addresses.map((item) => <label key={item.public_id} className="flex cursor-pointer gap-3 border border-line p-4 text-sm"><input type="radio" name="saved-address" checked={selectedAddress === item.public_id} onChange={() => onSelect(item.public_id)} /><span><strong>{item.label ?? "Saved address"}</strong><br />{item.line1}, {item.city} {item.postal_code}</span></label>)}
      <label className="flex cursor-pointer gap-3 border border-line p-4 text-sm"><input type="radio" name="saved-address" checked={manual} onChange={() => onSelect("")} />Use a different address</label>
    </div>}
    {manual && <div className="mt-5 grid gap-4 sm:grid-cols-2">
      <label className="sm:col-span-2 text-xs font-semibold uppercase tracking-widest">Address<input className={`${fieldClass} mt-2`} value={address.line1} onChange={(e) => onChange("line1", e.target.value)} required /></label>
      <label className="sm:col-span-2 text-xs font-semibold uppercase tracking-widest">Address line 2<input className={`${fieldClass} mt-2`} value={address.line2 ?? ""} onChange={(e) => onChange("line2", e.target.value)} /></label>
      <label className="text-xs font-semibold uppercase tracking-widest">City<input className={`${fieldClass} mt-2`} value={address.city} onChange={(e) => onChange("city", e.target.value)} required /></label>
      <label className="text-xs font-semibold uppercase tracking-widest">Province<input className={`${fieldClass} mt-2`} value={address.province ?? ""} onChange={(e) => onChange("province", e.target.value)} /></label>
      <label className="text-xs font-semibold uppercase tracking-widest">Postal code<input className={`${fieldClass} mt-2`} value={address.postal_code} onChange={(e) => onChange("postal_code", e.target.value)} required /></label>
    </div>}
  </section>;
}

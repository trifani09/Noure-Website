"use client";

import { FormEvent, useState } from "react";
import type { Address, AddressInput } from "@/types/account";

const empty: AddressInput = { label: "", recipient_name: "", phone: "", line1: "", line2: "", city: "", province: "", postal_code: "", country_code: "ID", is_default_shipping: false, is_default_billing: false };

export function AddressForm({ address, saving, onSubmit, onCancel }: { address?: Address; saving: boolean; onSubmit: (input: AddressInput) => Promise<void>; onCancel: () => void }) {
  const [values, setValues] = useState<AddressInput>(address ? { ...address } : empty);
  const set = (field: keyof AddressInput, value: string | boolean) => setValues((current) => ({ ...current, [field]: value }));
  async function submit(event: FormEvent<HTMLFormElement>) { event.preventDefault(); await onSubmit(values); }
  return <form onSubmit={submit} className="space-y-5 border border-line bg-ivory p-6 md:p-8">
    <div className="grid gap-5 md:grid-cols-2">
      {([['label', 'Label'], ['recipient_name', 'Recipient name'], ['phone', 'Phone'], ['line1', 'Address line 1'], ['line2', 'Address line 2'], ['city', 'City'], ['province', 'Province / state'], ['postal_code', 'Postal code'], ['country_code', 'Country code']] as const).map(([field, label]) => <label key={field} className={field === 'line1' || field === 'line2' ? 'md:col-span-2' : ''}><span className="mb-2 block text-xs font-semibold uppercase tracking-[.14em]">{label}</span><input required={!['label', 'line2', 'province'].includes(field)} value={String(values[field] ?? '')} onChange={(event) => set(field, event.target.value)} className="focus-ring w-full border border-line bg-paper px-3 py-3 text-sm" /></label>)}
    </div>
    <div className="flex flex-wrap gap-5 text-sm"><label className="flex items-center gap-2"><input type="checkbox" checked={values.is_default_shipping} onChange={(event) => set('is_default_shipping', event.target.checked)} /> Default shipping</label><label className="flex items-center gap-2"><input type="checkbox" checked={values.is_default_billing} onChange={(event) => set('is_default_billing', event.target.checked)} /> Default billing</label></div>
    <div className="flex gap-4"><button disabled={saving} className="button-primary disabled:opacity-50">{saving ? 'Saving...' : 'Save address'}</button><button type="button" onClick={onCancel} className="border-b border-ink text-xs font-semibold uppercase tracking-[.16em]">Cancel</button></div>
  </form>;
}
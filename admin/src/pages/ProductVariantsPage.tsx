import { useEffect, useMemo, useState, type FormEvent } from 'react'
import { ApiError } from '../api/client'
import { PageHeader } from '../components/PageHeader'
import { addOptionValue, createOption, generateVariants, getProduct, setDefaultVariant, updateVariant } from '../products/productService'
import type { Product, ProductOption, ProductVariant } from '../products/types'

const field = 'w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm'

function errorMessage(error: unknown): string {
  if (error instanceof ApiError) return error.errors.map((item) => item.message).filter(Boolean).join(' ') || error.message
  return 'The request could not be completed.'
}

function combinations(options: ProductOption[], selected: Record<string, string[]>): Record<string, string>[] {
  return options.reduce<Record<string, string>[]>((rows, option) => rows.flatMap((row) => (selected[option.code] ?? []).map((code) => ({ ...row, [option.code]: code }))), [{}])
}

export function ProductVariantsPage({ publicId }: { publicId: string }) {
  const [product, setProduct] = useState<Product | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [notice, setNotice] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)
  const [editing, setEditing] = useState<ProductVariant | null>(null)
  const [showGenerator, setShowGenerator] = useState(false)
  const [selected, setSelected] = useState<Record<string, string[]>>({})
  const [defaults, setDefaults] = useState({ price_amount: 0, compare_at_amount: '', currency: 'IDR', weight_grams: '', is_active: false, sku_template: '' })
  const [valueForm, setValueForm] = useState({ option: '', label: '', code: '', swatch_value: '', sort_order: 0 })
  const [optionForm, setOptionForm] = useState({ name: '', code: '', sort_order: 0, value_label: '', value_code: '', swatch_value: '' })

  const load = async () => {
    try { setProduct((await getProduct(publicId)).data); setError(null) } catch (caught) { setError(errorMessage(caught)) }
  }
  useEffect(() => { void load() }, [publicId])
  useEffect(() => {
    if (!product) return
    setSelected(Object.fromEntries((product.options ?? []).map((option) => [option.code, option.values.map((value) => value.code)])))
    setDefaults((current) => ({ ...current, price_amount: product.lowest_price ?? 0, currency: product.currency ?? 'IDR', sku_template: `NOU-${product.slug.toUpperCase()}-${(product.options ?? []).map((option) => `{${option.code}}`).join('-')}` }))
    setValueForm((current) => ({ ...current, option: current.option || product.options?.[0]?.code || '' }))
  }, [product?.public_id])

  const preview = useMemo(() => combinations(product?.options ?? [], selected), [product?.options, selected])
  const existingKeys = useMemo(() => new Set((product?.variants ?? []).map((variant) => (product?.options ?? []).map((option) => variant.option_values[option.code]).join('|'))), [product])

  async function submitValue(event: FormEvent) {
    event.preventDefault(); setBusy(true); setError(null)
    try {
      await addOptionValue(publicId, valueForm.option, { label: valueForm.label, code: valueForm.code, swatch_value: valueForm.swatch_value || null, sort_order: valueForm.sort_order })
      setNotice('Option value added. It is ready for variant generation.'); setValueForm((current) => ({ ...current, label: '', code: '', swatch_value: '', sort_order: current.sort_order + 1 })); await load()
    } catch (caught) { setError(errorMessage(caught)) } finally { setBusy(false) }
  }

  async function submitOption(event: FormEvent) {
    event.preventDefault(); setBusy(true); setError(null)
    try {
      await createOption(publicId, { name: optionForm.name, code: optionForm.code, sort_order: optionForm.sort_order, values: [{ label: optionForm.value_label, code: optionForm.value_code, swatch_value: optionForm.swatch_value || null, sort_order: 0 }] })
      setNotice('Product option created.'); setOptionForm({ name: '', code: '', sort_order: (product?.options?.length ?? 0) + 1, value_label: '', value_code: '', swatch_value: '' }); await load()
    } catch (caught) { setError(errorMessage(caught)) } finally { setBusy(false) }
  }

  async function submitGeneration(event: FormEvent) {
    event.preventDefault(); setBusy(true); setError(null)
    try {
      const response = await generateVariants(publicId, { option_values: selected, defaults: { price_amount: defaults.price_amount, compare_at_amount: defaults.compare_at_amount === '' ? null : Number(defaults.compare_at_amount), currency: defaults.currency, weight_grams: defaults.weight_grams === '' ? null : Number(defaults.weight_grams), is_active: defaults.is_active }, sku_template: defaults.sku_template })
      setNotice(`${response.data.created.length} variant${response.data.created.length === 1 ? '' : 's'} created; ${response.data.existing.length} already existed.`); setShowGenerator(false); await load()
    } catch (caught) { setError(errorMessage(caught)) } finally { setBusy(false) }
  }

  async function saveVariant(event: FormEvent) {
    event.preventDefault(); if (!editing?.public_id) return; setBusy(true); setError(null)
    try {
      await updateVariant(publicId, editing.public_id, { sku: editing.sku, title: editing.title, option_values: editing.option_values, price_amount: Number(editing.price_amount), compare_at_amount: editing.compare_at_amount === null ? null : Number(editing.compare_at_amount), currency: editing.currency, barcode: editing.barcode || null, weight_grams: editing.weight_grams === null ? null : Number(editing.weight_grams), is_active: editing.is_active })
      setNotice('Variant saved successfully.'); setEditing(null); await load()
    } catch (caught) { setError(errorMessage(caught)) } finally { setBusy(false) }
  }

  async function makeDefault(variant: ProductVariant) {
    if (!variant.public_id) return; setBusy(true); setError(null)
    try { await setDefaultVariant(publicId, variant.public_id); setNotice(`${variant.title || variant.sku} is now the default variant.`); await load() } catch (caught) { setError(errorMessage(caught)) } finally { setBusy(false) }
  }

  async function toggleActive(variant: ProductVariant) {
    if (!variant.public_id) return; setBusy(true); setError(null)
    try {
      await updateVariant(publicId, variant.public_id, { sku: variant.sku, title: variant.title, option_values: variant.option_values, price_amount: variant.price_amount, compare_at_amount: variant.compare_at_amount, currency: variant.currency, barcode: variant.barcode, weight_grams: variant.weight_grams, is_active: !variant.is_active })
      setNotice(`Variant ${variant.is_active ? 'disabled' : 'enabled'}.`); await load()
    } catch (caught) { setError(errorMessage(caught)) } finally { setBusy(false) }
  }

  if (!product && !error) return <div className="p-12 text-center text-sm text-stone-500">Loading variants…</div>
  if (!product) return <div className="rounded-lg bg-red-50 p-4 text-red-700">{error}</div>

  return <div className="space-y-6">
    <div className="flex flex-wrap items-end justify-between gap-3"><PageHeader title={`${product.name} variants`} description="Manage product options, combinations, pricing, and the default variant." /><div className="flex gap-2"><a className="rounded-lg border px-4 py-2 text-sm" href={`/products/${publicId}`}>Product details</a><button className="rounded-lg bg-stone-900 px-4 py-2 text-sm text-white" onClick={() => setShowGenerator((value) => !value)}>Generate variants</button></div></div>
    {error && <div className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</div>}
    {notice && <div className="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-700">{notice}</div>}

    <section className="rounded-xl border bg-white p-5"><div className="flex items-center justify-between"><h2 className="font-semibold">Product options</h2><span className="text-sm text-stone-500">{product.variant_count} total variants</span></div><div className="mt-4 grid gap-4 md:grid-cols-2">{product.options?.map((option) => <div key={option.code} className="rounded-lg border p-4"><div className="flex justify-between"><div><h3 className="font-medium">{option.name}</h3><code className="text-xs text-stone-500">{option.code}</code></div><span className="text-xs text-stone-500">Order {option.sort_order}</span></div><div className="mt-3 flex flex-wrap gap-2">{option.values.map((value) => <span key={value.code} className="inline-flex items-center gap-2 rounded-full bg-stone-100 px-3 py-1 text-sm">{value.swatch_value && <span className="h-3 w-3 rounded-full border" style={{ background: value.swatch_value }} />}{value.label} <code className="text-xs text-stone-500">{value.code}</code><small>#{value.sort_order}</small></span>)}</div></div>)}</div>
      <form className="mt-5 grid gap-3 border-t pt-5 sm:grid-cols-6" onSubmit={submitValue}><select className={field} value={valueForm.option} onChange={(event) => setValueForm({ ...valueForm, option: event.target.value })}>{product.options?.map((option) => <option key={option.code} value={option.code}>{option.name}</option>)}</select><input required className={field} placeholder="Value label" value={valueForm.label} onChange={(event) => setValueForm({ ...valueForm, label: event.target.value })} /><input required className={field} placeholder="value-code" value={valueForm.code} onChange={(event) => setValueForm({ ...valueForm, code: event.target.value })} /><input className={field} placeholder="Swatch (#fff)" value={valueForm.swatch_value} onChange={(event) => setValueForm({ ...valueForm, swatch_value: event.target.value })} /><input className={field} type="number" min="0" value={valueForm.sort_order} onChange={(event) => setValueForm({ ...valueForm, sort_order: Number(event.target.value) })} /><button disabled={busy} className="rounded-lg border px-3 py-2 text-sm disabled:opacity-50">Add value</button></form>
      <details className="mt-5 border-t pt-5"><summary className="cursor-pointer text-sm font-medium">Create a new option</summary><p className="mt-2 text-xs text-amber-700">Products with existing variants return the documented regeneration conflict.</p><form className="mt-3 grid gap-3 sm:grid-cols-4" onSubmit={submitOption}><input required className={field} placeholder="Option name (Size)" value={optionForm.name} onChange={(event) => setOptionForm({ ...optionForm, name: event.target.value })} /><input required className={field} placeholder="Option code (size)" value={optionForm.code} onChange={(event) => setOptionForm({ ...optionForm, code: event.target.value })} /><input required className={field} placeholder="Initial value (S)" value={optionForm.value_label} onChange={(event) => setOptionForm({ ...optionForm, value_label: event.target.value })} /><input required className={field} placeholder="Value code (s)" value={optionForm.value_code} onChange={(event) => setOptionForm({ ...optionForm, value_code: event.target.value })} /><input className={field} placeholder="Swatch" value={optionForm.swatch_value} onChange={(event) => setOptionForm({ ...optionForm, swatch_value: event.target.value })} /><input className={field} type="number" min="0" value={optionForm.sort_order} onChange={(event) => setOptionForm({ ...optionForm, sort_order: Number(event.target.value) })} /><button disabled={busy} className="rounded-lg bg-stone-900 px-3 py-2 text-sm text-white disabled:opacity-50">Create option</button></form></details>
    </section>

    {showGenerator && <form className="rounded-xl border bg-white p-5" onSubmit={submitGeneration}><h2 className="font-semibold">Generate missing combinations</h2><div className="mt-4 grid gap-4 md:grid-cols-2">{product.options?.map((option) => <fieldset key={option.code}><legend className="mb-2 text-sm font-medium">{option.name}</legend><div className="flex flex-wrap gap-2">{option.values.map((value) => <label key={value.code} className="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm"><input type="checkbox" checked={(selected[option.code] ?? []).includes(value.code)} onChange={(event) => setSelected({ ...selected, [option.code]: event.target.checked ? [...(selected[option.code] ?? []), value.code] : (selected[option.code] ?? []).filter((code) => code !== value.code) })} />{value.label}</label>)}</div></fieldset>)}</div><div className="mt-5 grid gap-3 md:grid-cols-3"><label className="text-sm">Price<input required className={`${field} mt-1`} type="number" min="0" value={defaults.price_amount} onChange={(event) => setDefaults({ ...defaults, price_amount: Number(event.target.value) })} /></label><label className="text-sm">Compare-at price<input className={`${field} mt-1`} type="number" min="0" value={defaults.compare_at_amount} onChange={(event) => setDefaults({ ...defaults, compare_at_amount: event.target.value })} /></label><label className="text-sm">Currency<input required className={`${field} mt-1`} maxLength={3} value={defaults.currency} onChange={(event) => setDefaults({ ...defaults, currency: event.target.value.toUpperCase() })} /></label><label className="text-sm">Weight (grams)<input className={`${field} mt-1`} type="number" min="0" value={defaults.weight_grams} onChange={(event) => setDefaults({ ...defaults, weight_grams: event.target.value })} /></label><label className="text-sm md:col-span-2">SKU template<input required className={`${field} mt-1`} value={defaults.sku_template} onChange={(event) => setDefaults({ ...defaults, sku_template: event.target.value })} /></label><label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={defaults.is_active} onChange={(event) => setDefaults({ ...defaults, is_active: event.target.checked })} />Generated variants are active</label></div><div className="mt-5 rounded-lg bg-stone-50 p-4"><h3 className="text-sm font-medium">Combination preview ({preview.length})</h3><div className="mt-2 flex max-h-44 flex-wrap gap-2 overflow-y-auto">{preview.map((row) => { const key = (product.options ?? []).map((option) => row[option.code]).join('|'); const exists = existingKeys.has(key); return <span key={key} className={`rounded-full px-3 py-1 text-xs ${exists ? 'bg-stone-200 text-stone-600' : 'bg-emerald-100 text-emerald-800'}`}>{(product.options ?? []).map((option) => option.values.find((value) => value.code === row[option.code])?.label).join(' / ')} · {exists ? 'exists' : 'will create'}</span> })}</div></div><button disabled={busy || preview.length === 0} className="mt-4 rounded-lg bg-stone-900 px-4 py-2 text-sm text-white disabled:opacity-50">{busy ? 'Generating…' : 'Generate missing variants'}</button></form>}

    <section className="overflow-hidden rounded-xl border bg-white"><div className="border-b p-5"><h2 className="font-semibold">Variants ({product.variants?.length ?? 0})</h2></div><div className="overflow-x-auto"><table className="w-full min-w-[900px] text-sm"><thead className="bg-stone-50 text-left text-stone-500"><tr><th className="p-3">Variant title</th><th>SKU</th><th>Selected options</th><th>Price</th><th>Compare-at</th><th>Status</th><th>Default</th><th>Actions</th></tr></thead><tbody>{product.variants?.map((variant) => <tr key={variant.public_id} className="border-t"><td className="p-3 font-medium">{variant.title || 'Untitled'}</td><td>{variant.sku}</td><td>{(product.options ?? []).map((option) => option.values.find((value) => value.code === variant.option_values[option.code])?.label).join(' / ')}</td><td>{variant.price_amount.toLocaleString()} {variant.currency}</td><td>{variant.compare_at_amount?.toLocaleString() ?? '—'}</td><td><span className={`rounded-full px-2 py-1 text-xs ${variant.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-200'}`}>{variant.is_active ? 'Active' : 'Inactive'}</span></td><td>{variant.is_default ? 'Yes' : 'No'}</td><td><div className="flex gap-2"><button className="underline" onClick={() => setEditing({ ...variant, option_values: { ...variant.option_values } })}>Edit</button><button disabled={busy || variant.is_default} className="underline disabled:text-stone-300" onClick={() => void toggleActive(variant)}>{variant.is_active ? 'Disable' : 'Enable'}</button>{!variant.is_default && <button disabled={busy || !variant.is_active} className="underline disabled:text-stone-300" onClick={() => void makeDefault(variant)}>Set default</button>}</div></td></tr>)}</tbody></table></div></section>

    {editing && <form className="rounded-xl border-2 border-stone-900 bg-white p-5" onSubmit={saveVariant}><div className="flex justify-between"><h2 className="font-semibold">Edit variant</h2><button type="button" className="text-sm underline" onClick={() => setEditing(null)}>Cancel</button></div><div className="mt-4 grid gap-3 md:grid-cols-3"><label className="text-sm">SKU<input required className={`${field} mt-1`} value={editing.sku} onChange={(event) => setEditing({ ...editing, sku: event.target.value })} /></label><label className="text-sm">Title<input className={`${field} mt-1`} value={editing.title ?? ''} onChange={(event) => setEditing({ ...editing, title: event.target.value || null })} /></label>{product.options?.map((option) => <label key={option.code} className="text-sm">{option.name}<select className={`${field} mt-1`} value={editing.option_values[option.code]} onChange={(event) => setEditing({ ...editing, option_values: { ...editing.option_values, [option.code]: event.target.value } })}>{option.values.map((value) => <option key={value.code} value={value.code}>{value.label}</option>)}</select></label>)}<label className="text-sm">Price<input required className={`${field} mt-1`} type="number" min="0" value={editing.price_amount} onChange={(event) => setEditing({ ...editing, price_amount: Number(event.target.value) })} /></label><label className="text-sm">Compare-at<input className={`${field} mt-1`} type="number" min="0" value={editing.compare_at_amount ?? ''} onChange={(event) => setEditing({ ...editing, compare_at_amount: event.target.value === '' ? null : Number(event.target.value) })} /></label><label className="text-sm">Currency<input required className={`${field} mt-1`} value={editing.currency} maxLength={3} onChange={(event) => setEditing({ ...editing, currency: event.target.value.toUpperCase() })} /></label><label className="text-sm">Barcode<input className={`${field} mt-1`} value={editing.barcode ?? ''} onChange={(event) => setEditing({ ...editing, barcode: event.target.value || null })} /></label><label className="text-sm">Weight (grams)<input className={`${field} mt-1`} type="number" min="0" value={editing.weight_grams ?? ''} onChange={(event) => setEditing({ ...editing, weight_grams: event.target.value === '' ? null : Number(event.target.value) })} /></label><label className="flex items-center gap-2 self-end py-2 text-sm"><input type="checkbox" checked={editing.is_active} onChange={(event) => setEditing({ ...editing, is_active: event.target.checked })} />Active</label></div><button disabled={busy} className="mt-4 rounded-lg bg-stone-900 px-4 py-2 text-sm text-white disabled:opacity-50">{busy ? 'Saving…' : 'Save variant'}</button></form>}
  </div>
}

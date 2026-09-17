import { useCallback, useEffect, useState, type FormEvent } from 'react'
import { ApiError } from '../api/client'
import { listCategories } from '../categories/categoryService'
import type { Category } from '../categories/types'
import { PageHeader } from '../components/PageHeader'
import { getVariantInventory } from '../inventory/inventoryService'
import type { InventoryLevel } from '../inventory/types'
import { getProduct, listProducts } from '../products/productService'
import type { Pagination, Product, ProductVariant } from '../products/types'

type Row = { product: Product; variant: ProductVariant; level: InventoryLevel | null }
const initial: Pagination = { total: 0, per_page: 10, current_page: 1, last_page: 1 }
const control = 'rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm'

export function InventoryPage() {
  const [rows, setRows] = useState<Row[]>([]); const [categories, setCategories] = useState<Category[]>([]); const [pagination, setPagination] = useState(initial)
  const [searchInput, setSearchInput] = useState(''); const [search, setSearch] = useState(''); const [category, setCategory] = useState(''); const [availability, setAvailability] = useState(''); const [page, setPage] = useState(1)
  const [loading, setLoading] = useState(true); const [error, setError] = useState<string | null>(null)
  const load = useCallback(async () => {
    setLoading(true); setError(null)
    try {
      const response = await listProducts({ page, per_page: 10, search, category, availability })
      const details = await Promise.all(response.data.map((product) => getProduct(product.public_id).then((result) => result.data)))
      const variantRows = details.flatMap((product) => (product.variants ?? []).map((variant) => ({ product, variant })))
      const inventory = await Promise.all(variantRows.map(({ variant }) => variant.public_id ? getVariantInventory(variant.public_id).then((result) => result.data).catch(() => []) : Promise.resolve([])))
      setRows(variantRows.flatMap<Row>((row, index) => inventory[index].length ? inventory[index].map((level) => ({ ...row, level })) : [{ ...row, level: null }]))
      setPagination(response.meta.pagination)
    } catch (caught) { setError(caught instanceof ApiError ? caught.message : 'Inventory could not be loaded.') } finally { setLoading(false) }
  }, [availability, category, page, search])
  // oxlint-disable-next-line react/set-state-in-effect
  useEffect(() => { void load() }, [load])
  useEffect(() => { listCategories({ per_page: 100 }).then((response) => setCategories(response.data)).catch(() => undefined) }, [])
  function submit(event: FormEvent) { event.preventDefault(); setPage(1); setSearch(searchInput.trim()) }
  return <div className="space-y-6"><PageHeader title="Inventory" description="Review stock by sellable product variant and inventory location." />
    <section className="rounded-xl border bg-white"><div className="grid gap-3 border-b p-4 md:grid-cols-3"><form className="flex gap-2" onSubmit={submit}><input className={`${control} min-w-0 flex-1`} placeholder="Search product" value={searchInput} onChange={(event) => setSearchInput(event.target.value)} /><button className="rounded-lg border px-3 text-sm">Search</button></form><select className={control} value={category} onChange={(event) => { setCategory(event.target.value); setPage(1) }}><option value="">All categories</option>{categories.map((item) => <option key={item.public_id} value={item.slug}>{item.name}</option>)}</select><select className={control} value={availability} onChange={(event) => { setAvailability(event.target.value); setPage(1) }}><option value="">All availability</option><option value="available">Available</option><option value="unavailable">Unavailable</option></select></div>
      {error ? <div className="p-10 text-center text-sm text-red-700">{error} <button className="underline" onClick={() => void load()}>Try again</button></div> : loading ? <div className="p-12 text-center text-sm text-stone-500">Loading inventory…</div> : <div className="overflow-x-auto"><table className="w-full min-w-[1050px] text-left text-sm"><thead className="bg-stone-50 text-xs uppercase text-stone-500"><tr><th className="p-3">Product</th><th>Variant</th><th>SKU</th><th>Location</th><th>On hand</th><th>Reserved</th><th>Safety stock</th><th>Available</th><th>Status</th></tr></thead><tbody className="divide-y">{rows.map(({ product, variant, level }) => { const query = new URLSearchParams({ product: product.name, variant: variant.title ?? variant.sku, sku: variant.sku }).toString(); return <tr key={`${variant.public_id}-${level?.location.code ?? 'none'}`}><td className="p-3 font-medium">{product.name}</td><td><a className="hover:underline" href={`/inventory/${variant.public_id}?${query}`}>{variant.title ?? 'Untitled'}</a></td><td>{variant.sku}</td><td>{level?.location.name ?? 'MAIN (not initialized)'}</td><td>{level?.on_hand ?? 0}</td><td>{level?.reserved ?? 0}</td><td>{level?.safety_stock ?? 0}</td><td className="font-medium">{level?.available ?? 0}</td><td><span className={`rounded-full px-2 py-1 text-xs ${(level?.available ?? 0) > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'}`}>{(level?.available ?? 0) > 0 ? 'Available' : 'Unavailable'}</span></td></tr> })}</tbody></table>{!rows.length && <div className="p-12 text-center text-sm text-stone-500">No inventory variants found.</div>}</div>}
      <div className="flex justify-between border-t p-3 text-sm"><span>{pagination.total} products · Page {pagination.current_page} of {pagination.last_page}</span><div className="flex gap-2"><button disabled={page <= 1} className="rounded border px-3 py-1 disabled:opacity-40" onClick={() => setPage(page - 1)}>Previous</button><button disabled={page >= pagination.last_page} className="rounded border px-3 py-1 disabled:opacity-40" onClick={() => setPage(page + 1)}>Next</button></div></div>
    </section>
  </div>
}

import { useEffect, useState } from 'react'
import { ApiError } from '../api/client'
import { PageHeader } from '../components/PageHeader'
import { getProduct } from '../products/productService'
import type { Product } from '../products/types'

export function ProductDetailPage({ publicId }: { publicId: string }) {
  const [product, setProduct] = useState<Product | null>(null)
  const [error, setError] = useState<string | null>(null)
  useEffect(() => { getProduct(publicId).then((response) => setProduct(response.data)).catch((caught) => setError(caught instanceof ApiError ? caught.message : 'Product could not be loaded.')) }, [publicId])
  if (error) return <div className="text-red-700">{error}</div>
  if (!product) return <div className="p-12 text-center text-sm text-stone-500">Loading product…</div>
  return <div className="space-y-6">
    <div className="flex flex-wrap items-end justify-between gap-3"><PageHeader title={product.name} description={product.short_description ?? 'Product details'} /><div className="flex gap-2"><a href={`/products/${publicId}/media`} className="rounded-lg border px-4 py-2 text-sm">Manage media</a><a href={`/products/${publicId}/variants`} className="rounded-lg border px-4 py-2 text-sm">Manage variants</a><a href={`/products/${publicId}/edit`} className="rounded-lg bg-stone-900 px-4 py-2 text-sm text-white">Edit product</a></div></div>
    {new URLSearchParams(location.search).has('updated') && <div className="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-700">Product updated successfully.</div>}
    <div className="grid gap-5 lg:grid-cols-3"><section className="rounded-xl border bg-white p-5 lg:col-span-2"><h2 className="font-semibold">Details</h2><dl className="mt-4 grid gap-3 sm:grid-cols-2"><div><dt className="text-xs text-stone-500">Status</dt><dd className="capitalize">{product.status}</dd></div><div><dt className="text-xs text-stone-500">Brand</dt><dd>{product.brand ?? '—'}</dd></div><div><dt className="text-xs text-stone-500">Slug</dt><dd>/{product.slug}</dd></div><div><dt className="text-xs text-stone-500">Category</dt><dd>{product.primary_category?.name ?? '—'}</dd></div></dl><p className="mt-5 whitespace-pre-wrap text-sm text-stone-700">{product.description}</p></section><section className="rounded-xl border bg-white p-5">{product.primary_image ? <img className="w-full rounded-lg" src={product.primary_image.url} alt={product.primary_image.alt_text ?? ''} /> : <div className="grid aspect-square place-items-center bg-stone-100 text-stone-400">No image</div>}</section></div>
    <section className="rounded-xl border bg-white p-5"><h2 className="font-semibold">Variants ({product.variants?.length})</h2><div className="mt-3 overflow-x-auto"><table className="w-full text-sm"><thead><tr className="text-left text-stone-500"><th>Title</th><th>SKU</th><th>Price</th><th>Active</th><th>Default</th></tr></thead><tbody>{product.variants?.map((variant) => <tr key={variant.public_id} className="border-t"><td className="py-3">{variant.title}</td><td>{variant.sku}</td><td>{variant.price_amount.toLocaleString()} {variant.currency}</td><td>{variant.is_active ? 'Yes' : 'No'}</td><td>{variant.is_default ? 'Yes' : 'No'}</td></tr>)}</tbody></table></div></section>
  </div>
}

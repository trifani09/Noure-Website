import { useEffect, useState } from 'react'
import { ApiError } from '../api/client'
import { PageHeader } from '../components/PageHeader'
import { ProductForm } from '../products/ProductForm'
import { getProduct, updateProduct } from '../products/productService'
import type { Product } from '../products/types'
export function ProductEditPage({ publicId }: { publicId: string }) { const [product, setProduct] = useState<Product | null>(null); const [error, setError] = useState<string | null>(null); useEffect(() => { getProduct(publicId).then((r) => setProduct(r.data)).catch((e) => setError(e instanceof ApiError ? e.message : 'Product could not be loaded.')) }, [publicId]); if (error) return <div className="text-red-700">{error}</div>; if (!product) return <div className="p-12 text-center text-sm text-stone-500">Loading product…</div>; return <div className="space-y-6"><PageHeader title={`Edit ${product.name}`} description="Relationship sections are saved as complete replacements." /><ProductForm product={product} onSave={async (input) => { await updateProduct(publicId, input); window.location.assign(`/products/${publicId}?updated=1`) }} /></div> }

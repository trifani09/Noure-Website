import { useMemo, useState, type FormEvent } from 'react'
import { ApiError } from '../api/client'
import type { Category, CategoryInput } from './types'

type Props = {
  category: Category | null
  categories: Category[]
  onClose: () => void
  onSubmit: (input: CategoryInput) => Promise<void>
}

export function CategoryFormDialog({ category, categories, onClose, onSubmit }: Props) {
  const [name, setName] = useState(category?.name ?? '')
  const [slug, setSlug] = useState(category?.slug ?? '')
  const [description, setDescription] = useState(category?.description ?? '')
  const [parent, setParent] = useState(category?.parent?.public_id ?? '')
  const [sortOrder, setSortOrder] = useState(String(category?.sort_order ?? 0))
  const [imagePath, setImagePath] = useState(category?.image_path ?? '')
  const [active, setActive] = useState(category?.is_active ?? true)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({})

  const blockedParents = useMemo(() => {
    if (!category) return new Set<string>()
    const blocked = new Set([category.public_id])
    let changed = true
    while (changed) {
      changed = false
      categories.forEach((candidate) => {
        if (candidate.parent && blocked.has(candidate.parent.public_id) && !blocked.has(candidate.public_id)) {
          blocked.add(candidate.public_id)
          changed = true
        }
      })
    }
    return blocked
  }, [categories, category])

  async function submit(event: FormEvent) {
    event.preventDefault()
    setSaving(true)
    setError(null)
    setFieldErrors({})
    try {
      await onSubmit({
        name: name.trim(),
        ...(slug.trim() ? { slug: slug.trim() } : {}),
        description: description.trim() || null,
        parent_public_id: parent || null,
        sort_order: Number(sortOrder),
        is_active: active,
        image_path: imagePath.trim() || null,
      })
    } catch (reason) {
      if (reason instanceof ApiError) {
        setError(reason.message)
        setFieldErrors(Object.fromEntries(reason.errors.filter((item) => item.field).map((item) => [item.field as string, item.message ?? 'Invalid value.'])))
      } else setError('The category could not be saved.')
    } finally {
      setSaving(false)
    }
  }

  const fieldClass = 'mt-1.5 w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 outline-none transition focus:border-stone-600 focus:ring-2 focus:ring-stone-200'
  const errorFor = (field: string) => fieldErrors[field] ? <p className="mt-1 text-xs text-red-700">{fieldErrors[field]}</p> : null

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/45 p-4" role="dialog" aria-modal="true" aria-labelledby="category-form-title">
      <form onSubmit={submit} className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white shadow-xl">
        <div className="border-b border-stone-200 px-6 py-5">
          <h2 id="category-form-title" className="text-lg font-semibold text-stone-950">{category ? 'Edit category' : 'Create category'}</h2>
          <p className="mt-1 text-sm text-stone-600">Set storefront naming, hierarchy, and visibility.</p>
        </div>
        <div className="grid gap-5 px-6 py-5 sm:grid-cols-2">
          {error && <div className="sm:col-span-2 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800">{error}</div>}
          <label className="text-sm font-medium text-stone-700">Name <span className="text-red-600">*</span>
            <input className={fieldClass} value={name} onChange={(e) => setName(e.target.value)} required maxLength={160} />
            {errorFor('name')}
          </label>
          <label className="text-sm font-medium text-stone-700">Slug
            <input className={fieldClass} value={slug} onChange={(e) => setSlug(e.target.value)} placeholder="Generated from name when blank" maxLength={180} />
            {errorFor('slug')}
          </label>
          <label className="text-sm font-medium text-stone-700">Parent category
            <select className={fieldClass} value={parent} onChange={(e) => setParent(e.target.value)}>
              <option value="">No parent (root)</option>
              {categories.filter((item) => !blockedParents.has(item.public_id)).map((item) => <option key={item.public_id} value={item.public_id}>{item.name}</option>)}
            </select>
            {errorFor('parent_public_id')}
          </label>
          <label className="text-sm font-medium text-stone-700">Sort order
            <input className={fieldClass} type="number" min="0" value={sortOrder} onChange={(e) => setSortOrder(e.target.value)} required />
            {errorFor('sort_order')}
          </label>
          <label className="sm:col-span-2 text-sm font-medium text-stone-700">Description
            <textarea className={`${fieldClass} min-h-24 resize-y`} value={description} onChange={(e) => setDescription(e.target.value)} maxLength={5000} />
            {errorFor('description')}
          </label>
          <label className="sm:col-span-2 text-sm font-medium text-stone-700">Image path
            <input className={fieldClass} value={imagePath} onChange={(e) => setImagePath(e.target.value)} placeholder="categories/dresses.jpg" maxLength={2048} />
            {errorFor('image_path')}
          </label>
          <label className="sm:col-span-2 flex items-center gap-3 text-sm font-medium text-stone-700">
            <input type="checkbox" checked={active} onChange={(e) => setActive(e.target.checked)} className="size-4 rounded border-stone-300" />
            Active and visible in the public catalog
          </label>
        </div>
        <div className="flex justify-end gap-3 border-t border-stone-200 px-6 py-4">
          <button type="button" onClick={onClose} disabled={saving} className="rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50 disabled:opacity-50">Cancel</button>
          <button type="submit" disabled={saving} className="rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-700 disabled:opacity-50">{saving ? 'Saving…' : category ? 'Save changes' : 'Create category'}</button>
        </div>
      </form>
    </div>
  )
}

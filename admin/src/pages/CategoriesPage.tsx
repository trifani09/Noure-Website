import { useCallback, useEffect, useState, type FormEvent } from 'react'
import { ApiError } from '../api/client'
import { CategoryFormDialog } from '../categories/CategoryFormDialog'
import { DeleteCategoryDialog } from '../categories/DeleteCategoryDialog'
import { createCategory, deleteCategory, listCategories, updateCategory } from '../categories/categoryService'
import type { Category, CategoryInput, Pagination } from '../categories/types'
import { PageHeader } from '../components/PageHeader'

const initialPagination: Pagination = { total: 0, per_page: 20, current_page: 1, last_page: 1 }

export function CategoriesPage() {
  const [categories, setCategories] = useState<Category[]>([])
  const [parentOptions, setParentOptions] = useState<Category[]>([])
  const [pagination, setPagination] = useState(initialPagination)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [searchInput, setSearchInput] = useState('')
  const [search, setSearch] = useState('')
  const [active, setActive] = useState('')
  const [parent, setParent] = useState('')
  const [sort, setSort] = useState('position')
  const [page, setPage] = useState(1)
  const [editing, setEditing] = useState<Category | 'create' | null>(null)
  const [deleting, setDeleting] = useState<Category | null>(null)

  const loadCategories = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const response = await listCategories({ page, per_page: 20, search, is_active: active, parent, sort })
      setCategories(response.data)
      setPagination(response.meta.pagination)
    } catch (reason) {
      setError(reason instanceof ApiError ? reason.message : 'Categories could not be loaded.')
    } finally { setLoading(false) }
  }, [active, page, parent, search, sort])

  const loadParentOptions = useCallback(async () => {
    try {
      const first = await listCategories({ page: 1, per_page: 100, sort: 'position' })
      const all = [...first.data]
      for (let next = 2; next <= first.meta.pagination.last_page; next += 1) {
        const response = await listCategories({ page: next, per_page: 100, sort: 'position' })
        all.push(...response.data)
      }
      setParentOptions(all)
    } catch { /* The main table remains usable if option loading fails. */ }
  }, [])

  // These effects synchronize the page with its remote category collections.
  // oxlint-disable-next-line react/set-state-in-effect
  useEffect(() => { void loadCategories() }, [loadCategories])
  // oxlint-disable-next-line react/set-state-in-effect
  useEffect(() => { void loadParentOptions() }, [loadParentOptions])

  function submitSearch(event: FormEvent) {
    event.preventDefault()
    setPage(1)
    setSearch(searchInput.trim())
  }

  async function save(input: CategoryInput) {
    if (editing === 'create') await createCategory(input)
    else if (editing) await updateCategory(editing.public_id, input)
    setEditing(null)
    await Promise.all([loadCategories(), loadParentOptions()])
  }

  async function remove() {
    if (!deleting) return
    await deleteCategory(deleting.public_id)
    setDeleting(null)
    if (categories.length === 1 && page > 1) setPage(page - 1)
    else await loadCategories()
    await loadParentOptions()
  }

  const controlClass = 'rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm text-stone-800 outline-none focus:border-stone-600 focus:ring-2 focus:ring-stone-200'

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <PageHeader title="Categories" description="Organize the catalog hierarchy and control which categories appear publicly." />
        <button onClick={() => setEditing('create')} className="shrink-0 rounded-lg bg-stone-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-stone-700">Create category</button>
      </div>

      <div className="rounded-xl border border-stone-200 bg-white shadow-sm">
        <div className="grid gap-3 border-b border-stone-200 p-4 lg:grid-cols-[minmax(14rem,1fr)_auto_auto_auto]">
          <form onSubmit={submitSearch} className="flex gap-2">
            <input className={`${controlClass} min-w-0 flex-1`} value={searchInput} onChange={(e) => setSearchInput(e.target.value)} placeholder="Search categories" aria-label="Search categories" />
            <button className="rounded-lg border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50">Search</button>
          </form>
          <select className={controlClass} value={active} onChange={(e) => { setActive(e.target.value); setPage(1) }} aria-label="Filter by status">
            <option value="">All statuses</option><option value="true">Active</option><option value="false">Inactive</option>
          </select>
          <select className={controlClass} value={parent} onChange={(e) => { setParent(e.target.value); setPage(1) }} aria-label="Filter by parent">
            <option value="">All parents</option><option value="root">Root categories</option>
            {parentOptions.map((item) => <option key={item.public_id} value={item.public_id}>Children of {item.name}</option>)}
          </select>
          <select className={controlClass} value={sort} onChange={(e) => { setSort(e.target.value); setPage(1) }} aria-label="Sort categories">
            <option value="position">Position</option><option value="name_asc">Name A–Z</option><option value="name_desc">Name Z–A</option><option value="newest">Newest</option><option value="oldest">Oldest</option>
          </select>
        </div>

        {error ? (
          <div className="p-8 text-center"><p className="text-sm text-red-700">{error}</p><button onClick={() => void loadCategories()} className="mt-3 text-sm font-medium text-stone-900 underline">Try again</button></div>
        ) : loading ? (
          <div className="p-12 text-center text-sm text-stone-500">Loading categories…</div>
        ) : categories.length === 0 ? (
          <div className="p-12 text-center"><p className="font-medium text-stone-900">No categories found</p><p className="mt-1 text-sm text-stone-500">Adjust the filters or create the first category.</p></div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full min-w-[760px] text-left text-sm">
              <thead className="bg-stone-50 text-xs uppercase tracking-wide text-stone-500"><tr><th className="px-4 py-3 font-medium">Category</th><th className="px-4 py-3 font-medium">Parent</th><th className="px-4 py-3 font-medium">Status</th><th className="px-4 py-3 text-right font-medium">Position</th><th className="px-4 py-3 text-right font-medium">Products</th><th className="px-4 py-3 text-right font-medium">Actions</th></tr></thead>
              <tbody className="divide-y divide-stone-100">
                {categories.map((category) => (
                  <tr key={category.public_id} className="hover:bg-stone-50/70">
                    <td className="px-4 py-4"><p className="font-medium text-stone-900">{category.name}</p><p className="mt-0.5 text-xs text-stone-500">/{category.slug}</p></td>
                    <td className="px-4 py-4 text-stone-600">{category.parent?.name ?? 'Root'}</td>
                    <td className="px-4 py-4"><span className={`rounded-full px-2.5 py-1 text-xs font-medium ${category.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-stone-100 text-stone-600'}`}>{category.is_active ? 'Active' : 'Inactive'}</span></td>
                    <td className="px-4 py-4 text-right text-stone-600">{category.sort_order}</td>
                    <td className="px-4 py-4 text-right text-stone-600">{category.direct_product_count}</td>
                    <td className="px-4 py-4 text-right"><button onClick={() => setEditing(category)} className="font-medium text-stone-700 hover:text-stone-950">Edit</button><button onClick={() => setDeleting(category)} className="ml-4 font-medium text-red-700 hover:text-red-900">Delete</button></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        <div className="flex items-center justify-between border-t border-stone-200 px-4 py-3 text-sm text-stone-600">
          <span>{pagination.total} {pagination.total === 1 ? 'category' : 'categories'} · Page {pagination.current_page} of {pagination.last_page}</span>
          <div className="flex gap-2"><button disabled={page <= 1 || loading} onClick={() => setPage(page - 1)} className="rounded-lg border border-stone-300 px-3 py-1.5 font-medium disabled:opacity-40">Previous</button><button disabled={page >= pagination.last_page || loading} onClick={() => setPage(page + 1)} className="rounded-lg border border-stone-300 px-3 py-1.5 font-medium disabled:opacity-40">Next</button></div>
        </div>
      </div>

      {editing && <CategoryFormDialog category={editing === 'create' ? null : editing} categories={parentOptions} onClose={() => setEditing(null)} onSubmit={save} />}
      {deleting && <DeleteCategoryDialog category={deleting} onClose={() => setDeleting(null)} onDelete={remove} />}
    </div>
  )
}

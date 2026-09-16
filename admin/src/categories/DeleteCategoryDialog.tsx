import { useState } from 'react'
import { ApiError } from '../api/client'
import type { Category } from './types'

export function DeleteCategoryDialog({ category, onClose, onDelete }: { category: Category; onClose: () => void; onDelete: () => Promise<void> }) {
  const [deleting, setDeleting] = useState(false)
  const [error, setError] = useState<string | null>(null)

  async function confirm() {
    setDeleting(true)
    setError(null)
    try { await onDelete() }
    catch (reason) { setError(reason instanceof ApiError ? reason.message : 'The category could not be deleted.') }
    finally { setDeleting(false) }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/45 p-4" role="alertdialog" aria-modal="true" aria-labelledby="delete-title">
      <div className="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
        <h2 id="delete-title" className="text-lg font-semibold text-stone-950">Delete category?</h2>
        <p className="mt-2 text-sm leading-6 text-stone-600">“{category.name}” will be removed from category management and the public catalog. This is a soft delete.</p>
        {error && <p className="mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800">{error}</p>}
        <div className="mt-6 flex justify-end gap-3">
          <button onClick={onClose} disabled={deleting} className="rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50 disabled:opacity-50">Cancel</button>
          <button onClick={confirm} disabled={deleting} className="rounded-lg bg-red-700 px-4 py-2 text-sm font-medium text-white hover:bg-red-800 disabled:opacity-50">{deleting ? 'Deleting…' : 'Delete category'}</button>
        </div>
      </div>
    </div>
  )
}

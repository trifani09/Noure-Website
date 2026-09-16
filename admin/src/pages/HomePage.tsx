import { useState } from 'react'
import { useAuth } from '../auth/useAuth'

export function HomePage({ onLoggedOut }: { onLoggedOut: () => void }) {
  const { admin, logout } = useAuth()
  const [submitting, setSubmitting] = useState(false)

  async function signOut() {
    setSubmitting(true)
    try { await logout(); onLoggedOut() } finally { setSubmitting(false) }
  }

  return (
    <main className="min-h-screen bg-stone-100 p-8">
      <section className="mx-auto max-w-3xl rounded-xl border border-stone-200 bg-white p-8 shadow-sm">
        <div className="flex items-start justify-between gap-6">
          <div>
            <p className="text-sm font-medium uppercase tracking-[0.2em] text-stone-500">Noure Admin</p>
            <h1 className="mt-3 text-3xl font-semibold text-stone-900">Welcome, {admin?.name}</h1>
            <p className="mt-2 text-stone-600">{admin?.email}</p>
          </div>
          <button className="rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 disabled:opacity-60" type="button" disabled={submitting} onClick={signOut}>
            {submitting ? 'Signing out…' : 'Sign out'}
          </button>
        </div>
      </section>
    </main>
  )
}

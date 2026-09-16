import { useState } from 'react'
import { useAuth } from '../auth/useAuth'
import { useRouter } from '../routing/useRouter'

export function Header({ title, onOpenNavigation }: { title: string; onOpenNavigation: () => void }) {
  const { admin, logout } = useAuth()
  const { navigate } = useRouter()
  const [loggingOut, setLoggingOut] = useState(false)

  async function signOut() {
    setLoggingOut(true)
    try {
      await logout()
      navigate('/login', { replace: true })
    } finally {
      setLoggingOut(false)
    }
  }

  return (
    <header className="sticky top-0 z-20 flex h-18 items-center justify-between border-b border-stone-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
      <div className="flex items-center gap-3">
        <button type="button" className="rounded-lg border border-stone-200 p-2 text-stone-700 hover:bg-stone-50 lg:hidden" aria-label="Open navigation" onClick={onOpenNavigation}>
          <span className="block h-0.5 w-5 bg-current" /><span className="mt-1 block h-0.5 w-5 bg-current" /><span className="mt-1 block h-0.5 w-5 bg-current" />
        </button>
        <p className="text-sm font-semibold text-stone-900 sm:text-base">{title}</p>
      </div>
      <div className="flex items-center gap-3 sm:gap-5">
        <div className="hidden text-right sm:block">
          <p className="text-sm font-medium text-stone-900">{admin?.name}</p>
          <p className="text-xs text-stone-500">{admin?.email}</p>
        </div>
        <button type="button" disabled={loggingOut} onClick={signOut} className="rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50 disabled:opacity-60">
          {loggingOut ? 'Signing out…' : 'Sign out'}
        </button>
      </div>
    </header>
  )
}

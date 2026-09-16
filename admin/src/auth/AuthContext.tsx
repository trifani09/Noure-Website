import { useEffect, useMemo, useState, type ReactNode } from 'react'
import { ApiError } from '../api/client'
import * as authService from './authService'
import type { Admin } from './authService'
import { AuthContext, type AuthContextValue } from './authContextValue'

export function AuthProvider({ children }: { children: ReactNode }) {
  const [admin, setAdmin] = useState<Admin | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    let active = true
    authService.currentAdmin()
      .then((value) => { if (active) setAdmin(value) })
      .catch((error: unknown) => {
        if (!(error instanceof ApiError) || error.status !== 401) console.error('Unable to restore the admin session.')
      })
      .finally(() => { if (active) setLoading(false) })

    return () => { active = false }
  }, [])

  const value = useMemo<AuthContextValue>(() => ({
    admin,
    loading,
    login: async (email, password) => setAdmin(await authService.login(email, password)),
    logout: async () => { await authService.logout(); setAdmin(null) },
  }), [admin, loading])

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

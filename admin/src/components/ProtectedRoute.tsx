import type { ReactNode } from 'react'
import { useAuth } from '../auth/useAuth'
import { Navigate } from '../routing/Navigate'
import { LoadingScreen } from './LoadingScreen'

export function ProtectedRoute({ children }: { children: ReactNode }) {
  const { admin, loading } = useAuth()
  if (loading) return <LoadingScreen />
  if (!admin) return <Navigate to="/login" replace />
  return children
}

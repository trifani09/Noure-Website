import { createContext } from 'react'
import type { Admin } from './authService'

export type AuthContextValue = {
  admin: Admin | null
  loading: boolean
  login: (email: string, password: string) => Promise<void>
  logout: () => Promise<void>
}

export const AuthContext = createContext<AuthContextValue | null>(null)

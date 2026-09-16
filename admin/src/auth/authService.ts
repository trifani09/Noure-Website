import { apiRequest, initializeCsrf } from '../api/client'

export type Admin = { name: string; email: string }
type AdminEnvelope = { data: Admin; meta: Record<string, never>; message: null }

export async function login(email: string, password: string): Promise<Admin> {
  await initializeCsrf()
  return (await apiRequest<AdminEnvelope>('/api/v1/admin/auth/login', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  })).data
}

export async function currentAdmin(): Promise<Admin> {
  return (await apiRequest<AdminEnvelope>('/api/v1/admin/auth/me')).data
}

export async function logout(): Promise<void> {
  await apiRequest('/api/v1/admin/auth/logout', { method: 'POST' })
}

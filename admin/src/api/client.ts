const API_URL = (import.meta.env.VITE_API_URL ?? 'http://localhost:8000').replace(/\/$/, '')

export type ApiErrorItem = { code?: string; field?: string; message?: string }
type ErrorEnvelope = { message?: string | null; meta?: { errors?: ApiErrorItem[] } }

export class ApiError extends Error {
  readonly status: number
  readonly code: string | null
  readonly errors: ApiErrorItem[]

  constructor(status: number, code: string | null, message: string, errors: ApiErrorItem[] = []) {
    super(message)
    this.status = status
    this.code = code
    this.errors = errors
  }
}

function csrfToken(): string | null {
  const cookie = document.cookie.split('; ').find((entry) => entry.startsWith('XSRF-TOKEN='))
  return cookie ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)) : null
}

export async function initializeCsrf(): Promise<void> {
  const response = await fetch(`${API_URL}/sanctum/csrf-cookie`, {
    credentials: 'include',
    headers: { Accept: 'application/json' },
  })
  if (!response.ok) throw new ApiError(response.status, null, 'Unable to initialize a secure session.')
}

export async function apiRequest<T>(path: string, init: RequestInit = {}): Promise<T> {
  const headers = new Headers(init.headers)
  headers.set('Accept', 'application/json')
  if (init.body !== undefined) headers.set('Content-Type', 'application/json')

  const token = csrfToken()
  if (token && init.method && init.method.toUpperCase() !== 'GET') headers.set('X-XSRF-TOKEN', token)

  const response = await fetch(`${API_URL}${path}`, { ...init, credentials: 'include', headers })
  const payload = (await response.json().catch(() => null)) as ErrorEnvelope | T | null

  if (!response.ok) {
    const error = payload as ErrorEnvelope | null
    throw new ApiError(
      response.status,
      error?.meta?.errors?.[0]?.code ?? null,
      error?.meta?.errors?.[0]?.message ?? error?.message ?? 'The request could not be completed.',
      error?.meta?.errors ?? [],
    )
  }

  return payload as T
}

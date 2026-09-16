import { useState, type FormEvent } from 'react'
import { ApiError } from '../api/client'
import { useAuth } from '../auth/useAuth'

export function LoginPage({ onAuthenticated }: { onAuthenticated: () => void }) {
  const { login } = useAuth()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState<string | null>(null)

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setSubmitting(true)
    setError(null)
    try {
      await login(email, password)
      onAuthenticated()
    } catch (caught: unknown) {
      setError(caught instanceof ApiError && caught.status === 401
        ? 'The email or password is incorrect.'
        : 'Unable to sign in right now. Please try again.')
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <main className="flex min-h-screen items-center justify-center bg-stone-100 px-6">
      <section className="w-full max-w-sm rounded-xl border border-stone-200 bg-white p-8 shadow-sm">
        <p className="text-sm font-medium uppercase tracking-[0.2em] text-stone-500">Noure</p>
        <h1 className="mt-2 text-2xl font-semibold text-stone-900">Admin sign in</h1>
        <p className="mt-2 text-sm text-stone-600">Use your staff account to continue.</p>
        <form className="mt-8 space-y-5" onSubmit={submit}>
          <label className="block text-sm font-medium text-stone-700">Email
            <input className="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 outline-none focus:border-stone-700" type="email" autoComplete="username" required value={email} onChange={(event) => setEmail(event.target.value)} />
          </label>
          <label className="block text-sm font-medium text-stone-700">Password
            <input className="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 outline-none focus:border-stone-700" type="password" autoComplete="current-password" required value={password} onChange={(event) => setPassword(event.target.value)} />
          </label>
          {error && <p role="alert" className="text-sm text-red-700">{error}</p>}
          <button className="w-full rounded-lg bg-stone-900 px-4 py-2.5 font-medium text-white disabled:cursor-not-allowed disabled:opacity-60" type="submit" disabled={submitting}>
            {submitting ? 'Signing in…' : 'Sign in'}
          </button>
        </form>
      </section>
    </main>
  )
}

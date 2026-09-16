import { useEffect } from 'react'
import { useAuth } from './auth/useAuth'
import { HomePage } from './pages/HomePage'
import { LoginPage } from './pages/LoginPage'

function App() {
  const { admin, loading } = useAuth()
  const path = window.location.pathname

  function navigate(nextPath: string) {
    window.history.replaceState(null, '', nextPath)
  }

  useEffect(() => {
    if (loading) return
    if (!admin && path !== '/login') navigate('/login')
    if (admin && path === '/login') navigate('/')
  }, [admin, loading, path])

  if (loading) {
    return <main className="flex min-h-screen items-center justify-center bg-stone-100 text-stone-600">Loading…</main>
  }

  if (!admin) return <LoginPage onAuthenticated={() => navigate('/')} />
  return <HomePage onLoggedOut={() => navigate('/login')} />
}

export default App

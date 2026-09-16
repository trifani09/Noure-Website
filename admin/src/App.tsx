import { useAuth } from './auth/useAuth'
import { AdminLayout } from './components/AdminLayout'
import { LoadingScreen } from './components/LoadingScreen'
import { ProtectedRoute } from './components/ProtectedRoute'
import { findAdminRoute } from './config/navigation'
import { DashboardPage } from './pages/DashboardPage'
import { LoginPage } from './pages/LoginPage'
import { NotFoundPage } from './pages/NotFoundPage'
import { PlaceholderPage } from './pages/PlaceholderPage'
import { Navigate } from './routing/Navigate'
import { useRouter } from './routing/useRouter'

function App() {
  const { admin, loading } = useAuth()
  const { pathname } = useRouter()

  if (loading) return <LoadingScreen />
  if (pathname === '/login') return admin ? <Navigate to="/" replace /> : <LoginPage />

  const route = findAdminRoute(pathname)

  return (
    <ProtectedRoute>
      <AdminLayout route={route ?? { path: pathname, label: 'Not found', shortLabel: '', description: '', implemented: false }}>
        {!route ? <NotFoundPage /> : route.implemented ? <DashboardPage /> : <PlaceholderPage route={route} />}
      </AdminLayout>
    </ProtectedRoute>
  )
}

export default App

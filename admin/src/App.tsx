import { useAuth } from './auth/useAuth'
import { AdminLayout } from './components/AdminLayout'
import { LoadingScreen } from './components/LoadingScreen'
import { ProtectedRoute } from './components/ProtectedRoute'
import { findAdminRoute } from './config/navigation'
import { DashboardPage } from './pages/DashboardPage'
import { CategoriesPage } from './pages/CategoriesPage'
import { LoginPage } from './pages/LoginPage'
import { NotFoundPage } from './pages/NotFoundPage'
import { PlaceholderPage } from './pages/PlaceholderPage'
import { ProductsPage } from './pages/ProductsPage'
import { ProductCreatePage } from './pages/ProductCreatePage'
import { ProductDetailPage } from './pages/ProductDetailPage'
import { ProductEditPage } from './pages/ProductEditPage'
import { Navigate } from './routing/Navigate'
import { useRouter } from './routing/useRouter'

function App() {
  const { admin, loading } = useAuth()
  const { pathname } = useRouter()

  if (loading) return <LoadingScreen />
  if (pathname === '/login') return admin ? <Navigate to="/" replace /> : <LoginPage />

  const route = findAdminRoute(pathname)

  const publicId = pathname.split('/')[2] ?? ''
  const content = !route ? <NotFoundPage /> : route.page === 'dashboard' ? <DashboardPage /> : route.page === 'categories' ? <CategoriesPage /> : route.page === 'products' ? <ProductsPage /> : route.page === 'product-create' ? <ProductCreatePage /> : route.page === 'product-detail' ? <ProductDetailPage publicId={publicId} /> : route.page === 'product-edit' ? <ProductEditPage publicId={publicId} /> : <PlaceholderPage route={route} />

  return (
    <ProtectedRoute>
      <AdminLayout route={route ?? { path: pathname, label: 'Not found', shortLabel: '', description: '', implemented: false, page: 'placeholder' }}>
        {content}
      </AdminLayout>
    </ProtectedRoute>
  )
}

export default App

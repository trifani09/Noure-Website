export type AdminRoute = {
  path: string
  label: string
  shortLabel: string
  description: string
  implemented: boolean
  page: 'dashboard' | 'categories' | 'placeholder'
}

export const adminRoutes: AdminRoute[] = [
  { path: '/', label: 'Dashboard', shortLabel: 'DB', description: 'Your Noure administration workspace.', implemented: true, page: 'dashboard' },
  { path: '/products', label: 'Products', shortLabel: 'PR', description: 'Product management will be available in a future phase.', implemented: false, page: 'placeholder' },
  { path: '/categories', label: 'Categories', shortLabel: 'CA', description: 'Organize the catalog hierarchy and storefront navigation.', implemented: true, page: 'categories' },
  { path: '/inventory', label: 'Inventory', shortLabel: 'IN', description: 'Inventory management will be available in a future phase.', implemented: false, page: 'placeholder' },
  { path: '/orders', label: 'Orders', shortLabel: 'OR', description: 'Order management will be available in a future phase.', implemented: false, page: 'placeholder' },
  { path: '/customers', label: 'Customers', shortLabel: 'CU', description: 'Customer management will be available in a future phase.', implemented: false, page: 'placeholder' },
  { path: '/discounts', label: 'Discounts', shortLabel: 'DI', description: 'Discount management will be available in a future phase.', implemented: false, page: 'placeholder' },
  { path: '/content', label: 'Content', shortLabel: 'CO', description: 'Content management will be available in a future phase.', implemented: false, page: 'placeholder' },
  { path: '/settings', label: 'Settings', shortLabel: 'SE', description: 'Settings will be available in a future phase.', implemented: false, page: 'placeholder' },
]

export function findAdminRoute(pathname: string): AdminRoute | undefined {
  return adminRoutes.find((route) => route.path === pathname)
}

export type AdminRoute = {
  path: string
  label: string
  shortLabel: string
  description: string
  implemented: boolean
}

export const adminRoutes: AdminRoute[] = [
  { path: '/', label: 'Dashboard', shortLabel: 'DB', description: 'Your Noure administration workspace.', implemented: true },
  { path: '/products', label: 'Products', shortLabel: 'PR', description: 'Product management will be available in a future phase.', implemented: false },
  { path: '/categories', label: 'Categories', shortLabel: 'CA', description: 'Category management will be available in a future phase.', implemented: false },
  { path: '/inventory', label: 'Inventory', shortLabel: 'IN', description: 'Inventory management will be available in a future phase.', implemented: false },
  { path: '/orders', label: 'Orders', shortLabel: 'OR', description: 'Order management will be available in a future phase.', implemented: false },
  { path: '/customers', label: 'Customers', shortLabel: 'CU', description: 'Customer management will be available in a future phase.', implemented: false },
  { path: '/discounts', label: 'Discounts', shortLabel: 'DI', description: 'Discount management will be available in a future phase.', implemented: false },
  { path: '/content', label: 'Content', shortLabel: 'CO', description: 'Content management will be available in a future phase.', implemented: false },
  { path: '/settings', label: 'Settings', shortLabel: 'SE', description: 'Settings will be available in a future phase.', implemented: false },
]

export function findAdminRoute(pathname: string): AdminRoute | undefined {
  return adminRoutes.find((route) => route.path === pathname)
}

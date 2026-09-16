import { useState, type ReactNode } from 'react'
import type { AdminRoute } from '../config/navigation'
import { Header } from './Header'
import { Sidebar } from './Sidebar'

export function AdminLayout({ route, children }: { route: AdminRoute; children: ReactNode }) {
  const [navigationOpen, setNavigationOpen] = useState(false)

  return (
    <div className="min-h-screen bg-stone-100">
      <Sidebar open={navigationOpen} onClose={() => setNavigationOpen(false)} />
      <div className="lg:pl-64">
        <Header title={route.label} onOpenNavigation={() => setNavigationOpen(true)} />
        <main className="p-4 sm:p-6 lg:p-8">{children}</main>
      </div>
    </div>
  )
}

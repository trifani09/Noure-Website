import type { AdminRoute } from '../config/navigation'
import { PageHeader } from '../components/PageHeader'

export function PlaceholderPage({ route }: { route: AdminRoute }) {
  return (
    <section className="rounded-xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
      <PageHeader title={route.label} description={route.description} />
      <div className="mt-8 rounded-lg border border-dashed border-stone-300 bg-stone-50 px-5 py-8 text-center text-sm text-stone-600">This module is not implemented yet.</div>
    </section>
  )
}

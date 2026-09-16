import { useAuth } from '../auth/useAuth'
import { PageHeader } from '../components/PageHeader'

export function DashboardPage() {
  const { admin } = useAuth()

  return (
    <section className="rounded-xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
      <PageHeader title="Dashboard" description="Your Noure administration workspace." />
      <div className="mt-8">
        <p className="text-lg font-medium text-stone-900">Welcome, {admin?.name}.</p>
        <p className="mt-2 text-sm leading-6 text-stone-600">Use the navigation to access Noure administration modules as they become available.</p>
      </div>
    </section>
  )
}

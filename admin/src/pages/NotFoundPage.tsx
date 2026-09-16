import { PageHeader } from '../components/PageHeader'
import { useRouter } from '../routing/useRouter'

export function NotFoundPage() {
  const { navigate } = useRouter()
  return (
    <section className="rounded-xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
      <PageHeader title="Page not found" description="The requested admin page does not exist." />
      <button type="button" onClick={() => navigate('/')} className="mt-8 rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white">Return to dashboard</button>
    </section>
  )
}

import type { OrderStatus } from './types'
const colors: Record<string, string> = { pending: 'bg-amber-100 text-amber-800', processing: 'bg-blue-100 text-blue-800', shipped: 'bg-violet-100 text-violet-800', completed: 'bg-emerald-100 text-emerald-800', cancelled: 'bg-red-100 text-red-800', unpaid: 'bg-stone-200 text-stone-700', paid: 'bg-emerald-100 text-emerald-800' }
export function OrderStatusBadge({ status }: { status: OrderStatus | string }) {
  return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize ${colors[status] ?? 'bg-stone-100 text-stone-700'}`}>{status}</span>
}

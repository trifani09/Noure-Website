import { useCallback, useEffect, useState, type FormEvent } from 'react'
import { ApiError } from '../api/client'
import { PageHeader } from '../components/PageHeader'
import { OrderFilter } from '../orders/OrderFilter'
import { OrderTable } from '../orders/OrderTable'
import { Pagination } from '../orders/Pagination'
import { listOrders } from '../orders/orderService'
import type { OrderSummary, PaginationData } from '../orders/types'
const initial: PaginationData = { total: 0, per_page: 20, current_page: 1, last_page: 1 }
export function OrdersPage() {
  const [orders, setOrders] = useState<OrderSummary[]>([]); const [pagination, setPagination] = useState(initial); const [loading, setLoading] = useState(true); const [error, setError] = useState<string | null>(null)
  const [searchInput, setSearchInput] = useState(''); const [search, setSearch] = useState(''); const [status, setStatus] = useState(''); const [paymentStatus, setPaymentStatus] = useState(''); const [dateFrom, setDateFrom] = useState(''); const [dateTo, setDateTo] = useState(''); const [sort, setSort] = useState('newest'); const [page, setPage] = useState(1)
  const load = useCallback(async () => { setLoading(true); setError(null); try { const response = await listOrders({ page, per_page: 20, search, status, payment_status: paymentStatus, date_from: dateFrom, date_to: dateTo, sort }); setOrders(response.data); setPagination(response.meta.pagination) } catch (caught) { setError(caught instanceof ApiError ? caught.message : 'Orders could not be loaded.') } finally { setLoading(false) } }, [dateFrom, dateTo, page, paymentStatus, search, sort, status])
  // oxlint-disable-next-line react/set-state-in-effect
  useEffect(() => { void load() }, [load])
  function submit(event: FormEvent) { event.preventDefault(); setPage(1); setSearch(searchInput.trim()) }
  function filter(field: string, value: string) { setPage(1); if (field === 'status') setStatus(value); if (field === 'paymentStatus') setPaymentStatus(value); if (field === 'dateFrom') setDateFrom(value); if (field === 'dateTo') setDateTo(value); if (field === 'sort') setSort(value) }
  return <div className="space-y-6"><PageHeader title="Orders" description="Review orders and manage their operational status." /><section className="rounded-xl border border-stone-200 bg-white shadow-sm"><OrderFilter searchInput={searchInput} status={status} paymentStatus={paymentStatus} dateFrom={dateFrom} dateTo={dateTo} sort={sort} onSearchInput={setSearchInput} onSearch={submit} onFilter={filter} />{error ? <div className="p-12 text-center text-sm text-red-700">{error} <button className="underline" onClick={() => void load()}>Try again</button></div> : loading ? <div className="p-12 text-center text-sm text-stone-500">Loading orders…</div> : orders.length === 0 ? <div className="p-12 text-center"><p className="font-medium">No orders found</p><p className="mt-1 text-sm text-stone-500">Adjust the filters to broaden your results.</p></div> : <OrderTable orders={orders} />}<Pagination pagination={pagination} onPage={setPage} /></section></div>
}

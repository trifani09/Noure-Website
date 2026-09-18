import type { PaginationData } from './types'
export function Pagination({ pagination, onPage }: { pagination: PaginationData; onPage: (page: number) => void }) {
  return <div className="flex justify-between border-t p-3 text-sm"><span>{pagination.total} orders · Page {pagination.current_page} of {pagination.last_page}</span><div className="flex gap-2"><button disabled={pagination.current_page <= 1} onClick={() => onPage(pagination.current_page - 1)} className="rounded border px-3 py-1 disabled:opacity-40">Previous</button><button disabled={pagination.current_page >= pagination.last_page} onClick={() => onPage(pagination.current_page + 1)} className="rounded border px-3 py-1 disabled:opacity-40">Next</button></div></div>
}

import { adminRoutes } from '../config/navigation'
import { useRouter } from '../routing/useRouter'

type SidebarProps = { open: boolean; onClose: () => void }

export function Sidebar({ open, onClose }: SidebarProps) {
  const { pathname, navigate } = useRouter()

  function select(path: string) {
    navigate(path)
    onClose()
  }

  return (
    <>
      {open && <button type="button" aria-label="Close navigation" className="fixed inset-0 z-30 bg-stone-950/35 lg:hidden" onClick={onClose} />}
      <aside className={`fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-stone-800 bg-stone-950 text-white transition-transform duration-200 lg:translate-x-0 ${open ? 'translate-x-0' : '-translate-x-full'}`}>
        <div className="flex h-18 items-center justify-between border-b border-stone-800 px-6">
          <div>
            <p className="text-lg font-semibold tracking-[0.18em]">NOURE</p>
            <p className="text-xs text-stone-400">Administration</p>
          </div>
          <button type="button" className="rounded p-2 text-stone-400 hover:bg-stone-800 hover:text-white lg:hidden" aria-label="Close navigation" onClick={onClose}>×</button>
        </div>
        <nav className="flex-1 overflow-y-auto px-3 py-5" aria-label="Admin navigation">
          <ul className="space-y-1">
            {adminRoutes.map((item) => {
              const active = pathname === item.path
              return (
                <li key={item.path}>
                  <a href={item.path} onClick={(event) => { event.preventDefault(); select(item.path) }} aria-current={active ? 'page' : undefined} className={`flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition ${active ? 'bg-white text-stone-950' : 'text-stone-300 hover:bg-stone-900 hover:text-white'}`}>
                    <span className={`flex h-7 w-7 items-center justify-center rounded-md text-[10px] font-bold tracking-wide ${active ? 'bg-stone-200' : 'bg-stone-800'}`}>{item.shortLabel}</span>
                    {item.label}
                  </a>
                </li>
              )
            })}
          </ul>
        </nav>
        <div className="border-t border-stone-800 px-6 py-4 text-xs text-stone-500">Noure Admin</div>
      </aside>
    </>
  )
}

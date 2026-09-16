import { useCallback, useEffect, useMemo, useState, type ReactNode } from 'react'
import { RouterContext, type NavigateOptions, type RouterContextValue } from './routerContextValue'

export function RouterProvider({ children }: { children: ReactNode }) {
  const [pathname, setPathname] = useState(window.location.pathname)

  useEffect(() => {
    const updatePath = () => setPathname(window.location.pathname)
    window.addEventListener('popstate', updatePath)
    return () => window.removeEventListener('popstate', updatePath)
  }, [])

  const navigate = useCallback((path: string, options: NavigateOptions = {}) => {
    if (window.location.pathname === path) return
    window.history[options.replace ? 'replaceState' : 'pushState'](null, '', path)
    setPathname(path)
    window.scrollTo({ top: 0 })
  }, [])

  const value = useMemo<RouterContextValue>(() => ({ pathname, navigate }), [navigate, pathname])

  return <RouterContext.Provider value={value}>{children}</RouterContext.Provider>
}

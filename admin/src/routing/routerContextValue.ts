import { createContext } from 'react'

export type NavigateOptions = { replace?: boolean }

export type RouterContextValue = {
  pathname: string
  navigate: (path: string, options?: NavigateOptions) => void
}

export const RouterContext = createContext<RouterContextValue | null>(null)

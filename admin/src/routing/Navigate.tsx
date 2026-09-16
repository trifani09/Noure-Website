import { useEffect } from 'react'
import { useRouter } from './useRouter'

export function Navigate({ to, replace = false }: { to: string; replace?: boolean }) {
  const { navigate } = useRouter()
  useEffect(() => navigate(to, { replace }), [navigate, replace, to])
  return null
}

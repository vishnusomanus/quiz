import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { useEffect, useMemo, useState } from 'react'
import { RouterProvider } from 'react-router-dom'
import { router } from '@/routes'
import { useAuthStore } from '@/store/authStore'

export default function App() {
  const queryClient = useMemo(
    () =>
      new QueryClient({
        defaultOptions: {
          queries: {
            staleTime: 10_000,
            retry: 0,
            refetchOnWindowFocus: false,
          },
        },
      }),
    [],
  )
  const [hydrated, setHydrated] = useState(() => useAuthStore.persist.hasHydrated())

  useEffect(() => {
    const unsub = useAuthStore.persist.onFinishHydration(() => setHydrated(true))
    setHydrated(useAuthStore.persist.hasHydrated())
    return unsub
  }, [])

  if (!hydrated) {
    return (
      <div className="flex min-h-dvh items-center justify-center bg-surface">
        <div className="erp-skeleton h-10 w-40 rounded-lg" />
      </div>
    )
  }

  return (
    <QueryClientProvider client={queryClient}>
      <RouterProvider router={router} />
    </QueryClientProvider>
  )
}

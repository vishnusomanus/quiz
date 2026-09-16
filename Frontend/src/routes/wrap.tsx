import { Suspense, type ReactNode } from 'react'

export function wrap(node: ReactNode) {
  return (
    <Suspense
      fallback={
        <div className="space-y-3 px-6 pt-6">
          <div className="erp-skeleton h-10 w-full rounded-lg" />
          <div className="erp-skeleton h-72 w-full rounded-xl" />
        </div>
      }
    >
      {node}
    </Suspense>
  )
}

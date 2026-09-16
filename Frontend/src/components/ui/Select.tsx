import { forwardRef, type SelectHTMLAttributes } from 'react'
import { cn } from '@/utils/cn'

export type SelectProps = SelectHTMLAttributes<HTMLSelectElement>

export const Select = forwardRef<HTMLSelectElement, SelectProps>(function Select(
  { className, children, ...props },
  ref,
) {
  return (
    <select
      ref={ref}
      className={cn(
        'h-10 w-full rounded-lg border border-outline-variant/50 bg-surface-container-lowest px-3 text-sm text-on-surface focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 disabled:cursor-not-allowed disabled:bg-surface-container-low',
        className,
      )}
      {...props}
    >
      {children}
    </select>
  )
})

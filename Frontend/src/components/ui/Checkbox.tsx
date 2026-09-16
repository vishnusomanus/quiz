import { forwardRef, type InputHTMLAttributes } from 'react'
import { cn } from '@/utils/cn'

export type CheckboxProps = Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> & {
  indeterminate?: boolean
}

export const Checkbox = forwardRef<HTMLInputElement, CheckboxProps>(function Checkbox(
  { className, indeterminate, ...props },
  ref,
) {
  return (
    <input
      ref={(node) => {
        if (node) node.indeterminate = Boolean(indeterminate)
        if (typeof ref === 'function') ref(node)
        else if (ref) ref.current = node
      }}
      type="checkbox"
      className={cn(
        'h-4 w-4 rounded accent-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
        className,
      )}
      {...props}
    />
  )
})

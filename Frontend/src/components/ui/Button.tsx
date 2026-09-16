import { forwardRef, type ButtonHTMLAttributes } from 'react'
import { Link, type LinkProps } from 'react-router-dom'
import { cn } from '@/utils/cn'

type Variant = 'primary' | 'secondary' | 'ghost' | 'danger' | 'soft'
type Size = 'sm' | 'md' | 'lg'

export type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
  variant?: Variant
  size?: Size
}

const variants: Record<Variant, string> = {
  primary: 'erp-btn-gradient text-on-primary disabled:bg-transparent',
  secondary: 'erp-btn-gradient-soft text-on-surface disabled:opacity-50',
  ghost: 'bg-transparent text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface disabled:opacity-50',
  danger: 'bg-error text-on-error hover:bg-red-800 disabled:bg-error/40',
  soft: 'bg-secondary-container text-on-surface hover:bg-surface-container-high disabled:opacity-50',
}

const sizes: Record<Size, string> = {
  sm: 'h-8 px-2.5 text-xs',
  md: 'h-8 px-3 text-xs',
  lg: 'h-11 px-4 text-sm',
}

function buttonClassName(variant: Variant, size: Size, className?: string) {
  return cn(
    'inline-flex items-center justify-center gap-1.5 rounded-lg font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-400/50 disabled:cursor-not-allowed',
    variants[variant],
    sizes[size],
    className,
  )
}

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(function Button(
  { className, variant = 'secondary', size = 'md', type = 'button', ...props },
  ref,
) {
  return <button ref={ref} type={type} className={buttonClassName(variant, size, className)} {...props} />
})

export function ButtonLink({
  className,
  variant = 'secondary',
  size = 'md',
  ...props
}: LinkProps & { variant?: Variant; size?: Size; className?: string }) {
  return <Link className={buttonClassName(variant, size, className)} {...props} />
}

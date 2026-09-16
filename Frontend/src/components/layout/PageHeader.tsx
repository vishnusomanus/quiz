import type { ReactNode } from 'react'
import { MsIcon } from '@/components/ui/MsIcon'

type PageHeaderProps = {
  title: string
  subtitle: string
  crumb?: string
  countLabel?: string
  actions?: ReactNode
}

export function PageHeader({ title, subtitle, crumb, countLabel, actions }: PageHeaderProps) {
  return (
    <section className="flex flex-col gap-1 px-4 pb-2 pt-4 sm:px-6">
      <nav className="flex items-center gap-1.5 text-[11px] text-on-surface-variant" aria-label="Breadcrumb">
        <span>Home</span>
        <MsIcon name="chevron_right" className="text-[14px]" />
        <span className="font-semibold text-on-surface">{crumb ?? title}</span>
      </nav>
      <div className="mt-1 flex flex-wrap items-center justify-between gap-3">
        <div>
          <div className="flex items-center gap-2">
            <h1 className="text-2xl font-semibold tracking-tight text-on-surface">{title}</h1>
            {countLabel ? (
              <span className="rounded-full bg-surface-container-high px-2 py-0.5 font-mono text-[11px] text-on-surface-variant">
                {countLabel}
              </span>
            ) : null}
          </div>
          <p className="mt-0.5 text-xs text-on-surface-variant">{subtitle}</p>
        </div>
        {actions ? <div className="flex flex-wrap items-center gap-2">{actions}</div> : null}
      </div>
    </section>
  )
}

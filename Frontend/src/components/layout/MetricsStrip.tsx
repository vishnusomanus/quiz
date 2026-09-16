import { MsIcon } from '@/components/ui/MsIcon'
import { cn } from '@/utils/cn'

export type MetricItem = {
  label: string
  value: string
  hint?: string
  icon: string
  tone?: 'default' | 'success' | 'danger' | 'muted'
}

const iconWrap: Record<NonNullable<MetricItem['tone']>, string> = {
  default: 'bg-surface-container-low text-primary',
  success: 'bg-tertiary-fixed/40 text-tertiary',
  danger: 'bg-error-container text-on-error-container',
  muted: 'bg-surface-container text-on-surface-variant',
}

const valueTone: Record<NonNullable<MetricItem['tone']>, string> = {
  default: 'text-on-surface',
  success: 'text-on-surface',
  danger: 'text-error',
  muted: 'text-on-surface-variant',
}

export function MetricsStrip({ items }: { items: MetricItem[] }) {
  return (
    <section className="px-4 pb-2 sm:px-6">
      <div className="grid grid-cols-2 gap-2 md:grid-cols-4">
        {items.map((item) => {
          const tone = item.tone ?? 'default'
          return (
            <div
              key={item.label}
              className="flex items-center justify-between rounded-xl bg-surface-container-lowest p-3 shadow-sm"
            >
              <div className="flex flex-col">
                <span className="text-[11px] font-semibold uppercase tracking-wide text-on-surface-variant">
                  {item.label}
                </span>
                <span className={cn('mt-0.5 font-mono text-sm font-bold', valueTone[tone])}>
                  {item.value}
                  {item.hint ? <span className="ml-1 text-[11px] font-medium text-tertiary">{item.hint}</span> : null}
                </span>
              </div>
              <div className={cn('flex h-8 w-8 items-center justify-center rounded-lg', iconWrap[tone])}>
                <MsIcon name={item.icon} className="text-[18px]" />
              </div>
            </div>
          )
        })}
      </div>
    </section>
  )
}

import { MsIcon } from '@/components/ui/MsIcon'
import { useToastStore } from '@/store/toastStore'
import { cn } from '@/utils/cn'

const toneIcon = {
  success: { name: 'check_circle', wrap: 'bg-tertiary-fixed text-tertiary', bar: 'bg-tertiary' },
  warning: { name: 'warning', wrap: 'bg-amber-100 text-amber-800', bar: 'bg-amber-500' },
  danger: { name: 'error', wrap: 'bg-error-container text-on-error-container', bar: 'bg-error' },
  info: { name: 'info', wrap: 'bg-secondary-container text-primary', bar: 'bg-primary' },
} as const

export function ToastViewport() {
  const toasts = useToastStore((state) => state.toasts)
  const dismiss = useToastStore((state) => state.dismiss)

  return (
    <div className="pointer-events-none fixed right-4 top-14 z-60 flex w-full max-w-sm flex-col gap-2 px-2 sm:right-6 sm:px-0">
      {toasts.map((toast) => {
        const tone = toneIcon[toast.tone]
        return (
          <div
            key={toast.id}
            role="status"
            className="pointer-events-auto relative overflow-hidden rounded-xl bg-surface-container-lowest p-3 shadow-xl"
          >
            <div className="flex items-start gap-2">
              <div className={cn('mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full', tone.wrap)}>
                <MsIcon name={tone.name} filled className="text-[20px]" />
              </div>
              <div className="min-w-0 flex-1">
                <div className="flex items-start justify-between gap-2">
                  <p className="text-sm font-semibold leading-snug text-on-surface">{toast.title}</p>
                  <button
                    type="button"
                    aria-label="Dismiss notification"
                    onClick={() => dismiss(toast.id)}
                    className="rounded-lg p-0.5 text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface"
                  >
                    <MsIcon name="close" className="text-[16px]" />
                  </button>
                </div>
                {toast.description ? (
                  <p className="mt-0.5 text-[11px] leading-relaxed text-on-surface-variant">{toast.description}</p>
                ) : null}
              </div>
            </div>
            <div className="absolute bottom-0 left-0 right-0 h-1 overflow-hidden bg-surface-container-high">
              <div className={cn('h-full w-full origin-left animate-[erp-toast_5.2s_linear_forwards]', tone.bar)} />
            </div>
          </div>
        )
      })}
    </div>
  )
}

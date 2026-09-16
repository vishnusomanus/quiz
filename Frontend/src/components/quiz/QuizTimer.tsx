import { formatDuration } from '@/utils/format'
import { cn } from '@/utils/cn'

type QuizTimerProps = {
  label: string
  seconds: number | null
  compact?: boolean
}

export function QuizTimer({ label, seconds, compact }: QuizTimerProps) {
  const urgent = seconds != null && seconds <= 10
  const display = seconds == null ? '∞' : formatDuration(seconds)

  return (
    <div
      className={cn(
        'rounded-xl px-3 py-2',
        urgent ? 'quiz-timer-urgent bg-error-container text-on-error-container' : 'bg-surface-container-low text-on-surface',
        compact && 'px-2 py-1.5',
      )}
    >
      <p className="text-[10px] font-semibold uppercase tracking-wide opacity-70">{label}</p>
      <p className={cn('font-mono font-bold', compact ? 'text-sm' : 'text-lg')}>{display}</p>
    </div>
  )
}

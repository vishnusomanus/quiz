type ProgressBarProps = {
  current: number
  total: number
}

export function ProgressBar({ current, total }: ProgressBarProps) {
  const safeTotal = Math.max(total, 1)
  const percent = Math.min(100, Math.round((current / safeTotal) * 100))

  return (
    <div>
      <div className="mb-1 flex items-center justify-between text-[11px] text-on-surface-variant">
        <span className="font-semibold text-on-surface">
          Question {Math.min(current, total)} of {total}
        </span>
        <span className="font-mono">{percent}%</span>
      </div>
      <div className="h-2 overflow-hidden rounded-full bg-surface-container-high">
        <div
          className="h-full rounded-full bg-primary transition-[width] duration-300"
          style={{ width: `${percent}%` }}
        />
      </div>
    </div>
  )
}

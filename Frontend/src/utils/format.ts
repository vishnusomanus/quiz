export function formatNumber(value?: number | null) {
  if (value == null || Number.isNaN(value)) return '—'
  return new Intl.NumberFormat('en-IN').format(value)
}

export function formatDateTime(value?: string | null) {
  if (!value) return '—'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '—'
  return new Intl.DateTimeFormat('en-IN', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

export function formatDuration(totalSeconds?: number | null) {
  if (totalSeconds == null || Number.isNaN(totalSeconds)) return '—'
  const seconds = Math.max(0, Math.floor(totalSeconds))
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const rest = seconds % 60
  if (hours > 0) {
    return `${hours}:${String(minutes).padStart(2, '0')}:${String(rest).padStart(2, '0')}`
  }
  return `${String(minutes).padStart(2, '0')}:${String(rest).padStart(2, '0')}`
}

export function formatPercent(value: number, digits = 0) {
  return `${value.toFixed(digits)}%`
}

export function initialsFromName(name: string) {
  return name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() ?? '')
    .join('')
}

export function countryLabel(code: string) {
  const labels: Record<string, string> = {
    IN: 'India',
    US: 'United States',
    GB: 'United Kingdom',
    AE: 'UAE',
    SG: 'Singapore',
    AU: 'Australia',
    CA: 'Canada',
    DE: 'Germany',
    FR: 'France',
    JP: 'Japan',
  }
  return labels[code] ?? code
}

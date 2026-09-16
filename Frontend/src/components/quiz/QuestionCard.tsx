import type { QuizQuestion } from '@/types/api'
import { cn } from '@/utils/cn'

type QuestionCardProps = {
  question: QuizQuestion
  selectedIds: string[]
  disabled?: boolean
  onToggle: (optionId: string) => void
}

export function QuestionCard({ question, selectedIds, disabled, onToggle }: QuestionCardProps) {
  const multiple = question.type === 'multiple_choice'
  const hasImages = question.options.some((option) => Boolean(option.image_url))

  return (
    <article className="quiz-enter rounded-2xl bg-surface-container-lowest p-5 shadow-sm sm:p-6">
      <div className="mb-4 flex flex-wrap items-center gap-2">
        <span className="rounded-full bg-primary-fixed px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-on-primary-fixed">
          {multiple ? 'Multiple choice' : 'Single choice'}
        </span>
        {hasImages ? (
          <span className="rounded-full bg-tertiary-fixed/60 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-tertiary">
            Image options
          </span>
        ) : null}
        <span className="rounded-full bg-surface-container-high px-2 py-0.5 font-mono text-[10px] text-on-surface-variant">
          {question.points} pt{question.points === 1 ? '' : 's'}
        </span>
      </div>
      <h2 className="text-lg font-semibold leading-7 text-on-surface sm:text-xl">{question.prompt}</h2>
      <p className="mt-1 text-xs text-on-surface-variant">
        {multiple ? 'Select all answers that apply.' : 'Select one answer.'}
      </p>
      <div className={cn('mt-5', hasImages ? 'grid gap-3 sm:grid-cols-2' : 'space-y-2')}>
        {question.options.map((option, index) => {
          const selected = selectedIds.includes(option.id)
          return (
            <button
              key={option.id}
              type="button"
              disabled={disabled}
              onClick={() => onToggle(option.id)}
              className={cn(
                'flex w-full items-start gap-3 rounded-xl border px-3 py-3 text-left transition sm:px-4',
                hasImages && 'flex-col',
                selected
                  ? 'border-primary bg-secondary-container/80 shadow-sm'
                  : 'border-outline-variant/40 bg-surface-container-low/40 hover:border-primary/40 hover:bg-surface-container-low',
                disabled && 'cursor-not-allowed opacity-70',
              )}
            >
              <span className="flex w-full items-start gap-3">
                <span
                  className={cn(
                    'mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center font-mono text-[11px] font-bold',
                    multiple ? 'rounded-md' : 'rounded-full',
                    selected ? 'bg-primary text-on-primary' : 'bg-surface-container-highest text-on-surface-variant',
                  )}
                >
                  {String.fromCharCode(65 + index)}
                </span>
                {option.label ? (
                  <span className="text-sm leading-6 text-on-surface">{option.label}</span>
                ) : (
                  <span className="text-sm leading-6 text-on-surface-variant">Option {String.fromCharCode(65 + index)}</span>
                )}
              </span>
              {option.image_url ? (
                <img
                  src={option.image_url}
                  alt={option.label || `Option ${String.fromCharCode(65 + index)}`}
                  className="max-h-32 w-full rounded-lg bg-surface-container-lowest object-contain"
                />
              ) : null}
            </button>
          )
        })}
      </div>
    </article>
  )
}

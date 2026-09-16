import { useQuery } from '@tanstack/react-query'
import { listQuizzes } from '@/api/quizzes'
import { PageHeader } from '@/components/layout/PageHeader'
import { MetricsStrip } from '@/components/layout/MetricsStrip'
import { Button, ButtonLink } from '@/components/ui/Button'
import { MsIcon } from '@/components/ui/MsIcon'
import { canAttendQuizzes } from '@/auth/permissions'
import { useAuthStore } from '@/store/authStore'
import { countryLabel, formatDuration } from '@/utils/format'
import { cn } from '@/utils/cn'

export function QuizListPage() {
  const user = useAuthStore((state) => state.user)
  const query = useQuery({
    queryKey: ['quizzes', 'attend', user?.id, user?.country],
    enabled: Boolean(user?.id) && canAttendQuizzes(user),
    queryFn: () =>
      listQuizzes({
        user_id: user!.id,
        country: user?.country,
        per_page: 50,
      }),
  })

  const quizzes = query.data?.data ?? []
  const completed = quizzes.filter((quiz) => quiz.is_completed && !quiz.in_progress_attempt_id).length
  const inProgress = quizzes.filter((quiz) => Boolean(quiz.in_progress_attempt_id)).length

  return (
    <>
      <PageHeader
        title="Quizzes"
        subtitle="Take quizzes here in the app. Start, resume, or open results and the leaderboard."
        countLabel={`${quizzes.length}`}
      />
      <MetricsStrip
        items={[
          { label: 'Available', value: String(quizzes.length), icon: 'quiz' },
          { label: 'In progress', value: String(inProgress), icon: 'play' },
          { label: 'Completed', value: String(completed), icon: 'check_circle', tone: 'success' },
          { label: 'Region', value: user?.country ? countryLabel(user.country) : 'Worldwide', icon: 'globe' },
        ]}
      />
      <section className="px-4 pb-20 sm:px-6">
        {query.isLoading ? (
          <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            {Array.from({ length: 6 }).map((_, index) => (
              <div key={index} className="erp-skeleton h-48 rounded-2xl" />
            ))}
          </div>
        ) : null}
        {query.isError ? (
          <div className="rounded-2xl bg-surface-container-lowest p-8 text-center shadow-sm">
            <MsIcon name="error" className="text-[28px] text-error" />
            <p className="mt-2 text-sm font-semibold">Could not load quizzes</p>
            <Button className="mt-4" variant="primary" onClick={() => void query.refetch()}>
              Retry
            </Button>
          </div>
        ) : null}
        {!query.isLoading && !query.isError && quizzes.length === 0 ? (
          <div className="rounded-2xl bg-surface-container-lowest p-10 text-center shadow-sm">
            <MsIcon name="search_off" className="text-[32px] text-on-surface-variant" />
            <p className="mt-2 text-sm font-semibold">No quizzes in your region yet</p>
          </div>
        ) : null}
        <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
          {quizzes.map((quiz) => {
            const status = quiz.in_progress_attempt_id ? 'in_progress' : quiz.is_completed ? 'completed' : 'open'
            const action =
              status === 'in_progress' ? 'Resume' : status === 'completed' ? 'View result' : 'Start quiz'
            return (
            <article key={quiz.id} className="flex flex-col rounded-2xl bg-surface-container-lowest p-4 shadow-sm">
              <div className="flex items-start justify-between gap-3">
                <div>
                  <h2 className="text-base font-semibold text-on-surface">{quiz.title}</h2>
                  <p className="mt-1 line-clamp-2 text-xs leading-5 text-on-surface-variant">
                    {quiz.description || 'No description'}
                  </p>
                </div>
                {status === 'completed' ? (
                  <span className="shrink-0 rounded-full bg-tertiary-fixed/50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-tertiary">
                    Completed
                  </span>
                ) : status === 'in_progress' ? (
                  <span className="shrink-0 rounded-full bg-secondary-container px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-on-secondary-container">
                    In progress
                  </span>
                ) : (
                  <span className="shrink-0 rounded-full bg-primary-fixed px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-on-primary-fixed">
                    Open
                  </span>
                )}
              </div>
              <div className="mt-4 flex flex-wrap gap-2 text-[11px] text-on-surface-variant">
                <span className="rounded-full bg-surface-container-low px-2 py-1">
                  {quiz.question_count} questions
                </span>
                <span className="rounded-full bg-surface-container-low px-2 py-1">
                  {quiz.time_limit_seconds ? formatDuration(quiz.time_limit_seconds) : 'No overall timer'}
                </span>
                <span className="rounded-full bg-surface-container-low px-2 py-1">
                  {quiz.countries.length ? quiz.countries.map(countryLabel).join(', ') : 'Worldwide'}
                </span>
              </div>
              <div className="mt-5 flex gap-2">
                <ButtonLink className="flex-1" to={`/quizzes/${quiz.id}`} variant={status === 'open' ? 'primary' : 'secondary'}>
                  <MsIcon name={status === 'completed' ? 'visibility' : 'play'} className="text-[16px]" />
                  {action}
                </ButtonLink>
                <ButtonLink to={`/quizzes/${quiz.id}/leaderboard`} aria-label="Leaderboard">
                  <MsIcon name="trophy" className="text-[16px]" />
                </ButtonLink>
              </div>
            </article>
            )
          })}
        </div>
        <p className={cn('mt-6 hidden text-center text-[11px] text-on-surface-variant', quizzes.length > 0 && 'block')}>
          Completed quizzes stay available for results and leaderboard. Each quiz can be attempted once.
        </p>
      </section>
    </>
  )
}

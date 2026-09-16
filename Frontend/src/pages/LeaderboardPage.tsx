import { useQuery } from '@tanstack/react-query'
import { useNavigate, useParams } from 'react-router-dom'
import { getLeaderboard, getQuiz, listQuizzes } from '@/api/quizzes'
import { PageHeader } from '@/components/layout/PageHeader'
import { Button, ButtonLink } from '@/components/ui/Button'
import { MsIcon } from '@/components/ui/MsIcon'
import { Select } from '@/components/ui/Select'
import { isAdmin } from '@/auth/permissions'
import { useAuthStore } from '@/store/authStore'
import { formatDateTime, formatDuration, initialsFromName } from '@/utils/format'
import { cn } from '@/utils/cn'

export function LeaderboardPage() {
  const { quizId = '' } = useParams()
  const navigate = useNavigate()
  const user = useAuthStore((state) => state.user)
  const admin = isAdmin(user)
  const boardPath = (id: string) => (admin ? `/admin/quizzes/${id}/leaderboard` : `/quizzes/${id}/leaderboard`)

  const quizzesQuery = useQuery({
    queryKey: ['quizzes', 'leaderboard-picker', user?.id, user?.role],
    enabled: Boolean(user),
    queryFn: () =>
      listQuizzes(
        admin
          ? { per_page: 100 }
          : { user_id: user!.id, country: user?.country, per_page: 100 },
      ),
  })

  const quizQuery = useQuery({
    queryKey: ['quiz', quizId],
    enabled: Boolean(quizId),
    queryFn: () => getQuiz(quizId),
  })

  const boardQuery = useQuery({
    queryKey: ['leaderboard', quizId],
    enabled: Boolean(quizId),
    queryFn: () => getLeaderboard(quizId, 50),
  })

  const quizzes = quizzesQuery.data?.data ?? []
  const rows = boardQuery.data?.data ?? []

  return (
    <>
      <PageHeader
        title="Leaderboard"
        subtitle={quizQuery.data?.data.title ?? 'Top scores for this quiz, ranked by score then completion time.'}
        crumb="Leaderboard"
        actions={
          admin ? (
            <ButtonLink to="/admin/quizzes">
              <MsIcon name="chevron_left" className="text-[16px]" />
              Back to list
            </ButtonLink>
          ) : (
            <ButtonLink to={quizId ? `/quizzes/${quizId}` : '/quizzes'} variant="primary">
              <MsIcon name="play" className="text-[16px]" />
              Open quiz
            </ButtonLink>
          )
        }
      />
      <section className="px-4 pb-20 sm:px-6">
        <div className="mb-4 max-w-sm">
          <label className="text-[11px] font-semibold uppercase tracking-wide text-on-surface-variant">
            Quiz
            <Select
              className="mt-1"
              value={quizId}
              onChange={(event) => navigate(boardPath(event.target.value))}
            >
              {quizzes.map((quiz) => (
                <option key={quiz.id} value={quiz.id}>
                  {quiz.title}
                </option>
              ))}
            </Select>
          </label>
        </div>
        <div className="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-sm">
          {boardQuery.isLoading ? (
            <div className="space-y-2 p-4">
              {Array.from({ length: 6 }).map((_, index) => (
                <div key={index} className="erp-skeleton h-10 rounded-lg" />
              ))}
            </div>
          ) : null}
          {boardQuery.isError ? (
            <div className="p-8 text-center">
              <p className="text-sm font-semibold">Could not load the leaderboard</p>
              <Button className="mt-3" variant="primary" onClick={() => void boardQuery.refetch()}>
                Retry
              </Button>
            </div>
          ) : null}
          {!boardQuery.isLoading && rows.length === 0 ? (
            <div className="p-10 text-center">
              <MsIcon name="trophy" className="text-[32px] text-on-surface-variant" />
              <p className="mt-2 text-sm font-semibold">No completed attempts yet</p>
              <p className="mt-1 text-xs text-on-surface-variant">
                {admin ? 'Learners have not completed this quiz yet.' : 'Be the first to finish this quiz.'}
              </p>
            </div>
          ) : null}
          {rows.length > 0 ? (
            <table className="erp-table w-full text-left">
              <thead>
                <tr className="bg-surface-container-low text-[11px] uppercase tracking-wide text-on-surface-variant">
                  <th className="px-4 py-3">Rank</th>
                  <th className="px-4 py-3">User</th>
                  <th className="px-4 py-3">Score</th>
                  <th className="px-4 py-3">Time</th>
                  <th className="hidden px-4 py-3 sm:table-cell">Completed</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((row) => {
                  const mine = row.user_id === user?.id
                  return (
                    <tr
                      key={`${row.rank}-${row.user_id}`}
                      className={cn('border-t border-outline-variant/20', mine && 'bg-secondary-container/50')}
                    >
                      <td className="px-4 py-3 font-mono text-sm font-bold text-primary">#{row.rank}</td>
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-2">
                          <span className="flex h-7 w-7 items-center justify-center rounded-full bg-primary-fixed text-[10px] font-bold text-on-primary-fixed">
                            {initialsFromName(row.user_name ?? 'User')}
                          </span>
                          <span className="text-sm font-semibold text-on-surface">
                            {row.user_name ?? 'Unknown'}
                            {mine ? (
                              <span className="ml-2 text-[10px] font-medium uppercase text-primary"> You</span>
                            ) : null}
                          </span>
                        </div>
                      </td>
                      <td className="px-4 py-3 font-mono text-sm font-bold">{row.score ?? 0}</td>
                      <td className="px-4 py-3 font-mono text-sm">{formatDuration(row.time_taken_seconds)}</td>
                      <td className="hidden px-4 py-3 text-xs text-on-surface-variant sm:table-cell">
                        {formatDateTime(row.completed_at)}
                      </td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          ) : null}
        </div>
      </section>
    </>
  )
}

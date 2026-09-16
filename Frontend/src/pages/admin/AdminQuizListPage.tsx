import { useMemo, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { deleteQuiz, listQuizzes } from '@/api/quizzes'
import { validationMessage } from '@/api/client'
import { PageHeader } from '@/components/layout/PageHeader'
import { MetricsStrip } from '@/components/layout/MetricsStrip'
import { Button, ButtonLink } from '@/components/ui/Button'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Input } from '@/components/ui/Input'
import { MsIcon } from '@/components/ui/MsIcon'
import { Select } from '@/components/ui/Select'
import { useToastStore } from '@/store/toastStore'
import { countryLabel, formatDuration } from '@/utils/format'

export function AdminQuizListPage() {
  const queryClient = useQueryClient()
  const push = useToastStore((state) => state.push)
  const [search, setSearch] = useState('')
  const [published, setPublished] = useState<'all' | '1' | '0'>('all')
  const [deleteId, setDeleteId] = useState<string | null>(null)

  const query = useQuery({
    queryKey: ['admin-quizzes', published],
    queryFn: () =>
      listQuizzes({
        per_page: 100,
        is_published: published === 'all' ? undefined : published === '1',
      }),
  })

  const remove = useMutation({
    mutationFn: (id: string) => deleteQuiz(id),
    onSuccess: async () => {
      push({ title: 'Quiz deleted', tone: 'success' })
      setDeleteId(null)
      await queryClient.invalidateQueries({ queryKey: ['admin-quizzes'] })
    },
    onError: (error) => {
      push({ title: 'Delete failed', description: validationMessage(error), tone: 'danger' })
    },
  })

  const quizzes = useMemo(() => {
    const rows = query.data?.data ?? []
    const term = search.trim().toLowerCase()
    if (!term) return rows
    return rows.filter(
      (quiz) =>
        quiz.title.toLowerCase().includes(term) ||
        (quiz.description ?? '').toLowerCase().includes(term) ||
        quiz.countries.join(' ').toLowerCase().includes(term),
    )
  }, [query.data?.data, search])

  const publishedCount = (query.data?.data ?? []).filter((quiz) => quiz.is_published).length

  return (
    <>
      <PageHeader
        title="Quiz List"
        subtitle="Add, edit, and delete quizzes, or open the leaderboard for any quiz."
        countLabel={`${query.data?.meta?.total ?? quizzes.length}`}
        actions={
          <ButtonLink to="/admin/quizzes/new" variant="primary">
            <MsIcon name="add" className="text-[16px]" />
            Add Quiz
          </ButtonLink>
        }
      />
      <MetricsStrip
        items={[
          { label: 'Total', value: String(query.data?.meta?.total ?? 0), icon: 'quiz' },
          { label: 'Published', value: String(publishedCount), icon: 'check_circle', tone: 'success' },
          {
            label: 'Drafts',
            value: String((query.data?.data ?? []).length - publishedCount),
            icon: 'edit',
            tone: 'muted',
          },
          { label: 'Visible now', value: String(quizzes.length), icon: 'visibility' },
        ]}
      />
      <section className="px-4 pb-20 sm:px-6">
        <div className="rounded-2xl bg-surface-container-lowest p-3 shadow-sm">
          <div className="mb-3 flex flex-wrap items-center gap-2">
            <div className="relative min-w-[220px] flex-1">
              <MsIcon name="search" className="absolute left-2.5 top-2 text-[18px] text-on-surface-variant" />
              <Input
                className="h-8 border-0 bg-surface-container-low pl-8"
                placeholder="Search quizzes..."
                value={search}
                onChange={(event) => setSearch(event.target.value)}
              />
            </div>
            <Select className="h-8 w-40" value={published} onChange={(event) => setPublished(event.target.value as typeof published)}>
              <option value="all">All statuses</option>
              <option value="1">Published</option>
              <option value="0">Drafts</option>
            </Select>
          </div>
          {query.isLoading ? (
            <div className="space-y-2 p-2">
              {Array.from({ length: 6 }).map((_, index) => (
                <div key={index} className="erp-skeleton h-12 rounded-lg" />
              ))}
            </div>
          ) : null}
          {query.isError ? (
            <div className="p-8 text-center">
              <p className="text-sm font-semibold">Could not load quizzes</p>
              <Button className="mt-3" variant="primary" onClick={() => void query.refetch()}>
                Retry
              </Button>
            </div>
          ) : null}
          {!query.isLoading && quizzes.length === 0 ? (
            <div className="p-10 text-center">
              <MsIcon name="search_off" className="text-[32px] text-on-surface-variant" />
              <p className="mt-2 text-sm font-semibold">No quizzes match this view</p>
            </div>
          ) : null}
          {quizzes.length > 0 ? (
            <div className="overflow-x-auto">
              <table className="erp-table w-full min-w-[720px] text-left">
                <thead>
                  <tr className="bg-surface-container-low text-[11px] uppercase tracking-wide text-on-surface-variant">
                    <th className="px-3 py-3">Quiz</th>
                    <th className="px-3 py-3">Countries</th>
                    <th className="px-3 py-3">Questions</th>
                    <th className="px-3 py-3">Timer</th>
                    <th className="px-3 py-3">Status</th>
                    <th className="px-3 py-3 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {quizzes.map((quiz) => (
                    <tr key={quiz.id} className="border-t border-outline-variant/20">
                      <td className="px-3 py-3">
                        <p className="text-sm font-semibold text-on-surface">{quiz.title}</p>
                        <p className="line-clamp-1 text-[11px] text-on-surface-variant">{quiz.description || 'No description'}</p>
                      </td>
                      <td className="px-3 py-3 text-xs text-on-surface-variant">
                        {quiz.countries.length ? quiz.countries.map(countryLabel).join(', ') : 'Worldwide'}
                      </td>
                      <td className="px-3 py-3 font-mono text-sm">{quiz.question_count}</td>
                      <td className="px-3 py-3 font-mono text-xs">
                        {quiz.time_limit_seconds ? formatDuration(quiz.time_limit_seconds) : '—'}
                      </td>
                      <td className="px-3 py-3">
                        <span
                          className={
                            quiz.is_published
                              ? 'rounded-full bg-tertiary-fixed/50 px-2 py-0.5 text-[10px] font-semibold uppercase text-tertiary'
                              : 'rounded-full bg-surface-container-high px-2 py-0.5 text-[10px] font-semibold uppercase text-on-surface-variant'
                          }
                        >
                          {quiz.is_published ? 'Published' : 'Draft'}
                        </span>
                      </td>
                      <td className="px-3 py-3">
                        <div className="flex justify-end gap-1">
                          <ButtonLink to={`/admin/quizzes/${quiz.id}/leaderboard`} size="sm" aria-label="View leaderboard">
                            <MsIcon name="trophy" className="text-[14px]" />
                            <span className="hidden lg:inline">Leaderboard</span>
                          </ButtonLink>
                          <ButtonLink to={`/admin/quizzes/${quiz.id}/edit`} size="sm" aria-label="Edit Quiz">
                            <MsIcon name="edit" className="text-[14px]" />
                            <span className="hidden sm:inline">Edit</span>
                          </ButtonLink>
                          <Button size="sm" aria-label="Delete Quiz" onClick={() => setDeleteId(quiz.id)}>
                            <MsIcon name="delete" className="text-[14px]" />
                            <span className="hidden sm:inline">Delete</span>
                          </Button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : null}
        </div>
      </section>
      <ConfirmDialog
        open={Boolean(deleteId)}
        title="Delete this quiz?"
        description="This removes the quiz and its questions. Existing attempts stay in history."
        confirmLabel="Delete quiz"
        danger
        busy={remove.isPending}
        onCancel={() => setDeleteId(null)}
        onConfirm={() => {
          if (deleteId) remove.mutate(deleteId)
        }}
      />
    </>
  )
}

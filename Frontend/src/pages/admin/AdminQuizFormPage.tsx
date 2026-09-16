import { useMutation, useQuery } from '@tanstack/react-query'
import { useNavigate, useParams } from 'react-router-dom'
import { createQuiz, getQuiz, updateQuiz } from '@/api/quizzes'
import { validationMessage } from '@/api/client'
import { PageHeader } from '@/components/layout/PageHeader'
import { QuizForm } from '@/components/quiz/QuizForm'
import { Button, ButtonLink } from '@/components/ui/Button'
import { MsIcon } from '@/components/ui/MsIcon'
import { useToastStore } from '@/store/toastStore'
import type { QuizPayload } from '@/types/api'

export function AdminQuizFormPage() {
  const { quizId } = useParams()
  const navigate = useNavigate()
  const push = useToastStore((state) => state.push)
  const editing = Boolean(quizId)

  const query = useQuery({
    queryKey: ['admin-quiz', quizId],
    enabled: editing,
    queryFn: () => getQuiz(quizId!),
  })

  const save = useMutation({
    mutationFn: (payload: QuizPayload) => (editing ? updateQuiz(quizId!, payload) : createQuiz(payload)),
    onSuccess: (response) => {
      push({
        title: editing ? 'Quiz updated' : 'Quiz created',
        description: response.data.title,
        tone: 'success',
      })
      navigate('/admin/quizzes')
    },
    onError: (error) => {
      push({ title: 'Save failed', description: validationMessage(error), tone: 'danger' })
    },
  })

  return (
    <>
      <PageHeader
        title={editing ? 'Edit Quiz' : 'Add Quiz'}
        subtitle="Manage questions and options, upload images for answers, assign the quiz to multiple countries, and set time limits."
        crumb="Quizzes"
        actions={
          <ButtonLink to="/admin/quizzes">
            <MsIcon name="chevron_left" className="text-[16px]" />
            Back to list
          </ButtonLink>
        }
      />
      <section className="px-4 pb-20 sm:px-6">
        {editing && query.isLoading ? <div className="erp-skeleton h-96 rounded-2xl" /> : null}
        {editing && query.isError ? (
          <div className="rounded-2xl bg-surface-container-lowest p-8 text-center shadow-sm">
            <p className="text-sm font-semibold">Could not load this quiz</p>
            <Button className="mt-3" variant="primary" onClick={() => void query.refetch()}>
              Retry
            </Button>
          </div>
        ) : null}
        {(!editing || query.data) && (
          <QuizForm
            key={query.data?.data.id ?? 'new'}
            quiz={query.data?.data}
            busy={save.isPending}
            onSubmit={(payload) => save.mutate(payload)}
          />
        )}
      </section>
    </>
  )
}

import { useCallback, useEffect, useMemo, useRef, useState, type ReactNode } from 'react'
import { useBlocker, useParams } from 'react-router-dom'
import { getAttempt, startOrResumeAttempt, submitAnswer } from '@/api/attempts'
import { getLeaderboard, getQuiz, listQuizzes } from '@/api/quizzes'
import { validationMessage } from '@/api/client'
import { ProgressBar } from '@/components/quiz/ProgressBar'
import { QuestionCard } from '@/components/quiz/QuestionCard'
import { QuizTimer } from '@/components/quiz/QuizTimer'
import { Button, ButtonLink } from '@/components/ui/Button'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { MsIcon } from '@/components/ui/MsIcon'
import { canAttendQuizzes } from '@/auth/permissions'
import { requestQuizFullscreen, useQuizTabGuard } from '@/hooks/useQuizTabGuard'
import {
  readStoredAttemptId,
  storeAttemptId,
  useAuthStore,
} from '@/store/authStore'
import { useToastStore } from '@/store/toastStore'
import type { QuizAttempt, QuizDetail, QuizListItem } from '@/types/api'
import { formatDuration, formatPercent } from '@/utils/format'

type Screen = 'loading' | 'start' | 'playing' | 'results'

export function QuizAttendPage() {
  const { quizId = '' } = useParams()
  const user = useAuthStore((state) => state.user)
  const push = useToastStore((state) => state.push)
  const [screen, setScreen] = useState<Screen>('loading')
  const [quiz, setQuiz] = useState<QuizDetail | null>(null)
  const [listItem, setListItem] = useState<QuizListItem | null>(null)
  const [attempt, setAttempt] = useState<QuizAttempt | null>(null)
  const [selectedIds, setSelectedIds] = useState<string[]>([])
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [overallLeft, setOverallLeft] = useState<number | null>(null)
  const [questionLeft, setQuestionLeft] = useState<number | null>(null)
  const submittingRef = useRef(false)
  const selectedRef = useRef<string[]>([])

  useEffect(() => {
    selectedRef.current = selectedIds
  }, [selectedIds])

  const applyAttempt = useCallback((next: QuizAttempt) => {
    setAttempt(next)
    setSelectedIds([])
    selectedRef.current = []
    setOverallLeft(next.status === 'in_progress' ? next.remaining_seconds : 0)
    setQuestionLeft(next.status === 'in_progress' ? next.current_question_remaining_seconds : 0)
    if (user && quizId) storeAttemptId(user.id, quizId, next.id)
    if (next.status === 'in_progress' && next.current_question) {
      setScreen('playing')
      return
    }
    setScreen('results')
  }, [quizId, user])

  useEffect(() => {
    let cancelled = false

    async function load() {
      if (!user || !quizId || !canAttendQuizzes(user)) return
      setError(null)
      setScreen((current) => (current === 'playing' ? current : 'loading'))
      try {
        const [detail, catalog] = await Promise.all([
          getQuiz(quizId),
          listQuizzes({
            user_id: user.id,
            country: user.country,
            per_page: 100,
          }),
        ])
        if (cancelled) return
        setQuiz(detail.data)
        const item = catalog.data.find((entry) => entry.id === quizId) ?? null
        setListItem(item)

        const storedId = readStoredAttemptId(user.id, quizId)
        const resumeId = item?.in_progress_attempt_id || storedId
        if (resumeId) {
          try {
            const existing = await getAttempt(resumeId)
            if (cancelled) return
            applyAttempt(existing.data)
            return
          } catch {
            // Fall through to completed or start.
          }
        }

        if (item?.completed_attempt_id) {
          try {
            const done = await getAttempt(item.completed_attempt_id)
            if (cancelled) return
            applyAttempt(done.data)
            return
          } catch {
            // Fall through to leaderboard summary.
          }
        }

        if (item?.is_completed) {
          const board = await getLeaderboard(quizId, 100)
          const mine = board.data.find((entry) => entry.user_id === user.id)
          setAttempt(
            mine
              ? {
                  id: item.completed_attempt_id ?? storedId ?? quizId,
                  quiz_id: quizId,
                  status: 'completed',
                  score: mine.score,
                  time_taken_seconds: mine.time_taken_seconds,
                  remaining_seconds: 0,
                  current_question_remaining_seconds: 0,
                  started_at: null,
                  completed_at: mine.completed_at,
                  expires_at: null,
                  progress: { answered: detail.data.questions.length, total: detail.data.questions.length },
                  current_question: null,
                  answers: undefined,
                }
              : null,
          )
          setScreen('results')
          return
        }

        setScreen((current) => (current === 'playing' || current === 'results' ? current : 'start'))
      } catch (err) {
        if (!cancelled) {
          setError(validationMessage(err))
          setScreen((current) => (current === 'playing' || current === 'results' ? current : 'start'))
        }
      }
    }

    void load()
    return () => {
      cancelled = true
    }
  }, [applyAttempt, quizId, user])

  const recordAnswer = useCallback(
    async (optionIds: string[]) => {
      if (!attempt || !attempt.current_question || submittingRef.current) return
      submittingRef.current = true
      setBusy(true)
      try {
        const response = await submitAnswer(attempt.id, attempt.current_question.id, optionIds)
        applyAttempt(response.data)
      } catch (err) {
        push({ title: 'Could not save answer', description: validationMessage(err), tone: 'danger' })
        try {
          const latest = await getAttempt(attempt.id)
          applyAttempt(latest.data)
        } catch {
          setError(validationMessage(err))
        }
      } finally {
        submittingRef.current = false
        setBusy(false)
      }
    },
    [applyAttempt, attempt, push],
  )

  const skipForTabLeave = useCallback(() => {
    void recordAnswer([])
  }, [recordAnswer])

  const tabGuard = useQuizTabGuard({
    active: screen === 'playing',
    attemptId: attempt?.status === 'in_progress' ? attempt.id : null,
    onRepeatLeave: skipForTabLeave,
  })

  useEffect(() => {
    if (screen !== 'playing' || !attempt || attempt.status !== 'in_progress') return

    const initialOverall = attempt.remaining_seconds
    const initialQuestion = attempt.current_question_remaining_seconds
    setOverallLeft(initialOverall)
    setQuestionLeft(initialQuestion)

    const startedAt = Date.now()
    let finished = false

    const expireOverall = () => {
      if (finished || submittingRef.current || !attempt.id) return
      finished = true
      submittingRef.current = true
      void getAttempt(attempt.id)
        .then((response) => applyAttempt(response.data))
        .catch((err) => setError(validationMessage(err)))
        .finally(() => {
          submittingRef.current = false
        })
    }

    const expireQuestion = () => {
      if (finished || submittingRef.current) return
      finished = true
      const selected = selectedRef.current
      if (selected.length > 0) {
        void recordAnswer(selected)
        return
      }
      submittingRef.current = true
      void getAttempt(attempt.id)
        .then((response) => applyAttempt(response.data))
        .catch((err) => setError(validationMessage(err)))
        .finally(() => {
          submittingRef.current = false
        })
    }

    if (initialOverall != null && initialOverall <= 0) {
      expireOverall()
      return
    }
    if (initialQuestion != null && initialQuestion <= 0) {
      expireQuestion()
      return
    }

    const timer = window.setInterval(() => {
      const elapsed = Math.floor((Date.now() - startedAt) / 1000)
      if (initialOverall != null) {
        const nextOverall = Math.max(0, initialOverall - elapsed)
        setOverallLeft(nextOverall)
        if (nextOverall <= 0) {
          window.clearInterval(timer)
          expireOverall()
          return
        }
      }
      if (initialQuestion != null) {
        const nextQuestion = Math.max(0, initialQuestion - elapsed)
        setQuestionLeft(nextQuestion)
        if (nextQuestion <= 0) {
          window.clearInterval(timer)
          expireQuestion()
        }
      }
    }, 250)

    return () => {
      finished = true
      window.clearInterval(timer)
    }
  }, [
    applyAttempt,
    attempt?.current_question?.id,
    attempt?.current_question_remaining_seconds,
    attempt?.id,
    attempt?.remaining_seconds,
    attempt?.status,
    recordAnswer,
    screen,
  ])

  async function handleStart() {
    if (!user || !quizId) return
    setBusy(true)
    setError(null)
    try {
      const response = await startOrResumeAttempt(quizId)
      applyAttempt(response.data)
      void requestQuizFullscreen()
    } catch (err) {
      const message = validationMessage(err)
      setError(message)
      push({ title: 'Could not start quiz', description: message, tone: 'danger' })
    } finally {
      setBusy(false)
    }
  }

  function toggleOption(optionId: string) {
    const multiple = attempt?.current_question?.type === 'multiple_choice'
    setSelectedIds((current) => {
      if (!multiple) return [optionId]
      return current.includes(optionId) ? current.filter((id) => id !== optionId) : [...current, optionId]
    })
  }

  const summary = useMemo(() => {
    const answers = attempt?.answers ?? []
    const totalPoints =
      quiz?.questions.reduce((sum, question) => sum + question.points, 0) ??
      answers.reduce((sum, answer) => sum + Math.max(answer.points_awarded, 1), 0)
    const score = attempt?.score ?? 0
    const correct = answers.filter((answer) => answer.is_correct).length
    const incorrect = answers.length ? answers.length - correct : quiz?.questions.length ?? 0
    const percent = totalPoints > 0 ? (score / totalPoints) * 100 : 0
    return { score, totalPoints, correct, incorrect, percent }
  }, [attempt, quiz])

  const questionNumber = (attempt?.progress.answered ?? 0) + 1
  const completed = listItem?.is_completed || attempt?.status === 'completed' || attempt?.status === 'expired'
  const blocker = useBlocker(screen === 'playing')

  useEffect(() => {
    if (screen !== 'playing' && blocker.state === 'blocked') {
      blocker.reset()
    }
  }, [blocker, screen])

  if (screen === 'loading' || !quiz) {
    return (
      <QuizPlayerFrame title="Quiz">
        <div className="erp-skeleton h-80 rounded-2xl" />
      </QuizPlayerFrame>
    )
  }

  return (
    <QuizPlayerFrame title={quiz.title} playing={screen === 'playing'}>
      {error ? <p className="mb-4 rounded-xl bg-error-container px-3 py-2 text-xs text-on-error-container">{error}</p> : null}

      {screen === 'start' ? (
        <div className="rounded-2xl bg-surface-container-lowest p-6 shadow-sm sm:p-8">
          <span className="rounded-full bg-primary-fixed px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-on-primary-fixed">
            In this app
          </span>
          <h2 className="mt-3 text-2xl font-semibold text-on-surface">{quiz.title}</h2>
          <p className="mt-2 text-sm leading-6 text-on-surface-variant">
            {quiz.description || 'No description provided.'}
          </p>
          <p className="mt-3 text-sm leading-6 text-on-surface">
            You take this quiz here — one question at a time. Stay on this tab; switching away is not allowed.
          </p>
          <dl className="mt-6 grid grid-cols-2 gap-3 text-sm">
            <div className="rounded-xl bg-surface-container-low p-3">
              <dt className="text-[11px] uppercase tracking-wide text-on-surface-variant">Questions</dt>
              <dd className="mt-1 font-semibold">{quiz.questions.length}</dd>
            </div>
            <div className="rounded-xl bg-surface-container-low p-3">
              <dt className="text-[11px] uppercase tracking-wide text-on-surface-variant">Overall time</dt>
              <dd className="mt-1 font-semibold">{quiz.time_limit_seconds ? formatDuration(quiz.time_limit_seconds) : 'Untimed'}</dd>
            </div>
            <div className="rounded-xl bg-surface-container-low p-3">
              <dt className="text-[11px] uppercase tracking-wide text-on-surface-variant">Per question</dt>
              <dd className="mt-1 font-semibold">
                {quiz.per_question_time_seconds ? formatDuration(quiz.per_question_time_seconds) : 'Untimed'}
              </dd>
            </div>
            <div className="rounded-xl bg-surface-container-low p-3">
              <dt className="text-[11px] uppercase tracking-wide text-on-surface-variant">Status</dt>
              <dd className="mt-1 font-semibold">{completed ? 'Already completed' : 'Not started'}</dd>
            </div>
          </dl>
          <Button className="mt-6 w-full sm:w-auto" variant="primary" size="lg" disabled={busy || completed} onClick={() => void handleStart()}>
            <MsIcon name="play" className="text-[18px]" />
            {busy ? 'Starting…' : listItem?.in_progress_attempt_id ? 'Resume quiz' : 'Start quiz'}
          </Button>
          {completed ? (
            <p className="mt-3 text-xs text-on-surface-variant">This quiz is already completed. Open results from the quiz list instead of starting again.</p>
          ) : (
            <p className="mt-3 text-xs text-on-surface-variant">
              Timers keep running if you switch tabs. A second leave skips the current question. You can still exit and resume from the quiz list.
            </p>
          )}
        </div>
      ) : null}

      {screen === 'playing' && attempt?.current_question ? (
        <div>
          <div className="mb-4 grid grid-cols-2 gap-2 sm:grid-cols-[1fr_auto_auto] sm:items-end">
            <ProgressBar current={questionNumber} total={attempt.progress.total} />
            <QuizTimer label="Quiz" seconds={overallLeft} compact />
            <QuizTimer label="Question" seconds={questionLeft} compact />
          </div>
          <QuestionCard
            key={attempt.current_question.id}
            question={attempt.current_question}
            selectedIds={selectedIds}
            disabled={busy || tabGuard.locked}
            onToggle={toggleOption}
          />
          <div className="mt-4 flex justify-end">
            <Button
              variant="primary"
              size="lg"
                disabled={busy || tabGuard.locked || selectedIds.length === 0}
              onClick={() => void recordAnswer(selectedIds)}
            >
              {questionNumber >= attempt.progress.total ? 'Finish' : 'Next question'}
              <MsIcon name="chevron_right" className="text-[18px]" />
            </Button>
          </div>
        </div>
      ) : null}

      {screen === 'results' ? (
        <div className="rounded-2xl bg-surface-container-lowest p-6 shadow-sm sm:p-8">
          <span className="rounded-full bg-tertiary-fixed/60 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-tertiary">
            {attempt?.status === 'expired' ? 'Time expired' : 'Already completed'}
          </span>
          <h2 className="mt-3 text-2xl font-semibold text-on-surface">Results</h2>
          <p className="mt-1 text-sm text-on-surface-variant">
            {attempt?.status === 'expired'
              ? 'The overall timer ended this attempt. Unanswered questions scored zero.'
              : 'This quiz is already completed. Retakes are not allowed.'}
          </p>
          <div className="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <ResultStat label="Score" value={`${summary.score}/${summary.totalPoints || '—'}`} />
            <ResultStat label="Percentage" value={formatPercent(summary.percent)} />
            <ResultStat label="Correct" value={String(summary.correct)} />
            <ResultStat label="Incorrect" value={String(summary.incorrect)} />
          </div>
          <p className="mt-4 text-sm text-on-surface-variant">
            Time taken: <strong className="text-on-surface">{formatDuration(attempt?.time_taken_seconds)}</strong>
          </p>
          <div className="mt-6 flex flex-wrap gap-2">
            <ButtonLink to={`/quizzes/${quiz.id}/leaderboard`} variant="primary">
              <MsIcon name="trophy" className="text-[16px]" />
              View leaderboard
            </ButtonLink>
            <ButtonLink to="/quizzes">Back to quizzes</ButtonLink>
          </div>
        </div>
      ) : null}

      <TabSwitchLock
        open={screen === 'playing' && tabGuard.locked}
        leaves={tabGuard.leaves}
        maxLeaves={tabGuard.maxLeaves}
        penalized={tabGuard.penalized}
        onContinue={tabGuard.dismiss}
      />
      <ConfirmDialog
        open={screen === 'playing' && blocker.state === 'blocked'}
        title="Leave this quiz?"
        description="Your progress stays saved in the app. You can resume from the quiz list without starting over."
        confirmLabel="Leave quiz"
        onCancel={() => {
          if (blocker.state === 'blocked') blocker.reset()
        }}
        onConfirm={() => {
          if (blocker.state === 'blocked') blocker.proceed()
        }}
      />
    </QuizPlayerFrame>
  )
}

function QuizPlayerFrame({
  title,
  playing = false,
  children,
}: {
  title: string
  playing?: boolean
  children: ReactNode
}) {
  return (
    <div className="flex min-h-dvh flex-col bg-surface">
      <header className="sticky top-0 z-10 flex h-12 items-center justify-between gap-3 border-b border-outline-variant/30 bg-surface-container-lowest/95 px-3 backdrop-blur-md sm:px-4">
        <div className="flex min-w-0 items-center gap-2">
          <div className="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-primary text-[10px] font-bold text-on-primary">
            Q
          </div>
          <div className="min-w-0">
            <p className="truncate text-[13px] font-semibold tracking-tight text-on-surface">{title}</p>
            <p className="hidden text-[11px] leading-tight text-on-surface-variant sm:block">
              {playing ? 'Stay on this tab — switching away is not allowed' : 'Taking the quiz in this app'}
            </p>
          </div>
        </div>
        <ButtonLink to="/quizzes" variant="ghost" size="sm">
          <MsIcon name="close" className="text-[16px]" />
          Exit
        </ButtonLink>
      </header>
      <section className="mx-auto flex w-full max-w-3xl flex-1 flex-col px-4 py-6 sm:px-6">{children}</section>
    </div>
  )
}

function TabSwitchLock({
  open,
  leaves,
  maxLeaves,
  penalized,
  onContinue,
}: {
  open: boolean
  leaves: number
  maxLeaves: number
  penalized: boolean
  onContinue: () => void
}) {
  if (!open) return null

  const remaining = Math.max(0, maxLeaves - leaves)

  return (
    <div className="fixed inset-0 z-40 flex items-center justify-center bg-inverse-surface/55 p-4 backdrop-blur-[3px]">
      <div
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="tab-lock-title"
        aria-describedby="tab-lock-copy"
        className="w-full max-w-md rounded-xl bg-surface-container-lowest p-5 shadow-2xl"
      >
        <div className="flex items-start gap-3">
          <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-error-container text-on-error-container">
            <MsIcon name="lock" className="text-[20px]" />
          </span>
          <div>
            <h2 id="tab-lock-title" className="text-base font-semibold text-on-surface">
              {penalized ? 'Question skipped' : 'Stay on this tab'}
            </h2>
            <p id="tab-lock-copy" className="mt-2 text-sm leading-6 text-on-surface-variant">
              {penalized
                ? 'You left this browser tab again, so the current question was submitted blank and scored zero. The quiz timer kept running.'
                : remaining > 0
                  ? `Tab switching is not allowed while you are taking a quiz. If you leave again, this question will be skipped. The timer kept running (${leaves} of ${maxLeaves}).`
                  : 'Tab switching is not allowed while you are taking a quiz. The timer kept running.'}
            </p>
          </div>
        </div>
        <div className="mt-5 flex justify-end">
          <Button variant="primary" onClick={onContinue}>
            Back to quiz
          </Button>
        </div>
      </div>
    </div>
  )
}

function ResultStat({ label, value }: { label: string; value: string }) {
  return (
    <div className="rounded-xl bg-surface-container-low p-3">
      <p className="text-[11px] font-semibold uppercase tracking-wide text-on-surface-variant">{label}</p>
      <p className="mt-1 font-mono text-lg font-bold text-on-surface">{value}</p>
    </div>
  )
}

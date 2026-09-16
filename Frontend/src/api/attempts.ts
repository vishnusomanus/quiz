import { apiRequest } from '@/api/client'
import type { QuizAttempt } from '@/types/api'

export function startOrResumeAttempt(quizId: string) {
  return apiRequest<QuizAttempt>(`/quizzes/${quizId}/attempts`, { method: 'POST' })
}

export function getAttempt(attemptId: string) {
  return apiRequest<QuizAttempt>(`/attempts/${attemptId}`)
}

export function submitAnswer(attemptId: string, questionId: string, optionIds: string[]) {
  return apiRequest<QuizAttempt>(`/attempts/${attemptId}/answers`, {
    method: 'POST',
    body: {
      question_id: questionId,
      option_ids: optionIds,
    },
  })
}

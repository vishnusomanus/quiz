import { apiRequest, ApiError, API_BASE } from '@/api/client'
import { useAuthStore } from '@/store/authStore'
import type { ApiFailure, ApiSuccess, LeaderboardEntry, QuizDetail, QuizListItem, QuizPayload } from '@/types/api'

export function listQuizzes(params: {
  user_id?: string
  country?: string | null
  per_page?: number
  page?: number
  is_published?: boolean
}) {
  return apiRequest<QuizListItem[]>('/quizzes', {
    params: {
      user_id: params.user_id,
      country: params.country,
      per_page: params.per_page,
      page: params.page,
      is_published: params.is_published === undefined ? undefined : params.is_published ? 1 : 0,
    },
  })
}

export function getQuiz(quizId: string) {
  return apiRequest<QuizDetail>(`/quizzes/${quizId}`)
}

export function createQuiz(payload: QuizPayload) {
  return apiRequest<QuizDetail>('/quizzes', {
    method: 'POST',
    body: payload,
  })
}

export function updateQuiz(quizId: string, payload: QuizPayload) {
  return apiRequest<QuizDetail>(`/quizzes/${quizId}`, {
    method: 'PUT',
    body: payload,
  })
}

export function deleteQuiz(quizId: string) {
  return apiRequest<null>(`/quizzes/${quizId}`, { method: 'DELETE' })
}

export async function uploadOptionImage(file: File) {
  const token = useAuthStore.getState().token
  const form = new FormData()
  form.append('image', file)
  const response = await fetch(`${API_BASE}/uploads/option-images`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body: form,
  })

  let payload: ApiSuccess<{ url: string; path: string }> | ApiFailure | null = null
  try {
    payload = (await response.json()) as ApiSuccess<{ url: string; path: string }> | ApiFailure
  } catch {
    payload = null
  }

  if (response.status === 401) {
    useAuthStore.getState().clearSession()
  }

  if (!response.ok || !payload || payload.status === false) {
    const failure = payload && payload.status === false ? payload : null
    throw new ApiError(failure?.message || 'Upload failed', response.status, failure)
  }

  return payload
}

export function getLeaderboard(quizId: string, perPage = 15, page = 1) {
  return apiRequest<LeaderboardEntry[]>(`/quizzes/${quizId}/leaderboard`, {
    params: { per_page: perPage, page },
  })
}

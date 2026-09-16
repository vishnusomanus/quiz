export type UserRole = 'admin' | 'customer' | 'current_user'

export type QuestionType = 'single_choice' | 'multiple_choice'

export type QuizAttemptStatus = 'in_progress' | 'completed' | 'expired'

export type AuthUser = {
  id: string
  uuid: string
  name: string
  email: string
  role: UserRole
  country?: string | null
}

export type LoginResponse = {
  token: string
  token_type: string
  expires_in: number
  user: AuthUser
}

export type QuizListItem = {
  id: string
  title: string
  description: string | null
  time_limit_seconds: number | null
  per_question_time_seconds: number | null
  countries: string[]
  question_count: number
  is_completed?: boolean
  in_progress_attempt_id?: string | null
  completed_attempt_id?: string | null
  is_published?: boolean
}

export type QuizOption = {
  id: string
  label: string
  image_url: string | null
  sort_order: number
  is_correct?: boolean
}

export type QuizQuestion = {
  id: string
  prompt: string
  type: QuestionType
  points: number
  time_limit_seconds: number | null
  sort_order: number
  options: QuizOption[]
}

export type QuizDetail = {
  id: string
  title: string
  description: string | null
  time_limit_seconds: number | null
  per_question_time_seconds: number | null
  is_published: boolean
  countries: string[]
  questions: QuizQuestion[]
}

export type AttemptAnswer = {
  question_id: string
  selected_option_ids: string[]
  is_correct: boolean | null
  points_awarded: number
  time_taken_seconds: number | null
}

export type AttemptProgress = {
  answered: number
  total: number
}

export type QuizAttempt = {
  id: string
  quiz_id: string
  status: QuizAttemptStatus
  score: number | null
  time_taken_seconds: number | null
  remaining_seconds: number | null
  current_question_remaining_seconds: number | null
  started_at: string | null
  completed_at: string | null
  expires_at: string | null
  progress: AttemptProgress
  current_question: QuizQuestion | null
  answers?: AttemptAnswer[]
}

export type LeaderboardEntry = {
  rank: number
  user_id: string | null
  user_name: string | null
  score: number | null
  time_taken_seconds: number | null
  completed_at: string | null
}

export type PaginationMeta = {
  total: number
  per_page: number
  current_page: number
  next: number
  prev: number
}

export type ApiSuccess<T> = {
  status: true
  message: string
  data: T
  meta?: PaginationMeta
}

export type ApiFailure = {
  status: false
  message: string
  errors?: unknown
}

export type QuizPayload = {
  title: string
  description?: string | null
  time_limit_seconds?: number | null
  per_question_time_seconds?: number | null
  is_published?: boolean
  countries?: string[]
  questions: Array<{
    id?: string
    prompt: string
    type: QuestionType
    points: number
    time_limit_seconds?: number | null
    sort_order?: number
    options: Array<{
      id?: string
      label: string
      image_url?: string | null
      is_correct: boolean
      sort_order?: number
    }>
  }>
}

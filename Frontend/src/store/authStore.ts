import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import type { AuthUser } from '@/types/api'

type AuthState = {
  token: string | null
  user: AuthUser | null
  setSession: (token: string, user: AuthUser) => void
  clearSession: () => void
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      token: null,
      user: null,
      setSession: (token, user) => set({ token, user }),
      clearSession: () => set({ token: null, user: null }),
    }),
    { name: 'quiz-auth' },
  ),
)

const ATTEMPT_PREFIX = 'quiz-attempt:'

export function attemptStorageKey(userId: string, quizId: string) {
  return `${ATTEMPT_PREFIX}${userId}:${quizId}`
}

export function readStoredAttemptId(userId: string, quizId: string) {
  return window.localStorage.getItem(attemptStorageKey(userId, quizId))
}

export function storeAttemptId(userId: string, quizId: string, attemptId: string) {
  window.localStorage.setItem(attemptStorageKey(userId, quizId), attemptId)
}

export function clearStoredAttemptId(userId: string, quizId: string) {
  window.localStorage.removeItem(attemptStorageKey(userId, quizId))
}

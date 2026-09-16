import type { AuthUser } from '@/types/api'

export function isAdmin(user: AuthUser | null | undefined) {
  return user?.role === 'admin'
}

export function canManageQuizzes(user: AuthUser | null | undefined) {
  return isAdmin(user)
}

export function canAttendQuizzes(user: AuthUser | null | undefined) {
  return user?.role === 'customer'
}

export function homePath(user: AuthUser | null | undefined) {
  return canManageQuizzes(user) ? '/admin/quizzes' : '/quizzes'
}

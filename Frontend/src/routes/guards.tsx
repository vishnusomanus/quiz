import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { canAttendQuizzes, canManageQuizzes, homePath } from '@/auth/permissions'
import { useAuthStore } from '@/store/authStore'

export function RequireAuth() {
  const token = useAuthStore((state) => state.token)
  const location = useLocation()

  if (!token) {
    return <Navigate to="/login" replace state={{ from: location.pathname }} />
  }

  return <Outlet />
}

export function RequireAdmin() {
  const user = useAuthStore((state) => state.user)

  if (!canManageQuizzes(user)) {
    return <Navigate to={homePath(user)} replace />
  }

  return <Outlet />
}

export function RequireLearner() {
  const user = useAuthStore((state) => state.user)

  if (!canAttendQuizzes(user)) {
    return <Navigate to={homePath(user)} replace />
  }

  return <Outlet />
}

export function RedirectIfAuthed() {
  const token = useAuthStore((state) => state.token)
  const user = useAuthStore((state) => state.user)

  if (token && user) {
    return <Navigate to={homePath(user)} replace />
  }

  return <Outlet />
}

export function HomeRedirect() {
  const user = useAuthStore((state) => state.user)
  return <Navigate to={homePath(user)} replace />
}

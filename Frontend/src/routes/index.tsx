import { lazy } from 'react'
import { createBrowserRouter } from 'react-router-dom'
import { AppShell } from '@/layouts/AppShell'
import { RedirectIfAuthed, RequireAdmin, RequireAuth, RequireLearner, HomeRedirect } from '@/routes/guards'
import { wrap } from '@/routes/wrap'

const LoginPage = lazy(() => import('@/pages/LoginPage').then((module) => ({ default: module.LoginPage })))
const QuizListPage = lazy(() => import('@/pages/QuizListPage').then((module) => ({ default: module.QuizListPage })))
const QuizAttendPage = lazy(() => import('@/pages/QuizAttendPage').then((module) => ({ default: module.QuizAttendPage })))
const LeaderboardPage = lazy(() => import('@/pages/LeaderboardPage').then((module) => ({ default: module.LeaderboardPage })))
const AdminQuizListPage = lazy(() =>
  import('@/pages/admin/AdminQuizListPage').then((module) => ({ default: module.AdminQuizListPage })),
)
const AdminQuizFormPage = lazy(() =>
  import('@/pages/admin/AdminQuizFormPage').then((module) => ({ default: module.AdminQuizFormPage })),
)

export const router = createBrowserRouter([
  {
    element: <RedirectIfAuthed />,
    children: [{ path: '/login', element: wrap(<LoginPage />) }],
  },
  {
    element: <RequireAuth />,
    children: [
      {
        element: <AppShell />,
        children: [
          { index: true, element: <HomeRedirect /> },
          {
            element: <RequireLearner />,
            children: [
              { path: '/quizzes', element: wrap(<QuizListPage />) },
              { path: '/quizzes/:quizId', element: wrap(<QuizAttendPage />) },
              { path: '/quizzes/:quizId/leaderboard', element: wrap(<LeaderboardPage />) },
            ],
          },
          {
            element: <RequireAdmin />,
            children: [
              { path: '/admin/quizzes', element: wrap(<AdminQuizListPage />) },
              { path: '/admin/quizzes/new', element: wrap(<AdminQuizFormPage />) },
              { path: '/admin/quizzes/:quizId/leaderboard', element: wrap(<LeaderboardPage />) },
              { path: '/admin/quizzes/:quizId/edit', element: wrap(<AdminQuizFormPage />) },
            ],
          },
          { path: '*', element: <HomeRedirect /> },
        ],
      },
    ],
  },
])

import { useState, type FormEvent } from 'react'
import { useLocation, useNavigate } from 'react-router-dom'
import { login } from '@/api/auth'
import { validationMessage } from '@/api/client'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { MsIcon } from '@/components/ui/MsIcon'
import { ToastViewport } from '@/components/ui/ToastViewport'
import { canAttendQuizzes, canManageQuizzes, homePath } from '@/auth/permissions'
import { useAuthStore } from '@/store/authStore'
import { useToastStore } from '@/store/toastStore'

const demos = [
  { email: 'customer@example.com', label: 'India learner', role: 'IN' },
  { email: 'anita@example.com', label: 'Anita Sharma', role: 'IN' },
  { email: 'us.customer@example.com', label: 'US learner', role: 'US' },
  { email: 'james@example.com', label: 'James Carter', role: 'US' },
  { email: 'admin@example.com', label: 'Quiz admin', role: 'Admin' },
]

export function LoginPage() {
  const navigate = useNavigate()
  const location = useLocation()
  const setSession = useAuthStore((state) => state.setSession)
  const push = useToastStore((state) => state.push)
  const [email, setEmail] = useState('customer@example.com')
  const [password, setPassword] = useState('password')
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState<string | null>(null)

  async function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setBusy(true)
    setError(null)
    try {
      const response = await login(email, password)
      setSession(response.data.token, response.data.user)
      const from = (location.state as { from?: string } | null)?.from
      const fallback = homePath(response.data.user)
      const allowedFrom = from
        ? canManageQuizzes(response.data.user)
          ? from.startsWith('/admin')
          : canAttendQuizzes(response.data.user) && from.startsWith('/quizzes')
        : false
      navigate(allowedFrom && from ? from : fallback, { replace: true })
    } catch (err) {
      const message = validationMessage(err)
      setError(message)
      push({ title: 'Sign in failed', description: message, tone: 'danger' })
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="flex min-h-dvh items-center justify-center bg-surface px-4 py-10">
      <div className="grid w-full max-w-4xl overflow-hidden rounded-2xl bg-surface-container-lowest shadow-xl md:grid-cols-[1.1fr_0.9fr]">
        <div className="hidden flex-col justify-between bg-primary p-8 text-on-primary md:flex">
          <div>
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 text-sm font-bold">Q</div>
            <h1 className="mt-8 text-3xl font-semibold tracking-tight">Quiz Platform</h1>
            <p className="mt-3 max-w-sm text-sm leading-6 text-white/80">
              Admins manage the quiz catalog. Learners take quizzes fully inside this app — one question at a time — then resume, see results, and view the leaderboard.
            </p>
          </div>
          <ul className="space-y-2 text-sm text-white/80">
            <li className="flex items-center gap-2">
              <MsIcon name="timer" className="text-[16px]" /> Server-side timers and resume
            </li>
            <li className="flex items-center gap-2">
              <MsIcon name="trophy" className="text-[16px]" /> Live leaderboards
            </li>
            <li className="flex items-center gap-2">
              <MsIcon name="quiz" className="text-[16px]" /> Admin create, edit, and publish
            </li>
          </ul>
        </div>
        <form className="p-6 sm:p-8" onSubmit={(event) => void handleSubmit(event)}>
          <p className="text-[11px] font-semibold uppercase tracking-[0.16em] text-on-surface-variant">Welcome back</p>
          <h2 className="mt-1 text-2xl font-semibold text-on-surface">Sign in</h2>
            <p className="mt-1 text-xs text-on-surface-variant">Use a seeded account. Password for all is password.</p>
          <label className="mt-6 block text-xs font-semibold text-on-surface-variant">
            Email
            <Input
              className="mt-1"
              type="email"
              autoComplete="email"
              value={email}
              onChange={(event) => setEmail(event.target.value)}
              required
            />
          </label>
          <label className="mt-3 block text-xs font-semibold text-on-surface-variant">
            Password
            <Input
              className="mt-1"
              type="password"
              autoComplete="current-password"
              value={password}
              onChange={(event) => setPassword(event.target.value)}
              required
            />
          </label>
          {error ? <p className="mt-3 text-xs text-error">{error}</p> : null}
          <Button className="mt-5 w-full" variant="primary" size="lg" type="submit" disabled={busy}>
            {busy ? (
              <>
                <MsIcon name="progress_activity" className="animate-spin text-[16px]" />
                Signing in
              </>
            ) : (
              'Continue'
            )}
          </Button>
          <div className="mt-6 space-y-2">
            <p className="text-[11px] font-semibold uppercase tracking-wide text-on-surface-variant">Demo accounts</p>
            <div className="grid gap-2">
              {demos.map((demo) => (
                <button
                  key={demo.email}
                  type="button"
                  className="flex items-center justify-between rounded-xl bg-surface-container-low px-3 py-2 text-left hover:bg-surface-container"
                  onClick={() => {
                    setEmail(demo.email)
                    setPassword('password')
                  }}
                >
                  <span>
                    <span className="block text-xs font-semibold text-on-surface">{demo.label}</span>
                    <span className="font-mono text-[11px] text-on-surface-variant">{demo.email}</span>
                  </span>
                  <span className="rounded-full bg-primary-fixed px-2 py-0.5 text-[10px] font-semibold text-on-primary-fixed">
                    {demo.role}
                  </span>
                </button>
              ))}
            </div>
          </div>
        </form>
      </div>
      <ToastViewport />
    </div>
  )
}

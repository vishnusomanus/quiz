import { NavLink } from 'react-router-dom'
import { canManageQuizzes } from '@/auth/permissions'
import { MsIcon } from '@/components/ui/MsIcon'
import { useAuthStore } from '@/store/authStore'
import { cn } from '@/utils/cn'
import { initialsFromName } from '@/utils/format'

type SidebarProps = {
  open: boolean
  onClose: () => void
  onNavigate?: () => void
}

type NavItem = {
  to: string
  label: string
  icon: string
  end?: boolean
}

export function Sidebar({ open, onClose, onNavigate }: SidebarProps) {
  const user = useAuthStore((state) => state.user)
  const admin = canManageQuizzes(user)

  const items: NavItem[] = admin
    ? [
        { to: '/admin/quizzes', label: 'Quiz List', icon: 'quiz', end: true },
        { to: '/admin/quizzes/new', label: 'Add Quiz', icon: 'add' },
      ]
    : [{ to: '/quizzes', label: 'Quizzes', icon: 'quiz' }]

  return (
    <>
      <div
        className={cn('fixed inset-0 z-30 bg-on-surface/30 md:hidden', open ? 'block' : 'hidden')}
        onClick={onClose}
        aria-hidden="true"
      />
      <aside
        className={cn(
          'fixed inset-y-0 left-0 z-40 flex w-60 flex-col justify-between border-r border-outline-variant/40 bg-surface-container-lowest shadow-[0_1px_8px_rgba(0,0,0,0.04)] transition-transform duration-200',
          open ? 'translate-x-0' : '-translate-x-full',
        )}
      >
        <div>
          <div className="flex h-12 items-center gap-2 border-b border-outline-variant/30 px-4">
            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-[11px] font-bold text-on-primary">
              Q
            </div>
            <div>
              <p className="text-sm font-semibold leading-none tracking-tight text-on-surface">Quiz Platform</p>
              <p className="mt-0.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-on-surface-variant">
                {admin ? 'Admin' : 'Learner'}
              </p>
            </div>
          </div>
          <p className="px-4 pb-1 pt-3 text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant/70">
            Navigation
          </p>
          <nav className="space-y-0.5 px-2" aria-label="Modules">
            {items.map((item) => (
              <NavLink
                key={item.to}
                to={item.to}
                end={item.end}
                onClick={onNavigate}
                className={({ isActive }) =>
                  cn(
                    'relative flex items-center justify-between rounded-lg px-2 py-1.5 text-[13px] transition-colors',
                    isActive
                      ? 'bg-secondary-container font-semibold text-on-surface before:absolute before:bottom-1 before:left-0 before:top-1 before:w-1 before:rounded-r before:bg-primary'
                      : 'font-normal text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface',
                  )
                }
              >
                <span className="flex items-center gap-2">
                  <MsIcon name={item.icon} className="text-[18px]" />
                  {item.label}
                </span>
              </NavLink>
            ))}
          </nav>
        </div>
        <div className="border-t border-outline-variant/30 p-2">
          <div className="flex items-center justify-between rounded-lg bg-surface-container-low p-2">
            <div className="flex min-w-0 items-center gap-2">
              <div className="flex h-6 w-6 shrink-0 items-center justify-center rounded bg-primary text-[10px] font-bold text-on-primary">
                {initialsFromName(user?.name ?? 'Q')}
              </div>
              <div className="min-w-0">
                <p className="truncate text-[11px] font-semibold text-on-surface">{user?.name}</p>
                <p className="truncate text-[11px] text-on-surface-variant">{user?.country ?? user?.role}</p>
              </div>
            </div>
          </div>
        </div>
      </aside>
    </>
  )
}

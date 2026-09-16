import { MsIcon } from '@/components/ui/MsIcon'
import { logout as logoutRequest } from '@/api/auth'
import { useAuthStore } from '@/store/authStore'
import { useToastStore } from '@/store/toastStore'
import { cn } from '@/utils/cn'
import { initialsFromName } from '@/utils/format'

type TopHeaderProps = {
  sidebarOpen: boolean
  onToggleSidebar: () => void
}

export function TopHeader({ sidebarOpen, onToggleSidebar }: TopHeaderProps) {
  const user = useAuthStore((state) => state.user)
  const clearSession = useAuthStore((state) => state.clearSession)
  const push = useToastStore((state) => state.push)
  const sidebarLabel = sidebarOpen ? 'Minimise side menu' : 'Expand side menu'

  async function handleLogout() {
    try {
      await logoutRequest()
    } catch {
      // Token may already be invalid; still clear local session.
    }
    clearSession()
    push({ title: 'Signed out', description: 'You can sign back in any time.', tone: 'info' })
  }

  return (
    <header
      className={cn(
        'fixed right-0 top-0 z-20 flex h-12 items-center justify-between gap-3 border-b border-outline-variant/30 bg-surface-container-lowest/95 px-3 backdrop-blur-md transition-[left] duration-200 md:px-4',
        sidebarOpen ? 'left-0 md:left-60' : 'left-0',
      )}
    >
      <div className="flex min-w-0 flex-1 items-center gap-2">
        <button
          type="button"
          className="erp-btn-gradient flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
          onClick={onToggleSidebar}
          aria-label={sidebarLabel}
          aria-expanded={sidebarOpen}
          title={sidebarLabel}
        >
          <MsIcon name={sidebarOpen ? 'panel_left_close' : 'panel_left'} className="text-[18px]" />
        </button>
        {!sidebarOpen ? (
          <div className="flex shrink-0 items-center gap-2 pr-1">
            <div className="flex h-7 w-7 items-center justify-center rounded-md bg-primary text-[10px] font-bold text-on-primary">
              Q
            </div>
            <p className="hidden text-[13px] font-semibold tracking-tight text-on-surface sm:block">Quiz Platform</p>
          </div>
        ) : null}
      </div>
      <div className="flex shrink-0 items-center gap-2 md:gap-3">
        <div className="hidden h-4 w-px bg-outline-variant/40 sm:block" />
        <div className="flex items-center gap-2">
          <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary-fixed text-[11px] font-bold text-on-primary-fixed">
            {initialsFromName(user?.name ?? 'Q')}
          </div>
          <div className="hidden text-left xl:block">
            <p className="text-[11px] font-semibold leading-tight text-on-surface">{user?.name}</p>
            <p className="text-[11px] leading-tight text-on-surface-variant capitalize">{user?.role}</p>
          </div>
        </div>
        <button
          type="button"
          onClick={() => void handleLogout()}
          className="inline-flex h-8 items-center gap-1.5 rounded-lg px-2 text-xs font-medium text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface"
        >
          <MsIcon name="logout" className="text-[16px]" />
          <span className="hidden sm:inline">Sign out</span>
        </button>
      </div>
    </header>
  )
}

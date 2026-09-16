import { useEffect, useState } from 'react'
import { Outlet, matchPath, useLocation } from 'react-router-dom'
import { Sidebar } from '@/components/layout/Sidebar'
import { TopHeader } from '@/components/layout/TopHeader'
import { ToastViewport } from '@/components/ui/ToastViewport'
import { cn } from '@/utils/cn'

const isDesktop = () => typeof window !== 'undefined' && window.matchMedia('(min-width: 768px)').matches

export function AppShell() {
  const location = useLocation()
  const [sidebarOpen, setSidebarOpen] = useState(isDesktop)
  const isQuizPlayer = Boolean(matchPath({ path: '/quizzes/:quizId', end: true }, location.pathname))

  useEffect(() => {
    const media = window.matchMedia('(min-width: 768px)')
    const onChange = () => setSidebarOpen(media.matches)
    media.addEventListener('change', onChange)
    return () => media.removeEventListener('change', onChange)
  }, [])

  if (isQuizPlayer) {
    return (
      <div className="min-h-dvh bg-surface text-on-surface">
        <main>
          <Outlet />
        </main>
        <ToastViewport />
      </div>
    )
  }

  return (
    <div className="min-h-dvh bg-surface text-on-surface">
      <Sidebar
        open={sidebarOpen}
        onClose={() => setSidebarOpen(false)}
        onNavigate={() => {
          if (!isDesktop()) setSidebarOpen(false)
        }}
      />
      <div className={cn('transition-[padding] duration-200', sidebarOpen && 'md:pl-60')}>
        <TopHeader sidebarOpen={sidebarOpen} onToggleSidebar={() => setSidebarOpen((value) => !value)} />
        <main className="pt-12">
          <Outlet />
        </main>
      </div>
      <ToastViewport />
    </div>
  )
}

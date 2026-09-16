import { useCallback, useEffect, useRef, useState } from 'react'

export const MAX_QUIZ_TAB_LEAVES = 2

const storageKey = (attemptId: string) => `quiz-tab-leaves:${attemptId}`

function readLeaves(attemptId: string) {
  const raw = sessionStorage.getItem(storageKey(attemptId))
  const parsed = raw ? Number.parseInt(raw, 10) : 0
  return Number.isFinite(parsed) && parsed > 0 ? parsed : 0
}

export function useQuizTabGuard({
  active,
  attemptId,
  onRepeatLeave,
}: {
  active: boolean
  attemptId: string | null
  onRepeatLeave: () => void
}) {
  const [locked, setLocked] = useState(false)
  const [leaves, setLeaves] = useState(0)
  const [penalized, setPenalized] = useState(false)
  const awayRef = useRef(false)
  const leavesRef = useRef(0)
  const onRepeatLeaveRef = useRef(onRepeatLeave)

  onRepeatLeaveRef.current = onRepeatLeave

  useEffect(() => {
    const next = attemptId ? readLeaves(attemptId) : 0
    leavesRef.current = next
    setLeaves(next)
    setLocked(false)
    setPenalized(false)
    awayRef.current = false
  }, [attemptId])

  useEffect(() => {
    if (!active || !attemptId) {
      awayRef.current = false
      setLocked(false)
      return
    }

    const persist = (count: number) => {
      sessionStorage.setItem(storageKey(attemptId), String(count))
    }

    const markAway = () => {
      if (awayRef.current) return
      awayRef.current = true
      leavesRef.current += 1
      persist(leavesRef.current)
      setLeaves(leavesRef.current)
    }

    const markBack = () => {
      if (!awayRef.current) return
      awayRef.current = false
      setLocked(true)
      if (leavesRef.current >= MAX_QUIZ_TAB_LEAVES) {
        setPenalized(true)
        onRepeatLeaveRef.current()
      }
    }

    const onVisibility = () => {
      if (document.visibilityState === 'hidden') markAway()
      else markBack()
    }

    const onPageHide = () => markAway()
    const onPageShow = () => {
      if (document.visibilityState === 'visible') markBack()
    }
    const onBeforeUnload = (event: BeforeUnloadEvent) => {
      event.preventDefault()
      event.returnValue = ''
    }

    document.addEventListener('visibilitychange', onVisibility)
    window.addEventListener('pagehide', onPageHide)
    window.addEventListener('pageshow', onPageShow)
    window.addEventListener('beforeunload', onBeforeUnload)

    return () => {
      document.removeEventListener('visibilitychange', onVisibility)
      window.removeEventListener('pagehide', onPageHide)
      window.removeEventListener('pageshow', onPageShow)
      window.removeEventListener('beforeunload', onBeforeUnload)
    }
  }, [active, attemptId])

  const dismiss = useCallback(() => {
    setLocked(false)
    setPenalized(false)
    void requestQuizFullscreen()
  }, [])

  return { locked, leaves, penalized, maxLeaves: MAX_QUIZ_TAB_LEAVES, dismiss }
}

export async function requestQuizFullscreen() {
  const node = document.documentElement as HTMLElement & {
    webkitRequestFullscreen?: () => Promise<void> | void
  }
  if (document.fullscreenElement) return
  try {
    if (node.requestFullscreen) await node.requestFullscreen()
    else await node.webkitRequestFullscreen?.()
  } catch {
    // Browser may deny fullscreen; the tab-visibility lock still applies.
  }
}

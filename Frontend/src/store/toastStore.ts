import { create } from 'zustand'

export type ToastTone = 'success' | 'warning' | 'danger' | 'info'

export type Toast = {
  id: string
  title: string
  description?: string
  tone: ToastTone
}

type ToastState = {
  toasts: Toast[]
  push: (toast: Omit<Toast, 'id'> & { id?: string }) => string
  dismiss: (id: string) => void
}

export const useToastStore = create<ToastState>((set) => ({
  toasts: [],
  push: (toast) => {
    const id = toast.id ?? crypto.randomUUID()
    set((state) => ({
      toasts: [...state.toasts, { ...toast, id }],
    }))
    window.setTimeout(() => {
      set((state) => ({ toasts: state.toasts.filter((item) => item.id !== id) }))
    }, 5200)
    return id
  },
  dismiss: (id) => set((state) => ({ toasts: state.toasts.filter((item) => item.id !== id) })),
}))

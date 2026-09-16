import { apiRequest } from '@/api/client'
import { useAuthStore } from '@/store/authStore'
import type { AuthUser, LoginResponse } from '@/types/api'

export async function login(email: string, password: string) {
  const response = await apiRequest<LoginResponse>('/auth/login', {
    method: 'POST',
    body: { email, password },
  })

  useAuthStore.getState().setSession(response.data.token, response.data.user)

  try {
    const me = await fetchMe()
    const user: AuthUser = {
      ...response.data.user,
      ...me.data,
      id: me.data.id || response.data.user.id,
      uuid: me.data.uuid || response.data.user.uuid,
    }
    useAuthStore.getState().setSession(response.data.token, user)
    return { ...response, data: { ...response.data, user } }
  } catch {
    return response
  }
}

export function logout() {
  return apiRequest<null>('/auth/logout', { method: 'POST' })
}

export function fetchMe() {
  return apiRequest<AuthUser>('/user')
}

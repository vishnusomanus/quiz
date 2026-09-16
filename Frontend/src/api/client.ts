import { useAuthStore } from '@/store/authStore'
import type { ApiFailure, ApiSuccess } from '@/types/api'

export const API_BASE = import.meta.env.VITE_API_BASE_URL ?? '/api/v1'

export class ApiError extends Error {
  status: number
  payload: ApiFailure | null

  constructor(message: string, status: number, payload: ApiFailure | null = null) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.payload = payload
  }
}

type RequestOptions = Omit<RequestInit, 'body'> & {
  body?: unknown
  params?: Record<string, string | number | boolean | null | undefined>
}

function buildUrl(path: string, params?: RequestOptions['params']) {
  const url = path.startsWith('http') ? new URL(path) : new URL(`${API_BASE}${path}`, window.location.origin)
  if (params) {
    Object.entries(params).forEach(([key, value]) => {
      if (value === undefined || value === null || value === '') return
      url.searchParams.set(key, String(value))
    })
  }
  return `${url.pathname}${url.search}`
}

export async function apiRequest<T>(path: string, options: RequestOptions = {}): Promise<ApiSuccess<T>> {
  const { body, params, headers, ...rest } = options
  const token = useAuthStore.getState().token
  const response = await fetch(buildUrl(path, params), {
    ...rest,
    headers: {
      Accept: 'application/json',
      ...(body !== undefined ? { 'Content-Type': 'application/json' } : {}),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...headers,
    },
    body: body !== undefined ? JSON.stringify(body) : undefined,
  })

  let payload: ApiSuccess<T> | ApiFailure | null = null
  try {
    payload = (await response.json()) as ApiSuccess<T> | ApiFailure
  } catch {
    payload = null
  }

  if (response.status === 401) {
    useAuthStore.getState().clearSession()
  }

  if (!response.ok || !payload || payload.status === false) {
    const failure = payload && payload.status === false ? payload : null
    throw new ApiError(failure?.message || 'Request failed', response.status, failure)
  }

  return payload
}

export function validationMessage(error: unknown) {
  if (error instanceof ApiError) {
    const errors = error.payload?.errors
    if (errors && typeof errors === 'object') {
      const first = Object.values(errors as Record<string, unknown>)[0]
      if (Array.isArray(first) && typeof first[0] === 'string') return first[0]
      if (typeof first === 'string') return first
    }
    return error.message
  }
  if (error instanceof Error) return error.message
  return 'Something went wrong'
}

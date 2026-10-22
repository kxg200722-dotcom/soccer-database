import type { AuthUser } from '@/stores/auth.store'
import { get, post } from './http'

export interface LoginResult {
  token: string
  user: AuthUser
}

export function loginApi(data: { email: string, password: string }): Promise<LoginResult> {
  return post<LoginResult>('/auth/login', data)
}

export function logoutApi(): Promise<unknown> {
  return post('/auth/logout')
}

export function meApi(): Promise<AuthUser> {
  return get<AuthUser>('/auth/me')
}

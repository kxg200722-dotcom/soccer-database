import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

export interface AuthUser {
  id: number
  name: string
  email: string
  is_admin: boolean
  permissions: string[]
}

const TOKEN_KEY = 'live_admin_token'
const USER_KEY = 'live_admin_user'

function readUser(): AuthUser | null {
  try {
    const raw = localStorage.getItem(USER_KEY)
    return raw ? (JSON.parse(raw) as AuthUser) : null
  }
  catch {
    return null
  }
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string>(localStorage.getItem(TOKEN_KEY) ?? '')
  const user = ref<AuthUser | null>(readUser())

  const isLoggedIn = computed(() => !!token.value)

  function setAuth(newToken: string, newUser: AuthUser): void {
    token.value = newToken
    user.value = newUser
    localStorage.setItem(TOKEN_KEY, newToken)
    localStorage.setItem(USER_KEY, JSON.stringify(newUser))
  }

  function setUser(newUser: AuthUser): void {
    user.value = newUser
    localStorage.setItem(USER_KEY, JSON.stringify(newUser))
  }

  /** 仅清理本地状态；远端登出由调用方先调 /auth/logout（避免拦截器循环调用） */
  function logout(): void {
    token.value = ''
    user.value = null
    localStorage.removeItem(TOKEN_KEY)
    localStorage.removeItem(USER_KEY)
  }

  return { token, user, isLoggedIn, setAuth, setUser, logout }
})

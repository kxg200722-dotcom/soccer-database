import { defineStore } from 'pinia'
import { computed } from 'vue'
import { useAuthStore } from './auth.store'

export const usePermissionStore = defineStore('permission', () => {
  const auth = useAuthStore()

  const permissions = computed<string[]>(() => auth.user?.permissions ?? [])
  const isAdmin = computed<boolean>(() => auth.user?.is_admin ?? false)

  /** admin 放行所有 live:* 权限点（与后端 EnsurePermission 一致） */
  function has(code: string): boolean {
    return isAdmin.value || permissions.value.includes(code)
  }

  return { permissions, isAdmin, has }
})

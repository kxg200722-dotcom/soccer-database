import type { Directive } from 'vue'
import { usePermissionStore } from '@/stores/permission.store'

function check(el: HTMLElement, code: string | undefined): void {
  if (!code) return
  const permission = usePermissionStore()
  if (!permission.has(code)) {
    el.parentNode?.removeChild(el)
  }
}

/** v-permission="'live:match:create'"：无权限时移除元素，updated 时随权限变化重检 */
export const vPermission: Directive<HTMLElement, string> = {
  mounted(el, binding) {
    check(el, binding.value)
  },
  updated(el, binding) {
    check(el, binding.value)
  },
}

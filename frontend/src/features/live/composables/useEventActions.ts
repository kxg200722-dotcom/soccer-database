import type { EventActionResult, EventPayload, EventUpdatePayload, MatchEventItem } from '../types/live'
import { ElMessage } from 'element-plus'
import { ref, type Ref } from 'vue'
import {
  createMatchEvent,
  deleteMatchEvent,
  fetchMatchEvents,
  updateMatchEvent,
} from '../services/liveAdminApi'

/**
 * 事件增删改：
 * - add 时 crypto.randomUUID() 作 client_token 防重；40009 提示"请勿重复提交"且不重发
 * - 成功用返回值（event + 最新比分/version）局部更新，不整页刷新
 */
export function useEventActions(matchId: Ref<number>) {
  const events = ref<MatchEventItem[]>([])
  const loading = ref(false)
  const submitting = ref(false)

  async function load(): Promise<void> {
    loading.value = true
    try {
      events.value = await fetchMatchEvents(matchId.value)
    }
    finally {
      loading.value = false
    }
  }

  /** 返回事件动作结果（含最新比分/version），40009 时返回 undefined */
  async function add(payload: Omit<EventPayload, 'client_token'>): Promise<EventActionResult | undefined> {
    if (submitting.value) return undefined
    submitting.value = true
    try {
      const result = await createMatchEvent(matchId.value, {
        ...payload,
        client_token: crypto.randomUUID(),
      })
      if (result.event) {
        events.value = [...events.value, result.event].sort((a, b) =>
          a.minute === b.minute ? a.id - b.id : a.minute - b.minute,
        )
      }
      return result
    }
    catch (e) {
      if ((e as { code?: number }).code === 40009) {
        ElMessage.warning('请勿重复提交')
        return undefined
      }
      throw e
    }
    finally {
      submitting.value = false
    }
  }

  async function update(eventId: number, payload: EventUpdatePayload): Promise<EventActionResult> {
    const result = await updateMatchEvent(eventId, payload)
    if (result.event) {
      events.value = events.value
        .map(item => (item.id === eventId ? result.event! : item))
        .sort((a, b) => (a.minute === b.minute ? a.id - b.id : a.minute - b.minute))
    }
    return result
  }

  async function remove(eventId: number): Promise<EventActionResult> {
    const result = await deleteMatchEvent(eventId)
    events.value = events.value.filter(item => item.id !== eventId)
    return result
  }

  return { events, loading, submitting, load, add, update, remove }
}

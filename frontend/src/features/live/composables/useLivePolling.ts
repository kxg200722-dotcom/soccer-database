import type { MatchDetail, MatchEventItem } from '../types/live'
import { onUnmounted, ref, watch, type Ref } from 'vue'
import { fetchPublicMatchDetail, fetchPublicMatchEvents } from '../services/livePublicApi'

const POLL_INTERVAL = 10_000

/**
 * 直播观看页轮询：
 * - 10s setInterval，事件用 since_id 增量拉取，详情同节拍刷新（比分/状态）
 * - 页面隐藏暂停轮询，重新可见立即补刷
 * - finished 自动停止；onUnmounted 清理定时器与监听
 */
export function useLivePolling(matchId: Ref<number>) {
  const detail = ref<MatchDetail>()
  const events = ref<MatchEventItem[]>([])
  const loading = ref(true)
  const error = ref<unknown>(null)

  let sinceId = 0
  let timer: ReturnType<typeof setInterval> | null = null

  async function tick(): Promise<void> {
    try {
      const [d, incremental] = await Promise.all([
        fetchPublicMatchDetail(matchId.value),
        fetchPublicMatchEvents(matchId.value, sinceId),
      ])
      detail.value = d
      if (incremental.length > 0) {
        sinceId = incremental[incremental.length - 1].id
        // 合并后按 (minute, id) 重排，容忍迟到事件
        events.value = [...events.value, ...incremental].sort((a, b) =>
          a.minute === b.minute ? a.id - b.id : a.minute - b.minute,
        )
      }
      if (d.status === 'finished') {
        stop()
      }
    }
    catch (e) {
      error.value = e
    }
    finally {
      loading.value = false
    }
  }

  function start(): void {
    if (timer !== null) return
    void tick()
    timer = setInterval(() => void tick(), POLL_INTERVAL)
  }

  function stop(): void {
    if (timer !== null) {
      clearInterval(timer)
      timer = null
    }
  }

  function onVisibilityChange(): void {
    if (document.hidden) {
      stop()
    }
    else {
      start()
    }
  }

  document.addEventListener('visibilitychange', onVisibilityChange)

  watch(matchId, () => {
    stop()
    events.value = []
    sinceId = 0
    loading.value = true
    error.value = null
    start()
  })

  start()

  onUnmounted(() => {
    stop()
    document.removeEventListener('visibilitychange', onVisibilityChange)
  })

  return { detail, events, loading, error }
}

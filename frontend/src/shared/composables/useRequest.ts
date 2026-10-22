import { ref, type Ref } from 'vue'

/** 统一 loading/error/data，并用序号防止竞态（只接受最后一次请求结果） */
export function useRequest<A extends unknown[], R>(fn: (...args: A) => Promise<R>) {
  const data = ref<R>() as Ref<R | undefined>
  const error = ref<unknown>(null)
  const loading = ref(false)
  let seq = 0

  async function run(...args: A): Promise<R | undefined> {
    const current = ++seq
    loading.value = true
    error.value = null
    try {
      const result = await fn(...args)
      if (current === seq) data.value = result
      return result
    }
    catch (e) {
      if (current === seq) error.value = e
      return undefined
    }
    finally {
      if (current === seq) loading.value = false
    }
  }

  return { data, error, loading, run }
}

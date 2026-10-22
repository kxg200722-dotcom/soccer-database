import { onUnmounted, ref } from 'vue'

/**
 * 上传通用：uploading 状态 + 卸载时中断未完成的请求
 * uploader 由调用方提供（通常走 adminApi 的 FormData 上传）
 */
export function useUpload<A extends unknown[], R>(
  uploader: (signal: AbortSignal, ...args: A) => Promise<R>,
) {
  const uploading = ref(false)
  let controller: AbortController | null = null

  async function upload(...args: A): Promise<R | undefined> {
    controller?.abort()
    controller = new AbortController()
    uploading.value = true
    try {
      return await uploader(controller.signal, ...args)
    }
    finally {
      uploading.value = false
    }
  }

  onUnmounted(() => {
    controller?.abort()
    controller = null
  })

  return { uploading, upload }
}

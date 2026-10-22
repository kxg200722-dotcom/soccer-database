import axios, { AxiosError, type AxiosInstance, type AxiosRequestConfig } from 'axios'
import { ElMessage } from 'element-plus'
import { useAuthStore } from '@/stores/auth.store'
import router from '@/app/router'

/** 与后端 Result 统一响应结构对齐 */
export interface ApiResult<T = unknown> {
  code: number
  msg: string
  data: T
  success: boolean
  trace_id: string
}

export interface PageQuery {
  page?: number
  page_size?: number
  [key: string]: unknown
}

export interface SimplePage<T> {
  list: T[]
  next_page: number | null
}

const http: AxiosInstance = axios.create({
  baseURL: '/api',
  timeout: 15000,
})

http.interceptors.request.use((config) => {
  const auth = useAuthStore()
  if (auth.token) {
    config.headers.Authorization = `Bearer ${auth.token}`
  }
  return config
})

/** 业务异常：code 非 0 时抛出，携带 code 便于页面按码处理 */
export class ApiError extends Error {
  code: number
  traceId: string

  constructor(result: ApiResult) {
    super(result.msg || '请求失败')
    this.code = result.code
    this.traceId = result.trace_id
  }
}

http.interceptors.response.use(
  (response) => {
    // 文件下载等 blob 响应直接放行
    if (response.config.responseType === 'blob') return response
    const result = response.data as ApiResult
    if (result && typeof result.code === 'number') {
      if (result.code === 0) return response
      handleErrorCode(result)
      return Promise.reject(new ApiError(result))
    }
    return response
  },
  (error: AxiosError) => {
    if (error.code === 'ECONNABORTED') {
      ElMessage.error('请求超时，请稍后重试')
    } else if (error.response) {
      ElMessage.error(`服务异常（${error.response.status}），请稍后重试`)
    } else {
      ElMessage.error('网络异常，请检查连接')
    }
    return Promise.reject(error)
  },
)

/** 按响应码段统一提示；仅 20001（未登录/Token 失效）做全局登出跳转 */
function handleErrorCode(result: ApiResult): void {
  const { code, msg, trace_id: traceId } = result
  if (code === 20001) {
    const auth = useAuthStore()
    auth.logout()
    if (router.currentRoute.value.path.startsWith('/admin')) {
      router.push({ path: '/admin/login', query: { redirect: router.currentRoute.value.fullPath } })
    }
    ElMessage.error(msg || '请先登录')
    return
  }
  // 系统级错误附带 trace_id 便于排查
  if (code >= 90000) {
    ElMessage.error(`${msg || '系统异常'}（trace: ${traceId}）`)
    return
  }
  ElMessage.error(msg || `操作失败（${code}）`)
}

/** 解包请求：成功返回 data，失败抛 ApiError（拦截器已统一提示） */
export async function request<T = unknown>(config: AxiosRequestConfig): Promise<T> {
  const response = await http.request<ApiResult<T>>(config)
  return response.data.data
}

export function get<T = unknown>(url: string, params?: Record<string, unknown>): Promise<T> {
  return request<T>({ method: 'GET', url, params })
}

export function post<T = unknown>(url: string, data?: unknown): Promise<T> {
  return request<T>({ method: 'POST', url, data })
}

export function put<T = unknown>(url: string, data?: unknown): Promise<T> {
  return request<T>({ method: 'PUT', url, data })
}

export function patch<T = unknown>(url: string, data?: unknown): Promise<T> {
  return request<T>({ method: 'PATCH', url, data })
}

export function del<T = unknown>(url: string): Promise<T> {
  return request<T>({ method: 'DELETE', url })
}

export default http

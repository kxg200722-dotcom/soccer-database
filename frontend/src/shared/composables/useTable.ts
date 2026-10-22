import type { SimplePage } from '@/shared/api/http'
import { reactive, ref, type Ref } from 'vue'
import { usePagination } from './usePagination'

type Query = Record<string, unknown>

/**
 * 列表页通用：loading + list + pagination + searchForm + reset
 * fetcher 返回 simplePaginate 结构 { list, next_page }
 */
export function useTable<T, Q extends Query = Query>(
  fetcher: (params: Q & { page: number, page_size: number }) => Promise<SimplePage<T>>,
  initialQuery: Q,
) {
  const loading = ref(false)
  const list = ref<T[]>([]) as Ref<T[]>
  const searchForm = reactive({ ...initialQuery }) as Q
  const { page, pageSize, resetPage } = usePagination()
  const nextPage = ref<number | null>(null)

  async function load(): Promise<void> {
    loading.value = true
    try {
      const result = await fetcher({ ...searchForm, page: page.value, page_size: pageSize.value })
      list.value = result.list
      nextPage.value = result.next_page
    }
    finally {
      loading.value = false
    }
  }

  /** 查询：回到第一页 */
  function search(): void {
    resetPage()
    void load()
  }

  /** 重置条件并查询 */
  function reset(): void {
    (Object.keys(searchForm) as (keyof Q)[]).forEach((key) => {
      searchForm[key] = initialQuery[key]
    })
    search()
  }

  return { loading, list, searchForm, page, pageSize, nextPage, load, search, reset }
}

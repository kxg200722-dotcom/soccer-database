import { ref } from 'vue'

/** 仅维护页码/条数（simplePaginate 无总数） */
export function usePagination(initialPageSize = 15) {
  const page = ref(1)
  const pageSize = ref(initialPageSize)

  function resetPage(): void {
    page.value = 1
  }

  return { page, pageSize, resetPage }
}

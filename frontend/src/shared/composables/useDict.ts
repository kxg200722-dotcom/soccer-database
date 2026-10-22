import type { DictItem, DictType, TagType } from '@/stores/dict.store'
import { useDictStore } from '@/stores/dict.store'
import { computed } from 'vue'

/** Store 存、Composable 取：字典读取入口 */
export function useDict(...types: DictType[]) {
  const dictStore = useDictStore()

  const dictOptions = computed(() => {
    const map = {} as Record<DictType, DictItem[]>
    types.forEach((type) => {
      map[type] = dictStore.options(type)
    })
    return map
  })

  function getLabel(type: DictType, value: string | null | undefined): string {
    return dictStore.label(type, value)
  }

  function getTagType(type: DictType, value: string | null | undefined): TagType {
    return dictStore.tagType(type, value)
  }

  return { dictOptions, getLabel, getTagType }
}

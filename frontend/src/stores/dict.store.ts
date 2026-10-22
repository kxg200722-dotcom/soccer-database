import { defineStore } from 'pinia'

export type TagType = 'primary' | 'success' | 'warning' | 'danger' | 'info'

export interface DictItem {
  value: string
  label: string
  tagType?: TagType
}

export type DictType = 'group_type' | 'match_status' | 'event_type' | 'file_type' | 'referee_role' | 'verify_result'

/** 字典集中维护（方案：useDictStore），组件内禁止重复定义 */
const DICTS: Record<DictType, DictItem[]> = {
  group_type: [
    { value: 'university', label: '大学组' },
    { value: 'middle_school', label: '中学组' },
    { value: 'primary_school', label: '小学组' },
  ],
  match_status: [
    { value: 'not_started', label: '待定', tagType: 'info' },
    { value: 'live', label: '直播中', tagType: 'danger' },
    { value: 'finished', label: '已完赛', tagType: 'success' },
  ],
  event_type: [
    { value: 'goal', label: '进球', tagType: 'success' },
    { value: 'yellow', label: '黄牌', tagType: 'warning' },
    { value: 'red', label: '红牌', tagType: 'danger' },
    { value: 'substitution', label: '换人', tagType: 'primary' },
    { value: 'text', label: '图文', tagType: 'info' },
  ],
  file_type: [
    { value: 'sign_in', label: '签到表' },
    { value: 'record_confirm', label: '记录集签字确认表' },
    { value: 'referee_report', label: '裁判员报告书' },
  ],
  referee_role: [
    { value: 'chief', label: '主裁判' },
    { value: 'assistant', label: '助理裁判' },
    { value: 'fourth', label: '第四官员' },
  ],
  verify_result: [
    { value: 'pass', label: '通过', tagType: 'success' },
    { value: 'fail', label: '未通过', tagType: 'danger' },
    { value: 'pending', label: '待核验', tagType: 'warning' },
  ],
}

export const useDictStore = defineStore('dict', () => {
  function options(type: DictType): DictItem[] {
    return DICTS[type]
  }

  function find(type: DictType, value: string | null | undefined): DictItem | undefined {
    if (value === null || value === undefined || value === '') return undefined
    return DICTS[type].find(item => item.value === value)
  }

  function label(type: DictType, value: string | null | undefined): string {
    return find(type, value)?.label ?? value ?? '-'
  }

  function tagType(type: DictType, value: string | null | undefined): TagType {
    return find(type, value)?.tagType ?? 'info'
  }

  return { options, label, tagType }
})

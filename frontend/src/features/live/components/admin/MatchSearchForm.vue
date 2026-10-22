<script setup lang="ts">
import type { GroupType, MatchStatus } from '../../types/live'
import { useDict } from '@/shared/composables/useDict'
import { ref, watch } from 'vue'

const props = defineProps<{
  modelValue: { group_type: GroupType | '', status: MatchStatus | '', keyword: string }
  loading?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: typeof props.modelValue]
  search: []
  reset: []
}>()

const { dictOptions } = useDict('group_type', 'match_status')

/** 输入搜索 300ms 防抖 */
const keyword = ref(props.modelValue.keyword)
let debounceTimer: ReturnType<typeof setTimeout> | null = null

watch(keyword, (value) => {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    emit('update:modelValue', { ...props.modelValue, keyword: value })
    emit('search')
  }, 300)
})

function onSelectChange(key: 'group_type' | 'status', value: string): void {
  emit('update:modelValue', { ...props.modelValue, [key]: value })
  emit('search')
}

function onReset(): void {
  keyword.value = ''
  emit('reset')
}
</script>

<template>
  <div class="match-search-form">
    <el-input
      v-model="keyword"
      placeholder="搜索主队/客队名称"
      clearable
      class="keyword"
      @clear="onReset"
    />
    <el-select
      :model-value="modelValue.group_type"
      placeholder="全部组别"
      clearable
      class="select"
      @change="(v: string) => onSelectChange('group_type', v ?? '')"
    >
      <el-option
        v-for="item in dictOptions.group_type"
        :key="item.value"
        :label="item.label"
        :value="item.value"
      />
    </el-select>
    <el-select
      :model-value="modelValue.status"
      placeholder="全部状态"
      clearable
      class="select"
      @change="(v: string) => onSelectChange('status', v ?? '')"
    >
      <el-option
        v-for="item in dictOptions.match_status"
        :key="item.value"
        :label="item.label"
        :value="item.value"
      />
    </el-select>
    <el-button :disabled="loading" @click="onReset">重置</el-button>
  </div>
</template>

<style scoped>
.match-search-form {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
}

.keyword {
  width: 240px;
}

.select {
  width: 150px;
}
</style>

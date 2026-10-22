<script setup lang="ts">
import type { DictType } from '@/stores/dict.store'
import { useDict } from '@/shared/composables/useDict'

const props = defineProps<{
  dict: DictType
  value: string | null | undefined
}>()

const { getLabel, getTagType } = useDict(props.dict)
</script>

<template>
  <el-tag :type="getTagType(dict, value)" disable-transitions>
    {{ getLabel(dict, value) }}
  </el-tag>
</template>

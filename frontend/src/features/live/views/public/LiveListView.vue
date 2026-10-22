<script setup lang="ts">
import type { BracketData } from '../../composables/bracketMock'
import { generateDemoBracket } from '../../composables/bracketMock'
import BracketTree from '../../components/public/BracketTree.vue'
import { computed, onMounted, ref } from 'vue'
import { useDict } from '@/shared/composables/useDict'
import EmptyBox from '@/shared/ui/EmptyBox.vue'

const { dictOptions } = useDict('group_type')

/** 当前选中组别，切换时重新生成骨架（演示不同队池） */
const selectedGroup = ref('group_middle')

const groupOption = computed(() => dictOptions.value?.group_type ?? [])
const groupLabelMap = computed(() => {
  const m = new Map<string, string>()
  for (const g of groupOption.value) m.set(g.value, g.label)
  return m
})

/** 根据选中组别生成 demo 树 */
const bracketData = ref<BracketData | null>(null)
function regen(): void {
  // mock 里的 pool key 和 dict 值对齐：dict 是 university/middle_school/primary_school
  // 我们的 TEAM_POOLS key 是 group_university 等，这里做映射
  const key = `group_${selectedGroup.value}`
  bracketData.value = generateDemoBracket(key)
}

onMounted(() => regen())
</script>

<template>
  <div class="bracket-page">
    <header class="page-header">
      <div class="title-row">
        <h1>赛事对阵</h1>
        <el-tag type="warning" effect="plain" size="small">演示数据</el-tag>
      </div>
      <p class="subtitle">双败淘汰 · 16 强 · {{ groupLabelMap.get(selectedGroup) ?? selectedGroup }}</p>
    </header>

    <div class="toolbar">
      <span class="toolbar-label">组别：</span>
      <el-radio-group v-model="selectedGroup" @change="regen" size="small">
        <el-radio-button
          v-for="g in groupOption"
          :key="g.value"
          :label="g.value"
        >{{ g.label }}</el-radio-button>
      </el-radio-group>
    </div>

    <el-alert
      type="warning"
      :closable="false"
      show-icon
      title="演示数据说明"
      description="当前页面使用随机生成的 16 强对阵与比分，用于树状展示参考。真实数据接入时，后端提供 /api/public/live/bracket 接口（返回 BracketData 结构），前端替换数据源即可。"
      class="demo-alert"
    />

    <div v-if="bracketData" class="tree-wrap">
      <BracketTree :data="bracketData" />
    </div>
    <EmptyBox v-else description="加载中..." />
  </div>
</template>

<style scoped>
.bracket-page {
  max-width: none;
  padding: 20px 16px 32px;
}

.page-header {
  margin-bottom: 16px;
}

.title-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 4px;
}

.title-row h1 {
  margin: 0;
  font-size: 22px;
  font-weight: 700;
  letter-spacing: .5px;
}

.subtitle {
  margin: 0;
  font-size: 13px;
  color: var(--el-text-color-secondary);
}

.toolbar {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}

.toolbar-label {
  font-size: 13px;
  color: var(--el-text-color-secondary);
}

.demo-alert {
  margin-bottom: 14px;
}

.tree-wrap {
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 8px;
  background: var(--el-bg-color, #fff);
  overflow: hidden;
}
</style>

<script setup lang="ts">
import type { MatchStatus, RefereeRole } from '../../types/live'
import type { RefereeRecord } from '../../services/liveAdminApi'
import { useDict } from '@/shared/composables/useDict'
import { ElMessage } from 'element-plus'
import { computed, onMounted, ref } from 'vue'
import { fetchMatchReferees, replaceMatchReferees } from '../../services/liveAdminApi'

const props = withDefaults(defineProps<{
  matchId: number
  status: MatchStatus
}>(), {})

const { dictOptions, getLabel } = useDict('referee_role')

interface Row {
  role: RefereeRole | ''
  name: string
}

const rows = ref<Row[]>([])
const loading = ref(false)
const saving = ref(false)

const readonly = computed(() => props.status === 'finished')

/** 角色人数上限（与服务端 RefereeRole::maxCount 一致） */
const ROLE_MAX: Record<RefereeRole, number> = { chief: 1, assistant: 2, fourth: 1 }

function roleCount(role: RefereeRole): number {
  return rows.value.filter(r => r.role === role).length
}

function roleExceeded(role: RefereeRole | ''): boolean {
  return !!role && roleCount(role) > ROLE_MAX[role]
}

const hasInvalid = computed(() =>
  rows.value.some(r => !r.role || !r.name.trim()) || rows.value.some(r => roleExceeded(r.role)),
)

function addRow(): void {
  rows.value.push({ role: '', name: '' })
}

function removeRow(index: number): void {
  rows.value.splice(index, 1)
}

async function load(): Promise<void> {
  loading.value = true
  try {
    const list: RefereeRecord[] = await fetchMatchReferees(props.matchId)
    rows.value = list.map(r => ({ role: r.role, name: r.name }))
  }
  finally {
    loading.value = false
  }
}

async function save(): Promise<void> {
  if (hasInvalid.value) {
    ElMessage.warning('请完善裁判信息，且角色人数不能超限')
    return
  }
  saving.value = true
  try {
    const list = await replaceMatchReferees(
      props.matchId,
      rows.value.map(r => ({ role: r.role as RefereeRole, name: r.name.trim() })),
    )
    rows.value = list.map(r => ({ role: r.role, name: r.name }))
    ElMessage.success('裁判配置已保存')
  }
  catch {
    // 拦截器已统一提示（10001/40002）
  }
  finally {
    saving.value = false
  }
}

onMounted(() => void load())
</script>

<template>
  <div v-loading="loading" class="referee-form-panel">
    <div class="toolbar">
      <el-button :disabled="readonly" @click="addRow">添加裁判</el-button>
      <span class="hint">主裁判/第四官员各至多 1 人，助理裁判至多 2 人</span>
      <span v-if="readonly" class="hint">比赛已结束，不可修改</span>
    </div>

    <div v-for="(row, index) in rows" :key="index" class="referee-row">
      <el-select v-model="row.role" placeholder="角色" class="role" :disabled="readonly">
        <el-option
          v-for="item in dictOptions.referee_role"
          :key="item.value"
          :label="item.label"
          :value="item.value"
        />
      </el-select>
      <el-input v-model="row.name" placeholder="姓名" maxlength="50" class="name" :disabled="readonly" />
      <el-tag v-if="roleExceeded(row.role)" type="danger" size="small">
        {{ getLabel('referee_role', row.role) }}人数超限
      </el-tag>
      <el-button size="small" link type="danger" :disabled="readonly" @click="removeRow(index)">
        移除
      </el-button>
    </div>

    <div v-if="rows.length === 0 && !loading" class="empty">暂未配置裁判</div>

    <el-button
      v-permission="'live:referee:manage'"
      type="primary"
      :loading="saving"
      :disabled="readonly || hasInvalid"
      @click="save"
    >
      保存裁判配置
    </el-button>
  </div>
</template>

<style scoped>
.toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.hint {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.referee-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.role {
  width: 150px;
}

.name {
  width: 200px;
}

.empty {
  padding: 24px;
  text-align: center;
  color: var(--el-text-color-secondary);
  font-size: 13px;
}
</style>

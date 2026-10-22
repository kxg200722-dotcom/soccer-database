<script setup lang="ts">
import type { EventActionResult, MatchEventItem, MatchStatus } from '../../types/live'
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, onMounted, ref, toRef } from 'vue'
import { useEventActions } from '../../composables/useEventActions'
import EventFormDialog, { type EventFormValue } from './EventFormDialog.vue'
import EventTable from './EventTable.vue'

const props = withDefaults(defineProps<{
  matchId: number
  status: MatchStatus
  homeTeamName?: string
  awayTeamName?: string
}>(), {
  homeTeamName: '主队',
  awayTeamName: '客队',
})

const emit = defineEmits<{
  /** 事件动作后同步最新比分/version 给父级 */
  scoreChange: [result: EventActionResult]
}>()

const { events, loading, submitting, load, add, update, remove } = useEventActions(toRef(props, 'matchId'))

const dialogVisible = ref(false)
const editingEvent = ref<MatchEventItem | null>(null)

const readonly = computed(() => props.status === 'finished')

function openAdd(): void {
  editingEvent.value = null
  dialogVisible.value = true
}

function openEdit(event: MatchEventItem): void {
  editingEvent.value = event
  dialogVisible.value = true
}

async function onSubmit(value: EventFormValue): Promise<void> {
  try {
    const result = editingEvent.value
      ? await update(editingEvent.value.id, value)
      : await add(value)
    if (!result) return // 40009 已提示
    emit('scoreChange', result)
    ElMessage.success(editingEvent.value ? '事件已更新' : '事件已录入')
    dialogVisible.value = false
  }
  catch {
    // 拦截器已统一提示（10001/40002 等）
  }
}

async function onRemove(event: MatchEventItem): Promise<void> {
  try {
    await ElMessageBox.confirm(
      event.type === 'goal' ? '删除进球事件将回退对应比分，确认删除？' : '确认删除该事件？',
      '删除确认',
      { type: 'warning' },
    )
  }
  catch {
    return
  }
  try {
    const result = await remove(event.id)
    emit('scoreChange', result)
    ElMessage.success('事件已删除')
  }
  catch {
    // 拦截器已统一提示
  }
}

onMounted(() => void load())
</script>

<template>
  <div class="event-manage-panel">
    <div class="toolbar">
      <el-button
        v-permission="'live:event:manage'"
        type="primary"
        :disabled="readonly"
        @click="openAdd"
      >
        录入事件
      </el-button>
      <span v-if="readonly" class="hint">比赛已结束，事件已锁定</span>
    </div>

    <EventTable
      :events="events"
      :loading="loading"
      :readonly="readonly"
      :home-team-name="homeTeamName"
      :away-team-name="awayTeamName"
      @edit="openEdit"
      @remove="onRemove"
    />

    <EventFormDialog
      v-model="dialogVisible"
      :event="editingEvent"
      :submitting="submitting"
      :home-team-name="homeTeamName"
      :away-team-name="awayTeamName"
      @submit="onSubmit"
    />
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
</style>

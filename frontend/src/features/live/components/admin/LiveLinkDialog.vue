<script setup lang="ts">
import type { MatchStatus } from '../../types/live'
import { ElMessage } from 'element-plus'
import { computed, ref, watch } from 'vue'
import { updateLiveLink } from '../../services/liveAdminApi'

const props = defineProps<{
  matchId: number
  liveLink: string | null
  status: MatchStatus
}>()

const emit = defineEmits<{
  updated: [result: { live_link: string | null, version: number }]
}>()

const dialogVisible = ref(false)
const inputValue = ref('')
const saving = ref(false)

const readonly = computed(() => props.status === 'finished')

watch(dialogVisible, (visible) => {
  if (visible) inputValue.value = props.liveLink ?? ''
})

async function save(): Promise<void> {
  saving.value = true
  try {
    const result = await updateLiveLink(props.matchId, inputValue.value.trim() || null)
    emit('updated', result)
    ElMessage.success('直播链接已保存')
    dialogVisible.value = false
  }
  catch {
    // 拦截器已统一提示（10001 url 校验 / 40002）
  }
  finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="live-link-panel">
    <div class="current">
      <span class="label">当前链接：</span>
      <el-link v-if="liveLink" :href="liveLink" target="_blank" type="primary">{{ liveLink }}</el-link>
      <span v-else class="empty">未设置</span>
    </div>
    <el-button
      v-permission="'live:link:manage'"
      type="primary"
      :disabled="readonly"
      @click="dialogVisible = true"
    >
      {{ liveLink ? '修改链接' : '设置链接' }}
    </el-button>
    <span v-if="readonly" class="hint">比赛已结束，不可修改</span>

    <el-dialog v-model="dialogVisible" title="直播链接" width="480px">
      <el-input
        v-model="inputValue"
        placeholder="https://…（留空则清除链接）"
        maxlength="512"
        clearable
      />
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="save">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped>
.live-link-panel {
  display: flex;
  align-items: center;
  gap: 16px;
}

.current {
  display: flex;
  align-items: center;
  gap: 4px;
  min-width: 0;
}

.label {
  color: var(--el-text-color-secondary);
  font-size: 13px;
  white-space: nowrap;
}

.empty {
  color: var(--el-text-color-secondary);
  font-size: 13px;
}

.hint {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
</style>

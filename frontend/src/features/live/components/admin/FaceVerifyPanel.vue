<script setup lang="ts">
import type { FaceVerifyRecordItem } from '../../types/live'
import StatusTag from '@/shared/ui/StatusTag.vue'
import UploadButton from '@/shared/ui/UploadButton.vue'
import { ElMessage } from 'element-plus'
import { onMounted, reactive, ref } from 'vue'
import { fetchFaceVerifyRecords, submitFaceVerify } from '../../services/liveAdminApi'

const props = defineProps<{ matchId: number }>()

const records = ref<FaceVerifyRecordItem[]>([])
const loading = ref(false)
const submitting = ref(false)

const form = reactive<{ player_name: string, player_no: number | null, photo: File | null }>({
  player_name: '',
  player_no: null,
  photo: null,
})

const photoName = ref('')

function onPhotoSelect(file: File): void {
  form.photo = file
  photoName.value = file.name
}

async function load(): Promise<void> {
  loading.value = true
  try {
    records.value = await fetchFaceVerifyRecords(props.matchId)
  }
  finally {
    loading.value = false
  }
}

async function submit(): Promise<void> {
  if (!form.player_name.trim()) {
    ElMessage.warning('请输入球员姓名')
    return
  }
  if (!form.photo) {
    ElMessage.warning('请选择核验照片（jpg/png，必填）')
    return
  }
  submitting.value = true
  try {
    const record = await submitFaceVerify(props.matchId, {
      player_name: form.player_name.trim(),
      player_no: form.player_no,
      photo: form.photo,
    })
    records.value = [record, ...records.value]
    form.player_name = ''
    form.player_no = null
    form.photo = null
    photoName.value = ''
    ElMessage.success('核验完成')
  }
  catch {
    // 拦截器已统一提示（50008 mock 超时 / 10006）
  }
  finally {
    submitting.value = false
  }
}

onMounted(() => void load())
</script>

<template>
  <div class="face-verify-panel">
    <el-alert
      type="info"
      :closable="false"
      title="当前为 mock 核验通道：姓名含 timeout 将模拟第三方超时（50008）"
      class="mock-tip"
    />

    <div v-permission="'live:face:manage'" class="verify-form">
      <el-input v-model="form.player_name" placeholder="球员姓名" maxlength="50" class="name" />
      <el-input-number v-model="form.player_no" :min="0" :max="99" placeholder="号码（选填）" />
      <div class="photo">
        <UploadButton
          accept=".jpg,.jpeg,.png"
          :max-size-m-b="10"
          button-text="选择照片（必选）"
          @select="onPhotoSelect"
        />
        <span v-if="photoName" class="photo-name">{{ photoName }}</span>
      </div>
      <el-button type="primary" :loading="submitting" @click="submit">发起核验</el-button>
    </div>

    <el-table :data="records" v-loading="loading" border size="small">
      <el-table-column prop="player_name" label="球员" min-width="110" />
      <el-table-column label="号码" width="70" align="center">
        <template #default="{ row }">{{ row.player_no ?? '-' }}</template>
      </el-table-column>
      <el-table-column label="结果" width="90" align="center">
        <template #default="{ row }">
          <StatusTag dict="verify_result" :value="row.result" />
        </template>
      </el-table-column>
      <el-table-column label="相似度" width="90" align="right">
        <template #default="{ row }">{{ row.score ?? '-' }}</template>
      </el-table-column>
      <el-table-column prop="channel" label="通道" width="80" align="center" />
      <el-table-column prop="request_id" label="请求ID" min-width="130" show-overflow-tooltip>
        <template #default="{ row }">{{ row.request_id ?? '-' }}</template>
      </el-table-column>
      <el-table-column label="时间" width="160">
        <template #default="{ row }">{{ row.created_at?.slice(0, 19) }}</template>
      </el-table-column>
    </el-table>
  </div>
</template>

<style scoped>
.mock-tip {
  margin-bottom: 12px;
}

.verify-form {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 16px;
}

.name {
  width: 160px;
}

.photo {
  display: flex;
  align-items: center;
  gap: 8px;
}

.photo-name {
  font-size: 12px;
  color: var(--el-text-color-secondary);
  max-width: 160px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>

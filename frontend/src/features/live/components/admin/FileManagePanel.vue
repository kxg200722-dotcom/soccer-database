<script setup lang="ts">
import type { MatchFileItem, MatchFileType } from '../../types/live'
import { useDict } from '@/shared/composables/useDict'
import { useUpload } from '@/shared/composables/useUpload'
import EmptyBox from '@/shared/ui/EmptyBox.vue'
import UploadButton from '@/shared/ui/UploadButton.vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { onMounted, ref } from 'vue'
import {
  deleteMatchFile,
  downloadMatchFile,
  fetchMatchFiles,
  uploadMatchFile,
} from '../../services/liveAdminApi'

const props = defineProps<{ matchId: number }>()

const { dictOptions, getLabel } = useDict('file_type')

const files = ref<MatchFileItem[]>([])
const loading = ref(false)
const uploadType = ref<MatchFileType>('sign_in')
const downloadingId = ref<number | null>(null)

const { uploading, upload } = useUpload(
  (signal: AbortSignal, type: MatchFileType, file: File) =>
    uploadMatchFile(props.matchId, type, file, signal),
)

function formatSize(size: number): string {
  return size >= 1024 * 1024 ? `${(size / 1024 / 1024).toFixed(1)} MB` : `${Math.ceil(size / 1024)} KB`
}

async function load(): Promise<void> {
  loading.value = true
  try {
    files.value = await fetchMatchFiles(props.matchId)
  }
  finally {
    loading.value = false
  }
}

async function onSelectFile(file: File): Promise<void> {
  try {
    await upload(uploadType.value, file)
    ElMessage.success('上传成功')
    await load()
  }
  catch {
    // 拦截器已统一提示（10006/10007）
  }
}

async function onDownload(file: MatchFileItem): Promise<void> {
  downloadingId.value = file.id
  try {
    await downloadMatchFile(file.id, file.file_name)
  }
  catch {
    // 拦截器已统一提示
  }
  finally {
    downloadingId.value = null
  }
}

async function onRemove(file: MatchFileItem): Promise<void> {
  try {
    await ElMessageBox.confirm(`确认删除文件「${file.file_name}」？`, '删除确认', { type: 'warning' })
  }
  catch {
    return
  }
  try {
    await deleteMatchFile(file.id)
    ElMessage.success('文件已删除')
    await load()
  }
  catch {
    // 拦截器已统一提示
  }
}

onMounted(() => void load())
</script>

<template>
  <div class="file-manage-panel">
    <div v-permission="'live:file:manage'" class="toolbar">
      <el-select v-model="uploadType" class="type-select">
        <el-option
          v-for="item in dictOptions.file_type"
          :key="item.value"
          :label="item.label"
          :value="item.value"
        />
      </el-select>
      <UploadButton :loading="uploading" button-text="选择并上传" @select="onSelectFile" />
    </div>

    <el-table :data="files" v-loading="loading" border size="small">
      <el-table-column label="类型" width="170">
        <template #default="{ row }">{{ getLabel('file_type', row.type) }}</template>
      </el-table-column>
      <el-table-column prop="file_name" label="文件名" min-width="200" show-overflow-tooltip />
      <el-table-column label="大小" width="100" align="right">
        <template #default="{ row }">{{ formatSize(row.size) }}</template>
      </el-table-column>
      <el-table-column label="上传时间" width="160">
        <template #default="{ row }">{{ row.created_at?.slice(0, 16) }}</template>
      </el-table-column>
      <el-table-column label="操作" width="130" fixed="right">
        <template #default="{ row }">
          <el-button size="small" link type="primary" :loading="downloadingId === row.id" @click="onDownload(row)">
            下载
          </el-button>
          <el-button
            v-permission="'live:file:manage'"
            size="small"
            link
            type="danger"
            @click="onRemove(row)"
          >
            删除
          </el-button>
        </template>
      </el-table-column>
      <template #empty>
        <EmptyBox description="暂未上传文件" />
      </template>
    </el-table>
  </div>
</template>

<style scoped>
.toolbar {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 12px;
}

.type-select {
  width: 210px;
}
</style>

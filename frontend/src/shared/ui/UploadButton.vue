<script setup lang="ts">
import type { UploadRequestOptions } from 'element-plus'
import { computed } from 'vue'

const props = withDefaults(defineProps<{
  /** 逗号分隔的扩展名，如 ".pdf,.jpg,.png" */
  accept?: string
  /** 大小上限（MB），超出本地拦截 */
  maxSizeMb?: number
  loading?: boolean
  buttonText?: string
}>(), {
  accept: '.pdf,.jpg,.jpeg,.png',
  maxSizeMb: 10,
  loading: false,
  buttonText: '上传文件',
})

const emit = defineEmits<{
  select: [file: File]
}>()

const tip = computed(() => `支持 ${props.accept}，不超过 ${props.maxSizeMb}MB`)

function beforeUpload(rawFile: File): boolean {
  if (rawFile.size > props.maxSizeMb * 1024 * 1024) {
    return false
  }
  return true
}

/** 不使用 el-upload 内置 action，由父组件自定义上传逻辑 */
function handleRequest(options: UploadRequestOptions): void {
  emit('select', options.file as File)
}
</script>

<template>
  <el-upload
    :show-file-list="false"
    :accept="accept"
    :before-upload="beforeUpload"
    :http-request="handleRequest"
  >
    <el-button type="primary" :loading="loading">{{ buttonText }}</el-button>
    <template #tip>
      <div class="upload-tip">{{ tip }}</div>
    </template>
  </el-upload>
</template>

<style scoped>
.upload-tip {
  margin-top: 4px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
</style>

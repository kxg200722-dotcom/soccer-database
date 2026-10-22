<script setup lang="ts">
import { VideoPlay } from '@element-plus/icons-vue'

defineProps<{ liveLink: string | null }>()
</script>

<template>
  <div v-if="liveLink" class="live-link-entry">
    <el-button type="danger" :icon="VideoPlay" tag="a" :href="liveLink" target="_blank">
      观看视频直播
    </el-button>
  </div>
</template>

<style scoped>
.live-link-entry {
  display: flex;
  justify-content: center;
  margin: 12px 0;
}
</style>

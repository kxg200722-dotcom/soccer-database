<script setup lang="ts">
withDefaults(defineProps<{ description?: string }>(), {
  description: '暂无数据',
})
</script>

<template>
  <div class="empty-box">
    <el-empty :description="description" :image-size="80" />
  </div>
</template>

<style scoped>
.empty-box {
  display: flex;
  justify-content: center;
  padding: 24px 0;
}
</style>

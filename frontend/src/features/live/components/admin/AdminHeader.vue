<script setup lang="ts">
import { logoutApi } from '@/shared/api/authApi'
import { useAuthStore } from '@/stores/auth.store'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const router = useRouter()

async function onLogout(): Promise<void> {
  try {
    await logoutApi()
  }
  catch {
    // 远端登出失败不阻塞本地清理
  }
  auth.logout()
  router.push('/admin/login')
}
</script>

<template>
  <div class="admin-header">
    <span class="title">赛事直播管理</span>
    <div class="right">
      <el-link href="/live" target="_blank" class="portal-link">公众门户</el-link>
      <span class="user">{{ auth.user?.name }}</span>
      <el-button size="small" @click="onLogout">退出登录</el-button>
    </div>
  </div>
</template>

<style scoped>
.admin-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 20px;
  background: var(--el-bg-color);
  border-bottom: 1px solid var(--el-border-color-light);
}

.title {
  font-size: 16px;
  font-weight: 600;
}

.right {
  display: flex;
  align-items: center;
  gap: 16px;
}

.portal-link {
  font-size: 13px;
}

.user {
  font-size: 13px;
  color: var(--el-text-color-secondary);
}
</style>

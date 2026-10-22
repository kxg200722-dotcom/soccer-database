<script setup lang="ts">
import type { FormInstance, FormRules } from 'element-plus'
import { loginApi } from '@/shared/api/authApi'
import { useAuthStore } from '@/stores/auth.store'
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

const formRef = ref<FormInstance>()
const loading = ref(false)
const form = reactive({
  email: '',
  password: '',
})

const rules: FormRules = {
  email: [
    { required: true, message: '请输入邮箱', trigger: 'blur' },
    { type: 'email', message: '邮箱格式不正确', trigger: 'blur' },
  ],
  password: [{ required: true, message: '请输入密码', trigger: 'blur' }],
}

async function submit(): Promise<void> {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  loading.value = true
  try {
    const result = await loginApi({ email: form.email, password: form.password })
    auth.setAuth(result.token, result.user)
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/admin/live/matches'
    router.push(redirect)
  }
  catch {
    // 拦截器已统一提示（20002 密码错误等）
  }
  finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-page">
    <el-card class="login-card">
      <h2 class="login-title">
        赛事直播管理后台
      </h2>
      <el-form ref="formRef" :model="form" :rules="rules" label-position="top" @submit.prevent="submit">
        <el-form-item label="邮箱" prop="email">
          <el-input v-model="form.email" placeholder="admin@ball.local" autocomplete="username" />
        </el-form-item>
        <el-form-item label="密码" prop="password">
          <el-input
            v-model="form.password"
            type="password"
            show-password
            autocomplete="current-password"
            @keyup.enter="submit"
          />
        </el-form-item>
        <el-button type="primary" class="login-button" :loading="loading" @click="submit">
          登录
        </el-button>
      </el-form>
    </el-card>
  </div>
</template>

<style scoped>
.login-page {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background: var(--el-fill-color-light);
}

.login-card {
  width: 380px;
}

.login-title {
  margin: 0 0 16px;
  text-align: center;
  font-size: 18px;
}

.login-button {
  width: 100%;
}
</style>

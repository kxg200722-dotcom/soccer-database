<script setup lang="ts">
import type { AdminMatchDetail, EventActionResult } from '../../types/live'
import EmptyBox from '@/shared/ui/EmptyBox.vue'
import StatusTag from '@/shared/ui/StatusTag.vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import AdminHeader from '../../components/admin/AdminHeader.vue'
import EventManagePanel from '../../components/admin/EventManagePanel.vue'
import FaceVerifyPanel from '../../components/admin/FaceVerifyPanel.vue'
import FileManagePanel from '../../components/admin/FileManagePanel.vue'
import LiveLinkDialog from '../../components/admin/LiveLinkDialog.vue'
import MatchFormDialog from '../../components/admin/MatchFormDialog.vue'
import RefereeFormPanel from '../../components/admin/RefereeFormPanel.vue'
import { changeMatchStatus, fetchAdminMatchDetail } from '../../services/liveAdminApi'

const route = useRoute()
const matchId = ref(Number(route.params.id))

const detail = ref<AdminMatchDetail>()
const loading = ref(true)
const notFound = ref(false)
const editVisible = ref(false)
const statusSubmitting = ref(false)

const scoreText = computed(() => {
  if (!detail.value || detail.value.status === 'not_started') return ''
  return `${detail.value.home_score} : ${detail.value.away_score}`
})

async function load(): Promise<void> {
  loading.value = true
  try {
    detail.value = await fetchAdminMatchDetail(matchId.value)
  }
  catch (e) {
    if ((e as { code?: number }).code === 30001) notFound.value = true
  }
  finally {
    loading.value = false
  }
}

function onSaved(saved: AdminMatchDetail): void {
  detail.value = saved
}

/** 事件增删改后局部同步比分与 version，无需整页刷新 */
function onScoreChange(result: EventActionResult): void {
  if (!detail.value) return
  detail.value.home_score = result.home_score
  detail.value.away_score = result.away_score
  detail.value.version = result.version
}

function onLinkUpdated(result: { live_link: string | null, version: number }): void {
  if (!detail.value) return
  detail.value.live_link = result.live_link
  detail.value.version = result.version
}

async function onChangeStatus(action: 'start' | 'finish'): Promise<void> {
  if (!detail.value) return
  const tip = action === 'start' ? '确认开始比赛？开始后可录入图文事件。' : '确认结束比赛？结束后所有内容将锁定。'
  try {
    await ElMessageBox.confirm(tip, '状态流转', { type: 'warning' })
  }
  catch {
    return
  }
  statusSubmitting.value = true
  try {
    detail.value = await changeMatchStatus(matchId.value, {
      action,
      version: detail.value.version,
    })
    ElMessage.success(action === 'start' ? '比赛已开始' : '比赛已结束')
  }
  catch {
    // 拦截器已统一提示（40002/9）
  }
  finally {
    statusSubmitting.value = false
  }
}

onMounted(() => void load())
</script>

<template>
  <div class="match-detail-admin">
    <AdminHeader />
    <div v-loading="loading" class="body">
      <EmptyBox v-if="notFound" description="比赛不存在或已删除" />

      <template v-else-if="detail">
        <div class="head">
          <el-button text @click="$router.push('/admin/live/matches')">&larr; 返回列表</el-button>
          <div class="title-row">
            <h2 class="title">
              {{ detail.home_team_name }} vs {{ detail.away_team_name }}
              <span v-if="scoreText" class="score">{{ scoreText }}</span>
            </h2>
            <StatusTag dict="match_status" :value="detail.status" />
          </div>
          <div class="meta">
            <span>{{ detail.group_type_label }}</span>
            <span>{{ detail.match_time?.slice(0, 16) }}</span>
            <span v-if="detail.venue">{{ detail.venue }}</span>
          </div>
          <div class="actions">
            <el-button
              v-if="detail.status === 'not_started'"
              v-permission="'live:match:update'"
              type="success"
              :loading="statusSubmitting"
              @click="onChangeStatus('start')"
            >
              开始比赛
            </el-button>
            <el-button
              v-if="detail.status === 'live'"
              v-permission="'live:match:update'"
              type="danger"
              :loading="statusSubmitting"
              @click="onChangeStatus('finish')"
            >
              结束比赛
            </el-button>
            <el-button
              v-if="detail.status !== 'finished'"
              v-permission="'live:match:update'"
              @click="editVisible = true"
            >
              编辑信息
            </el-button>
          </div>
        </div>

        <el-tabs class="tabs">
          <el-tab-pane label="图文事件">
            <EventManagePanel
              :match-id="matchId"
              :status="detail.status"
              :home-team-name="detail.home_team_name"
              :away-team-name="detail.away_team_name"
              @score-change="onScoreChange"
            />
          </el-tab-pane>
          <el-tab-pane label="裁判员">
            <RefereeFormPanel :match-id="matchId" :status="detail.status" />
          </el-tab-pane>
          <el-tab-pane label="赛事文件">
            <FileManagePanel :match-id="matchId" />
          </el-tab-pane>
          <el-tab-pane label="直播链接">
            <LiveLinkDialog
              :match-id="matchId"
              :live-link="detail.live_link"
              :status="detail.status"
              @updated="onLinkUpdated"
            />
          </el-tab-pane>
          <el-tab-pane label="人脸核验">
            <FaceVerifyPanel :match-id="matchId" />
          </el-tab-pane>
        </el-tabs>

        <MatchFormDialog v-model="editVisible" :match="detail" @saved="onSaved" />
      </template>
    </div>
  </div>
</template>

<style scoped>
.body {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
  min-height: 300px;
}

.head {
  margin-bottom: 8px;
}

.title-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 4px;
}

.title {
  margin: 0;
  font-size: 20px;
}

.score {
  color: var(--el-color-danger);
  margin-left: 8px;
}

.meta {
  display: flex;
  gap: 16px;
  margin: 8px 0;
  font-size: 13px;
  color: var(--el-text-color-secondary);
}

.actions {
  display: flex;
  gap: 8px;
}

.tabs {
  margin-top: 12px;
}
</style>

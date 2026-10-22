<script setup lang="ts">
import EmptyBox from '@/shared/ui/EmptyBox.vue'
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import EventTimeline from '../../components/public/EventTimeline.vue'
import LineupPanel from '../../components/public/LineupPanel.vue'
import LiveLinkEntry from '../../components/public/LiveLinkEntry.vue'
import ScoreBoard from '../../components/public/ScoreBoard.vue'
import { useLivePolling } from '../../composables/useLivePolling'

const route = useRoute()
const matchId = ref(Number(route.params.id))

const { detail, events, loading, error } = useLivePolling(matchId)

const notFound = computed(() => {
  const e = error.value as { code?: number } | null
  return e?.code === 30001
})
</script>

<template>
  <div class="live-detail-page">
    <el-button text class="back" @click="$router.push('/live')">
      &larr; 返回列表
    </el-button>

    <div v-loading="loading" class="content">
      <EmptyBox v-if="notFound" description="比赛不存在或已删除" />

      <template v-else-if="detail">
        <ScoreBoard :detail="detail" />
        <LiveLinkEntry :live-link="detail.live_link" />

        <el-tabs class="sections">
          <el-tab-pane label="图文直播">
            <EventTimeline
              :events="events"
              :home-team-name="detail.home_team_name"
              :away-team-name="detail.away_team_name"
            />
          </el-tab-pane>
          <el-tab-pane label="双方阵容">
            <LineupPanel
              :lineup-home="detail.lineup_home"
              :lineup-away="detail.lineup_away"
              :home-team-name="detail.home_team_name"
              :away-team-name="detail.away_team_name"
            />
          </el-tab-pane>
          <el-tab-pane v-if="detail.referees?.length" label="裁判组">
            <ul class="referee-list">
              <li v-for="(r, i) in detail.referees" :key="i">
                <el-tag size="small" effect="plain">{{ r.role_label }}</el-tag>
                <span>{{ r.name }}</span>
              </li>
            </ul>
          </el-tab-pane>
        </el-tabs>
      </template>
    </div>
  </div>
</template>

<style scoped>
.live-detail-page {
  max-width: 760px;
  margin: 0 auto;
  padding: 16px;
}

.back {
  margin-bottom: 8px;
  padding-left: 0;
}

.content {
  min-height: 200px;
}

.sections {
  margin-top: 16px;
}

.referee-list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.referee-list li {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 0;
  border-bottom: 1px solid var(--el-border-color-lighter);
}
</style>

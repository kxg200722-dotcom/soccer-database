<script setup lang="ts">
import type { MatchDetail } from '../../types/live'
import StatusTag from '@/shared/ui/StatusTag.vue'
import { computed } from 'vue'

const props = defineProps<{ detail: MatchDetail }>()

const scoreText = computed(() =>
  props.detail.status === 'not_started'
    ? 'VS'
    : `${props.detail.home_score} : ${props.detail.away_score}`,
)
</script>

<template>
  <div class="score-board">
    <div class="meta">
      <el-tag size="small" effect="plain">{{ detail.group_type_label }}</el-tag>
      <StatusTag dict="match_status" :value="detail.status" />
    </div>
    <div class="main">
      <div class="team">
        <el-avatar v-if="detail.home_team_logo" :src="detail.home_team_logo" :size="56" />
        <span class="name">{{ detail.home_team_name }}</span>
      </div>
      <div class="score" :class="{ live: detail.status === 'live' }">
        {{ scoreText }}
      </div>
      <div class="team">
        <el-avatar v-if="detail.away_team_logo" :src="detail.away_team_logo" :size="56" />
        <span class="name">{{ detail.away_team_name }}</span>
      </div>
    </div>
    <div class="sub">
      <span v-if="detail.match_time">{{ detail.match_time.slice(0, 16) }}</span>
      <span v-if="detail.venue">{{ detail.venue }}</span>
    </div>
  </div>
</template>

<style scoped>
.score-board {
  padding: 20px;
  border-radius: 8px;
  background: var(--el-fill-color-light);
  text-align: center;
}

.meta {
  display: flex;
  justify-content: center;
  gap: 8px;
  margin-bottom: 16px;
}

.main {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 32px;
}

.team {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  width: 140px;
}

.team .name {
  font-size: 15px;
  font-weight: 500;
}

.score {
  font-size: 40px;
  font-weight: 700;
}

.score.live {
  color: var(--el-color-danger);
}

.sub {
  margin-top: 12px;
  display: flex;
  justify-content: center;
  gap: 16px;
  font-size: 13px;
  color: var(--el-text-color-secondary);
}
</style>

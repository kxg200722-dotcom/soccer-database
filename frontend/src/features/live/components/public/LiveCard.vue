<script setup lang="ts">
import type { MatchListItem } from '../../types/live'
import { computed } from 'vue'

const props = defineProps<{ match: MatchListItem }>()

const matchTime = computed(() => props.match.match_time?.slice(0, 16) ?? '时间待定')
const venue = computed(() => props.match.venue)
const groupLabel = computed(() => props.match.group_type_label)

/** 变量名必须与 template 里 :class="{ live, finished, pending }" 完全对齐 */
const live = computed(() => props.match.status === 'live')
const finished = computed(() => props.match.status === 'finished')
const pending = computed(() => props.match.status === 'not_started')

/** 比分：待定显示 "— : —"（参考图空比分格） */
const scoreText = computed(() => {
  if (pending.value) return '— : —'
  return `${props.match.home_score} : ${props.match.away_score}`
})

/** 顶部标题行的状态小标：组别 · 状态文字 */
const headerStatusLabel = computed(() => {
  if (live.value) return '直播中'
  if (finished.value) return '已完赛'
  return '待定'
})

const headerStatusClass = computed(() => {
  if (live.value) return 'status-live'
  if (finished.value) return 'status-finished'
  return 'status-pending'
})
</script>

<template>
  <div class="live-card"
       :class="{ live, finished, pending }"
       role="button"
       tabindex="0"
       @click="$router.push(`/live/${match.id}`)"
       @keydown.enter="$router.push(`/live/${match.id}`)"
  >
    <!-- 顶部行：左=组别·状态头条  右=比分 -->
    <div class="card-head">
      <div class="head-left">
        <span class="group">{{ groupLabel }}</span>
        <span class="dot-sep">·</span>
        <span class="status" :class="headerStatusClass">
          {{ headerStatusLabel }}
          <span v-if="live" class="live-dot" />
        </span>
      </div>
      <div class="head-right">
        <span class="score" :class="{ live, finished, pending }">{{ scoreText }}</span>
      </div>
    </div>

    <!-- 中间：一行对阵（参考图，纯文字无头像） -->
    <div class="vs-row">
      <span class="team home">{{ match.home_team_name }}</span>
      <span class="vs-sep">VS</span>
      <span class="team away">{{ match.away_team_name }}</span>
      <!-- 未开赛角标（待定场次） -->
      <span v-if="pending" class="badge-pending">未开赛</span>
    </div>

    <!-- 底部：日期时间 + 场地 -->
    <div class="card-foot">
      <span class="datetime">{{ matchTime }}</span>
      <template v-if="venue">
        <span class="foot-sep">·</span>
        <span class="venue">{{ venue }}</span>
      </template>
    </div>
  </div>
</template>

<style scoped>
.live-card {
  position: relative;
  cursor: pointer;
  padding: 0;
  border-radius: 8px;
  border: 1px solid var(--el-border-color-lighter);
  border-left-width: 3px;
  border-left-style: solid;
  border-left-color: var(--el-border-color-lighter);  /* 默认中性 */
  background: #fff;
  transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
  outline: none;
}

.live-card:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, .08);
  border-color: var(--el-color-primary-light-5, #d9ecff);
}

.live-card:focus-visible {
  box-shadow: 0 0 0 2px var(--el-color-primary-light-5);
}

/* 状态：左侧彩色竖条（覆盖默认 border-left-color） */
.live-card.live     { border-left-color: var(--el-color-danger); }
.live-card.finished { border-left-color: var(--el-color-success); opacity: .95; }
.live-card.pending  { border-left-color: var(--el-color-info); }

/* ==================== 顶部行 ==================== */
.card-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 14px 8px;
}

.head-left {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: var(--el-text-color-secondary);
  min-width: 0;
}

.group {
  font-weight: 500;
  color: var(--el-text-color-regular);
}

.dot-sep {
  color: var(--el-border-color);
}

.status {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  font-weight: 600;
  padding: 1px 8px;
  border-radius: 3px;
  letter-spacing: .5px;
}

.status-live     { color: var(--el-color-danger);   background: var(--el-color-danger-light-9, #fef0f0); }
.status-finished { color: var(--el-color-success); background: var(--el-color-success-light-9, #f0f9eb); }
.status-pending  { color: var(--el-color-info);    background: var(--el-color-info-light-9, #f4f4f5); }

.live-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--el-color-danger);
  box-shadow: 0 0 0 0 rgba(245, 108, 108, .7);
  animation: live-pulse 1.4s infinite;
}

@keyframes live-pulse {
  0%   { box-shadow: 0 0 0 0 rgba(245, 108, 108, .6); }
  70%  { box-shadow: 0 0 0 6px rgba(245, 108, 108, 0); }
  100% { box-shadow: 0 0 0 0 rgba(245, 108, 108, 0); }
}

.head-right { flex-shrink: 0; margin-left: 12px; }

/* 比分：小字号 + tabular-nums 等宽对齐 */
.score {
  font-size: 16px;
  font-weight: 700;
  letter-spacing: 1px;
  font-variant-numeric: tabular-nums;
  color: var(--el-text-color-primary);
}
.score.live     { color: var(--el-color-danger); }
.score.finished { color: var(--el-text-color-secondary); }
.score.pending  { color: var(--el-text-color-placeholder); }

/* ==================== 对阵行 ==================== */
.vs-row {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 10px 14px;
  font-size: 15px;
  font-weight: 500;
  color: var(--el-text-color-primary);
  border-top: 1px solid var(--el-border-color-lighter);
  border-bottom: 1px solid var(--el-border-color-lighter);
  line-height: 1.35;
}

.team {
  flex: 1;
  min-width: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.team.home { text-align: right; }
.team.away { text-align: left; }

.vs-sep {
  flex-shrink: 0;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 2px;
  color: var(--el-text-color-placeholder);
  padding: 0 4px;
}

.badge-pending {
  position: absolute;
  top: 4px;
  right: 8px;
  font-size: 10px;
  font-weight: 500;
  color: var(--el-color-info);
  background: var(--el-color-info-light-9, #f4f4f5);
  padding: 0 6px;
  border-radius: 3px;
  line-height: 16px;
  letter-spacing: .5px;
}

/* ==================== 底部 ==================== */
.card-foot {
  padding: 8px 14px 12px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
  display: flex;
  justify-content: center;
  align-items: center;
}
.card-foot .foot-sep { margin: 0 6px; color: var(--el-border-color); }
.card-foot .datetime,
.card-foot .venue { white-space: nowrap; }
</style>

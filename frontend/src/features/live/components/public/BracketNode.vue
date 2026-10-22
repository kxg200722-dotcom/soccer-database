<script setup lang="ts">
import type { BracketNode } from '../../composables/bracketMock'
import { computed } from 'vue'

const props = defineProps<{ node: BracketNode }>()
defineEmits<{ click: [id: string] }>()

const live = computed(() => props.node.status === 'live')
const finished = computed(() => props.node.status === 'finished')
const pending = computed(() => props.node.status === 'not_started')

const scoreText = computed(() => {
  if (pending.value) return 'VS'
  const { home_score: h, away_score: a } = props.node
  if (h === null || a === null) return 'VS'
  return `${h} : ${a}`
})

const statusLabel = computed(() => {
  if (live.value) return '直播中'
  if (finished.value) return '已完赛'
  return '待定'
})

const regionClass = computed(() => {
  if (props.node.region === 'gf') return 'node-gf'
  if (props.node.region === 'loser') return 'node-loser'
  return 'node-winner'
})
</script>

<template>
  <div class="bracket-node"
       :class="[regionClass, { live, finished, pending }]"
       :data-node-id="node.id"
       @click="$emit('click', node.id)"
  >
    <!-- 左锚点（上一场来的连线入口） -->
    <span class="anchor anchor-in home" data-anchor="home-in" />
    <span class="anchor anchor-in away" data-anchor="away-in" />

    <div class="teams">
      <div class="team home">
        <span class="t-name" :title="node.home_team">{{ node.home_team }}</span>
      </div>
      <span class="mid-sep">:</span>
      <div class="team away">
        <span class="t-name" :title="node.away_team">{{ node.away_team }}</span>
      </div>
    </div>

    <div class="score-row" :class="{ live, finished, pending }">
      {{ scoreText }}
      <span v-if="live" class="live-dot" />
    </div>

    <div class="bottom-row">
      <span class="status-mini" :class="{ live, finished, pending }">{{ statusLabel }}</span>
    </div>

    <!-- 右锚点（输出去的连线出口） -->
    <span class="anchor anchor-out" data-anchor="out" />
  </div>
</template>

<style scoped>
.bracket-node {
  position: relative;
  width: 200px;
  background: #fff;
  border-radius: 6px;
  border: 1px solid var(--el-border-color-lighter);
  border-left-width: 3px;
  border-left-style: solid;
  border-left-color: var(--el-color-info);  /* 默认 pending 灰 */
  padding: 6px 10px;
  cursor: pointer;
  transition: box-shadow .15s, transform .15s;
  box-sizing: border-box;
}

.bracket-node:hover {
  box-shadow: 0 2px 10px rgba(0, 0, 0, .1);
  transform: translateY(-1px);
}

.bracket-node.live     { border-left-color: var(--el-color-danger); }
.bracket-node.finished { border-left-color: var(--el-color-success); }
.bracket-node.pending  { border-left-color: var(--el-color-info); }

/* 败者组节点加不同底色区隔 */
.bracket-node.node-loser {
  background: var(--el-fill-color-lighter, #fafafa);
}
.bracket-node.node-loser::after {
  content: '败';
  position: absolute;
  top: 2px;
  right: 4px;
  font-size: 9px;
  color: var(--el-text-color-placeholder);
  letter-spacing: 1px;
}

/* Grand Final 醒目 */
.bracket-node.node-gf {
  width: 220px;
  background: linear-gradient(135deg, #fff7e6 0%, #fff 100%);
  border-left-color: #e6a23c;
  border: 1px solid #f5dab1;
  border-left-width: 4px;
}

/* ===== 锚点 ===== */
.anchor {
  position: absolute;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: var(--el-text-color-placeholder);
  opacity: .6;
  transform: translateY(-50%);
  z-index: 2;
  pointer-events: none;
}
.anchor-in.home {
  left: -2px;
  top: 40%;   /* 上入口 */
}
.anchor-in.away {
  left: -2px;
  top: 60%;   /* 下入口 */
}
.anchor-out {
  right: -2px;
  top: 50%;
  background: var(--el-color-primary);
  opacity: .8;
}

/* ===== 队伍行 ===== */
.teams {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  gap: 4px;
  padding: 2px 0;
}

.team .t-name {
  font-size: 12px;
  font-weight: 500;
  color: var(--el-text-color-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
  display: inline-block;
}
.team.home { text-align: right; }
.team.away { text-align: left; }

.mid-sep {
  font-size: 11px;
  color: var(--el-text-color-placeholder);
  font-weight: 700;
}

/* ===== 比分 ===== */
.score-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  font-size: 18px;
  font-weight: 700;
  letter-spacing: 1px;
  padding: 4px 0;
  font-variant-numeric: tabular-nums;
}
.score-row.live     { color: var(--el-color-danger); }
.score-row.finished { color: var(--el-text-color-secondary); }
.score-row.pending  { color: var(--el-text-color-placeholder); }

.live-dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  background: var(--el-color-danger);
  animation: live-pulse 1.4s infinite;
}
@keyframes live-pulse {
  0%   { box-shadow: 0 0 0 0 rgba(245, 108, 108, .6); }
  70%  { box-shadow: 0 0 0 6px rgba(245, 108, 108, 0); }
  100% { box-shadow: 0 0 0 0 rgba(245, 108, 108, 0); }
}

/* ===== 底部 ===== */
.bottom-row {
  padding-top: 2px;
  display: flex;
  justify-content: center;
}
.status-mini {
  font-size: 10px;
  font-weight: 500;
  padding: 0 5px;
  border-radius: 3px;
  line-height: 14px;
  letter-spacing: .5px;
}
.status-mini.live     { color: var(--el-color-danger); background: var(--el-color-danger-light-9, #fef0f0); }
.status-mini.finished { color: var(--el-color-success); background: var(--el-color-success-light-9, #f0f9eb); }
.status-mini.pending  { color: var(--el-color-info); background: var(--el-color-info-light-9, #f4f4f5); }
</style>

<script setup lang="ts">
import type { MatchEventItem } from '../../types/live'
import EmptyBox from '@/shared/ui/EmptyBox.vue'
import { useDict } from '@/shared/composables/useDict'
import { computed } from 'vue'

const props = withDefaults(defineProps<{
  events: MatchEventItem[]
  homeTeamName?: string
  awayTeamName?: string
}>(), {
  homeTeamName: '主队',
  awayTeamName: '客队',
})

const { getLabel, getTagType } = useDict('event_type')

/** 最新事件在上 */
const sorted = computed(() => [...props.events].sort((a, b) =>
  a.minute === b.minute ? b.id - a.id : b.minute - a.minute,
))

function sideName(side: string | null): string {
  if (side === 'home') return props.homeTeamName
  if (side === 'away') return props.awayTeamName
  return ''
}

function describe(event: MatchEventItem): string {
  const side = sideName(event.team_side)
  switch (event.type) {
    case 'goal': {
      const assist = event.related_player_name ? `（助攻：${event.related_player_name}）` : ''
      return `${event.player_name ?? ''} 破门${assist} ${side}`.trim()
    }
    case 'yellow':
    case 'red':
      return `${event.player_name ?? ''} ${side}`.trim()
    case 'substitution':
      return `${event.related_player_name ?? ''} 换上，${event.player_name ?? ''} 换下 ${side}`.trim()
    default:
      return event.content ?? ''
  }
}
</script>

<template>
  <div class="event-timeline">
    <EmptyBox v-if="sorted.length === 0" description="暂无直播事件" />
    <el-timeline v-else>
      <el-timeline-item
        v-for="event in sorted"
        :key="event.id"
        :timestamp="`${event.minute}'`"
        :type="getTagType('event_type', event.type)"
        :hollow="event.type === 'text'"
        placement="top"
      >
        <div class="event-row">
          <el-tag size="small" :type="getTagType('event_type', event.type)" effect="plain">
            {{ getLabel('event_type', event.type) }}
          </el-tag>
          <span class="event-text">{{ describe(event) }}</span>
        </div>
      </el-timeline-item>
    </el-timeline>
  </div>
</template>

<style scoped>
.event-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.event-text {
  font-size: 14px;
  line-height: 1.6;
  word-break: break-all;
}
</style>

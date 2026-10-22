<script setup lang="ts">
import type { LineupPlayer } from '../../types/live'
import EmptyBox from '@/shared/ui/EmptyBox.vue'

withDefaults(defineProps<{
  lineupHome: LineupPlayer[] | null
  lineupAway: LineupPlayer[] | null
  homeTeamName?: string
  awayTeamName?: string
}>(), {
  homeTeamName: '主队',
  awayTeamName: '客队',
})
</script>

<template>
  <div class="lineup-panel">
    <div v-if="(lineupHome?.length ?? 0) === 0 && (lineupAway?.length ?? 0) === 0">
      <EmptyBox description="阵容暂未公布" />
    </div>
    <div v-else class="lineup-columns">
      <div class="lineup-col">
        <h4 class="col-title">{{ homeTeamName }}</h4>
        <ul>
          <li v-for="(p, i) in lineupHome ?? []" :key="`h-${i}`">
            <span class="no">{{ p.no }}</span>
            <span class="pname">{{ p.name }}</span>
            <span v-if="p.position" class="pos">{{ p.position }}</span>
            <el-tag v-if="p.is_captain" size="small" type="warning" effect="plain">C</el-tag>
          </li>
        </ul>
      </div>
      <div class="lineup-col">
        <h4 class="col-title">{{ awayTeamName }}</h4>
        <ul>
          <li v-for="(p, i) in lineupAway ?? []" :key="`a-${i}`">
            <span class="no">{{ p.no }}</span>
            <span class="pname">{{ p.name }}</span>
            <span v-if="p.position" class="pos">{{ p.position }}</span>
            <el-tag v-if="p.is_captain" size="small" type="warning" effect="plain">C</el-tag>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<style scoped>
.lineup-columns {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.col-title {
  margin: 0 0 8px;
  font-size: 14px;
  text-align: center;
  color: var(--el-text-color-primary);
}

ul {
  list-style: none;
  margin: 0;
  padding: 0;
}

li {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 8px;
  border-bottom: 1px solid var(--el-border-color-lighter);
  font-size: 13px;
}

.no {
  width: 28px;
  text-align: right;
  color: var(--el-text-color-secondary);
}

.pname {
  flex: 1;
}

.pos {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
</style>

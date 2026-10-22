<script setup lang="ts">
import type { MatchEventItem } from '../../types/live'
import StatusTag from '@/shared/ui/StatusTag.vue'

withDefaults(defineProps<{
  events: MatchEventItem[]
  loading?: boolean
  /** finished 后禁止编辑/删除 */
  readonly?: boolean
  homeTeamName?: string
  awayTeamName?: string
}>(), {
  loading: false,
  readonly: false,
  homeTeamName: '主队',
  awayTeamName: '客队',
})

const emit = defineEmits<{
  edit: [event: MatchEventItem]
  remove: [event: MatchEventItem]
}>()

function sideName(side: string | null, home: string, away: string): string {
  if (side === 'home') return home
  if (side === 'away') return away
  return '-'
}
</script>

<template>
  <el-table :data="events" v-loading="loading" border size="small">
    <el-table-column prop="minute" label="分钟" width="70" align="center">
      <template #default="{ row }">{{ row.minute }}'</template>
    </el-table-column>
    <el-table-column label="类型" width="90" align="center">
      <template #default="{ row }">
        <StatusTag dict="event_type" :value="row.type" />
      </template>
    </el-table-column>
    <el-table-column label="方" width="130">
      <template #default="{ row }">{{ sideName(row.team_side, homeTeamName, awayTeamName) }}</template>
    </el-table-column>
    <el-table-column label="球员" min-width="110">
      <template #default="{ row }">
        <span v-if="row.player_name">{{ row.player_name }}</span>
        <span v-if="row.player_no !== null" class="no">#{{ row.player_no }}</span>
        <span v-if="!row.player_name">-</span>
      </template>
    </el-table-column>
    <el-table-column label="关联" min-width="110">
      <template #default="{ row }">
        <template v-if="row.related_player_name">
          {{ row.type === 'substitution' ? '换上：' : '助攻：' }}{{ row.related_player_name }}
        </template>
        <template v-else>-</template>
      </template>
    </el-table-column>
    <el-table-column prop="content" label="内容" min-width="160" show-overflow-tooltip>
      <template #default="{ row }">{{ row.content ?? '-' }}</template>
    </el-table-column>
    <el-table-column label="操作" width="110" fixed="right">
      <template #default="{ row }">
        <template v-if="!readonly">
          <el-button size="small" link type="primary" @click="emit('edit', row)">编辑</el-button>
          <el-button size="small" link type="danger" @click="emit('remove', row)">删除</el-button>
        </template>
        <span v-else class="locked">已锁定</span>
      </template>
    </el-table-column>
  </el-table>
</template>

<style scoped>
.no {
  margin-left: 4px;
  color: var(--el-text-color-secondary);
  font-size: 12px;
}

.locked {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
</style>

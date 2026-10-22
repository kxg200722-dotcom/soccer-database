<script setup lang="ts">
import type { MatchListItem } from '../../types/live'
import StatusTag from '@/shared/ui/StatusTag.vue'
import { useRouter } from 'vue-router'

defineProps<{
  list: MatchListItem[]
  loading?: boolean
}>()

const emit = defineEmits<{
  edit: [row: MatchListItem]
  remove: [row: MatchListItem]
}>()

const router = useRouter()

function canDelete(row: MatchListItem): boolean {
  return row.status === 'not_started'
}

function scoreText(row: MatchListItem): string {
  return row.status === 'not_started' ? '-' : `${row.home_score} : ${row.away_score}`
}
</script>

<template>
  <el-table :data="list" v-loading="loading" border>
    <el-table-column prop="id" label="ID" width="70" />
    <el-table-column prop="group_type_label" label="组别" width="90" />
    <el-table-column label="对阵" min-width="220">
      <template #default="{ row }">
        <el-link type="primary" @click="router.push(`/admin/live/matches/${row.id}`)">
          {{ row.home_team_name }} vs {{ row.away_team_name }}
        </el-link>
      </template>
    </el-table-column>
    <el-table-column label="时间" width="160">
      <template #default="{ row }">{{ row.match_time?.slice(0, 16) ?? '-' }}</template>
    </el-table-column>
    <el-table-column prop="venue" label="地点" min-width="120" show-overflow-tooltip />
    <el-table-column label="状态" width="100" align="center">
      <template #default="{ row }">
        <StatusTag dict="match_status" :value="row.status" />
      </template>
    </el-table-column>
    <el-table-column label="比分" width="90" align="center">
      <template #default="{ row }">{{ scoreText(row) }}</template>
    </el-table-column>
    <el-table-column label="操作" width="180" fixed="right">
      <template #default="{ row }">
        <el-button
          v-permission="'live:match:update'"
          size="small"
          link
          type="primary"
          @click="emit('edit', row)"
        >
          编辑
        </el-button>
        <el-button
          size="small"
          link
          type="primary"
          @click="router.push(`/admin/live/matches/${row.id}`)"
        >
          管理
        </el-button>
        <el-button
          v-if="canDelete(row)"
          v-permission="'live:match:delete'"
          size="small"
          link
          type="danger"
          @click="emit('remove', row)"
        >
          删除
        </el-button>
      </template>
    </el-table-column>
  </el-table>
</template>

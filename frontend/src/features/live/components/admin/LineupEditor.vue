<script setup lang="ts">
import type { LineupPlayer } from '../../types/live'
import { Plus, Delete } from '@element-plus/icons-vue'

const props = withDefaults(defineProps<{
  home: LineupPlayer[]
  away: LineupPlayer[]
  homeTeamName?: string
  awayTeamName?: string
  disabled?: boolean
}>(), {
  homeTeamName: '主队',
  awayTeamName: '客队',
  disabled: false,
})

const emit = defineEmits<{
  'update:home': [value: LineupPlayer[]]
  'update:away': [value: LineupPlayer[]]
}>()

function addPlayer(side: 'home' | 'away'): void {
  const player: LineupPlayer = { name: '', no: '', position: '', is_captain: false }
  if (side === 'home') emit('update:home', [...props.home, player])
  else emit('update:away', [...props.away, player])
}

function removePlayer(side: 'home' | 'away', index: number): void {
  if (side === 'home') emit('update:home', props.home.filter((_, i) => i !== index))
  else emit('update:away', props.away.filter((_, i) => i !== index))
}

function patchPlayer(side: 'home' | 'away', index: number, patch: Partial<LineupPlayer>): void {
  const source = side === 'home' ? props.home : props.away
  const next = source.map((p, i) => (i === index ? { ...p, ...patch } : p))
  if (side === 'home') emit('update:home', next)
  else emit('update:away', next)
}
</script>

<template>
  <div class="lineup-editor">
    <div v-for="side in (['home', 'away'] as const)" :key="side" class="side">
      <div class="side-header">
        <span>{{ side === 'home' ? homeTeamName : awayTeamName }}</span>
        <el-button
          size="small"
          :icon="Plus"
          :disabled="disabled"
          @click="addPlayer(side)"
        >
          添加球员
        </el-button>
      </div>
      <div
        v-for="(player, index) in (side === 'home' ? home : away)"
        :key="index"
        class="player-row"
      >
        <el-input
          :model-value="player.no"
          placeholder="号码"
          class="no"
          :disabled="disabled"
          @update:model-value="(v: string) => patchPlayer(side, index, { no: v })"
        />
        <el-input
          :model-value="player.name"
          placeholder="姓名"
          class="name"
          :disabled="disabled"
          @update:model-value="(v: string) => patchPlayer(side, index, { name: v })"
        />
        <el-input
          :model-value="player.position"
          placeholder="位置"
          class="position"
          :disabled="disabled"
          @update:model-value="(v: string) => patchPlayer(side, index, { position: v })"
        />
        <el-checkbox
          :model-value="player.is_captain"
          :disabled="disabled"
          @update:model-value="(v: boolean) => patchPlayer(side, index, { is_captain: v })"
        >
          队长
        </el-checkbox>
        <el-button
          size="small"
          link
          type="danger"
          :icon="Delete"
          :disabled="disabled"
          @click="removePlayer(side, index)"
        />
      </div>
      <div v-if="(side === 'home' ? home : away).length === 0" class="empty-hint">
        暂未添加球员
      </div>
    </div>
  </div>
</template>

<style scoped>
.lineup-editor {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.side-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
  font-weight: 500;
}

.player-row {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 6px;
}

.no {
  width: 64px;
}

.name {
  width: 110px;
}

.position {
  width: 90px;
}

.empty-hint {
  padding: 12px;
  text-align: center;
  font-size: 12px;
  color: var(--el-text-color-secondary);
  border: 1px dashed var(--el-border-color);
  border-radius: 4px;
}
</style>

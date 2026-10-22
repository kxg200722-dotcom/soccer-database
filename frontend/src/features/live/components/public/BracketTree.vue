<script setup lang="ts">
import type { BracketData, BracketNode as BracketNodeData } from '../../composables/bracketMock'
import { groupByRound } from '../../composables/bracketMock'
import BracketNode from './BracketNode.vue'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps<{ data: BracketData }>()
const emit = defineEmits<{ nodeClick: [id: string] }>()

/** 按 round 分组 */
const columns = computed(() => groupByRound(props.data.nodes))

/** 胜者组列（R1-R4） */
const winnerCols = computed(() => columns.value.filter(c => colRegion(c) === 'winner'))
/** 败者组列（LR1-LR4） */
const loserCols = computed(() => columns.value.filter(c => colRegion(c) === 'loser'))
/** 冠军赛列（GF） */
const gfCol = computed(() => columns.value.find(c => colRegion(c) === 'gf'))

const containerRef = ref<HTMLElement | null>(null)
const paths = ref<Array<{ d: string; color: string }>>([])

/**
 * 框间连线定义（参考图：一轮一框，仅在框间连线）
 * from/to 为 round 名；entry 为入口位置（top=顶边中点、upper=左边偏上、lower=左边偏下、left=左边中点）
 */
const FRAME_LINKS: Array<{ from: string; to: string; color: string; entry?: 'left' | 'upper' | 'lower' | 'top' }> = [
  // 胜者组横向晋级（蓝）
  { from: 'R1', to: 'R2', color: '#409eff' },
  { from: 'R2', to: 'R3', color: '#409eff' },
  { from: 'R3', to: 'R4', color: '#409eff' },
  // 胜者组决赛 → 冠军赛上半入口（金）
  { from: 'R4', to: 'GF', color: '#e6a23c', entry: 'upper' },
  // 败者组横向晋级（绿）
  { from: 'LR1', to: 'LR2', color: '#67c23a' },
  { from: 'LR2', to: 'LR3', color: '#67c23a' },
  { from: 'LR3', to: 'LR4', color: '#67c23a' },
  // 败者组决赛 → 冠军赛下半入口（金）
  { from: 'LR4', to: 'GF', color: '#e6a23c', entry: 'lower' },
  // 胜者组首轮败者下沉到败者组（橙，竖线）
  { from: 'R1', to: 'LR1', color: '#e6a23c', entry: 'top' },
]

/** 水平优先折线：水平→垂直→水平 */
function buildHPath(from: { x: number; y: number }, to: { x: number; y: number }): string {
  const midX = (from.x + to.x) / 2
  return `M ${from.x} ${from.y} L ${midX} ${from.y} L ${midX} ${to.y} L ${to.x} ${to.y}`
}

/** 垂直线（同列上下框之间） */
function buildVPath(from: { x: number; y: number }, to: { x: number; y: number }): string {
  return `M ${from.x} ${from.y} L ${to.x} ${to.y}`
}

function rerenderConnections(): void {
  const container = containerRef.value
  if (!container) return

  const containerRect = container.getBoundingClientRect()

  // 1. 取所有轮次框的 rect（容器坐标系）
  const frameRects = new Map<string, DOMRect>()
  container.querySelectorAll<HTMLElement>('[data-round]').forEach(el => {
    const r = el.getBoundingClientRect()
    frameRects.set(el.dataset.round!, new DOMRect(
      r.left - containerRect.left,
      r.top - containerRect.top,
      r.width,
      r.height,
    ))
  })

  // 2. 按 FRAME_LINKS 生成 path
  const ps: Array<{ d: string; color: string }> = []
  for (const link of FRAME_LINKS) {
    const fromRect = frameRects.get(link.from)
    const toRect = frameRects.get(link.to)
    if (!fromRect || !toRect) continue

    // 出口：entry=top 时为底边中点，其余为右边中点
    const outPt = link.entry === 'top'
      ? { x: fromRect.left + fromRect.width / 2, y: fromRect.top + fromRect.height }
      : { x: fromRect.left + fromRect.width, y: fromRect.top + fromRect.height / 2 }

    // 入口
    let inPt: { x: number; y: number }
    switch (link.entry) {
      case 'top':   inPt = { x: toRect.left + toRect.width / 2, y: toRect.top }; break
      case 'upper': inPt = { x: toRect.left, y: toRect.top + toRect.height * 0.35 }; break
      case 'lower': inPt = { x: toRect.left, y: toRect.top + toRect.height * 0.65 }; break
      default:      inPt = { x: toRect.left, y: toRect.top + toRect.height / 2 }
    }

    const d = link.entry === 'top' ? buildVPath(outPt, inPt) : buildHPath(outPt, inPt)
    ps.push({ d, color: link.color })
  }

  paths.value = ps
}

let ro: ResizeObserver | null = null

function setupObserver(): void {
  ro = new ResizeObserver(() => rerenderConnections())
  if (containerRef.value) {
    ro.observe(containerRef.value)
    containerRef.value.querySelectorAll('[data-round]').forEach(el => ro!.observe(el))
  }
}

onMounted(async () => {
  await nextTick()
  rerenderConnections()
  setupObserver()
})

watch(
  () => props.data.nodes.length,
  async () => { await nextTick(); rerenderConnections() },
)

onBeforeUnmount(() => ro?.disconnect())

function colRegion(col: BracketNodeData[]): 'winner' | 'loser' | 'gf' {
  return col[0].region
}
</script>

<template>
  <div class="bracket-scroll">
    <div class="bracket-tree" ref="containerRef">

      <!-- 连线层：框间连线（一轮一框，仅在框间连线） -->
      <svg
        class="bracket-svg"
        xmlns="http://www.w3.org/2000/svg"
        preserveAspectRatio="none"
      >
        <path
          v-for="(p, i) in paths"
          :key="i"
          :d="p.d"
          fill="none"
          :stroke="p.color"
          stroke-width="1.6"
          stroke-linecap="round"
          stroke-linejoin="round"
          opacity="0.78"
        />
      </svg>

      <!-- 上排：胜者组 -->
      <div class="bracket-row row-winner">
        <div class="row-header">
          <span class="row-title row-title-winner">胜者组</span>
        </div>
        <div class="row-cols">
          <div
            v-for="(col, cidx) in winnerCols"
            :key="`w-${cidx}`"
            class="round-frame frame-winner"
            :data-round="col[0].round"
          >
            <div class="col-nodes">
              <BracketNode
                v-for="n in col"
                :key="n.id"
                :node="n"
                @click="emit('nodeClick', n.id)"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- 下排：败者组 -->
      <div class="bracket-row row-loser">
        <div class="row-header">
          <span class="row-title row-title-loser">败者组</span>
        </div>
        <div class="row-cols">
          <div
            v-for="(col, cidx) in loserCols"
            :key="`l-${cidx}`"
            class="round-frame frame-loser"
            :data-round="col[0].round"
          >
            <div class="col-nodes">
              <BracketNode
                v-for="n in col"
                :key="n.id"
                :node="n"
                @click="emit('nodeClick', n.id)"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- 冠军赛：最右列，跨两行垂直居中 -->
      <div v-if="gfCol" class="bracket-row row-gf">
        <div class="row-header">
          <span class="row-title row-title-gf">冠军赛</span>
        </div>
        <div class="row-cols">
          <div class="round-frame frame-gf" :data-round="gfCol[0].round">
            <div class="col-nodes">
              <BracketNode
                v-for="n in gfCol"
                :key="n.id"
                :node="n"
                @click="emit('nodeClick', n.id)"
              />
            </div>
            <div class="gf-crown">🏆</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.bracket-scroll {
  width: 100%;
  overflow-x: auto;
  overflow-y: visible;
}

.bracket-tree {
  position: relative;
  display: grid;
  /* 4 轮列 + 冠军赛列（宽度自适应内容） */
  grid-template-columns: repeat(4, auto) auto;
  grid-template-rows: auto auto;
  column-gap: 64px;  /* 横向框间距加大，更通透 */
  row-gap: 72px;     /* 两排间距，对齐参考图 */
  padding: 24px 16px 48px;
  min-width: fit-content;
}

.bracket-svg {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 0;
  display: block;
}

/* 上排：胜者组 */
.row-winner {
  grid-row: 1;
  grid-column: 1 / 5;
  position: relative;
  z-index: 1;
}

/* 下排：败者组 */
.row-loser {
  grid-row: 2;
  grid-column: 1 / 5;
  position: relative;
  z-index: 1;
}

/* 冠军赛：最右列，跨两行垂直居中 */
.row-gf {
  grid-row: 1 / 3;
  grid-column: 5;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  position: relative;
  z-index: 1;
}

/* 排级标题 */
.row-header {
  margin-bottom: 10px;
  padding-bottom: 6px;
  border-bottom: 2px solid var(--el-border-color-lighter);
}

.row-title {
  font-size: 15px;
  font-weight: 700;
  letter-spacing: 1px;
  padding-left: 10px;
  border-left: 4px solid transparent;
}
.row-title-winner { color: var(--el-color-primary); border-left-color: var(--el-color-primary); }
.row-title-loser  { color: var(--el-color-warning); border-left-color: var(--el-color-warning); }
.row-title-gf     { color: #e6a23c; border-left-color: #e6a23c; }

.row-cols {
  display: flex;
  gap: 64px;
  align-items: center;  /* 矮框相对高框垂直居中， bracket 观感 */
}

/* 轮次框：一轮一框，框间连线；宽度随内容自适应 */
.round-frame {
  display: flex;
  flex-direction: column;
  gap: 10px;
  width: fit-content;
  min-width: 232px;
  flex-shrink: 0;
  box-sizing: border-box;
  padding: 14px 16px;
  border: 1px solid var(--el-border-color);
  border-radius: 10px;
  background: var(--el-bg-color, #fff);
}

.frame-winner { border-top: 3px solid var(--el-color-primary); }
.frame-loser  { border-top: 3px solid var(--el-color-warning); }
.frame-gf     { border: 1px solid #f5dab1; border-top: 3px solid #e6a23c; background: linear-gradient(180deg, #fffaf0 0%, #fff 100%); }

.col-nodes {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.gf-crown {
  text-align: center;
  font-size: 24px;
  margin-top: 4px;
}
</style>

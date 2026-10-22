/**
 * 双败淘汰 16 强树状图 —— 演示数据
 *
 * ⚠️ 纯 mock 演示数据，用于前端视觉展示参考。
 * 真实系统接入时，把本文件替换为 /api/public/live/bracket 返回即可（接口按下方 TS 契约定义）。
 *
 * 树结构：
 *   胜者组（上半区，左→右）: R1(8场) → R2(4场) → R3(2场) → R4(胜者组决赛, 1场)
 *   败者组（下半区，左→右）: LR1(4场) → LR2(4场) → LR3(2场) → LR4(败者组决赛, 1场)
 *   Grand Final（最右中间）: W4 胜者  vs  L4 胜者
 *   胜者组每轮败者向下汇聚到败者组（R1败者→LR1, R2败者→LR2, R3败者→LR3, R4败者→LR4）
 */

import type { MatchStatus } from '../types/live'

export type BracketRegion = 'winner' | 'loser' | 'gf'
/** 状态直接复用 types/live 单一来源（与后端 matches.status 对齐） */
export type { MatchStatus }

/** 一个树节点 = 一场比赛 */
export interface BracketNode {
  id: string
  /** 胜者组 R1-R4 / 败者组 LR1-LR4 / GF=99 */
  round: string
  region: BracketRegion
  /** 同轮内索引（0-based） */
  position: number
  home_team: string
  away_team: string
  home_score: number | null
  away_score: number | null
  status: MatchStatus
  /** 胜者晋级到哪个节点（GF 的胜者不晋级 = undefined） */
  winner_to?: string
  /** 败者向下（胜者组专属，进败者组哪个节点） */
  loser_to?: string
  /** 本节点 home 队来自哪个上一场 */
  home_from?: string
  /** 本节点 away 队来自哪个上一场 */
  away_from?: string
}

/** 一条连线 */
export interface BracketConnection {
  from: string   // node id
  to: string     // node id
  /** 连线种类（控制起点位置/颜色/虚线） */
  kind: 'winner-up'     // 胜者组晋级
    | 'loser-down'     // 胜者组败者入败者组
    | 'gf-in-home'     // GF 上半入口
    | 'gf-in-away'     // GF 下半入口
    | 'home-up'        // 败者组 home 晋级
    | 'away-up'        // 败者组 away 晋级
}

export interface BracketData {
  nodes: BracketNode[]
  connections: BracketConnection[]
  /** 总节点数量（用于横向滚动容器） */
  totalRounds: number
  /** 演示数据标记 */
  isDemo: true
}

// ============== Mock 队伍池（16 强，每组 16 队） ==============
const TEAM_POOLS: Record<string, string[]> = {
  'group_middle': [
    '中学A', '中学B', '中学C', '中学D', '中学E', '中学F', '中学G', '中学H',
    '中学I', '中学J', '中学K', '中学L', '中学M', '中学N', '中学O', '中学P',
  ],
  'group_primary': [
    '小学A', '小学B', '小学C', '小学D', '小学E', '小学F', '小学G', '小学H',
    '小学I', '小学J', '小学K', '小学L', '小学M', '小学N', '小学O', '小学P',
  ],
  'group_university': [
    '大学A', '大学B', '大学C', '大学D', '大学E', '大学F', '大学G', '大学H',
    '大学I', '大学J', '大学K', '大学L', '大学M', '大学N', '大学O', '大学P',
  ],
}

/** 随机生成比分（避免平局） */
function randomResult(): { hs: number; as: number } {
  let hs = Math.floor(Math.random() * 4)
  let as = Math.floor(Math.random() * 4)
  while (hs === as) { as = Math.floor(Math.random() * 4) }
  return { hs, as }
}

function buildWinnerRounds(nodes: BracketNode[], teams: string[]): void {
  // R1: 8 场
  const r1: BracketNode[] = []
  for (let i = 0; i < 8; i++) {
    r1.push({
      id: `W-R1-${i}`,
      round: 'R1', region: 'winner', position: i,
      home_team: teams[i * 2],
      away_team: teams[i * 2 + 1],
      ...(() => {
        const { hs, as } = randomResult()
        return { home_score: hs, away_score: as, status: 'finished' as MatchStatus }
      })(),
    })
  }
  nodes.push(...r1)

  // R2: 4 场 —— 由 R1 胜者组成
  const r2: BracketNode[] = []
  for (let i = 0; i < 4; i++) {
    const a = r1[i * 2], b = r1[i * 2 + 1]
    const aWinner = (a.home_score! > a.away_score!) ? a.home_team : a.away_team
    const bWinner = (b.home_score! > b.away_score!) ? b.home_team : b.away_team
    r2.push({
      id: `W-R2-${i}`,
      round: 'R2', region: 'winner', position: i,
      home_team: aWinner, away_team: bWinner,
      home_score: null, away_score: null, status: 'not_started',
      home_from: a.id, away_from: b.id,
    })
  }
  nodes.push(...r2)

  // R3: 2 场
  const r3: BracketNode[] = []
  for (let i = 0; i < 2; i++) {
    const a = r2[i * 2], b = r2[i * 2 + 1]
    r3.push({
      id: `W-R3-${i}`,
      round: 'R3', region: 'winner', position: i,
      home_team: `W${a.id}胜者`, away_team: `W${b.id}胜者`,
      home_score: null, away_score: null, status: 'not_started',
      home_from: a.id, away_from: b.id,
    })
  }
  nodes.push(...r3)

  // R4 胜者组决赛
  const wf: BracketNode = {
    id: 'W-R4',
    round: 'R4', region: 'winner', position: 0,
    home_team: `W${r3[0].id}胜者`, away_team: `W${r3[1].id}胜者`,
    home_score: null, away_score: null, status: 'not_started',
    home_from: r3[0].id, away_from: r3[1].id,
  }
  nodes.push(wf)
}

function buildLoserRounds(nodes: BracketNode[]): void {
  const r1 = nodes.filter(n => n.round === 'R1')
  const r2 = nodes.filter(n => n.round === 'R2')
  const r3 = nodes.filter(n => n.round === 'R3')
  const wf = nodes.find(n => n.id === 'W-R4')!

  // LR1: R1 败者 8 队互相打 → 4 场
  const lr1: BracketNode[] = []
  for (let i = 0; i < 4; i++) {
    const looserA = (r1[i * 2].home_score! < r1[i * 2].away_score!) ? r1[i * 2].home_team : r1[i * 2].away_team
    const looserB = (r1[i * 2 + 1].home_score! < r1[i * 2 + 1].away_score!) ? r1[i * 2 + 1].home_team : r1[i * 2 + 1].away_team
    lr1.push({
      id: `L-LR1-${i}`,
      round: 'LR1', region: 'loser', position: i,
      home_team: looserA, away_team: looserB,
      home_score: null, away_score: null, status: 'not_started',
    })
  }
  nodes.push(...lr1)

  // LR2: LR1 胜者 vs R2 败者（4 场）。R2 未开赛，败者未知 → 占位列
  const lr2: BracketNode[] = []
  for (let i = 0; i < 4; i++) {
    lr2.push({
      id: `L-LR2-${i}`,
      round: 'LR2', region: 'loser', position: i,
      home_team: `L${lr1[i].id}胜者`, away_team: `W${r2[i].id}败者`,
      home_score: null, away_score: null, status: 'not_started',
      home_from: lr1[i].id,
    })
  }
  nodes.push(...lr2)

  // LR3: LR2 胜者 vs R3 败者（2 场）。R3 未开赛，败者未知 → 占位列
  const lr3: BracketNode[] = []
  for (let i = 0; i < 2; i++) {
    lr3.push({
      id: `L-LR3-${i}`,
      round: 'LR3', region: 'loser', position: i,
      home_team: `L${lr2[i * 2].id}胜者`, away_team: `W${r3[i].id}败者`,
      home_score: null, away_score: null, status: 'not_started',
      home_from: lr2[i * 2].id,
    })
  }
  nodes.push(...lr3)

  // LR4 败者组决赛（LR3 胜者 vs 胜者组决赛败者）
  const lf: BracketNode = {
    id: 'L-LR4',
    round: 'LR4', region: 'loser', position: 0,
    home_team: `L${lr3[0].id}胜者`, away_team: `W${wf.id}败者`,
    home_score: null, away_score: null, status: 'not_started',
    home_from: lr3[0].id, away_from: wf.id,
  }
  nodes.push(lf)

  // Grand Final
  nodes.push({
    id: 'GF',
    round: 'GF', region: 'gf', position: 0,
    home_team: `W${wf.id}胜者`, away_team: `L${lf.id}胜者`,
    home_score: null, away_score: null, status: 'not_started',
    home_from: wf.id, away_from: lf.id,
  })
}

/** 计算所有连线 */
function buildConnections(nodes: BracketNode[]): BracketConnection[] {
  const byId = new Map(nodes.map(n => [n.id, n]))
  const conns: BracketConnection[] = []

  for (const n of nodes) {
    if (n.home_from && byId.has(n.home_from)) {
      // home_from 节点 → 本节点 home 入口
      const f = byId.get(n.home_from)!
      if (n.region === 'gf') {
        conns.push({ from: f.id, to: n.id, kind: 'gf-in-home' })
      } else {
        conns.push({
          from: f.id, to: n.id,
          kind: f.region === 'winner' ? 'winner-up' : 'home-up',
        })
      }
    }
    if (n.away_from && byId.has(n.away_from)) {
      const f = byId.get(n.away_from)!
      // GF 的 away 入口来自败者组决赛（败者组决赛 home_from 是 LR3，away_from 是 WF 败者）
      // 但 GF 的 away_from 是 L-LR4，L-LR4 的 home_from/away_from 是 LR3/WF
      // 所以 GF.away_from → GF.away 走 gf-in-away
      if (n.region === 'gf') {
        conns.push({ from: f.id, to: n.id, kind: 'gf-in-away' })
      } else {
        conns.push({
          from: f.id, to: n.id,
          kind: f.region === 'winner' ? 'winner-up' : 'away-up',
        })
      }
    }
  }

  // 胜者组败者 → 败者组对应节点（loser-down 连线）
  // R1 败者 → LR1；R2 败者 → LR2；R3 败者 → LR3；R4 败者 → LR4(away_from 已建)
  for (const n of nodes) {
    if (n.round === 'R1') {
      conns.push({ from: n.id, to: `L-LR1-${n.position}`, kind: 'loser-down' })
    } else if (n.round === 'R2') {
      conns.push({ from: n.id, to: `L-LR2-${n.position}`, kind: 'loser-down' })
    } else if (n.round === 'R3') {
      conns.push({ from: n.id, to: `L-LR3-${n.position}`, kind: 'loser-down' })
    } else if (n.id === 'W-R4') {
      conns.push({ from: n.id, to: 'L-LR4', kind: 'loser-down' })
    }
  }

  return conns
}

/** 生成完整 demo bracket */
export function generateDemoBracket(group: string): BracketData {
  const pool = TEAM_POOLS[group] ?? TEAM_POOLS.group_middle
  const nodes: BracketNode[] = []

  buildWinnerRounds(nodes, pool)
  buildLoserRounds(nodes)

  return {
    nodes,
    connections: buildConnections(nodes),
    totalRounds: new Set(nodes.map(n => n.round)).size, // R1-R4 + LR1-LR4 + GF = 9
    isDemo: true,
  }
}

/** 列分组：相同 round 归一列 */
export function groupByRound(nodes: BracketNode[]): BracketNode[][] {
  const roundOrder = ['R1', 'R2', 'R3', 'R4', 'LR1', 'LR2', 'LR3', 'LR4', 'GF']
  const map = new Map<string, BracketNode[]>()
  for (const n of nodes) {
    if (!map.has(n.round)) map.set(n.round, [])
    map.get(n.round)!.push(n)
  }
  return roundOrder.filter(r => map.has(r)).map(r => map.get(r)!)
}

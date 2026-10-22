import type { SimplePage } from '@/shared/api/http'
import { get } from '@/shared/api/http'
import type { GroupType, MatchDetail, MatchEventItem, MatchListItem, MatchStatus } from '../types/live'

export interface PublicMatchQuery {
  group_type?: GroupType | ''
  status?: MatchStatus | ''
  page?: number
}

/** P1 比赛列表（免登） */
export function fetchPublicMatches(params: PublicMatchQuery): Promise<SimplePage<MatchListItem>> {
  return get<SimplePage<MatchListItem>>('/public/live/matches', params as Record<string, unknown>)
}

/** P2 比赛详情（免登） */
export function fetchPublicMatchDetail(id: number): Promise<MatchDetail> {
  return get<MatchDetail>(`/public/live/matches/${id}`)
}

/** P3 时间轴（免登，since_id 增量） */
export function fetchPublicMatchEvents(id: number, sinceId = 0): Promise<MatchEventItem[]> {
  return get<MatchEventItem[]>(`/public/live/matches/${id}/events`, sinceId > 0 ? { since_id: sinceId } : {})
}

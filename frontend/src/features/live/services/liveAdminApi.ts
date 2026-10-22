import type { SimplePage } from '@/shared/api/http'
import http, { del, get, patch, post, put, request } from '@/shared/api/http'
import type {
  AdminMatchDetail,
  EventActionResult,
  EventPayload,
  EventUpdatePayload,
  FaceVerifyRecordItem,
  GroupType,
  MatchEventItem,
  MatchFileItem,
  MatchFileType,
  MatchListItem,
  MatchPayload,
  MatchStatus,
  RefereeRole,
} from '../types/live'

const BASE = '/admin/live'

export interface AdminMatchQuery {
  group_type?: GroupType | ''
  status?: MatchStatus | ''
  keyword?: string
  page?: number
}

// ---- 比赛 A1-A7 ----
export function fetchAdminMatches(params: AdminMatchQuery): Promise<SimplePage<MatchListItem>> {
  return get<SimplePage<MatchListItem>>(`${BASE}/matches`, params as Record<string, unknown>)
}

export function fetchAdminMatchDetail(id: number): Promise<AdminMatchDetail> {
  return get<AdminMatchDetail>(`${BASE}/matches/${id}`)
}

export function createMatch(data: MatchPayload): Promise<AdminMatchDetail> {
  return post<AdminMatchDetail>(`${BASE}/matches`, data)
}

export function updateMatch(id: number, data: MatchPayload & { version: number }): Promise<AdminMatchDetail> {
  return put<AdminMatchDetail>(`${BASE}/matches/${id}`, data)
}

export function deleteMatch(id: number): Promise<unknown> {
  return del(`${BASE}/matches/${id}`)
}

export function changeMatchStatus(
  id: number,
  data: { action: 'start' | 'finish', version: number },
): Promise<AdminMatchDetail> {
  return patch<AdminMatchDetail>(`${BASE}/matches/${id}/status`, data)
}

export function updateMatchLineup(
  id: number,
  data: { version: number, lineup_home: unknown[], lineup_away: unknown[] },
): Promise<AdminMatchDetail> {
  return put<AdminMatchDetail>(`${BASE}/matches/${id}/lineup`, data)
}

// ---- 事件 A10-A13 ----
export function fetchMatchEvents(matchId: number): Promise<MatchEventItem[]> {
  return get<MatchEventItem[]>(`${BASE}/matches/${matchId}/events`)
}

export function createMatchEvent(matchId: number, data: EventPayload): Promise<EventActionResult> {
  return post<EventActionResult>(`${BASE}/matches/${matchId}/events`, data)
}

export function updateMatchEvent(eventId: number, data: EventUpdatePayload): Promise<EventActionResult> {
  return put<EventActionResult>(`${BASE}/events/${eventId}`, data)
}

export function deleteMatchEvent(eventId: number): Promise<EventActionResult> {
  return del<EventActionResult>(`${BASE}/events/${eventId}`)
}

// ---- 裁判 A8-A9 ----
export interface RefereeRecord {
  id: number
  role: RefereeRole
  role_label: string
  name: string
}

export function fetchMatchReferees(matchId: number): Promise<RefereeRecord[]> {
  return get<RefereeRecord[]>(`${BASE}/matches/${matchId}/referees`)
}

export function replaceMatchReferees(
  matchId: number,
  referees: Array<{ role: RefereeRole, name: string }>,
): Promise<RefereeRecord[]> {
  return put<RefereeRecord[]>(`${BASE}/matches/${matchId}/referees`, { referees })
}

// ---- 赛事文件 A14-A17 ----
export function fetchMatchFiles(matchId: number): Promise<MatchFileItem[]> {
  return get<MatchFileItem[]>(`${BASE}/matches/${matchId}/files`)
}

export function uploadMatchFile(
  matchId: number,
  type: MatchFileType,
  file: File,
  signal?: AbortSignal,
): Promise<MatchFileItem> {
  const form = new FormData()
  form.append('type', type)
  form.append('file', file)
  return request<MatchFileItem>({
    method: 'POST',
    url: `${BASE}/matches/${matchId}/files`,
    data: form,
    signal,
  })
}

export function deleteMatchFile(fileId: number): Promise<unknown> {
  return del(`${BASE}/files/${fileId}`)
}

/** 鉴权下载：走 axios blob 携带 Authorization，成功后触发浏览器保存 */
export async function downloadMatchFile(fileId: number, fileName: string): Promise<void> {
  const response = await http.get(`${BASE}/files/${fileId}/download`, { responseType: 'blob' })
  const url = URL.createObjectURL(response.data as Blob)
  const a = document.createElement('a')
  a.href = url
  a.download = fileName
  a.click()
  URL.revokeObjectURL(url)
}

// ---- 直播链接 A18 ----
export function updateLiveLink(
  matchId: number,
  liveLink: string | null,
): Promise<{ live_link: string | null, version: number }> {
  return put(`${BASE}/matches/${matchId}/live-link`, { live_link: liveLink })
}

// ---- 人脸核验 A19-A20 ----
export interface FaceVerifyPayload {
  player_name: string
  player_no?: number | null
  photo?: File | null
}

export function submitFaceVerify(matchId: number, payload: FaceVerifyPayload): Promise<FaceVerifyRecordItem> {
  const form = new FormData()
  form.append('player_name', payload.player_name)
  if (payload.player_no !== null && payload.player_no !== undefined) {
    form.append('player_no', String(payload.player_no))
  }
  if (payload.photo) {
    form.append('photo', payload.photo)
  }
  return post<FaceVerifyRecordItem>(`${BASE}/matches/${matchId}/face-verify`, form)
}

export function fetchFaceVerifyRecords(matchId: number): Promise<FaceVerifyRecordItem[]> {
  return get<FaceVerifyRecordItem[]>(`${BASE}/matches/${matchId}/face-verify-records`)
}

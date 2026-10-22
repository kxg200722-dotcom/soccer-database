# 接口文档（赛事直播模块）

统一约定：

- 所有响应为五字段结构：`{ code, msg, data, success, trace_id }`，成功 `code=0`。
- 分页参数 `page`；simplePaginate 返回 `{ list, next_page }`（`next_page=null` 表示无下一页）。
- 管理端需 `Authorization: Bearer <token>`（登录接口 `/api/auth/login` 获取）。
- 管理端所有 `version` 字段为乐观锁：携带过期 version → `code=9`。
- 比赛 `finished` 后所有写操作 → `code=40002`；重复提交（同 client_token）→ `code=40009`。

响应码速查（本模块使用）：

| Code | 含义 | 触发场景 |
|---|---|---|
| 0 | 成功 | — |
| 9 | 并发冲突（乐观锁） | version 过期 |
| 10001 | 参数错误 | 校验失败（msg 取首条错误信息） |
| 10006 | 文件格式错误 | 赛事文件非 pdf/jpg/png、照片非 jpg/png |
| 10007 | 文件过大 | 文件 >10MB |
| 20001 | 未登录/Token 失效 | 未携带或错误 Bearer token |
| 20002 | 账号或密码错误 | 登录失败 |
| 30001 | 数据不存在 | id 不存在 / 接口不存在 |
| 40002 | 当前状态不可操作 | finished 后修改 / live·finished 删除比赛 |
| 40009 | 重复提交 | client_token 冲突 |
| 50008 | 第三方接口超时/异常 | 人脸核验服务超时 |
| 60001 | 数据库异常 | SQL 执行失败 |
| 90001 | 系统异常 | 未捕获异常（排查用 trace_id） |

## 0. 认证

### POST /api/auth/login
请求：`{ email, password }`
成功 `data`：`{ token, user: { id, name, email, is_admin, permissions[] } }`
响应码：20002

### POST /api/auth/logout（登录）
无参数；使当前 token 失效。

### GET /api/auth/me（登录）
`data`：`{ id, name, email, is_admin, permissions[] }`

## 1. 公众端（免登，/api/public/live）

### P1 GET /api/public/live/matches
参数：`group_type?` `status?`（not_started/live/finished）`page?`
`data`：`{ list: MatchListItem[], next_page }`
- `live` 状态列表走 Redis 缓存（30s）。

MatchListItem：`{ id, group_type, group_type_label, home_team_name, away_team_name, home_team_logo, away_team_logo, match_time, venue, status, status_label, home_score, away_score }`

### P2 GET /api/public/live/matches/{id}
`data`：MatchListItem + `{ lineup_home: LineupPlayer[]|null, lineup_away, live_link }`
- 详情缓存 TTL 10s（与前端轮询同节拍）。
- LineupPlayer：`{ name, no, position?, is_captain? }`

响应码：30001

### P3 GET /api/public/live/matches/{id}/events
参数：`since_id?`（增量拉取，仅返回 id > since_id 的事件，上限 200 条）
`data`：`MatchEventItem[]`，按 `(minute, id)` 升序。

MatchEventItem：`{ id, type, type_label, minute, team_side, player_name, player_no, related_player_name, content, created_at }`
- type ∈ goal/yellow/red/substitution/text。

响应码：30001

## 2. 管理端（/api/admin/live，auth:sanctum + live:* 权限点）

### A1 GET /matches
权限 `live:match:view`。参数：`group_type?` `status?` `keyword?`（队名模糊）`page?`
`data`：`{ list: MatchListItem[], next_page }`

### A2 POST /matches
权限 `live:match:create`。
请求：`{ group_type*, home_team_name*, away_team_name*, match_time*(date), home_team_logo?, away_team_logo?, venue? }`
`data`：AdminMatchDetail。

### A3 GET /matches/{id}
权限 `live:match:view`。`data`：AdminMatchDetail。

AdminMatchDetail = MatchListItem + `{ lineup_home, lineup_away, live_link, referees[]: { role, role_label, name }, version, created_by, updated_by, created_at, updated_at }`

### A4 PUT /matches/{id}
权限 `live:match:update`。
请求：`{ version* } + A2 字段（均 sometimes）`。
响应码：10001 / 30001 / 9 / 40002

### A5 DELETE /matches/{id}
权限 `live:match:delete`。仅 `not_started` 可删；live/finished → 40002。

### A6 PATCH /matches/{id}/status
权限 `live:match:update`。
请求：`{ action: "start"|"finish", version* }`，状态机 not_started→live→finished 单向不可逆。
响应码：10001 / 9 / 40002

### A7 PUT /matches/{id}/lineup
权限 `live:match:update`。
请求：`{ version*, lineup_home: LineupPlayer[](present), lineup_away: LineupPlayer[](present) }`（整体保存，空数组表示清空）。
响应码：10001 / 9 / 40002

### A8 GET /matches/{id}/referees
权限 `live:referee:manage`。`data`：`{ id, role, role_label, name }[]`

### A9 PUT /matches/{id}/referees
权限 `live:referee:manage`。整体替换。
请求：`{ referees: { role: chief|assistant|fourth, name }[] (max 4, present) }`
规则：chief≤1、fourth≤1、assistant≤2，违反 → 10001。

### A10 GET /matches/{match}/events
权限 `live:event:manage`。`data`：`MatchEventItem[]`（全量，按 minute,id 升序）。

### A11 POST /matches/{match}/events
权限 `live:event:manage`。
请求：`{ type*, minute*(0-130), client_token*, team_side?, player_name?, player_no?, related_player_name?, content? }`
- `team_side`/`player_name`：type ∈ goal/yellow/red/substitution 必填。
- `related_player_name`：substitution 必填（换上球员）。
- `content`：text 必填（≤500 字）。
- `client_token`：前端 UUID，唯一索引防重复提交。

`data`：`{ event: MatchEventItem, home_score, away_score, version }`（goal 事件同事务联动比分，前端局部更新）。
响应码：10001 / 30001 / 40002 / 40009

### A12 PUT /events/{id}
权限 `live:event:manage`。部分更新（同 A11 规则，字段 sometimes；client_token 不可改）。
类型/归属变化时比分先回退再重算（同事务）。
`data`：同 A11。
响应码：10001 / 30001 / 40002

### A13 DELETE /events/{id}
权限 `live:event:manage`。goal 删除同事务比分 -1。
`data`：`{ home_score, away_score, version }`（无 event）。
响应码：30001 / 40002

### A14 GET /matches/{match}/files
权限 `live:file:manage`。
`data`：`{ id, type, type_label, file_name, mime, size, created_at }[]`
type ∈ sign_in/record_confirm/referee_report。

### A15 POST /matches/{match}/files
权限 `live:file:manage`。multipart：`type*` + `file*`（pdf/jpg/jpeg/png，≤10MB）。
`data`：同 A14 单条。
响应码：10001 / 10006 / 10007 / 30001

### A16 GET /files/{id}/download
权限 `live:file:manage`。私有 disk 鉴权流式下载（匿名 → 20001）。
成功为文件流（非 JSON）。

### A17 DELETE /files/{id}
权限 `live:file:manage`。删库 + 删物理文件（物理删除失败仅记 exception 日志，不回滚）。

### A18 PUT /matches/{match}/live-link
权限 `live:link:manage`。
请求：`{ live_link: url|null }`（null/空串 清空）。
响应码：10001 / 40002

### A19 POST /matches/{match}/face-verify
权限 `live:face:manage`。multipart：`player_name*`、`player_no?`（0-99）、`photo*`（jpg/png ≤10MB）。
当前为 mock driver（config/faceverify.php），调 FaceVerifyClient 契约（3s 超时）。
`data`：FaceVerifyRecord 单条。
响应码：10001 / 10006 / 10007 / 50008（超时/异常）

### A20 GET /matches/{match}/face-verify-records
权限 `live:face:manage`。
`data`：`{ id, player_name, player_no, result, result_label, score, channel, request_id, created_at }[]`
result ∈ pass/fail/pending。

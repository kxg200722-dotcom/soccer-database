# 功能映射表（MAPPING）

> 逐条对照功能设计登记实现状态。范围：赛事直播模块（门户域）。完成日期：2026-10-22。

## 一、公众侧（赛事门户）

| # | 功能点 | 实现 | 关联接口/页面 | 状态 |
|---|---|---|---|---|
| 1 | 直播列表页（组别/状态筛选、比分展示） | LiveListView + LiveCard | P1 GET /api/public/live/matches | ✅ |
| 2 | 直播观看页-实时比分 | ScoreBoard | P2 GET /api/public/live/matches/{id} | ✅ |
| 3 | 直播观看页-图文时间轴（10s 轮询增量刷新、切后台暂停、结束自动停） | EventTimeline + useLivePolling | P3 GET /api/public/live/matches/{id}/events?since_id | ✅ |
| 4 | 直播观看页-双方阵容 | LineupPanel | P2（lineup_home/away） | ✅ |
| 5 | 直播观看页-第三方直播链接入口 | LiveLinkEntry | P2（live_link） | ✅ |

## 二、管理侧（赛事管理后台）

| # | 功能点 | 实现 | 关联接口 | 状态 |
|---|---|---|---|---|
| 6 | 比赛录入/编辑（组别、队名、Logo、时间、场地） | MatchListView + MatchFormDialog | A1-A4 | ✅ |
| 7 | 比赛删除（仅未开始） | MatchTable | A5 | ✅ |
| 8 | 比赛状态流转（开始/结束，单向） | AdminHeader | A6 PATCH /matches/{id}/status | ✅ |
| 9 | 双方阵容录入 | LineupEditor | A7 PUT /matches/{id}/lineup | ✅ |
| 10 | 裁判员录入（主裁≤1/助理≤2/第四官员≤1，整体替换） | RefereeFormPanel | A8-A9 | ✅ |
| 11 | 进球事件（联动比分、同事务） | EventManagePanel + EventFormDialog | A10-A13 | ✅ |
| 12 | 判罚事件（黄牌/红牌） | 同上 | A10-A13 | ✅ |
| 13 | 换人事件（换上球员关联） | 同上 | A10-A13 | ✅ |
| 14 | 文本直播事件 | 同上 | A10-A13 | ✅ |
| 15 | 事件防重复提交（client_token 幂等 → 40009） | useEventActions | A11 | ✅ |
| 16 | 删除进球事件比分回退（同事务） | EventTable | A13 | ✅ |
| 17 | 赛事文件上传（签到表/记录集签字确认表/裁判员报告书，pdf/jpg/png ≤10MB） | FileManagePanel | A14-A15 | ✅ |
| 18 | 赛事文件下载（私有 disk 鉴权，匿名 20001） | FileManagePanel | A16 | ✅ |
| 19 | 赛事文件删除 | FileManagePanel | A17 | ✅ |
| 20 | 直播链接管理（url 校验、可清空，结束锁定） | LiveLinkDialog | A18 | ✅ |
| 21 | 球员人脸核验（mock 契约先行，超时 → 50008） | FaceVerifyPanel | A19 | ✅ |
| 22 | 人脸核验记录列表 | FaceVerifyPanel | A20 | ✅ |

## 三、横切能力

| # | 功能点 | 实现 | 状态 |
|---|---|---|---|
| 23 | 管理员登录/登出/当前用户 | AuthController + AdminLoginView | ✅ |
| 24 | 权限点控制（live:match/event/referee/file/link/face:*） | permission 中间件 + v-permission | ✅ |
| 25 | 统一响应五字段 + trace_id | Result + TraceIdMiddleware | ✅ |
| 26 | 三通道日志（business 14d / exception 30d / api 7d） | config/logging.php + 中间件 | ✅ |
| 27 | 乐观锁并发控制（version → 9） | MatchService 条件更新 | ✅ |
| 28 | finished 锁定全部写操作（→ 40002） | 各 Service | ✅ |
| 29 | live 列表 Redis 缓存 30s / 详情缓存 10s / 变更主动失效 | LiveQueryService + afterCommit forget | ✅ |
| 30 | 冒烟测试（计划 §七 8 大项全覆盖） | backend/scripts/smoke_test.py 41/41 | ✅ |

## 四、遗留问题

| # | 事项 | 说明 |
|---|---|---|
| 1 | 人脸核验真实第三方接入 | 契约已就绪（ADR-002），待供应商确定后新增实现类并切 config driver |
| 2 | 队伍 Logo 公开素材上传 | 当前 Logo 仅支持填 URL 字符串；公开素材 public disk 上传待新闻首页模块一并设计 |

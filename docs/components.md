# 组件登记（赛事直播模块）

> 团队手册要求：复用组件（≥2 页面/模块）必须登记名称、Props、使用场景。新增/修改组件同步更新本文档。

## 1. 通用组件（src/shared/ui/）

| 组件 | Props / Emits | 使用场景 |
|---|---|---|
| StatusTag.vue | `status: MatchStatus` | 比赛状态标签（未开始/直播中/已结束），公众端与管理端列表/详情通用 |
| EmptyBox.vue | `text?: string` | 空数据占位（无比赛、无事件、无文件） |
| UploadButton.vue | `accept?: string`、`loading?: boolean`；emit `select(file: File)` | 统一上传触发按钮（赛事文件、人脸核验照片） |

## 2. 公众端组件（src/features/live/components/public/）

| 组件 | Props / Emits | 使用场景 |
|---|---|---|
| LiveCard.vue | `match: MatchListItem` | 直播列表页单场卡，**三段式**：顶行"组别·状态"头条（直播中带红跳点）+ 右侧小比分；中间一行"主队 VS 客队"纯文字对阵 + 待定场次右上"未开赛"角标；底部日期·场地。无头像/Logo。左侧竖色条按状态区分（红/绿/灰）。点击进详情。 |
| BracketNode.vue | `node: BracketNode` | 双败淘汰树**单场比赛卡**（节点）。顶部 round 小标签（胜者组 N/败者组 N/冠军赛）、中间队伍名+冒号分隔、比分行（待定=VS/已完赛灰/直播中红）、底部状态小标。左侧竖色条（live 红/finished 绿/pending 灰）。败者组节点右上角 CSS `::after` 灰色"败"字。Grand Final 金色边框+渐变底。宽度 200px，树内密集排布。 |
| BracketTree.vue | `data: BracketData`；emit `nodeClick(id)` | **双败淘汰树容器**：胜者组（上半区）+ 败者组（下半区）+ Grand Final（最右）横向展开，每列独立 round 标题。**SVG 折线连线**（水平→垂直→水平，蓝/橙/绿三色按 kind 轮询），动态计算节点锚点坐标，ResizeObserver 监听尺寸变化自动重算。外层横向滚动容器。 |
| ScoreBoard.vue | `match: MatchDetail` | 观看页比分区（队名/Logo/比分/状态） |
| EventTimeline.vue | `events: MatchEventItem[]` | 图文直播时间轴（类型图标 + 分钟 + 描述） |
| LineupPanel.vue | `home: LineupPlayer[] \| null`、`away: LineupPlayer[] \| null` | 观看页双方阵容展示 |
| LiveLinkEntry.vue | `link: string \| null` | 第三方直播链接入口（为空不渲染） |

## 3. 管理端组件（src/features/live/components/admin/）

| 组件 | Props / Emits | 使用场景 |
|---|---|---|
| AdminHeader.vue | `match: AdminMatchDetail`；emits 操作事件 | 单场管理页头部（比赛信息 + 状态操作） |
| MatchSearchForm.vue | `modelValue: SearchForm`；emit `search` / `reset` | 比赛列表查询表单（组别/状态/关键词，300ms 防抖） |
| MatchTable.vue | `list: MatchListItem[]`、`loading: boolean`；emit `edit` / `remove` / `manage` | 比赛列表表格 |
| MatchFormDialog.vue | `visible`、`match?: AdminMatchDetail`；emit `saved` | 新建/编辑比赛弹窗（重开清空；确定按钮 Loading+禁用） |
| LineupEditor.vue | `v-model: LineupPlayer[]` | 阵容编辑（MatchFormDialog 与详情页阵容 Tab 复用） |
| EventManagePanel.vue | `match: AdminMatchDetail` | 事件管理 Tab 容器（表格 + 新增按钮） |
| EventTable.vue | `events`、`loading`；emit `edit` / `remove` | 事件列表 |
| EventFormDialog.vue | `visible`、`matchId`、`event?`；emit `saved(result)` | 事件新增/编辑弹窗，按 type 动态显隐字段，提交带 client_token |
| RefereeFormPanel.vue | `matchId` | 裁判配置 Tab（chief/assistant×2/fourth 整体替换） |
| FileManagePanel.vue | `matchId` | 赛事文件 Tab（列表/上传/下载/删除） |
| LiveLinkDialog.vue | `visible`、`matchId`、`current: string \| null`；emit `saved` | 直播链接设置弹窗（可清空） |
| FaceVerifyPanel.vue | `matchId` | 人脸核验 Tab（拍照上传 + 核验记录列表） |

## 4. Composables（src/features/live/composables/ 与 shared/）

| Composable | 说明 |
|---|---|
| useLivePolling(matchId) | 公众观看页 10s 轮询 + since_id 增量；visibilitychange 隐藏暂停/显示即刷；finished 自动停；onUnmounted 清理 |
| useEventActions(matchId) | 事件增删改；add 自动生成 crypto.randomUUID() client_token；40009 提示不重发；成功用返回值局部更新比分 |
| usePagination / useTable / useDict / useUpload / useRequest | shared/composables 通用能力（分页、表格、字典缓存、上传、请求态） |
| bracketMock.ts（src/features/live/composables/） | **演示数据生成器 + TS 契约**。导出 `BracketNode/BracketConnection/BracketData` 类型 + `generateDemoBracket(group)` 生成双败淘汰 16 强树 + `groupByRound()` 列分组。胜者组 4 轮 + 败者组 4 轮 + Grand Final 共 9 列。真实数据接入时替换为 `/api/public/live/bracket` 接口，前端只改数据源。 |

## 5. 视图与路由

| 视图 | 路由 | 说明 |
|---|---|---|
| LiveListView | /live | **双败淘汰树状图（演示数据）**：9 列横向布局（胜者组 R1-R4 + 败者组 LR1-LR4 + Grand Final），SVG 折线连线，组别 el-radio-button 切换（大学/中学/小学三组不同队名池），横向滚动，顶部黄色"演示数据"alert 标注。真实数据接入路径：后端 `/api/public/live/bracket` 按 BracketData 契约返回，前端替换 bracketMock.ts 数据源。 |
| LiveDetailView | /live/:id | 公众观看页（壳，组装 ScoreBoard/EventTimeline/LineupPanel/LiveLinkEntry） |
| AdminLoginView | /admin/login | 管理员登录 |
| MatchListView | /admin/live/matches | 比赛管理列表（壳：SearchForm/Table/FormDialog） |
| MatchDetailAdminView | /admin/live/matches/:id | 单场管理页（壳，五 Tab：事件/裁判/文件/阵容/人脸核验） |

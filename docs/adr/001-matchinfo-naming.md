# ADR-001：比赛模型命名 MatchInfo

- 状态：已接受（2026-10-22）
- 上下文：赛事直播模块核心实体为"比赛"，对应数据表 `matches`。
- 决策：Eloquent 模型命名为 `MatchInfo`（`protected $table = 'matches'`），不使用 `Match`。
- 理由：`match` 是 PHP 8 的保留字（match 表达式），类名 `Match` 在 PHP 8 下为非法/受限，会导致解析错误或 IDE/静态分析告警。
- 后果：
  - 所有代码引用 `App\Models\MatchInfo`；路由模型绑定显式声明 `{match}` → MatchInfo。
  - 数据表、路由路径、API 字段不受命名影响（仍为 matches / match_id）。
  - 备选名 `Game`/`Fixture` 未采用：与业务术语"比赛（match）"偏差更大。

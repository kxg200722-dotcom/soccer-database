# ADR-002：文件存储策略与人脸核验契约

- 状态：已接受（2026-10-22）

## 一、赛事文件存储

- 上下文：三类赛事文件（签到表 / 记录集签字确认表 / 裁判员报告书）含敏感信息，匿名访客不可访问；队伍 Logo 等公开素材需匿名直链。
- 决策：
  1. 赛事文件走 **local 私有 disk**（storage/app/private），不生成公开 URL；
  2. 下载统一经 `GET /api/admin/live/files/{id}/download`（auth:sanctum + live:file:manage），Service 流式输出；
  3. 公开素材（Logo 等）后续接入时走 **public disk**，与私有文件严格分目录；
  4. 删除文件：先删库（事务内），物理删除失败仅记 exception 日志不回滚（避免文件系统抖动拖垮业务）。
- 后果：匿名访问私有文件恒 20001；文件访问全程可审计（api 日志含操作者与耗时）。

## 二、人脸核验契约

- 上下文：球员人脸核验需对接第三方服务，但供应商未定、联调周期长，不能阻塞模块交付。
- 决策：
  1. 定义 `App\Integrations\FaceVerify\FaceVerifyClient` 接口 + `VerifyResultDTO`；
  2. 提供 `MockFaceVerifyClient`（默认通过；姓名含 "timeout" 模拟超时）；
  3. 容器按 `config/faceverify.php` 的 `driver` 绑定实现（默认 mock），超时阈值 3s；
  4. 超时/异常 → BusinessException(50008) 并写 exception 日志。
- 后果：替换真实第三方仅需新增实现类 + 改 config，业务代码零改动；冒烟测试可通过 mock 确定性覆盖成功与超时两条路径。

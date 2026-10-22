"""T4 验收：比赛 CRUD + 状态流转 + 阵容（A1-A7）"""
import json
import sys
import urllib.request

BASE = "http://127.0.0.1:8000/api"
failures = []


def req(method, path, body=None, token=None):
    r = urllib.request.Request(
        BASE + path,
        method=method,
        data=json.dumps(body).encode() if body is not None else None,
        headers={
            "Content-Type": "application/json",
            "Accept": "application/json",
            **({"Authorization": f"Bearer {token}"} if token else {}),
        },
    )
    try:
        with urllib.request.urlopen(r) as resp:
            return json.loads(resp.read())
    except urllib.error.HTTPError as e:
        return json.loads(e.read())


def check(name, cond):
    print(("PASS " if cond else "FAIL ") + name)
    if not cond:
        failures.append(name)


# 登录
login = req("POST", "/auth/login", {"email": "admin@ball.local", "password": "admin123"})
token = login["data"]["token"]

# 无 token → 20001
check("no token -> 20001", req("GET", "/admin/live/matches")["code"] == 20001)

# 缺字段 → 10001
check("missing fields -> 10001", req("POST", "/admin/live/matches", {"group_type": "university"}, token)["code"] == 10001)

# 创建
created = req("POST", "/admin/live/matches", {
    "group_type": "university", "home_team_name": "测试主队", "away_team_name": "测试客队",
    "match_time": "2026-10-25 15:00:00", "venue": "测试场",
}, token)
check("create ok", created["code"] == 0 and created["data"]["status"] == "not_started")
mid = created["data"]["id"]

# 旧 version 更新 → 9
check("old version -> 9", req("PUT", f"/admin/live/matches/{mid}", {"version": 99, "venue": "x"}, token)["code"] == 9)

# 正常更新
upd = req("PUT", f"/admin/live/matches/{mid}", {"version": 0, "venue": "新场地"}, token)
check("update ok + version+1", upd["code"] == 0 and upd["data"]["venue"] == "新场地" and upd["data"]["version"] == 1)

# 状态机：finish 非法（not_started 不能直接 finish）→ 40002
check("not_started finish -> 40002", req("PATCH", f"/admin/live/matches/{mid}/status", {"action": "finish", "version": 1}, token)["code"] == 40002)

# start
started = req("PATCH", f"/admin/live/matches/{mid}/status", {"action": "start", "version": 1}, token)
check("start ok", started["code"] == 0 and started["data"]["status"] == "live")

# 阵容（live 状态可改）
lineup = [{"name": "张三", "no": "10", "position": "前锋"}]
lu = req("PUT", f"/admin/live/matches/{mid}/lineup", {"version": 2, "lineup_home": lineup, "lineup_away": []}, token)
check("lineup ok", lu["code"] == 0 and lu["data"]["lineup_home"][0]["name"] == "张三")

# live 状态删除 → 40002
check("delete live -> 40002", req("DELETE", f"/admin/live/matches/{mid}", token=token)["code"] == 40002)

# 公众端详情可见（缓存失效生效）
pub = req("GET", f"/public/live/matches/{mid}")
check("public detail updated", pub["code"] == 0 and pub["data"]["venue"] == "新场地" and pub["data"]["lineup_home"][0]["no"] == "10")

# finish
finished = req("PATCH", f"/admin/live/matches/{mid}/status", {"action": "finish", "version": 3}, token)
check("finish ok", finished["code"] == 0 and finished["data"]["status"] == "finished")

# finished 后 PUT → 40002
check("finished PUT -> 40002", req("PUT", f"/admin/live/matches/{mid}", {"version": 4, "venue": "y"}, token)["code"] == 40002)

# finished 删除 → 40002
check("delete finished -> 40002", req("DELETE", f"/admin/live/matches/{mid}", token=token)["code"] == 40002)

# 新建一场 not_started 可删
tmp = req("POST", "/admin/live/matches", {
    "group_type": "primary_school", "home_team_name": "删甲", "away_team_name": "删乙", "match_time": "2026-10-26 10:00:00",
}, token)["data"]["id"]
check("delete not_started ok", req("DELETE", f"/admin/live/matches/{tmp}", token=token)["code"] == 0)

print("\n%d failures" % len(failures))
sys.exit(1 if failures else 0)

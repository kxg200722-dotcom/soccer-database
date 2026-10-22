"""T5 验收：事件子系统 A10-A13（事务核心）
- 同 client_token 重发 → 40009
- 3 个 goal 后比分 2:1 且等于事件聚合
- goal 改归属比分重算、删 goal 比分回退
- finished 后事件增改删 → 40002
"""
import json
import sys
import urllib.request
import uuid

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


def goal(token_val, minute, side, player, no=9):
    return {"type": "goal", "minute": minute, "team_side": side,
            "player_name": player, "player_no": no, "client_token": token_val}


login = req("POST", "/auth/login", {"email": "admin@ball.local", "password": "admin123"})
token = login["data"]["token"]

# 建赛并开赛
mid = req("POST", "/admin/live/matches", {
    "group_type": "middle_school", "home_team_name": "事件主队", "away_team_name": "事件客队",
    "match_time": "2026-10-25 16:00:00",
}, token)["data"]["id"]
req("PATCH", f"/admin/live/matches/{mid}/status", {"action": "start", "version": 0}, token)

# 无 token → 20001
check("no token -> 20001", req("GET", f"/admin/live/matches/{mid}/events")["code"] == 20001)

# 条件校验：goal 缺 team_side → 10001；text 缺 content → 10001
bad = {"type": "goal", "minute": 5, "client_token": uuid.uuid4().hex}
check("goal missing team_side -> 10001", req("POST", f"/admin/live/matches/{mid}/events", bad, token)["code"] == 10001)
bad2 = {"type": "text", "minute": 5, "client_token": uuid.uuid4().hex}
check("text missing content -> 10001", req("POST", f"/admin/live/matches/{mid}/events", bad2, token)["code"] == 10001)

# 3 个 goal：主、主、客
t1, t2, t3 = uuid.uuid4().hex, uuid.uuid4().hex, uuid.uuid4().hex
r1 = req("POST", f"/admin/live/matches/{mid}/events", goal(t1, 10, "home", "张三", 10), token)
check("goal1 ok score 1:0", r1["code"] == 0 and r1["data"]["home_score"] == 1 and r1["data"]["away_score"] == 0)
r2 = req("POST", f"/admin/live/matches/{mid}/events", goal(t2, 20, "home", "李四", 7), token)
check("goal2 ok score 2:0", r2["code"] == 0 and r2["data"]["home_score"] == 2)
r3 = req("POST", f"/admin/live/matches/{mid}/events", goal(t3, 30, "away", "王五", 11), token)
check("goal3 ok score 2:1", r3["code"] == 0 and r3["data"]["home_score"] == 2 and r3["data"]["away_score"] == 1)
e1 = r1["data"]["event"]["id"]

# 同 client_token 重发 → 40009
dup = req("POST", f"/admin/live/matches/{mid}/events", goal(t1, 10, "home", "张三"), token)
check("same client_token -> 40009", dup["code"] == 40009)

# 比分 = 事件聚合（公众端口径核对）
pub = req("GET", f"/public/live/matches/{mid}")
evs = req("GET", f"/public/live/matches/{mid}/events?since_id=0")["data"]
agg_home = sum(1 for e in evs if e["type"] == "goal" and e["team_side"] == "home")
agg_away = sum(1 for e in evs if e["type"] == "goal" and e["team_side"] == "away")
check("score == event aggregation (2:1)",
      pub["data"]["home_score"] == agg_home == 2 and pub["data"]["away_score"] == agg_away == 1)

# 管理端事件列表
admin_evs = req("GET", f"/admin/live/matches/{mid}/events", token=token)
check("admin events list == 3", admin_evs["code"] == 0 and len(admin_evs["data"]) == 3)

# 改 goal 归属 home→away：2:1 → 1:2
upd = req("PUT", f"/admin/live/events/{e1}",
          {"type": "goal", "team_side": "away", "player_name": "张三", "minute": 11}, token)
check("flip goal side -> 1:2",
      upd["code"] == 0 and upd["data"]["home_score"] == 1 and upd["data"]["away_score"] == 2)

# 部分更新 minute（不传 type 不强制必填），比分不变
part = req("PUT", f"/admin/live/events/{e1}", {"minute": 12}, token)
check("partial update minute ok", part["code"] == 0 and part["data"]["event"]["minute"] == 12
      and part["data"]["home_score"] == 1)

# 删该 goal → 1:1 回退
dele = req("DELETE", f"/admin/live/events/{e1}", token=token)
check("delete goal -> score rollback 1:1",
      dele["code"] == 0 and dele["data"]["home_score"] == 1 and dele["data"]["away_score"] == 1)

# 补一条 text 事件（无比分联动）
txt = req("POST", f"/admin/live/matches/{mid}/events",
          {"type": "text", "minute": 35, "content": "中场哨响", "client_token": uuid.uuid4().hex}, token)
check("text event ok no score change", txt["code"] == 0 and txt["data"]["home_score"] == 1)

# 完赛
ver = req("GET", f"/admin/live/matches/{mid}", token=token)["data"]["version"]
fin = req("PATCH", f"/admin/live/matches/{mid}/status", {"action": "finish", "version": ver}, token)
check("finish ok", fin["code"] == 0 and fin["data"]["status"] == "finished")

# finished 后事件增/改/删 → 40002
e2 = r2["data"]["event"]["id"]
add_after = req("POST", f"/admin/live/matches/{mid}/events", goal(uuid.uuid4().hex, 60, "home", "赵六"), token)
check("add after finish -> 40002", add_after["code"] == 40002)
check("update after finish -> 40002",
      req("PUT", f"/admin/live/events/{e2}", {"minute": 61}, token)["code"] == 40002)
check("delete after finish -> 40002",
      req("DELETE", f"/admin/live/events/{e2}", token=token)["code"] == 40002)

print("\n%d failures" % len(failures))
sys.exit(1 if failures else 0)

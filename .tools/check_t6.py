"""T6 验收：裁判 A8-A9（整体替换，角色人数上限）"""
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


login = req("POST", "/auth/login", {"email": "admin@ball.local", "password": "admin123"})
token = login["data"]["token"]

mid = req("POST", "/admin/live/matches", {
    "group_type": "primary_school", "home_team_name": "裁判主队", "away_team_name": "裁判客队",
    "match_time": "2026-10-26 09:00:00",
}, token)["data"]["id"]

# 无 token → 20001
check("no token -> 20001", req("GET", f"/admin/live/matches/{mid}/referees")["code"] == 20001)

# 初始为空
check("initial empty", req("GET", f"/admin/live/matches/{mid}/referees", token=token)["data"] == [])

# chief×2 → 10001
dup_chief = {"referees": [{"role": "chief", "name": "裁判甲"}, {"role": "chief", "name": "裁判乙"}]}
check("chief x2 -> 10001", req("PUT", f"/admin/live/matches/{mid}/referees", dup_chief, token)["code"] == 10001)

# assistant×3 → 10001
dup_asst = {"referees": [{"role": "assistant", "name": f"助{i}"} for i in range(3)]}
check("assistant x3 -> 10001", req("PUT", f"/admin/live/matches/{mid}/referees", dup_asst, token)["code"] == 10001)

# 缺 name → 10001
check("missing name -> 10001",
      req("PUT", f"/admin/live/matches/{mid}/referees", {"referees": [{"role": "chief"}]}, token)["code"] == 10001)

# 合法组合：chief+assistant×2+fourth → 0
ok_combo = {"referees": [
    {"role": "chief", "name": "主裁王"},
    {"role": "assistant", "name": "边裁李"},
    {"role": "assistant", "name": "边裁赵"},
    {"role": "fourth", "name": "四官钱"},
]}
saved = req("PUT", f"/admin/live/matches/{mid}/referees", ok_combo, token)
check("valid combo ok", saved["code"] == 0 and len(saved["data"]) == 4)

# 公众端详情同步可见（缓存失效）
pub = req("GET", f"/public/live/matches/{mid}")
check("public detail referees synced",
      pub["code"] == 0 and len(pub["data"]["referees"]) == 4
      and pub["data"]["referees"][0]["name"] == "主裁王")

# 整体替换为仅 chief
replace = req("PUT", f"/admin/live/matches/{mid}/referees", {"referees": [{"role": "chief", "name": "新主裁"}]}, token)
check("replace to 1 ok", replace["code"] == 0 and len(replace["data"]) == 1
      and replace["data"][0]["name"] == "新主裁")
check("get after replace == 1",
      len(req("GET", f"/admin/live/matches/{mid}/referees", token=token)["data"]) == 1)

# 完赛后替换 → 40002
ver = req("GET", f"/admin/live/matches/{mid}", token=token)["data"]["version"]
req("PATCH", f"/admin/live/matches/{mid}/status", {"action": "start", "version": ver}, token)
ver2 = req("GET", f"/admin/live/matches/{mid}", token=token)["data"]["version"]
req("PATCH", f"/admin/live/matches/{mid}/status", {"action": "finish", "version": ver2}, token)
check("replace after finish -> 40002",
      req("PUT", f"/admin/live/matches/{mid}/referees", {"referees": []}, token)["code"] == 40002)

print("\n%d failures" % len(failures))
sys.exit(1 if failures else 0)

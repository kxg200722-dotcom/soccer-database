"""T8 验收：直播链接 A18 + 人脸核验 A19-A20（mock）
- live_link 非法 url → 10001；可置空；finished → 40002
- mock pass 落库（channel/request_id/score）；mock 超时 → 50008 且写 exception 日志
- 照片伪格式 → 10006
"""
import json
import sys
import urllib.request
import uuid

BASE = "http://127.0.0.1:8000/api"
failures = []

# 1x1 PNG
PNG_BYTES = bytes.fromhex(
    "89504e470d0a1a0a0000000d49484452000000010000000108060000001f15c489"
    "0000000d49444154789c626001000000ffff03000006000557bfabd4"
    "0000000049454e44ae426082"
)


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


def upload(path, fields, file_field, filename, content, ctype, token=None):
    boundary = uuid.uuid4().hex
    body = b""
    for k, v in fields.items():
        body += f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode()
    body += (f'--{boundary}\r\nContent-Disposition: form-data; name="{file_field}"; '
             f'filename="{filename}"\r\nContent-Type: {ctype}\r\n\r\n').encode()
    body += content + f'\r\n--{boundary}--\r\n'.encode()
    r = urllib.request.Request(BASE + path, data=body, method="POST", headers={
        "Content-Type": f"multipart/form-data; boundary={boundary}",
        "Accept": "application/json",
        **({"Authorization": f"Bearer {token}"} if token else {}),
    })
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
    "group_type": "middle_school", "home_team_name": "链接主队", "away_team_name": "链接客队",
    "match_time": "2026-10-28 10:00:00",
}, token)["data"]["id"]

# ---- A18 直播链接 ----
check("no token live-link -> 20001",
      req("PUT", f"/admin/live/matches/{mid}/live-link", {"live_link": "https://a.com/live"})["code"] == 20001)

check("invalid url -> 10001",
      req("PUT", f"/admin/live/matches/{mid}/live-link", {"live_link": "not-a-url"}, token)["code"] == 10001)

setok = req("PUT", f"/admin/live/matches/{mid}/live-link", {"live_link": "https://live.example.com/m/123"}, token)
check("set link ok", setok["code"] == 0 and setok["data"]["live_link"] == "https://live.example.com/m/123")

pub = req("GET", f"/public/live/matches/{mid}")
check("public detail has link", pub["data"]["live_link"] == "https://live.example.com/m/123")

cleared = req("PUT", f"/admin/live/matches/{mid}/live-link", {"live_link": ""}, token)
check("clear link ok", cleared["code"] == 0 and cleared["data"]["live_link"] is None)

# ---- A19-A20 人脸核验 ----
check("no token face-verify -> 20001",
      req("POST", f"/admin/live/matches/{mid}/face-verify")["code"] == 20001)

fake = upload(f"/admin/live/matches/{mid}/face-verify", {"player_name": "球员甲", "player_no": "10"},
                 "photo", "p.png", b"not a png", "image/png", token)
check("fake png -> 10006", fake["code"] == 10006)

ok = upload(f"/admin/live/matches/{mid}/face-verify", {"player_name": "球员甲", "player_no": "10"},
               "photo", "face.png", PNG_BYTES, "image/png", token)
check("mock pass ok", ok["code"] == 0 and ok["data"]["result"] == "pass"
      and ok["data"]["channel"] == "mock" and ok["data"]["request_id"].startswith("mock-")
      and ok["data"]["score"] > 0)

# mock 超时 → 50008
tout = upload(f"/admin/live/matches/{mid}/face-verify", {"player_name": "timeout_player"},
                 "photo", "face2.png", PNG_BYTES, "image/png", token)
check("mock timeout -> 50008", tout["code"] == 50008)

# 记录列表：仅成功落库 1 条
recs = req("GET", f"/admin/live/matches/{mid}/face-verify-records", token=token)
check("records == 1 (timeout not recorded)",
      recs["code"] == 0 and len(recs["data"]) == 1 and recs["data"][0]["player_name"] == "球员甲")

# finished 后 live-link → 40002
ver = req("GET", f"/admin/live/matches/{mid}", token=token)["data"]["version"]
req("PATCH", f"/admin/live/matches/{mid}/status", {"action": "start", "version": ver}, token)
ver2 = req("GET", f"/admin/live/matches/{mid}", token=token)["data"]["version"]
req("PATCH", f"/admin/live/matches/{mid}/status", {"action": "finish", "version": ver2}, token)
check("live-link after finish -> 40002",
      req("PUT", f"/admin/live/matches/{mid}/live-link", {"live_link": "https://x.com"}, token)["code"] == 40002)

print("\n%d failures" % len(failures))
sys.exit(1 if failures else 0)

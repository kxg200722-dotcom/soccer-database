"""T7 验收：赛事文件 A14-A17
- 伪格式 → 10006；11MB 超限 → 10007；匿名下载 → 20001
- 上传/列表/下载内容一致/删除后 30001
"""
import json
import sys
import urllib.request
import uuid

BASE = "http://127.0.0.1:8000/api"
failures = []

# 最小合法 PDF 文件头
PDF_BYTES = b"%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Size 1>>\n%%EOF\n"


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


def upload(path, fields, filename, content, ctype, token=None):
    boundary = uuid.uuid4().hex
    body = b""
    for k, v in fields.items():
        body += f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode()
    body += (f'--{boundary}\r\nContent-Disposition: form-data; name="file"; '
             f'filename="{filename}"\r\nContent-Type: {ctype}\r\n\r\n').encode()
    body += content + f'\r\n--{boundary}--\r\n'.encode()
    r = urllib.request.Request(BASE + path, data=body, method="POST", headers={
        "Content-Type": f"multipart/form-data; boundary={boundary}",
        "Accept": "application/json",
        **({"Authorization": f"Bearer {token}"} if token else {}),
    })
    try:
        with urllib.request.urlopen(r) as resp:
            raw = resp.read()
            return json.loads(raw), raw
    except urllib.error.HTTPError as e:
        raw = e.read()
        try:
            return json.loads(raw), raw
        except json.JSONDecodeError:
            return {"code": -1, "raw": raw[:200]}, raw


def download(path, token=None):
    r = urllib.request.Request(BASE + path, headers={
        "Accept": "application/json",
        **({"Authorization": f"Bearer {token}"} if token else {}),
    })
    try:
        with urllib.request.urlopen(r) as resp:
            return resp.status, resp.read()
    except urllib.error.HTTPError as e:
        return e.code, e.read()


def check(name, cond):
    print(("PASS " if cond else "FAIL ") + name)
    if not cond:
        failures.append(name)


login = req("POST", "/auth/login", {"email": "admin@ball.local", "password": "admin123"})
token = login["data"]["token"]

mid = req("POST", "/admin/live/matches", {
    "group_type": "university", "home_team_name": "文件主队", "away_team_name": "文件客队",
    "match_time": "2026-10-27 14:00:00",
}, token)["data"]["id"]

# 无 token 列表 → 20001
check("no token list -> 20001", req("GET", f"/admin/live/matches/{mid}/files")["code"] == 20001)

# 伪格式（txt 内容伪装 pdf）→ 10006
fake, _ = upload(f"/admin/live/matches/{mid}/files", {"type": "sign_in"},
                 "fake.pdf", b"this is plain text, not pdf", "application/pdf", token)
check("fake pdf -> 10006", fake["code"] == 10006)

# 11MB 超限 → 10007（Laravel max:10240 规则，php.ini 已放宽至 20M）
big, _ = upload(f"/admin/live/matches/{mid}/files", {"type": "sign_in"},
                "big.pdf", PDF_BYTES + b"0" * (11 * 1024 * 1024), "application/pdf", token)
check("11MB -> 10007", big["code"] == 10007)

# 非法 type → 10001
badtype, _ = upload(f"/admin/live/matches/{mid}/files", {"type": "other"},
                    "a.pdf", PDF_BYTES, "application/pdf", token)
check("bad type -> 10001", badtype["code"] == 10001)

# 正常上传
ok, _ = upload(f"/admin/live/matches/{mid}/files", {"type": "sign_in"},
               "签到表.pdf", PDF_BYTES, "application/pdf", token)
check("upload ok", ok["code"] == 0 and ok["data"]["file_name"] == "签到表.pdf"
      and ok["data"]["type_label"] == "签到表")
fid = ok["data"]["id"]

# 列表
lst = req("GET", f"/admin/live/matches/{mid}/files", token=token)
check("list == 1", lst["code"] == 0 and len(lst["data"]) == 1)

# 匿名下载 → 20001
status, _ = download(f"/admin/live/files/{fid}/download")
check("anonymous download -> 20001", status == 200 and json.loads(_)["code"] == 20001 or status == 401)

# 鉴权下载内容一致
status, content = download(f"/admin/live/files/{fid}/download", token)
check("download content match", status == 200 and content == PDF_BYTES)

# 删除 → 0；再下载 → 30001；再删 → 30001
check("delete ok", req("DELETE", f"/admin/live/files/{fid}", token=token)["code"] == 0)
status2, body2 = download(f"/admin/live/files/{fid}/download", token)
check("download deleted -> 30001", json.loads(body2)["code"] == 30001)
check("delete again -> 30001", req("DELETE", f"/admin/live/files/{fid}", token=token)["code"] == 30001)

# 完赛后仍可上传裁判报告书（无 finished 锁定）
ver = req("GET", f"/admin/live/matches/{mid}", token=token)["data"]["version"]
req("PATCH", f"/admin/live/matches/{mid}/status", {"action": "start", "version": ver}, token)
ver2 = req("GET", f"/admin/live/matches/{mid}", token=token)["data"]["version"]
req("PATCH", f"/admin/live/matches/{mid}/status", {"action": "finish", "version": ver2}, token)
after, _ = upload(f"/admin/live/matches/{mid}/files", {"type": "referee_report"},
                  "报告书.pdf", PDF_BYTES, "application/pdf", token)
check("upload after finish ok", after["code"] == 0)

print("\n%d failures" % len(failures))
sys.exit(1 if failures else 0)

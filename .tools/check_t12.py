"""T12 联调：各错误码触发一次，断言五字段 + msg 文案对用户友好（前端拦截器直接 toast msg）。"""
import json
import urllib.error
import urllib.request
import uuid

BASE = 'http://127.0.0.1:8000/api'
passed = failed = 0


def check(name, cond, extra=''):
    global passed, failed
    if cond:
        passed += 1
        print(f'  PASS {name}')
    else:
        failed += 1
        print(f'  FAIL {name} {extra}')


def req(method, url, data=None, token=None, raw=None, ctype=None):
    r = urllib.request.Request(BASE + url, method=method)
    if token:
        r.add_header('Authorization', f'Bearer {token}')
    body = raw
    if data is not None:
        r.add_header('Content-Type', 'application/json')
        body = json.dumps(data).encode()
    if ctype:
        r.add_header('Content-Type', ctype)
    try:
        with urllib.request.urlopen(r, body) as resp:
            return json.loads(resp.read().decode())
    except urllib.error.HTTPError as e:
        return json.loads(e.read().decode())


def friendly(res):
    """五字段齐全且 msg 非空（前端 toast 直接展示）"""
    return (all(k in res for k in ('code', 'msg', 'data', 'success', 'trace_id'))
            and isinstance(res['msg'], str) and len(res['msg']) > 0)


login = req('POST', '/auth/login', {'email': 'admin@ball.local', 'password': 'admin123'})
token = login['data']['token']

# 20001 未登录
res = req('GET', '/admin/live/matches')
check('20001 未登录', res['code'] == 20001 and friendly(res), res)

# 10001 参数错误（缺字段建赛）
res = req('POST', '/admin/live/matches', {'group_type': 'university'}, token)
check('10001 参数错误', res['code'] == 10001 and friendly(res), res)

# 30001 数据不存在
res = req('GET', '/admin/live/matches/99999', token=token)
check('30001 不存在', res['code'] == 30001 and friendly(res), res)

# 9 乐观锁冲突（旧 version 更新）
detail = req('GET', '/admin/live/matches/1', token=token)['data']
payload = {k: detail[k] for k in ('group_type', 'home_team_name', 'away_team_name', 'match_time')}
payload['version'] = detail['version'] + 99
res = req('PUT', '/admin/live/matches/1', payload, token)
check('9 乐观锁冲突', res['code'] == 9 and friendly(res), res)

# 40009 重复提交（同 client_token 两次）
evt = {'type': 'text', 'minute': 60, 'content': 'T12 联调事件', 'client_token': uuid.uuid4().hex}
r1 = req('POST', '/admin/live/matches/2/events', evt, token)
r2 = req('POST', '/admin/live/matches/2/events', evt, token)
check('40009 重复提交', r1['code'] == 0 and r2['code'] == 40009 and friendly(r2), r2)

# 清理联调事件
if r1['code'] == 0:
    req('DELETE', f"/admin/live/events/{r1['data']['event']['id']}", token=token)

# 40002 finished 锁定（match 3 已结束：录事件/改链接/改信息）
res = req('POST', '/admin/live/matches/3/events',
          {'type': 'text', 'minute': 90, 'content': 'x', 'client_token': uuid.uuid4().hex}, token)
check('40002 finished 录事件', res['code'] == 40002 and friendly(res), res)
res = req('PUT', '/admin/live/matches/3/live-link', {'live_link': 'https://a.com'}, token)
check('40002 finished 改链接', res['code'] == 40002 and friendly(res), res)

# 50008 mock 人脸核验超时（姓名含 timeout）
boundary = uuid.uuid4().hex
png = bytes.fromhex(
    '89504e470d0a1a0a0000000d4948445200000001000000010806000000'
    '1f15c4890000000d49444154789c626001000000ffff030000060005'
    '57bfabd40000000049454e44ae426082'
)
body = (
    f'--{boundary}\r\nContent-Disposition: form-data; name="player_name"\r\n\r\ntimeout球员\r\n'
    f'--{boundary}\r\nContent-Disposition: form-data; name="photo"; filename="p.png"\r\n'
    f'Content-Type: image/png\r\n\r\n'
).encode() + png + f'\r\n--{boundary}--\r\n'.encode()
res = req('POST', '/admin/live/matches/2/face-verify', token=token,
          raw=body, ctype=f'multipart/form-data; boundary={boundary}')
check('50008 核验超时', res['code'] == 50008 and friendly(res), res)

# 10006 文件格式错误（上传 txt 作赛事文件）
boundary = uuid.uuid4().hex
body = (
    f'--{boundary}\r\nContent-Disposition: form-data; name="type"\r\n\r\nsign_in\r\n'
    f'--{boundary}\r\nContent-Disposition: form-data; name="file"; filename="x.txt"\r\n'
    f'Content-Type: text/plain\r\n\r\nhello\r\n--{boundary}--\r\n'
).encode()
res = req('POST', '/admin/live/matches/2/files', token=token,
          raw=body, ctype=f'multipart/form-data; boundary={boundary}')
check('10006 文件格式', res['code'] == 10006 and friendly(res), res)

print(f'\n{passed} passed, {failed} failed')
raise SystemExit(1 if failed else 0)

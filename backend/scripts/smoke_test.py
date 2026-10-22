"""赛事直播模块冒烟测试（实施计划 §七 全覆盖）。

覆盖：1 登录与响应格式 / 2 比赛生命周期（含比分聚合、幂等、状态锁）/ 3 裁判 /
4 赛事文件 / 5 事件幂等(含于2) / 6 直播链接 / 7 人脸核验 / 8 权限与日志。

前置：后端 dev server 运行于 127.0.0.1:8000（php artisan serve）。
用法：python scripts/smoke_test.py ；全部通过退出码 0，否则非零。
"""
import json
import os
import re
import urllib.error
import urllib.request
import uuid
from datetime import datetime

BASE = 'http://127.0.0.1:8000/api'
LOG_DIR = os.path.normpath(os.path.join(os.path.dirname(__file__), '..', 'storage', 'logs'))
TODAY = datetime.now().strftime('%Y-%m-%d')
passed = failed = 0
traces = {}


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


def download(url, token=None):
    """文件下载：返回 (body_bytes)。匿名等错误场景 body 为 JSON 错误体。"""
    r = urllib.request.Request(BASE + url, method='GET')
    if token:
        r.add_header('Authorization', f'Bearer {token}')
    try:
        with urllib.request.urlopen(r) as resp:
            return resp.read()
    except urllib.error.HTTPError as e:
        return e.read()


def multipart(fields, file_field, filename, content, mime):
    boundary = uuid.uuid4().hex
    parts = b''
    for k, v in fields.items():
        parts += f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode()
    parts += (
        f'--{boundary}\r\nContent-Disposition: form-data; name="{file_field}"; filename="{filename}"\r\n'
        f'Content-Type: {mime}\r\n\r\n'
    ).encode() + content + f'\r\n--{boundary}--\r\n'.encode()
    return parts, f'multipart/form-data; boundary={boundary}'


FIVE = ('code', 'msg', 'data', 'success', 'trace_id')
UUID_RE = re.compile(r'^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$')


def wellformed(res):
    return isinstance(res, dict) and all(k in res for k in FIVE)


def log_has(channel, needle):
    path = os.path.join(LOG_DIR, f'{channel}-{TODAY}.log')
    if not os.path.exists(path):
        return False
    with open(path, encoding='utf-8', errors='ignore') as f:
        return needle in f.read()


# ==================== 1. 登录与响应格式 ====================
print('== 1. 登录与响应格式 ==')
bad = req('POST', '/auth/login', {'email': 'admin@ball.local', 'password': 'wrong-pass'})
check('错误密码→20002', wellformed(bad) and bad['code'] == 20002, bad)
check('trace_id 为 UUID 格式', bool(UUID_RE.match(str(bad.get('trace_id', '')))), bad.get('trace_id'))

login = req('POST', '/auth/login', {'email': 'admin@ball.local', 'password': 'admin123'})
check('登录成功', wellformed(login) and login['code'] == 0 and login['data'].get('token'), login)
token = login['data']['token']

# ==================== 2. 比赛生命周期 ====================
print('== 2. 比赛生命周期 ==')
m = req('POST', '/admin/live/matches', {
    'group_type': 'middle_school',
    'home_team_name': '冒烟主队',
    'away_team_name': '冒烟客队',
    'match_time': '2026-10-25 14:00:00',
}, token)
check('创建比赛', m['code'] == 0 and m['data'].get('id'), m)
mid = m['data']['id']

res = req('POST', '/admin/live/matches', {'group_type': 'middle_school'}, token)
check('缺字段→10001', res['code'] == 10001, res)

detail = req('GET', f'/admin/live/matches/{mid}', token=token)['data']
stale = {k: detail[k] for k in ('group_type', 'home_team_name', 'away_team_name', 'match_time')}
stale['version'] = detail['version'] + 99
res = req('PUT', f'/admin/live/matches/{mid}', stale, token)
check('旧 version→9 乐观锁', res['code'] == 9, res)

# ==================== 3. 裁判 ====================
print('== 3. 裁判 ==')
res = req('PUT', f'/admin/live/matches/{mid}/referees',
          {'referees': [{'role': 'chief', 'name': '裁判甲'}, {'role': 'chief', 'name': '裁判乙'}]}, token)
check('chief×2→10001', res['code'] == 10001, res)
res = req('PUT', f'/admin/live/matches/{mid}/referees', {'referees': [
    {'role': 'chief', 'name': '裁判甲'},
    {'role': 'assistant', 'name': '助理甲'},
    {'role': 'assistant', 'name': '助理乙'},
    {'role': 'fourth', 'name': '第四官员'},
]}, token)
check('合法裁判组合→0', res['code'] == 0, res)
refs = req('GET', f'/admin/live/matches/{mid}/referees', token=token)
check('裁判整体替换生效', refs['code'] == 0 and len(refs['data']) == 4, refs)

# ==================== 6. 直播链接 ====================
print('== 6. 直播链接 ==')
res = req('PUT', f'/admin/live/matches/{mid}/live-link', {'live_link': 'not-a-url'}, token)
check('非法 url→10001', res['code'] == 10001, res)
res = req('PUT', f'/admin/live/matches/{mid}/live-link', {'live_link': 'https://example.com/live'}, token)
check('设置链接→0', res['code'] == 0, res)
res = req('PUT', f'/admin/live/matches/{mid}/live-link', {'live_link': None}, token)
check('置空链接→0', res['code'] == 0, res)

# ==================== 2b. 状态流转与公众可见 ====================
print('== 2b. 状态流转与公众可见 ==')
detail = req('GET', f'/admin/live/matches/{mid}', token=token)['data']
res = req('PATCH', f'/admin/live/matches/{mid}/status',
          {'action': 'start', 'version': detail['version']}, token)
check('start→0', res['code'] == 0 and res['data']['status'] == 'live', res)

pub = req('GET', '/public/live/matches?status=live&page=1')
ids = [x['id'] for x in pub['data']['list']]
check('公众 live 列表可见', pub['code'] == 0 and mid in ids, ids)
check('公众详情可访问', req('GET', f'/public/live/matches/{mid}')['code'] == 0)

res = req('DELETE', f'/admin/live/matches/{mid}', token=token)
check('live 状态删除→40002', res['code'] == 40002, res)

# ==================== 4. 赛事文件 ====================
print('== 4. 赛事文件 ==')
body, ctype = multipart({'type': 'sign_in'}, 'file', 'x.txt', b'hello', 'text/plain')
res = req('POST', f'/admin/live/matches/{mid}/files', token=token, raw=body, ctype=ctype)
check('伪格式→10006', res['code'] == 10006, res)

# 带真实 PDF 头（finfo 识别为 application/pdf），仅大小超标以触发 10007
big = b'%PDF-1.4\n' + b'A' * (10 * 1024 * 1024 + 512 * 1024)  # 10.5MB
body, ctype = multipart({'type': 'sign_in'}, 'file', 'big.pdf', big, 'application/pdf')
res = req('POST', f'/admin/live/matches/{mid}/files', token=token, raw=body, ctype=ctype)
check('>10MB→10007', res['code'] == 10007, res)

pdf = b'%PDF-1.4 smoke-test-content'
body, ctype = multipart({'type': 'sign_in'}, 'file', 'sign.pdf', pdf, 'application/pdf')
res = req('POST', f'/admin/live/matches/{mid}/files', token=token, raw=body, ctype=ctype)
check('合法文件上传→0', res['code'] == 0 and res['data'].get('id'), res)
fid = res['data']['id']

anon = download(f'/admin/live/files/{fid}/download')
try:
    anon_code = json.loads(anon.decode())['code']
except Exception:
    anon_code = None
check('匿名下载→20001', anon_code == 20001, anon[:120])

auth_dl = download(f'/admin/live/files/{fid}/download', token)
check('鉴权下载内容一致', auth_dl == pdf, auth_dl[:60])

res = req('DELETE', f'/admin/live/files/{fid}', token=token)
check('删除文件→0', res['code'] == 0, res)

# ==================== 7. 人脸核验 ====================
print('== 7. 人脸核验 ==')
png = bytes.fromhex(
    '89504e470d0a1a0a0000000d4948445200000001000000010806000000'
    '1f15c4890000000d49444154789c626001000000ffff030000060005'
    '57bfabd40000000049454e44ae426082'
)
body, ctype = multipart({'player_name': '冒烟球员', 'player_no': '9'}, 'photo', 'p.png', png, 'image/png')
res = req('POST', f'/admin/live/matches/{mid}/face-verify', token=token, raw=body, ctype=ctype)
check('mock 核验 pass→0', res['code'] == 0, res)
recs = req('GET', f'/admin/live/matches/{mid}/face-verify-records', token=token)
names = [r['player_name'] for r in recs['data']]
check('核验记录落库', recs['code'] == 0 and '冒烟球员' in names, recs)

body, ctype = multipart({'player_name': 'timeout球员'}, 'photo', 'p.png', png, 'image/png')
fv = req('POST', f'/admin/live/matches/{mid}/face-verify', token=token, raw=body, ctype=ctype)
check('核验超时→50008', fv['code'] == 50008, fv)
traces['exception'] = fv.get('trace_id', '')

# ==================== 2c/5. 事件、比分聚合与幂等 ====================
print('== 2c/5. 事件、比分聚合与幂等 ==')
t1 = uuid.uuid4().hex


def add_event(payload):
    payload.setdefault('client_token', uuid.uuid4().hex)
    return req('POST', f'/admin/live/matches/{mid}/events', payload, token)


g1 = add_event({'type': 'goal', 'minute': 10, 'team_side': 'home',
                'player_name': '主队前锋', 'player_no': 9, 'client_token': t1})
check('goal1 比分 1:0', g1['code'] == 0 and g1['data']['home_score'] == 1
      and g1['data']['away_score'] == 0, g1)
g2 = add_event({'type': 'goal', 'minute': 20, 'team_side': 'home',
                'player_name': '主队中场', 'player_no': 8})
check('goal2 比分 2:0', g2['code'] == 0 and g2['data']['home_score'] == 2, g2)
g3 = add_event({'type': 'goal', 'minute': 30, 'team_side': 'away',
                'player_name': '客队前锋', 'player_no': 10})
check('goal3 比分 2:1', g3['code'] == 0 and g3['data']['home_score'] == 2
      and g3['data']['away_score'] == 1, g3)
traces['business'] = g3.get('trace_id', '')

pub_detail = req('GET', f'/public/live/matches/{mid}')['data']
check('公众详情比分 2:1', pub_detail['home_score'] == 2 and pub_detail['away_score'] == 1, pub_detail)

evs = req('GET', f'/public/live/matches/{mid}/events')['data']
home_goals = sum(1 for e in evs if e['type'] == 'goal' and e['team_side'] == 'home')
away_goals = sum(1 for e in evs if e['type'] == 'goal' and e['team_side'] == 'away')
check('比分=事件聚合', (home_goals, away_goals) == (2, 1), (home_goals, away_goals))

away_goal_id = next(e['id'] for e in evs if e['type'] == 'goal' and e['team_side'] == 'away')
d = req('DELETE', f'/admin/live/events/{away_goal_id}', token=token)
check('删 goal 比分回退 2:0', d['code'] == 0 and d['data']['home_score'] == 2
      and d['data']['away_score'] == 0, d)

dup = add_event({'type': 'goal', 'minute': 10, 'team_side': 'home',
                 'player_name': '主队前锋', 'player_no': 9, 'client_token': t1})
check('同 client_token→40009', dup['code'] == 40009, dup)
traces['api'] = dup.get('trace_id', '')

# ==================== 2d. finished 锁定与删除规则 ====================
print('== 2d. finished 锁定与删除规则 ==')
detail = req('GET', f'/admin/live/matches/{mid}', token=token)['data']
res = req('PATCH', f'/admin/live/matches/{mid}/status',
          {'action': 'finish', 'version': detail['version']}, token)
check('finish→0', res['code'] == 0 and res['data']['status'] == 'finished', res)

res = add_event({'type': 'text', 'minute': 90, 'content': 'x'})
check('finished 录事件→40002', res['code'] == 40002, res)

detail = req('GET', f'/admin/live/matches/{mid}', token=token)['data']
done = {k: detail[k] for k in ('group_type', 'home_team_name', 'away_team_name', 'match_time')}
done['version'] = detail['version']
res = req('PUT', f'/admin/live/matches/{mid}', done, token)
check('finished PUT→40002', res['code'] == 40002, res)

res = req('DELETE', f'/admin/live/matches/{mid}', token=token)
check('finished 删除→40002', res['code'] == 40002, res)

m2 = req('POST', '/admin/live/matches', {
    'group_type': 'primary_school',
    'home_team_name': '临时主队',
    'away_team_name': '临时客队',
    'match_time': '2026-10-26 10:00:00',
}, token)
res = req('DELETE', f"/admin/live/matches/{m2['data']['id']}", token=token)
check('not_started 删除→0', res['code'] == 0, res)

# ==================== 8. 权限与日志 ====================
print('== 8. 权限与日志 ==')
res = req('GET', '/admin/live/matches')
check('无 token→20001', wellformed(res) and res['code'] == 20001, res)

check('business 日志含 trace_id', log_has('business', traces.get('business', '$$$')), LOG_DIR)
check('exception 日志含 trace_id', log_has('exception', traces.get('exception', '$$$')), LOG_DIR)
check('api 日志含 trace_id', log_has('api', traces.get('api', '$$$')), LOG_DIR)

print(f'\n{passed} passed, {failed} failed')
raise SystemExit(1 if failed else 0)

"""T9 验收：经 Vite 代理调通 /auth/login（5173 -> 8000）。"""
import json
import urllib.request
import urllib.error

BASE = 'http://localhost:5173'
passed = failed = 0


def check(name, cond, extra=''):
    global passed, failed
    if cond:
        passed += 1
        print(f'  PASS {name}')
    else:
        failed += 1
        print(f'  FAIL {name} {extra}')


def req(method, url, data=None, token=None):
    r = urllib.request.Request(BASE + url, method=method)
    r.add_header('Content-Type', 'application/json')
    if token:
        r.add_header('Authorization', f'Bearer {token}')
    body = json.dumps(data).encode() if data is not None else None
    try:
        with urllib.request.urlopen(r, body) as resp:
            return resp.status, json.loads(resp.read().decode())
    except urllib.error.HTTPError as e:
        return e.code, json.loads(e.read().decode())


# 1. 登录页可访问
with urllib.request.urlopen(BASE + '/admin/login') as resp:
    html = resp.read().decode()
check('login page served', resp.status == 200 and '<div id="app">' in html)

# 2. 错误密码 -> 20002（五字段齐全）
st, res = req('POST', '/api/auth/login', {'email': 'admin@ball.local', 'password': 'wrong'})
check('wrong pwd -> 20002', res.get('code') == 20002, res)
check('result has trace_id', bool(res.get('trace_id')))

# 3. 正确密码 -> token + user
st, res = req('POST', '/api/auth/login', {'email': 'admin@ball.local', 'password': 'admin123'})
check('login ok', res.get('code') == 0 and res['data'].get('token'), res)
token = res['data']['token']

# 4. /auth/me 鉴权可用
st, res = req('GET', '/api/auth/me', token=token)
check('me ok', res.get('code') == 0 and res['data'].get('email') == 'admin@ball.local', res)

print(f'\n{passed} passed, {failed} failed')
raise SystemExit(1 if failed else 0)

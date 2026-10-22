"""T11 收尾：人脸核验 API + 删除遗留测试事件。"""
import json
import urllib.request
import uuid

BASE = 'http://127.0.0.1:8000/api'


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
    with urllib.request.urlopen(r, body) as resp:
        return json.loads(resp.read().decode())


login = req('POST', '/auth/login', {'email': 'admin@ball.local', 'password': 'admin123'})
token = login['data']['token']

# 人脸核验（multipart 含必填照片，对应 FaceVerifyPanel 提交）
boundary = uuid.uuid4().hex
png = bytes.fromhex(
    '89504e470d0a1a0a0000000d4948445200000001000000010806000000'
    '1f15c4890000000d49444154789c626001000000ffff030000060005'
    '57bfabd40000000049454e44ae426082'
)
parts = (
    f'--{boundary}\r\nContent-Disposition: form-data; name="player_name"\r\n\r\n测试球员\r\n'
    f'--{boundary}\r\nContent-Disposition: form-data; name="player_no"\r\n\r\n7\r\n'
    f'--{boundary}\r\nContent-Disposition: form-data; name="photo"; filename="p.png"\r\n'
    f'Content-Type: image/png\r\n\r\n'
).encode() + png + f'\r\n--{boundary}--\r\n'.encode()
res = req('POST', '/admin/live/matches/2/face-verify', token=token,
          raw=parts, ctype=f'multipart/form-data; boundary={boundary}')
print('face verify resp:', json.dumps(res, ensure_ascii=False))

# 删除浏览器测试遗留事件 id=5
res = req('DELETE', '/admin/live/events/5', token=token)
print('delete event:', res['code'], 'score:', res['data']['home_score'], ':', res['data']['away_score'])

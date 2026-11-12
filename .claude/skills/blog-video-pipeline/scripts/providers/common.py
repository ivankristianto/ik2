import json
import time
import urllib.error
import urllib.request
from pathlib import Path

UA = "Mozilla/5.0 blog-video-pipeline"


class Fail(Exception):
    pass


def http(method, url, *, token=None, data=None, raw=None, headers=None, want_headers=False, timeout=120):
    hdrs = {"User-Agent": UA, **(headers or {})}
    if token:
        hdrs["Authorization"] = "Bearer " + token
    body = raw
    if data is not None:
        body = json.dumps(data).encode()
        hdrs.setdefault("Content-Type", "application/json; charset=UTF-8")
    req = urllib.request.Request(url, data=body, method=method, headers=hdrs)
    try:
        with urllib.request.urlopen(req, timeout=timeout) as res:
            text = res.read().decode() or "{}"
            parsed = json.loads(text) if text.lstrip().startswith(("{", "[")) else {"raw": text}
            return (parsed, dict(res.headers)) if want_headers else parsed
    except urllib.error.HTTPError as err:
        detail = err.read().decode(errors="replace")[:600]
        raise Fail(f"{method} {url.split('?')[0]} returned {err.code}: {detail}") from None


def receipt_path(vdir, name):
    return Path(vdir) / "out" / f"{name}-upload-receipt.json"


def read_receipt(vdir, name):
    path = receipt_path(vdir, name)
    return json.loads(path.read_text()) if path.exists() else None


def write_receipt(vdir, name, receipt):
    path = receipt_path(vdir, name)
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(receipt, indent=2) + "\n")
    return path


def now():
    return time.strftime("%Y-%m-%dT%H:%M:%S%z")

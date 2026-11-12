"""YouTube over the Data API v3: resumable upload, custom thumbnail, read back. Stdlib only."""
import json
import os
import time
import urllib.parse
from pathlib import Path

from .common import Fail, http, now, read_receipt, write_receipt

NAME = "youtube"
CREDENTIALS = {
    "YOUTUBE_CLIENT_JSON": "op://Agents/Rei YouTube OAuth/oauth_client_json",
    "YOUTUBE_TOKEN_JSON": "op://Agents/Rei YouTube OAuth/oauth_token_json",
}
API = "https://www.googleapis.com/youtube/v3"
UPLOAD = "https://www.googleapis.com/upload/youtube/v3"
UPLOAD_SCOPE = "https://www.googleapis.com/auth/youtube.upload"
THUMB_MAX = 2 * 1024 * 1024
PRIVACY = ("private", "unlisted", "public")


def tags_length(tags):
    # YouTube's 500-character cap counts the commas between tags and quotes around tags with spaces.
    return sum(len(t) + (2 if " " in t else 0) for t in tags) + max(len(tags) - 1, 0)


def validate(meta, vdir):
    problems = []
    title, desc, tags = meta.get("title", ""), meta.get("description", ""), meta.get("tags", [])
    if not title.strip():
        problems.append("title is empty")
    if len(title) > 100:
        problems.append(f"title is {len(title)} characters, YouTube allows 100")
    if len(desc.encode()) > 5000:
        problems.append(f"description is {len(desc.encode())} bytes, YouTube allows 5000")
    for field, text in (("title", title), ("description", desc)):
        if "<" in text or ">" in text:
            problems.append(f"{field} contains < or >, which YouTube rejects")
    if tags_length(tags) > 500:
        problems.append(f"tags add up to {tags_length(tags)} characters, YouTube allows 500")
    if meta.get("privacy") not in PRIVACY:
        problems.append(f"privacy must be one of {', '.join(PRIVACY)}")
    if not isinstance(meta.get("syntheticMedia"), bool):
        problems.append("syntheticMedia must be true or false; Ivan decides it")
    cover = Path(vdir) / meta.get("cover", "")
    if cover.is_file() and cover.stat().st_size > THUMB_MAX:
        problems.append(f"cover is {cover.stat().st_size} bytes, YouTube thumbnails allow 2 MB")
    if cover.is_file() and cover.suffix.lower() not in (".jpg", ".jpeg", ".png"):
        problems.append("cover must be a JPG or PNG for YouTube")
    return problems


def video_body(meta):
    yt = meta.get("youtube", {})
    return {
        "snippet": {
            "title": meta["title"],
            "description": meta["description"],
            "tags": meta.get("tags", []),
            "categoryId": str(yt.get("categoryId", "28")),
            "defaultLanguage": meta.get("language", "en"),
            "defaultAudioLanguage": meta.get("language", "en"),
        },
        "status": {
            "privacyStatus": meta["privacy"],
            "selfDeclaredMadeForKids": False,
            "containsSyntheticMedia": meta["syntheticMedia"],
            "embeddable": True,
            "license": "youtube",
        },
    }


def access_token():
    try:
        client = json.loads(os.environ["YOUTUBE_CLIENT_JSON"])
        saved = json.loads(os.environ["YOUTUBE_TOKEN_JSON"])
    except (KeyError, json.JSONDecodeError):
        raise Fail("YOUTUBE_CLIENT_JSON / YOUTUBE_TOKEN_JSON are missing or not JSON; run through publish.py, which wraps op run") from None
    app = client.get("installed") or client.get("web") or {}
    if not saved.get("refresh_token"):
        raise Fail("the saved YouTube token has no refresh_token; Ivan needs to authorize the app again")
    form = urllib.parse.urlencode({
        "client_id": app.get("client_id", ""),
        "client_secret": app.get("client_secret", ""),
        "refresh_token": saved["refresh_token"],
        "grant_type": "refresh_token",
    }).encode()
    try:
        res = http("POST", app.get("token_uri", "https://oauth2.googleapis.com/token"), raw=form,
                   headers={"Content-Type": "application/x-www-form-urlencoded"})
    except Fail as err:
        if "invalid_grant" in str(err):
            raise Fail("Google rejected the refresh token (invalid_grant). It expires after 7 days while the OAuth app is in "
                       "Testing, or when access is revoked. Ivan needs to authorize again and save the new token JSON to "
                       "the Agents item 'Rei YouTube OAuth'.") from None
        raise
    scopes = res.get("scope", "").split()
    if UPLOAD_SCOPE not in scopes:
        raise Fail(f"the token lacks {UPLOAD_SCOPE}; it has: {' '.join(scopes) or 'none'}")
    return res["access_token"], scopes


def preflight():
    token, scopes = access_token()
    res = http("GET", f"{API}/channels?part=snippet,status&mine=true", token=token)
    items = res.get("items", [])
    if not items:
        raise Fail("the token works but no channel came back for it")
    ch = items[0]
    return {
        "channelId": ch["id"],
        "channelTitle": ch["snippet"]["title"],
        "channelUrl": f"https://www.youtube.com/channel/{ch['id']}",
        "scopes": scopes,
        "captionsUpload": "https://www.googleapis.com/auth/youtube.force-ssl" in scopes,
    }


def plan(meta, vdir):
    receipt = read_receipt(vdir, NAME)
    out = {"provider": NAME, "problems": validate(meta, vdir), "body": video_body(meta) if meta.get("title") else None}
    if receipt:
        out["action"] = "skip, already uploaded" if thumbnail_done(receipt) else "finish: set the thumbnail and read back"
        out["existing"] = {"id": receipt.get("id"), "url": receipt.get("url"), "privacy": receipt.get("privacyStatus")}
    else:
        out["action"] = "upload video, set thumbnail, read back"
    out["notifySubscribers"] = bool(meta.get("youtube", {}).get("notifySubscribers", False))
    out["captions"] = "not uploaded: the token has no youtube.force-ssl scope"
    return out


def thumbnail_done(receipt):
    return bool(receipt.get("thumbnail") or receipt.get("thumbnailSetResponse"))


def upload(meta, vdir, reupload=False):
    vdir = Path(vdir)
    problems = validate(meta, vdir)
    if problems:
        raise Fail("; ".join(problems))
    receipt = None if reupload else read_receipt(vdir, NAME)
    token, _ = access_token()
    if receipt is None:
        receipt = upload_video(meta, vdir, token)
        write_receipt(vdir, NAME, receipt)
    if not thumbnail_done(receipt):
        cover = vdir / meta["cover"]
        mime = "image/png" if cover.suffix.lower() == ".png" else "image/jpeg"
        res = http("POST", f"{UPLOAD}/thumbnails/set?videoId={receipt['id']}", token=token, raw=cover.read_bytes(),
                   headers={"Content-Type": mime})
        receipt["thumbnail"] = {"file": str(cover), "setAt": now(), "sizes": sorted(res.get("items", [{}])[0])}
        write_receipt(vdir, NAME, receipt)
    receipt["readback"] = readback(receipt, meta, token=token)
    write_receipt(vdir, NAME, receipt)
    return receipt


def upload_video(meta, vdir, token):
    video = vdir / meta["video"]
    size = video.stat().st_size
    notify = "true" if meta.get("youtube", {}).get("notifySubscribers", False) else "false"
    body = video_body(meta)
    _, headers = http(
        "POST", f"{UPLOAD}/videos?uploadType=resumable&part=snippet,status&notifySubscribers={notify}",
        token=token, data=body, want_headers=True,
        headers={"X-Upload-Content-Type": "video/mp4", "X-Upload-Content-Length": str(size)},
    )
    location = headers.get("Location") or headers.get("location")
    if not location:
        raise Fail("YouTube did not return a resumable upload URL")
    res = http("PUT", location, token=token, raw=video.read_bytes(), headers={"Content-Type": "video/mp4"}, timeout=900)
    return {
        "provider": NAME,
        "id": res["id"],
        "url": f"https://www.youtube.com/shorts/{res['id']}" if meta.get("orientation", "vertical") == "vertical"
        else f"https://www.youtube.com/watch?v={res['id']}",
        "watchUrl": f"https://www.youtube.com/watch?v={res['id']}",
        "studioUrl": f"https://studio.youtube.com/video/{res['id']}/edit",
        "channelId": res["snippet"]["channelId"],
        "uploadedAt": now(),
        "file": str(video),
        "bytes": size,
        "title": body["snippet"]["title"],
        "privacyStatus": body["status"]["privacyStatus"],
        "notifySubscribers": notify == "true",
        "containsSyntheticMedia": body["status"]["containsSyntheticMedia"],
    }


def readback(receipt, meta=None, token=None, wait=180):
    token = token or access_token()[0]
    deadline = time.time() + wait
    while True:
        res = http("GET", f"{API}/videos?part=snippet,status,processingDetails,contentDetails&id={receipt['id']}", token=token)
        if not res.get("items"):
            raise Fail(f"video {receipt['id']} not found on read back")
        item = res["items"][0]
        processing = item.get("processingDetails", {}).get("processingStatus")
        if processing != "processing" or time.time() > deadline:
            break
        time.sleep(10)
    sn, st = item["snippet"], item["status"]
    out = {
        "checkedAt": now(),
        "uploadStatus": st.get("uploadStatus"),
        "processingStatus": processing,
        "privacyStatus": st.get("privacyStatus"),
        "containsSyntheticMedia": st.get("containsSyntheticMedia"),
        "duration": item.get("contentDetails", {}).get("duration"),
        "thumbnails": sorted(sn.get("thumbnails", {})),
    }
    if meta:
        out["matches"] = {
            "title": sn.get("title") == meta["title"],
            "description": sn.get("description") == meta["description"],
            "privacy": st.get("privacyStatus") == meta["privacy"],
            # The API sometimes leaves the flag out; None means unknown, not a mismatch.
            "syntheticMedia": None if st.get("containsSyntheticMedia") is None
            else st.get("containsSyntheticMedia") == meta["syntheticMedia"],
        }
    return out

"""Print Ivan's latest long posts as plain text, to calibrate voice before drafting.
Usage: python3 -I recent-posts.py [count=3] [max_words=1400]
"""
import html, json, re, sys, urllib.request

count = int(sys.argv[1]) if len(sys.argv) > 1 else 3
limit = int(sys.argv[2]) if len(sys.argv) > 2 else 1400
url = "https://www.ivankristianto.com/wp-json/wp/v2/posts?per_page=20&_fields=date,title,content"
req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0"})


def to_text(markup):
    markup = re.sub(r"<h([1-6])[^>]*>", lambda m: "\n\n" + "#" * int(m.group(1)) + " ", markup)
    markup = re.sub(r"<li[^>]*>", "\n- ", markup)
    markup = re.sub(r"<pre[^>]*>", "\n```\n", markup).replace("</pre>", "\n```\n")
    markup = re.sub(r"</p>|<br ?/?>", "\n\n", markup)
    markup = html.unescape(re.sub(r"<[^>]+>", "", markup))
    return re.sub(r"\n{3,}", "\n\n", markup).strip()


shown = 0
for post in json.load(urllib.request.urlopen(req)):
    text = to_text(post["content"]["rendered"])
    if len(text.split()) < 400:  # skip embeds and link posts
        continue
    words = text.split(" ")
    print(f"=== {html.unescape(post['title']['rendered'])} ({post['date'][:10]}) ===\n")
    print(" ".join(words[:limit]), "\n")
    shown += 1
    if shown == count:
        break

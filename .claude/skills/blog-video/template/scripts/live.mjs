// Refuse to render an outro URL that doesn't resolve: the post must be published at ivankristianto.com/<slug>/.
import { readFileSync } from 'node:fs';

const { slug } = JSON.parse(readFileSync('src/video.json', 'utf8')).post;
const url = `https://www.ivankristianto.com/${slug}/`;
const res = await fetch(url, { headers: { 'User-Agent': 'Mozilla/5.0 (Macintosh) blog-video' }, redirect: 'manual' });
if (res.status !== 200) {
	console.error(`${url} returned ${res.status}. The outro shows this URL, so publish the post or fix post.slug first.`);
	process.exit(1);
}
console.log(`${url} is live`);

// Posters for the films and the Try Audazzio demo, in the site's own look (white, the dashed wave lines,
// the title in Inter Tight). They stand in until the new films arrive with their own stills.
//   node scripts/media/posters.mjs   -> assets/img/poster-<youtube id>.jpg, poster-demo.jpg
import fs from "node:fs";
import path from "node:path";
import { pathToFileURL } from "node:url";
import { launch } from "../shots/browser.mjs";
import { VIDEOS, DEMO } from "../content/site.mjs";

const ROOT = path.join(import.meta.dirname, "..", "..");
const A = path.join(ROOT, "wp-content", "plugins", "audazzio-core", "assets");
const font = (f) => pathToFileURL(path.join(A, "fonts", f)).href;
const sym = fs.readFileSync(path.join(ROOT, "brand", "audazzio-symbol.svg"), "utf8").replace("<svg ", '<svg class="sym" ');

function waves(w, h, cy, seed, ink = "12,18,34") {
  let out = "";
  for (let i = 0; i < 9; i++) {
    const c = i - 4, y0 = h * cy + c * 16, pts = [];
    for (let x = -10; x <= w + 10; x += 8) {
      const d = x - w * 0.5, env = Math.exp(-(d * d) / (w * 0.3) ** 2), r = Math.sqrt(d * d + 400);
      const s = 0.72 * Math.sin(r / 48 - seed + i * 0.55) + 0.28 * Math.sin(r / 21 - seed * 1.6 + i);
      pts.push(`${x},${(y0 + env * 54 * (1 - (Math.abs(c) / 5) * 0.55) * s).toFixed(1)}`);
    }
    out += `<polyline points="${pts.join(" ")}" fill="none" stroke="rgba(${ink},${(0.32 * (1 - (Math.abs(c) / 5) * 0.6)).toFixed(2)})" stroke-width="1.5" stroke-dasharray="7 7"/>`;
  }
  const mid = [];
  for (let x = w * 0.58; x <= w * 0.7; x += 6) { const d = x - w * 0.5, r = Math.sqrt(d * d + 400); mid.push(`${x},${(h * cy + Math.exp(-(d * d) / (w * 0.3) ** 2) * 54 * (0.72 * Math.sin(r / 48 - seed + 4 * 0.55) + 0.28 * Math.sin(r / 21 - seed * 1.6 + 4))).toFixed(1)}`); }
  return out + `<polyline points="${mid.join(" ")}" fill="none" stroke="#F26A2E" stroke-width="3" stroke-linecap="round"/>`;
}

const page = (p, i) => p.dark ? dark(p, i) : `<!doctype html><html><head><meta charset="utf-8"><style>
@font-face{font-family:"Inter Tight";font-weight:100 900;src:url("${font("inter-tight-latin-wght-normal.woff2")}") format("woff2-variations")}
@font-face{font-family:"Geist Mono";font-weight:500;src:url("${font("geist-mono-latin-500-normal.woff2")}") format("woff2")}
*{margin:0;box-sizing:border-box}html,body{width:1600px;height:900px;background:#fff;overflow:hidden}
.p{position:relative;width:1600px;height:900px;background:radial-gradient(ellipse 60% 70% at 50% 40%,#fff 40%,#f3f4f6 100%)}
svg.w{position:absolute;inset:0}
.in{position:absolute;left:120px;top:110px;right:120px}
.sym{width:56px;height:auto;color:#F89C68;display:block;margin-bottom:56px}
.eb{font:500 22px "Geist Mono";letter-spacing:.14em;text-transform:uppercase;color:#6b7080}
h1{margin-top:22px;font:600 104px/0.98 "Inter Tight";letter-spacing:-.045em;color:#0c1222;max-width:13em}
h1 em{font-style:normal;color:#9a9eaa}
.len{position:absolute;right:120px;top:128px;font:500 22px "Geist Mono";letter-spacing:.1em;text-transform:uppercase;color:#6b7080}
</style></head><body><div class="p"><svg class="w" viewBox="0 0 1600 900">${waves(1600, 900, 0.78, 1.3 + i * 0.9)}</svg><div class="in">${sym}<p class="eb">${p.eb}</p><h1>${p.title}</h1></div></div></body></html>`;

const dark = (p, i) => `<!doctype html><html><head><meta charset="utf-8"><style>
@font-face{font-family:"Inter Tight";font-weight:100 900;src:url("${font("inter-tight-latin-wght-normal.woff2")}") format("woff2-variations")}
@font-face{font-family:"Geist Mono";font-weight:500;src:url("${font("geist-mono-latin-500-normal.woff2")}") format("woff2")}
*{margin:0;box-sizing:border-box}html,body{width:1600px;height:900px;background:#0b0f1a;overflow:hidden}
.p{position:relative;width:1600px;height:900px;background:radial-gradient(ellipse 70% 80% at 50% 30%,#18203a 0%,#0b0f1a 70%)}
svg.w{position:absolute;inset:0}
.in{position:absolute;left:0;right:0;top:150px;text-align:center}
.sym{width:52px;height:auto;color:#F89C68;display:block;margin:0 auto 32px}
.eb{font:500 22px "Geist Mono";letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.6)}
h1{margin-top:18px;font:600 96px/0.96 "Inter Tight";letter-spacing:-.045em;color:#fff}
h1 em{font-style:normal;color:rgba(255,255,255,.45)}
</style></head><body><div class="p"><svg class="w" viewBox="0 0 1600 900">${waves(1600, 900, 0.86, 2.1, "255,255,255")}</svg><div class="in">${sym}<p class="eb">${p.eb}</p></div></div></body></html>`;

const items = [
  ...VIDEOS.map((v) => ({ file: `poster-${v.id}.jpg`, eb: v.label.replace(" · ", " · "), title: v.title.replace(/^(.*?)(\s\S+)$/, "$1<em>$2</em>"), len: v.length })),
  { file: "poster-demo.jpg", eb: "Audazzio functional demo", title: "Speakers on.<br><em>Phone out.</em>", len: DEMO.length, dark: true },
];
const b = await launch();
const pg = await b.newPage({ viewport: { width: 1600, height: 900 } });
const tmp = path.join(ROOT, "media", "work");
fs.mkdirSync(tmp, { recursive: true });
for (const [i, p] of items.entries()) {
  const f = path.join(tmp, "poster.html");
  fs.writeFileSync(f, page(p, i));
  await pg.goto(pathToFileURL(f).href);
  await pg.evaluate(() => document.fonts.ready);
  await pg.screenshot({ path: path.join(A, "img", p.file), type: "jpeg", quality: 84 });
  console.log("poster", p.file);
}
await b.close();

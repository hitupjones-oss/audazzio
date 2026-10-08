// Shared by the two printed documents (the proposal deck and the site guide): brand values, faces,
// the marks, and the HTML -> PDF step (headless Chrome) with the checks that have bitten before:
//   - the build stops if a face did not load;
//   - nothing may print as a soft mask (see-through PNGs, alpha gradients, blurred shadows, CSS masks,
//     gradient-filled text): some PDF viewers, Acrobat included, drop what a soft mask covers. So pictures
//     are JPGs, the RSPKT lettering is flattened onto white first, and there are no shadows;
//   - every page is rendered back from the PDF (pdftoppm) into scripts/shots/out/<name>-NN.jpg for proofing.
import fs from "node:fs";
import path from "node:path";
import { pathToFileURL } from "node:url";
import { execFileSync } from "node:child_process";
import sharp from "sharp";
import { launch } from "../shots/browser.mjs";

export const ROOT = path.join(import.meta.dirname, "..", "..");
export const url = (...p) => pathToFileURL(path.join(ROOT, ...p)).href;
export const C = { ink: "#0C1222", ink2: "#3B4152", ink3: "#6B7080", ink4: "#868A96", line: "#E3E4E8", mist: "#F5F5F7", cloud: "#ECECF0", wave: "#F26A2E", peach: "#F89C68", white: "#FFFFFF" };
// The preview on Vercel (project "audazzio-rspkt", serving site/). Change it here if the project is named differently.
export const LIVE = "audazzio-rspkt.vercel.app";
export const PRICE = "$23,500";
export const shot = (n) => url("scripts", "shots", "out", n + ".jpg");
export const esc = (s) => String(s).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");

const svg = (f) => fs.readFileSync(path.join(ROOT, "brand", f), "utf8").replace(/<\?xml[^>]*>/, "");
/** The Audazzio symbol (peach) and logotype (ink), inline. */
export const symbol = (h = 28, color = C.peach) => svg("audazzio-symbol.svg").replace("<svg ", `<svg style="height:${h}px;width:auto;color:${color};display:inline-block;vertical-align:middle" `);
export const logotype = (h = 18, color = C.ink) => svg("audazzio-logotype.svg").replace("<svg ", `<svg style="height:${h}px;width:auto;color:${color};display:inline-block;vertical-align:middle" `);
export const lockup = (h = 30) => `<span class="lockup">${symbol(h)}${logotype(h * 0.5)}</span>`;

/** The RSPKT lettering on white (the file is black on clear), made once per build into docs/.gen. */
export async function marks() {
  const gen = path.join(ROOT, "docs", ".gen");
  fs.mkdirSync(gen, { recursive: true });
  const out = path.join(gen, "rspkt-on-white.jpg");
  await sharp(path.join(ROOT, "brand", "rspkt-joule.png")).resize({ width: 600 }).flatten({ background: "#ffffff" }).jpeg({ quality: 94 }).toFile(out);
  return { rspkt: pathToFileURL(out).href };
}
/** The Joule mark, drawn (a vector, in the accent). */
export const joule = (h = 22, color = C.wave) => `<svg viewBox="0 0 468.02 186.4" height="${h}" style="display:inline-block;vertical-align:middle"><polygon fill="${color}" points="0 186.4 94.21 65.48 271.59 22.62 266.83 50 468.02 0 206.11 125 209.69 101.19 0 186.4"/></svg>`;

/** A fine dashed wave (the site's motif), as a static SVG. */
export function wave(w = 1600, h = 160, { lines = 7, amp = 34, cy = 0.5, accent = true, ink = "12,18,34" } = {}) {
  let out = "";
  const mid = (lines - 1) / 2;
  const y = (x, i) => { const c = i - mid, d = x - w * 0.5, env = Math.exp(-(d * d) / (w * 0.3) ** 2), r = Math.sqrt(d * d + 400); return h * cy + c * 12 + env * amp * (1 - (Math.abs(c) / (mid + 1)) * 0.55) * (0.72 * Math.sin(r / 48 + i * 0.55) + 0.28 * Math.sin(r / 21 + i)); };
  for (let i = 0; i < lines; i++) {
    const pts = [];
    for (let x = -10; x <= w + 10; x += 8) pts.push(`${x},${y(x, i).toFixed(1)}`);
    out += `<polyline points="${pts.join(" ")}" fill="none" stroke="rgb(${ink})" stroke-opacity="${(0.3 * (1 - (Math.abs(i - mid) / (mid + 1)) * 0.6)).toFixed(2)}" stroke-width="1.3" stroke-dasharray="6 6"/>`;
  }
  if (accent) { const pts = []; for (let x = w * 0.6; x <= w * 0.7; x += 6) pts.push(`${x},${y(x, Math.round(mid)).toFixed(1)}`); out += `<polyline points="${pts.join(" ")}" fill="none" stroke="${C.wave}" stroke-width="2.6" stroke-linecap="round"/>`; }
  return `<svg class="wave" viewBox="0 0 ${w} ${h}" preserveAspectRatio="none" width="100%" height="${h}">${out}</svg>`;
}

const fonts = url("wp-content", "plugins", "audazzio-core", "assets", "fonts");
export const FACES = `@font-face{font-family:"Inter";font-weight:100 900;src:url("${fonts}/inter-latin-wght-normal.woff2") format("woff2-variations")}
@font-face{font-family:"Inter Tight";font-weight:100 900;src:url("${fonts}/inter-tight-latin-wght-normal.woff2") format("woff2-variations")}
@font-face{font-family:"Geist Mono";font-weight:500;src:url("${fonts}/geist-mono-latin-500-normal.woff2") format("woff2")}`;

/** A heading with its *second tone* in grey; a line break starts a new line. */
export const hl = (t) => esc(t).split("\n").map((l) => `<span class="ln">${l.replace(/\*([^*]+)\*/g, '<em class="hl">$1</em>')}</span>`).join("");

export const BASE_CSS = `${FACES}
:root{--ink:${C.ink};--ink2:${C.ink2};--ink3:${C.ink3};--ink4:${C.ink4};--line:${C.line};--mist:${C.mist};--cloud:${C.cloud};--wave:${C.wave};--peach:${C.peach}}
*{box-sizing:border-box;margin:0}
html{-webkit-print-color-adjust:exact;print-color-adjust:exact}
body{background:#fff;color:var(--ink);font-family:"Inter",Arial,sans-serif;font-weight:400;-webkit-font-smoothing:antialiased}
img,svg{display:block;max-width:100%}
a{color:inherit;text-decoration:none}
.d{font-family:"Inter Tight","Inter",Arial,sans-serif;font-weight:600;letter-spacing:-.04em;line-height:.98}
.d .ln{display:block}
.hl{font-style:normal;color:var(--ink4)}
.eb{display:flex;align-items:center;gap:.8em;font-family:"Geist Mono",monospace;font-weight:500;text-transform:uppercase;letter-spacing:.14em;color:var(--ink3)}
.eb b{color:var(--ink);font-weight:500}
.eb i{flex:1;border-top:1px solid var(--line)}
b,strong{font-weight:600;color:var(--ink)}
.lockup{display:inline-flex;align-items:center;gap:.5em}
.shot{border:1px solid var(--line);border-radius:18px;background:var(--mist);overflow:hidden}
.shot img{width:100%;height:100%;object-fit:cover;object-position:top}
.tag{display:inline-block;border:1px solid var(--ink);color:var(--ink);border-radius:999px;padding:.32em .9em .26em;font-family:"Geist Mono",monospace;font-weight:500;text-transform:uppercase;letter-spacing:.1em}
.wave{display:block}
`;

/** Print an HTML file to PDF and proof it from the PDF itself. Returns { pages, masks }. */
export async function print(htmlFile, pdfFile, { w, h, name } = {}) {
  const b = await launch();
  const page = await b.newPage({ viewport: { width: w, height: h } });
  await page.goto(pathToFileURL(htmlFile).href, { waitUntil: "load" });
  await page.evaluate(() => Promise.all([...document.images].map((i) => (i.complete ? 1 : new Promise((r) => { i.onload = i.onerror = r; })))).then(() => document.fonts.ready));
  const missing = await page.evaluate(() => [...document.images].filter((i) => !i.naturalWidth).map((i) => i.src.split("/").pop()));
  if (missing.length) console.log(`${name}: MISSING images: ${missing.join(", ")}`);
  const loaded = await page.evaluate(() => Promise.all(['600 40px "Inter Tight"', '400 20px "Inter"', '500 14px "Geist Mono"'].map((f) => document.fonts.load(f))).then((r) => r.map((x) => x.length)));
  if (loaded.some((n) => !n)) { console.error(`${name}: fonts did not load:`, loaded); await b.close(); process.exit(1); }
  const over = await page.evaluate(() => [...document.querySelectorAll(".pg,.s")].map((e, i) => [i + 1, e.scrollHeight - e.clientHeight]).filter((x) => x[1] > 1));
  if (over.length) console.log(`${name}: pages whose content overflows (page, px): ${JSON.stringify(over)}`);
  await page.waitForTimeout(300);
  const pdf = await page.pdf({ printBackground: true, preferCSSPageSize: true, width: `${w}px`, height: `${h}px`, margin: { top: 0, right: 0, bottom: 0, left: 0 } });
  await b.close();
  fs.writeFileSync(pdfFile, pdf);
  const masks = (pdf.toString("latin1").match(/\/SMask\s*\d+\s+\d+\s+R|\/SMask\s*<</g) || []).length;
  if (masks) console.error(`WARNING: ${name} PDF has ${masks} soft mask(s); what they cover may not show in every viewer`);
  const out = path.join(ROOT, "scripts", "shots", "out");
  fs.mkdirSync(out, { recursive: true });
  for (const f of fs.readdirSync(out).filter((f) => f.startsWith(name + "-"))) fs.rmSync(path.join(out, f));
  execFileSync("pdftoppm", ["-r", String(Math.round((1400 / w) * 72)), "-jpeg", pdfFile, path.join(out, name)]);
  const pages = fs.readdirSync(out).filter((f) => f.startsWith(name + "-")).length;
  console.log(`${name}: ${pages} pages, ${masks} soft masks, ${(pdf.length / 1e6).toFixed(1)} MB -> ${path.relative(ROOT, pdfFile)}`);
  return { pages, masks };
}

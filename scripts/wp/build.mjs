// Build the Audazzio Core plugin's assets and seed content.
//   node scripts/wp/build.mjs            (add --zip to also write dist/*.zip for upload)
// - CSS:   wp-src/css/*.css (in order) + the font faces                -> assets/css/az.css
// - JS:    wp-src/js/main.js (bundled with the QR code library)         -> assets/js/az.js
// - fonts: Inter, Inter Tight (variable) and Geist Mono, self-hosted     -> assets/fonts
// - brand: the Audazzio marks, favicon, touch icon, share card, RSPKT    -> assets/brand
// - seed:  pages (pages.mjs), facts (scripts/content/site.mjs), people and logos (content/*.json),
//          the privacy policy (content/privacy.html)                    -> seed/*.json
// Pictures are made by scripts/media/build.mjs and scripts/media/posters.mjs.
import fs from "node:fs";
import path from "node:path";
import { build } from "esbuild";
import sharp from "sharp";
import { PAGES } from "./pages.mjs";
import * as SITE from "../content/site.mjs";

const root = path.join(import.meta.dirname, "..", "..");
const plugin = path.join(root, "wp-content", "plugins", "audazzio-core");
const P = (...a) => path.join(plugin, ...a);
const R = (...a) => path.join(root, ...a);
const mk = (d) => fs.mkdirSync(d, { recursive: true });
const copy = (from, to) => { mk(path.dirname(to)); fs.copyFileSync(from, to); };
const readJSON = (f, d) => (fs.existsSync(f) ? JSON.parse(fs.readFileSync(f, "utf8")) : d);

/* ---------------------------------------------------------------- fonts + CSS */
const RANGES = {
  latin: "U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD",
  "latin-ext": "U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+0304,U+0308,U+0329,U+1D00-1DBF,U+1E00-1E9F,U+1EF2-1EFF,U+2020,U+20A0-20AB,U+20AD-20C0,U+2113,U+2C60-2C7F,U+A720-A7FF",
};
let css = "";
let nFonts = 0;
mk(P("assets", "fonts"));
for (const [family, pkg, base] of [["Inter", "@fontsource-variable/inter", "inter"], ["Inter Tight", "@fontsource-variable/inter-tight", "inter-tight"]]) {
  for (const sub of ["latin", "latin-ext"]) {
    const file = `${base}-${sub}-wght-normal.woff2`;
    copy(R("node_modules", pkg, "files", file), P("assets", "fonts", file)); nFonts++;
    css += `@font-face{font-family:"${family}";font-style:normal;font-weight:100 900;font-display:swap;src:url(../fonts/${file}) format("woff2-variations");unicode-range:${RANGES[sub]}}\n`;
  }
}
for (const w of [400, 500]) {
  const file = `geist-mono-latin-${w}-normal.woff2`;
  copy(R("node_modules", "@fontsource", "geist-mono", "files", file), P("assets", "fonts", file)); nFonts++;
  css += `@font-face{font-family:"Geist Mono";font-style:normal;font-weight:${w};font-display:swap;src:url(../fonts/${file}) format("woff2");unicode-range:${RANGES.latin}}\n`;
}
const cssDir = R("wp-src", "css");
for (const f of fs.readdirSync(cssDir).filter((n) => n.endsWith(".css")).sort()) css += "\n" + fs.readFileSync(path.join(cssDir, f), "utf8");
// Elementor styles every picture on a page it built (".elementor img": height auto, no radius, no shadow).
// A class that only ever sits on a picture is written out as "img.class" so it weighs as much as that rule;
// the sheet is printed after Elementor's (assets.php), so at equal weight this site's rule holds.
css = css.replace(/(^|[\s>+~,(}{])\.(az-player__poster|az-sim__shot|az-story__badge)(?![\w-])/g, "$1img.$2");
const min = css.replace(/\/\*[\s\S]*?\*\//g, "").replace(/\s*\n\s*/g, " ").replace(/\s*([{};])\s*/g, "$1").replace(/;}/g, "}").replace(/\s{2,}/g, " ");
mk(P("assets", "css"));
fs.writeFileSync(P("assets", "css", "az.css"), "/* The Audazzio design system. Built by scripts/wp/build.mjs: edit wp-src/css, not this file. */\n" + min);

/* ------------------------------------------------------------------------- JS */
await build({
  entryPoints: { az: R("wp-src", "js", "main.js") },
  outdir: P("assets", "js"), bundle: true, minify: true, format: "iife", target: "es2020", legalComments: "none", logLevel: "warning",
});

/* ---------------------------------------------------------------------- brand */
mk(P("assets", "brand"));
for (const f of ["audazzio-symbol.svg", "audazzio-logotype.svg"]) copy(R("brand", f), P("assets", "brand", f));
copy(R("brand", "rspkt-joule.png"), P("assets", "brand", "rspkt-joule.png"));
const symbolPath = fs.readFileSync(R("brand", "audazzio-symbol.svg"), "utf8").match(/ d="([^"]+)"/)[1];
const fav = (size, pad, round, bg) => `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${size} ${size}"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#FFAD7A"/><stop offset="1" stop-color="#F26A2E"/></linearGradient></defs><rect width="${size}" height="${size}" rx="${round}" fill="${bg}"/><path transform="translate(${pad} ${pad}) scale(${(size - pad * 2) / 600})" fill="#ffffff" d="${symbolPath}"/></svg>`;
fs.writeFileSync(P("assets", "brand", "favicon.svg"), fav(64, 12, 15, "url(#g)"));
await sharp(Buffer.from(fav(180, 34, 0, "url(#g)"))).png().toFile(P("assets", "brand", "touch.png"));
// the card shown when a link is shared: white, the wave lines, the line
{
  const lines = Array.from({ length: 9 }, (_, i) => {
    const y0 = 420 + (i - 4) * 16, pts = [];
    for (let x = -10; x <= 1210; x += 10) { const d = x - 600, env = Math.exp(-(d * d) / (380 * 380)); pts.push(`${x},${(y0 + env * 46 * (1 - Math.abs(i - 4) / 5 * 0.55) * Math.sin(d / 42 + i * 0.55)).toFixed(1)}`); }
    return `<polyline points="${pts.join(" ")}" fill="none" stroke="rgba(12,18,34,${(0.3 * (1 - Math.abs(i - 4) / 5 * 0.6)).toFixed(2)})" stroke-width="1.4" stroke-dasharray="6 6"/>`;
  }).join("");
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630"><rect width="1200" height="630" fill="#ffffff"/>${lines}<g transform="translate(96 108) scale(.1)" fill="#F89C68"><path d="${symbolPath}"/></g><text x="96" y="268" font-family="Inter Tight, Inter, Helvetica, Arial, sans-serif" font-weight="600" font-size="92" letter-spacing="-4" fill="#0C1222">It comes in waves.</text><text x="98" y="560" font-family="Helvetica, Arial, sans-serif" font-size="22" letter-spacing="3" fill="#6B7080">AUDAZZIO · LIVE QR™ SECOND-SCREEN TECHNOLOGY</text></svg>`;
  await sharp(Buffer.from(svg)).jpeg({ quality: 88 }).toFile(P("assets", "brand", "og.jpg"));
}
fs.writeFileSync(P("assets", "brand", "brand.json"), JSON.stringify({ name: "Audazzio", colors: { ink: "#0C1222", slate: "#3B4152", grey: "#6B7080", mist: "#F5F5F7", line: "#E3E4E8", white: "#FFFFFF", wave: "#F26A2E", peach: "#F89C68" }, type: "Inter Tight (headlines), Inter (text), Geist Mono (labels)" }, null, 2));

/* ------------------------------------------------------------- logos (ratios) */
const RATIOS = {};
if (fs.existsSync(P("assets", "logos"))) {
  for (const f of fs.readdirSync(P("assets", "logos")).filter((n) => /\.(svg|png|webp|jpg)$/.test(n))) {
    const m = await sharp(P("assets", "logos", f)).metadata();
    if (m.width && m.height) RATIOS[f] = Math.round((m.width / m.height) * 100) / 100;
  }
}

/* ----------------------------------------------------------------------- seed */
mk(P("seed"));
const people = readJSON(R("content", "people.json"), []);
const logos = readJSON(R("content", "logos.json"), []);
const apps = readJSON(R("content", "apps.json"), {});
const privacy = fs.existsSync(R("content", "privacy.html")) ? fs.readFileSync(R("content", "privacy.html"), "utf8") : '<p>The full policy is available as a PDF from the button above.</p>';
const pages = JSON.parse(JSON.stringify(PAGES).replace('"@privacy"', JSON.stringify(privacy)));
fs.writeFileSync(P("seed", "pages.json"), JSON.stringify(pages));
const site = {
  company: SITE.COMPANY,
  apps: { app_store: apps.app_store || "", play_store: apps.play_store || "", demo_video: `https://www.youtube.com/watch?v=${SITE.DEMO.id}`, demo_poster: "asset:img/poster-demo.jpg" },
  videos: SITE.VIDEOS.map((v) => ({ title: v.title, label: v.label, length: v.length, url: `https://www.youtube.com/watch?v=${v.id}`, poster: `poster-${v.id}.jpg` })),
  screens: SITE.SCREENS,
  cases: SITE.CASES,
  press: SITE.PRESS,
  quotes: SITE.QUOTES,
  people,
  logos,
};
fs.writeFileSync(P("seed", "site.json"), JSON.stringify(site));
fs.writeFileSync(P("seed", "logo-ratios.json"), JSON.stringify(RATIOS));

// every picture and file the seed asks for should exist
const need = [];
const has = (rel) => fs.existsSync(P("assets", rel));
for (const v of site.videos) if (!has("img/" + v.poster)) need.push("poster " + v.poster);
for (const c of site.cases) { if (!has("img/" + c.image)) need.push("case picture " + c.image); if (!has("docs/" + c.pdf)) need.push("case PDF " + c.pdf); }
for (const p of site.press) { if (p.pdf && !has("docs/" + p.pdf)) need.push("press PDF " + p.pdf); if (p.image && !has("img/" + p.image)) need.push("press picture " + p.image); }
for (const s of site.screens) if (!has("img/" + s.file)) need.push("screen " + s.file);
for (const p of people) if (p.photo && !has("people/" + p.photo)) need.push("portrait " + p.photo);
for (const l of logos) if (!has("logos/" + l.file)) need.push("logo " + l.file);
for (const pg of pages) for (const [, set] of pg.widgets) for (const v of JSON.stringify(set || {}).match(/asset:[^"\\]+/g) || []) if (!has(v.slice(6))) need.push(`${pg.slug}: ${v}`);
if (need.length) console.warn("NOT BUILT YET:\n  " + need.join("\n  "));
// house rules for copy: a long dash or an exclamation mark in page copy is a build warning
const copyText = JSON.stringify(PAGES) + JSON.stringify(SITE.CASES) + JSON.stringify(SITE.PRESS);
for (const [what, re] of [["long dash", /—|–/], ["exclamation mark", /!/]]) {
  const m = re.exec(copyText);
  if (m) console.warn(`WARNING: ${what} in page copy: "${copyText.slice(Math.max(0, m.index - 30), m.index + 30)}"`);
}

const size = (dir) => (fs.existsSync(dir) ? fs.readdirSync(dir, { withFileTypes: true, recursive: true }).filter((e) => e.isFile()).reduce((n, e) => n + fs.statSync(path.join(e.parentPath ?? e.path, e.name)).size, 0) : 0);
const kb = (f) => (fs.statSync(f).size / 1024).toFixed(0) + " KB";
console.log(`plugin built: css ${kb(P("assets", "css", "az.css"))}, js ${kb(P("assets", "js", "az.js"))}, ${nFonts} font files | seed: ${pages.length} pages, ${site.press.length} news, ${site.cases.length} cases, ${people.length} people, ${logos.length} logos | plugin total ${(size(plugin) / 1e6).toFixed(1)} MB`);

/* ------------------------------------------------------------------------ zip */
if (process.argv.includes("--zip")) {
  const { execFileSync } = await import("node:child_process");
  mk(R("dist"));
  for (const [dir, name] of [["wp-content/themes", "audazzio"], ["wp-content/plugins", "audazzio-core"]]) {
    const out = R("dist", name + ".zip");
    fs.rmSync(out, { force: true });
    execFileSync("zip", ["-rq", "-X", out, name, "-x", "*.DS_Store"], { cwd: R(dir), stdio: "inherit" });
    console.log("zip: dist/" + name + ".zip " + (fs.statSync(out).size / 1e6).toFixed(1) + " MB");
  }
}

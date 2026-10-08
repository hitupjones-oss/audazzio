// A static copy of the running site, for hosts that cannot run WordPress (Vercel).
//   node scripts/deploy/export.mjs      (needs the dev site: scripts/wp/dev.sh)
//   -> site/   every page as HTML and every file the pages use, plus vercel.json at the repository root.
// What a static copy is NOT: there is no WordPress behind it, so no dashboard and no inbox. The Join the
// Wave form still walks through its steps and grades the answers, but nothing is sent: the copy tells the
// page it is a preview and the page says so.
import fs from "node:fs";
import path from "node:path";

const ROOT = path.join(import.meta.dirname, "..", "..");
const OUT = path.join(ROOT, "site");
const ORIGIN = process.env.AZ_ORIGIN || "http://localhost:3031";
const PLUGIN = path.join(ROOT, "wp-content", "plugins", "audazzio-core");
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

fs.rmSync(OUT, { recursive: true, force: true });
fs.mkdirSync(OUT, { recursive: true });

async function get(url, as = "text") {
  for (let i = 0; i < 4; i++) {
    try {
      const r = await fetch(url, { redirect: "follow" });
      if (r.ok) return as === "text" ? await r.text() : Buffer.from(await r.arrayBuffer());
      if (r.status === 404) return null;
    } catch { /* the local server can be busy: wait and try again */ }
    await sleep(600 * (i + 1));
  }
  return null;
}

const pages = ["/", ...JSON.parse(fs.readFileSync(path.join(PLUGIN, "seed", "pages.json"), "utf8")).filter((p) => p.slug !== "home").map((p) => `/${p.slug}/`)];
const assets = new Set();
const esc = ORIGIN.replace(/\//g, "\\/");
const want = (u) => /^\/(wp-content|wp-includes)\//.test(u) && /\.[a-z0-9]{2,5}$/i.test(u) && !/[*<>|{}]/.test(u);
function add(u) {
  if (!u) return;
  u = u.replace(/&amp;/g, "&").replace(/&#038;/g, "&").replace(/\\\//g, "/");
  if (u.startsWith(ORIGIN)) u = u.slice(ORIGIN.length);
  if (u.startsWith("//") || /^[a-z]+:/i.test(u)) return;
  const clean = u.split("#")[0].split("?")[0];
  if (want(clean)) assets.add(clean);
}
function collect(html) {
  for (const m of html.matchAll(/(?:src|href|poster|data-src|data-az-src|content)=["']([^"']+)["']/g)) add(m[1]);
  for (const m of html.matchAll(/srcset=["']([^"']+)["']/g)) m[1].split(",").forEach((p) => add(p.trim().split(/\s+/)[0]));
  for (const m of html.matchAll(/url\((['"]?)([^'")]+)\1\)/g)) add(m[2]);
  for (const m of html.matchAll(/"(\/wp-content\/[^"\\]+|\\\/wp-content\\\/[^"]+?)"/g)) add(m[1].replace(/\\\//g, "/"));
}

/** Told to every page: this copy has no server behind it, so the form grades itself and says nothing was sent. */
function rewrite(html) {
  let out = html.split(esc).join("").split(ORIGIN).join("");
  out = out.replace(/<meta name=['"]robots['"][^>]*>/g, "").replace(/<link rel=['"](?:https:\/\/api\.w\.org\/|EditURI|alternate|shortlink)['"][^>]*>\s*/g, "");
  out = out.replace(/<link rel=['"]dns-prefetch['"] href=['"]\/\/localhost['"][^>]*>\s*/g, "");
  out = out.replace("<head>", '<head><meta name="robots" content="noindex, nofollow">');
  return out.replace("window.AZ_CONFIG={", 'window.AZ_CONFIG={"static":true,');
}
function save(rel, data) {
  const file = path.join(OUT, rel);
  fs.mkdirSync(path.dirname(file), { recursive: true });
  fs.writeFileSync(file, data);
}

/* ------------------------------------------------------------------ pages */
const failed = [];
for (const p of pages) {
  const html = await get(ORIGIN + p);
  if (!html || !html.includes("az-main")) { failed.push(p); continue; }
  const out = rewrite(html);
  collect(out);
  save(path.join(p, "index.html"), out);
}
const notFound = await fetch(ORIGIN + "/this-page-does-not-exist/").then((r) => (r.status === 404 ? r.text() : "")).catch(() => "");
if (notFound.includes("az-main")) { const out = rewrite(notFound); collect(out); save("404.html", out); }
console.log(`pages: ${pages.length - failed.length} saved${failed.length ? ", FAILED: " + failed.join(" ") : ""}${notFound ? ", plus the not-found page" : ""}`);

/* ----------------------------------------------------------------- assets */
// files that Elementor's front-end script loads by name once the page is running (its webpack chunks):
// all of them, read from the dev site's copy of Elementor when it is there
const CHUNK_DIR = path.join(ROOT, ".wp", "wordpress", "wp-content", "plugins", "elementor", "assets", "js", "chunks");
const CHUNKS = fs.existsSync(CHUNK_DIR) ? fs.readdirSync(CHUNK_DIR).filter((f) => f.endsWith(".min.js")) : "accordion alert background-slideshow background-video container-grid-container counter image-carousel lightbox nested-tabs progress shared-frontend-handlers text-editor toggle video".split(" ").map((c) => c + ".min.js");
for (const c of CHUNKS) assets.add(`/wp-content/plugins/elementor/assets/js/chunks/${c}`);
// every picture, font, film poster and document the plugin ships
for (const e of fs.readdirSync(path.join(PLUGIN, "assets"), { withFileTypes: true, recursive: true })) {
  if (e.isFile()) assets.add("/wp-content/plugins/audazzio-core/assets/" + path.relative(path.join(PLUGIN, "assets"), path.join(e.parentPath ?? e.path, e.name)).replace(/\\/g, "/"));
}
let got = 0;
const miss = [], queue = [...assets], seen = new Set(queue);
while (queue.length) {
  const a = queue.shift();
  const buf = await get(ORIGIN + a, "buffer");
  if (!buf) { miss.push(a); continue; }
  if (a.endsWith(".css")) {
    const css = buf.toString("utf8").split(ORIGIN).join("");
    for (const m of css.matchAll(/url\((['"]?)([^'")]+)\1\)/g)) {
      if (/^(data:|https?:|\/\/)/.test(m[2])) continue;
      const u = m[2].split("#")[0].split("?")[0];
      const abs = u.startsWith("/") ? u : path.posix.normalize(path.posix.join(path.posix.dirname(a), u));
      if (want(abs) && !seen.has(abs)) { seen.add(abs); queue.push(abs); }
    }
    save(a, css);
  } else if (a.endsWith(".js") || a.endsWith(".json")) save(a, buf.toString("utf8").split(ORIGIN).join("").split(esc).join(""));
  else save(a, buf);
  got++;
}
const real = miss.filter((m) => !m.includes("/elementor/assets/js/chunks/"));
console.log(`files: ${got} saved${real.length ? ", MISSING: " + real.slice(0, 8).join(" ") : ""}`);

/* ------------------------------------------------------------ host config */
fs.copyFileSync(path.join(PLUGIN, "assets", "brand", "favicon.svg"), path.join(OUT, "favicon.svg"));
// today's addresses on audazzio.com, sent to their new pages (the WordPress plugin does the same)
const REDIRECTS = [["/liveqr", "/live-qr/"], ["/applications", "/solutions/#beyond"], ["/broadcasters", "/solutions/#broadcasters"], ["/teams-leagues", "/solutions/#teams"], ["/sponsors-brands", "/solutions/#sponsors"], ["/resources-audazzio", "/newsroom/"], ["/resources", "/newsroom/"], ["/about-0-0", "/about/"], ["/contact", "/join/"], ["/contact-typ", "/join/"], ["/demo", "/try/"], ["/explainer", "/live-qr/#explainer"]];
fs.writeFileSync(path.join(ROOT, "vercel.json"), JSON.stringify({
  $schema: "https://openapi.vercel.sh/vercel.json",
  framework: null,
  installCommand: "",
  buildCommand: "",
  outputDirectory: "site",
  cleanUrls: false,
  trailingSlash: true,
  redirects: REDIRECTS.map(([source, destination]) => ({ source, destination, permanent: true })),
  headers: [
    { source: "/(.*)", headers: [{ key: "X-Robots-Tag", value: "noindex, nofollow" }] },
    { source: "/wp-content/(.*)", headers: [{ key: "Cache-Control", value: "public, max-age=604800" }] },
  ],
}, null, 2) + "\n");
save("robots.txt", "User-agent: *\nDisallow: /\n");
const files = fs.readdirSync(OUT, { withFileTypes: true, recursive: true }).filter((e) => e.isFile()).map((e) => [fs.statSync(path.join(e.parentPath ?? e.path, e.name)).size, e.name]);
const big = files.slice().sort((x, y) => y[0] - x[0]).slice(0, 3);
console.log(`site/: ${files.length} files, ${(files.reduce((n, f) => n + f[0], 0) / 1e6).toFixed(1)} MB | largest: ${big.map(([s, n]) => n + " " + (s / 1e6).toFixed(1) + " MB").join(", ")}`);
if (failed.length || real.length) process.exit(1);

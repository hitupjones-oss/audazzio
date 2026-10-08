// Serve site/ the way Vercel will (index.html per folder, the 404 page, the redirects in vercel.json).
//   node scripts/deploy/serve.mjs   -> http://localhost:3051
import http from "node:http";
import fs from "node:fs";
import path from "node:path";

const ROOT = path.join(import.meta.dirname, "..", "..");
const SITE = path.join(ROOT, "site");
const PORT = +(process.env.AZ_STATIC_PORT || 3051);
const cfg = JSON.parse(fs.readFileSync(path.join(ROOT, "vercel.json"), "utf8"));
const TYPES = { ".html": "text/html; charset=utf-8", ".css": "text/css", ".js": "text/javascript", ".json": "application/json", ".svg": "image/svg+xml", ".png": "image/png", ".jpg": "image/jpeg", ".webp": "image/webp", ".woff2": "font/woff2", ".pdf": "application/pdf", ".txt": "text/plain", ".mp4": "video/mp4" };
http.createServer((req, res) => {
  const u = decodeURIComponent(req.url.split("?")[0]);
  const r = (cfg.redirects || []).find((x) => x.source === u || x.source + "/" === u);
  if (r) { res.writeHead(308, { Location: r.destination }); return res.end(); }
  let f = path.join(SITE, u);
  if (!f.startsWith(SITE)) { res.writeHead(400); return res.end(); }
  if (fs.existsSync(f) && fs.statSync(f).isDirectory()) {
    if (!u.endsWith("/")) { res.writeHead(308, { Location: u + "/" }); return res.end(); }
    f = path.join(f, "index.html");
  }
  if (!fs.existsSync(f)) { res.writeHead(404, { "Content-Type": TYPES[".html"] }); return res.end(fs.readFileSync(path.join(SITE, "404.html"))); }
  res.writeHead(200, { "Content-Type": TYPES[path.extname(f)] || "application/octet-stream" });
  fs.createReadStream(f).pipe(res);
}).listen(PORT, () => console.log(`static site on http://localhost:${PORT}`));

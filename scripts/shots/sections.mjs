// One picture per section of a page (cleaner than one long screenshot: the fixed header and bar stay out).
//   node scripts/shots/sections.mjs /about/ [--mobile] -> scripts/shots/out/s-<page>-NN.png
import fs from "node:fs";
import path from "node:path";
import { launch, settle, ORIGIN } from "./browser.mjs";

const args = process.argv.slice(2);
const mobile = args.includes("--mobile");
const p = args.find((a) => !a.startsWith("--")) || "/";
const OUT = path.join(import.meta.dirname, "out");
fs.mkdirSync(OUT, { recursive: true });
const b = await launch();
const ctx = await b.newContext(mobile ? { viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, isMobile: true, hasTouch: true } : { viewport: { width: 1440, height: 900 } });
const page = await ctx.newPage();
const errors = [];
page.on("pageerror", (e) => errors.push(e.message));
page.on("console", (m) => { if (m.type() === "error") errors.push(m.text()); });
await page.goto(ORIGIN + p, { waitUntil: "networkidle" });
await settle(page);
await page.addStyleTag({ content: ".az-nav,.az-bar,.az-notify,.az-skip{visibility:hidden!important}" });
const name = p.replace(/\//g, "_").replace(/^_|_$/g, "") || "home";
const secs = await page.$$(".az-hero, .az-phero, .az-sec, .az-foot");
let i = 0;
for (const s of secs) {
  const f = path.join(OUT, `s-${name}${mobile ? "-m" : ""}-${String(++i).padStart(2, "0")}.png`);
  await s.screenshot({ path: f });
}
console.log(`${name}: ${i} sections`, errors.length ? "ERRORS: " + errors.join(" | ") : "ok");
await b.close();

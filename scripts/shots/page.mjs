// Screenshot pages: node scripts/shots/page.mjs [/path/ ...] [--mobile] [--full] -> scripts/shots/out/
import fs from "node:fs";
import path from "node:path";
import { launch, settle, ORIGIN } from "./browser.mjs";

const args = process.argv.slice(2);
const mobile = args.includes("--mobile"), full = args.includes("--full");
const paths = args.filter((a) => !a.startsWith("--"));
const OUT = path.join(import.meta.dirname, "out");
fs.mkdirSync(OUT, { recursive: true });
const b = await launch();
const ctx = await b.newContext(mobile ? { viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, isMobile: true, hasTouch: true } : { viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 });
for (const p of paths.length ? paths : ["/"]) {
  const page = await ctx.newPage();
  const errors = [];
  page.on("pageerror", (e) => errors.push(e.message));
  page.on("console", (m) => { if (m.type() === "error") errors.push(m.text()); });
  await page.goto(ORIGIN + p, { waitUntil: "networkidle" });
  await settle(page);
  const name = (p.replace(/\//g, "_").replace(/^_|_$/g, "") || "home") + (mobile ? "-m" : "") + (full ? "-full" : "");
  await page.screenshot({ path: path.join(OUT, name + ".png"), fullPage: full });
  console.log(name, errors.length ? "ERRORS: " + errors.join(" | ") : "ok");
  await page.close();
}
await b.close();

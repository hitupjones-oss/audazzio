// Open a page in Elementor's editor as the admin and check the Audazzio widgets load:
//   node scripts/shots/editor.mjs [/slug/] -> scripts/shots/out/editor.png
import fs from "node:fs";
import path from "node:path";
import { launch, ORIGIN } from "./browser.mjs";

const slug = (process.argv[2] || "home").replace(/\//g, "");
const OUT = path.join(import.meta.dirname, "out");
fs.mkdirSync(OUT, { recursive: true });
const b = await launch();
const page = await b.newPage({ viewport: { width: 1600, height: 1000 } });
const errors = [];
page.on("pageerror", (e) => errors.push(e.message));
await page.goto(ORIGIN + "/wp-login.php");
await page.fill("#user_login", "rspkt");
await page.fill("#user_pass", "rspkt-local");
await page.click("#wp-submit");
await page.waitForLoadState("networkidle");
const id = await page.evaluate(async ([o, s]) => {
  const r = await fetch(`${o}/wp-json/wp/v2/pages?slug=${s}&_fields=id`, { credentials: "include" });
  return (await r.json())[0]?.id;
}, [ORIGIN, slug]);
await page.goto(`${ORIGIN}/wp-admin/post.php?post=${id}&action=elementor`, { waitUntil: "load", timeout: 120000 });
await page.waitForSelector("#elementor-preview-iframe", { timeout: 120000 });
await page.waitForTimeout(8000);
const frame = page.frame({ url: /elementor-preview/ });
const widgets = frame ? await frame.$$eval(".elementor-widget[data-widget_type^='az-']", (a) => a.map((e) => e.dataset.widget_type)) : [];
await page.screenshot({ path: path.join(OUT, "editor.png") });
// open the first widget's controls
if (frame) {
  const w = await frame.$(".elementor-widget[data-widget_type^='az-']");
  if (w) { await w.click(); await page.waitForTimeout(2500); await page.screenshot({ path: path.join(OUT, "editor-controls.png") }); }
}
const cat = await page.$("#elementor-panel-category-audazzio").catch(() => null);
console.log(JSON.stringify({ page: slug, id, widgets: widgets.length, kinds: [...new Set(widgets)], errors: errors.slice(0, 5) }));
await b.close();

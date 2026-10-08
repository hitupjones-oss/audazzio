// Pictures of the site for the proposal deck and the site guide: scripts/shots/out/d-*.jpg
//   node scripts/shots/deck.mjs     (needs the dev site, with sample inquiries: scripts/wp/samples.php,
//                                    and the static copy on :3051 for the preview form's grade)
import fs from "node:fs";
import path from "node:path";
import { launch, settle, ORIGIN } from "./browser.mjs";
import { stubYouTube } from "./ytstub.mjs";

const OUT = path.join(import.meta.dirname, "out");
const STATIC = process.env.AZ_STATIC || "http://localhost:3051";
fs.mkdirSync(OUT, { recursive: true });
const b = await launch();
const desk = await b.newContext({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 2 });
const phone = await b.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 3, isMobile: true, hasTouch: true });
await stubYouTube(desk); await stubYouTube(phone);
const HIDE = ".az-nav,.az-bar,.az-notify,.az-skip{visibility:hidden!important}";
const save = async (page, name, opts = {}) => { await page.screenshot({ path: path.join(OUT, `d-${name}.jpg`), type: "jpeg", quality: 88, ...opts }); console.log("d-" + name); };
async function go(ctx, url, { hide = false, wait = 0 } = {}) {
  const page = await ctx.newPage();
  await page.goto(url, { waitUntil: "networkidle" });
  await settle(page);
  if (hide) await page.addStyleTag({ content: HIDE });
  if (wait) await page.waitForTimeout(wait);
  return page;
}
const section = async (page, sel, name) => { const el = await page.$(sel); if (el) { await el.scrollIntoViewIfNeeded(); await page.waitForTimeout(700); await el.screenshot({ path: path.join(OUT, `d-${name}.jpg`), type: "jpeg", quality: 88 }); console.log("d-" + name); } else console.log("MISSING " + sel); };

// home: the first screen, then with the notification
{
  const p = await go(desk, ORIGIN + "/");
  await p.addStyleTag({ content: ".az-notify,.az-skip{visibility:hidden!important}" });
  await p.evaluate(() => scrollTo(0, 0)); await p.waitForTimeout(1500);
  await save(p, "home");
  await p.addStyleTag({ content: ".az-notify{visibility:visible!important}" });
  await p.waitForSelector(".az-notify.is-in", { timeout: 9000 }).catch(() => {});
  await p.waitForTimeout(900);
  await save(p, "notify");
  await p.addStyleTag({ content: HIDE });
  for (const [sel, name] of [["[data-az-steps]", "steps"], ["[data-az-try-section]", "try"], [".az-numbers", "numbers"], [".az-videos", "films"], [".az-sols", "who"], [".az-cases", "cases"], [".az-logos", "logos"], [".az-press", "press"], [".az-cta", "cta"]]) await section(p, sel, name);
  // the whole page, small, for the one-minute story
  await p.evaluate(() => scrollTo(0, 0));
  await p.screenshot({ path: path.join(OUT, "d-home-full.jpg"), type: "jpeg", quality: 80, fullPage: true });
  await p.close();
}
// the Try sheet, playing
{
  const p = await go(desk, ORIGIN + "/");
  await p.addStyleTag({ content: ".az-notify,.az-skip,.az-bar{visibility:hidden!important}" });
  await p.click("[data-az-try].az-nav__try");
  await p.waitForSelector("#az-try[open]");
  await p.waitForTimeout(600);
  await save(p, "try-sheet");
  await p.click("#az-try .az-player__play");
  await p.waitForTimeout(9000);
  await save(p, "try-playing");
  await p.close();
}
// Join the Wave: step 1, step 3 filled, the preview's grade
{
  const p = await go(desk, STATIC + "/");
  await p.addStyleTag({ content: ".az-notify,.az-skip,.az-bar{visibility:hidden!important}" });
  await p.click(".az-nav__join");
  await p.waitForSelector("#az-join[open]"); await p.waitForTimeout(600);
  const f = "#az-join form";
  await p.fill(`${f} [name=name]`, "Dana Whitfield"); await p.fill(`${f} [name=position]`, "VP, Digital Products");
  await p.fill(`${f} [name=company]`, "Regional Sports Network"); await p.fill(`${f} [name=email]`, "dana@rsn-example.com"); await p.fill(`${f} [name=phone]`, "210 555 0142");
  await save(p, "join-1");
  await p.click(`${f} [data-az-next]`);
  await p.check(`${f} [name=org][value=broadcaster]`, { force: true }); await p.check(`${f} [name="uses[]"][value=broadcast]`, { force: true }); await p.check(`${f} [name="uses[]"][value=sponsor]`, { force: true });
  await p.fill(`${f} [name=details]`, "Every home game next season: player profiles when a substitute comes on, and a presenting sponsor’s competition at half time.");
  await save(p, "join-2");
  await p.click(`${f} [data-az-next]`);
  for (const [q, v] of [["audience", "xl"], ["frequency", "many"], ["budget", "xl"], ["timeline", "soon"], ["role", "decide"]]) await p.check(`${f} [name=${q}][value=${v}]`, { force: true });
  await save(p, "join-3");
  await p.click(`${f} [data-az-next]`); await p.check(`${f} [name=consent]`, { force: true });
  await p.click(`${f} [data-az-send]`);
  await p.waitForSelector(`${f} [data-az-done]:not([hidden])`); await p.waitForTimeout(600);
  await save(p, "join-done");
  await p.close();
}
// inner pages
{
  const p = await go(desk, ORIGIN + "/live-qr/", { hide: true });
  await p.evaluate(() => scrollTo(0, 0)); await p.waitForTimeout(1200);
  await save(p, "liveqr");
  for (const [sel, name] of [["[data-az-flow]", "flow"], [".az-canvas", "canvas"], [".az-faq", "faq"]]) await section(p, sel, name);
  await p.close();
}
{
  const p = await go(desk, ORIGIN + "/solutions/", { hide: true });
  await p.evaluate(() => scrollTo(0, 0)); await p.waitForTimeout(1000);
  await save(p, "solutions");
  await section(p, "#broadcasters", "broadcasters"); await section(p, "#beyond", "beyond");
  await p.close();
}
{
  const p = await go(desk, ORIGIN + "/newsroom/", { hide: true });
  await section(p, ".az-press", "newsroom");
  await p.close();
}
{
  const p = await go(desk, ORIGIN + "/about/", { hide: true });
  await p.evaluate(() => scrollTo(0, 0)); await p.waitForTimeout(1000);
  await save(p, "about");
  await section(p, ".az-story", "story"); await section(p, "#leadership", "leadership"); await section(p, "#board", "board");
  await p.click("#leadership .az-person__more"); await p.waitForSelector("#az-bio[open]"); await p.waitForTimeout(700);
  await save(p, "bio");
  await p.close();
}
// phones
for (const [u, name, sheet] of [["/", "m-home"], ["/try/", "m-try"], ["/join/", "m-join"], ["/about/", "m-about"]]) {
  const p = await go(phone, ORIGIN + u);
  await p.addStyleTag({ content: ".az-notify,.az-skip{visibility:hidden!important}" });
  await p.evaluate(() => scrollTo(0, 0)); await p.waitForTimeout(1200);
  if (name === "m-try") { await p.evaluate(() => document.querySelector("[data-az-try-section]").scrollIntoView()); await p.waitForTimeout(800); }
  if (name === "m-about") { await p.evaluate(() => document.querySelector("#leadership").scrollIntoView()); await p.waitForTimeout(800); }
  await save(p, name);
  await p.close();
}
// the back office
{
  const p = await desk.newPage();
  await p.goto(ORIGIN + "/wp-login.php");
  await p.fill("#user_login", "rspkt"); await p.fill("#user_pass", "rspkt-local"); await p.click("#wp-submit");
  await p.waitForLoadState("networkidle");
  await p.goto(ORIGIN + "/wp-admin/admin.php?page=audazzio"); await p.waitForTimeout(800);
  await save(p, "inbox");
  const first = await p.$("table.widefat a[href*='lead=']");
  if (first) { await first.click(); await p.waitForLoadState("networkidle"); await save(p, "lead", { fullPage: false }); }
  await p.goto(ORIGIN + "/wp-admin/admin.php?page=audazzio-settings"); await p.waitForTimeout(600);
  await save(p, "settings");
  const id = await p.evaluate(async (o) => (await (await fetch(`${o}/wp-json/wp/v2/pages?slug=home&_fields=id`)).json())[0].id, ORIGIN);
  await p.goto(`${ORIGIN}/wp-admin/post.php?post=${id}&action=elementor`, { waitUntil: "load", timeout: 120000 });
  await p.waitForSelector("#elementor-preview-iframe", { timeout: 120000 }); await p.waitForTimeout(8000);
  const fr = p.frame({ url: /elementor-preview/ });
  const w = fr && (await fr.$(".elementor-widget-az-hero"));
  if (w) { await w.click(); await p.waitForTimeout(2500); }
  await save(p, "editor");
  await p.close();
}
await b.close();

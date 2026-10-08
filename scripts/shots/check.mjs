// End-to-end checks in headless Chrome, on the WordPress dev site or the static copy:
//   node scripts/shots/check.mjs                      (http://localhost:3031, the real form)
//   AZ_ORIGIN=http://localhost:3051 node scripts/shots/check.mjs   (site/, the preview form)
import { launch, ORIGIN } from "./browser.mjs";

const PAGES = ["/", "/live-qr/", "/solutions/", "/newsroom/", "/about/", "/try/", "/join/", "/privacy/"];
const results = [];
const ok = (name, pass, info = "") => { results.push([pass, name, info]); console.log(`${pass ? "PASS" : "FAIL"}  ${name}${info ? "  (" + info + ")" : ""}`); };
const b = await launch();

async function open(path, opts = {}) {
  const ctx = await b.newContext(opts.mobile ? { viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, isMobile: true, hasTouch: true } : { viewport: { width: 1440, height: 900 } });
  const page = await ctx.newPage();
  const errors = [];
  page.on("pageerror", (e) => errors.push(e.message));
  page.on("console", (m) => { if (m.type() === "error" && !/youtube|ytimg|doubleclick|googlevideo|Failed to load resource.*(youtube|google)/i.test(m.text())) errors.push(m.text()); });
  page.on("requestfailed", (r) => { if (!/youtube|ytimg|google|doubleclick/.test(r.url())) errors.push("failed " + r.url()); });
  page.on("response", (r) => { if (r.status() >= 400 && !/youtube|ytimg|google|favicon/.test(r.url()) && r.request().resourceType() !== "document") errors.push(r.status() + " " + r.url()); });
  await page.goto(ORIGIN + path, { waitUntil: "networkidle" });
  return { ctx, page, errors };
}

// 1. every page, desktop and phone: loads, no errors, nothing wider than the screen
for (const p of PAGES) {
  for (const mobile of [false, true]) {
    const { ctx, page, errors } = await open(p, { mobile });
    const over = await page.evaluate(() => document.documentElement.scrollWidth - innerWidth);
    const main = await page.$(".az-main");
    ok(`${p} ${mobile ? "phone" : "desktop"} loads clean`, !!main && !errors.length && over <= 0, [errors.slice(0, 3).join(" | "), over > 0 ? `overflows by ${over}px` : ""].filter(Boolean).join("; "));
    await ctx.close();
  }
}

// 2. home: the notification arrives and opens the Try sheet; the player starts; the phone wakes
{
  const { ctx, page } = await open("/");
  const n = await page.waitForSelector(".az-notify.is-in", { timeout: 9000 }).catch(() => null);
  ok("notification slides in on the home page", !!n);
  if (n) {
    await page.click(".az-notify__card");
    const d = await page.waitForSelector("#az-try[open]", { timeout: 3000 }).catch(() => null);
    ok("notification opens Try Audazzio", !!d);
    await page.click("#az-try .az-player__play");
    const started = await page.waitForSelector("#az-try .az-player.is-started", { timeout: 4000 }).catch(() => null);
    ok("demo player starts", !!started);
    const sim = await page.waitForSelector("#az-try .az-sim.is-active", { timeout: 6000 }).catch(() => null);
    ok("phone simulation shows a screen", !!sim);
    await page.keyboard.press("Escape");
    await page.waitForTimeout(400);
    ok("Escape closes the sheet", !(await page.$("#az-try[open]")));
  }
  // the bar: off at the top, on after the first screen, off over the footer
  const barTop = await page.$eval(".az-bar", (e) => e.classList.contains("is-on"));
  await page.evaluate(() => scrollTo(0, innerHeight * 1.5)); await page.waitForTimeout(500);
  const barMid = await page.$eval(".az-bar", (e) => e.classList.contains("is-on"));
  await page.evaluate(() => scrollTo(0, document.body.scrollHeight)); await page.waitForTimeout(700);
  const barEnd = await page.$eval(".az-bar", (e) => e.classList.contains("is-on"));
  ok("Join the Wave bar: hidden at top, shown mid-page, hidden at the footer", !barTop && barMid && !barEnd, `${barTop}/${barMid}/${barEnd}`);
  // a film opens in the lightbox
  await page.evaluate(() => scrollTo(0, 0));
  const film = await page.$("[data-az-video]");
  if (film) { await film.click(); const f = await page.waitForSelector("#az-film[open] iframe, #az-film[open] video", { timeout: 3000 }).catch(() => null); ok("a film opens in the lightbox", !!f); await page.keyboard.press("Escape"); }
  const logos = await page.$$eval(".az-logo img", (a) => a.length);
  ok("logo carousel has logos", logos > 0, `${logos} images`);
  await ctx.close();
}

// 3. Join the Wave: the dialog from the header, validation, four steps, the review, sending
{
  const { ctx, page } = await open("/");
  await page.click(".az-nav__join");
  await page.waitForSelector("#az-join[open]");
  const f = "#az-join form";
  await page.click(`${f} [data-az-next]`);
  const bad = await page.$$eval(`${f} .is-bad`, (a) => a.length);
  ok("empty step 1 is stopped with messages", bad >= 5, `${bad} fields flagged`);
  await page.fill(`${f} [name=name]`, "Jordan Rivera");
  await page.fill(`${f} [name=position]`, "VP, Digital");
  await page.fill(`${f} [name=company]`, "Example Sports Network");
  await page.fill(`${f} [name=email]`, "jordan@example-network.com");
  await page.fill(`${f} [name=phone]`, "210 555 0100");
  await page.waitForTimeout(4200); // a person takes a few seconds; the server ignores anything faster
  await page.click(`${f} [data-az-next]`);
  await page.click(`${f} [name=org][value=broadcaster] + span`);
  await page.click(`${f} [name="uses[]"][value=broadcast] + span`);
  await page.click(`${f} [name="uses[]"][value=sponsor] + span`);
  await page.fill(`${f} [name=details]`, "A national broadcast of a season of games: player profiles and sponsor competitions on viewers' phones at key moments.");
  await page.click(`${f} [data-az-next]`);
  for (const [q, v] of [["audience", "xl"], ["frequency", "many"], ["budget", "l"], ["timeline", "soon"], ["role", "decide"]]) await page.click(`${f} [name=${q}][value=${v}] + span`);
  await page.click(`${f} [data-az-next]`);
  const review = await page.$$eval(`${f} [data-az-review] dd`, (a) => a.length);
  ok("step 4 shows the review", review >= 12, `${review} answers`);
  await page.click(`${f} [data-az-send]`);
  const consent = await page.$$eval(`${f} .az-check.is-bad`, (a) => a.length);
  ok("sending without consent is stopped", consent === 1);
  await page.check(`${f} [name=consent]`);
  const [resp] = await Promise.all([
    page.waitForResponse((r) => r.url().includes("/wp-json/audazzio/v1/join"), { timeout: 8000 }).catch(() => null),
    page.click(`${f} [data-az-send]`),
  ]);
  const done = await page.waitForSelector(`${f} [data-az-done]:not([hidden])`, { timeout: 8000 }).catch(() => null);
  const isStatic = await page.evaluate(() => !!(window.AZ_CONFIG || {}).static);
  if (isStatic) {
    const g = await page.$eval(`${f} [data-az-grade]`, (e) => e.textContent).catch(() => "");
    ok("preview: the form finishes and shows the team's grade", !!done && /grade A/.test(g), g.slice(0, 90));
  } else {
    ok("the form sends to WordPress and finishes", !!done && !!resp && resp.ok(), resp ? String(resp.status()) : "no request");
  }
  await ctx.close();
}

// 4. About: a biography opens with the right name
{
  const { ctx, page } = await open("/about/");
  const first = await page.$(".az-person__more");
  if (first) {
    const name = await page.$eval(".az-person__name", (e) => e.textContent);
    await first.click();
    const t = await page.waitForSelector("#az-bio[open] .az-bio__name", { timeout: 3000 }).catch(() => null);
    ok("a biography opens", !!t && (await t.textContent()) === name, name);
    const li = await page.$$eval(".az-person__li", (a) => a.length);
    ok("LinkedIn links on the leadership cards", li >= 3, `${li} links`);
  } else ok("people on the About page", false);
  await ctx.close();
}

// 5. phone: the menu opens and closes
{
  const { ctx, page } = await open("/", { mobile: true });
  await page.click(".az-nav__burger");
  const m = await page.isVisible("#az-menu");
  await page.click(".az-nav__burger");
  ok("phone menu opens and closes", m && !(await page.isVisible("#az-menu")));
  await ctx.close();
}

// 6. today's addresses land on the new pages
for (const [from, to] of [["/liveqr", "/live-qr/"], ["/contact", "/join/"], ["/about-0-0", "/about/"]]) {
  const r = await fetch(ORIGIN + from, { redirect: "manual" });
  const loc = r.headers.get("location") || "";
  ok(`redirect ${from} -> ${to}`, [301, 308].includes(r.status) && loc.endsWith(to), `${r.status} ${loc}`);
}

await b.close();
const failed = results.filter((r) => !r[0]);
console.log(`\n${results.length - failed.length}/${results.length} checks passed on ${ORIGIN}`);
process.exit(failed.length ? 1 : 0);

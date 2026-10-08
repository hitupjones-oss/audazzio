// Today's audazzio.com, for the "today" slide: scripts/shots/out/d-before-*.jpg
import path from "node:path";
import { launch } from "./browser.mjs";
const OUT = path.join(import.meta.dirname, "out");
const b = await launch();
const ctx = await b.newContext({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 2 });
for (const [u, name] of [["https://www.audazzio.com/", "home"], ["https://www.audazzio.com/liveqr", "liveqr"], ["https://www.audazzio.com/demo", "demo"], ["https://www.audazzio.com/contact", "contact"]]) {
  const p = await ctx.newPage();
  try {
    await p.goto(u, { waitUntil: "networkidle", timeout: 60000 });
    await p.waitForTimeout(2500);
    await p.addStyleTag({ content: "#hs-eu-cookie-confirmation,#hs-banner-parent,.hs-cookie-notification-position-bottom{display:none!important}" });
    await p.screenshot({ path: path.join(OUT, `d-before-${name}.jpg`), type: "jpeg", quality: 85 });
    console.log("d-before-" + name);
  } catch (e) { console.log("failed " + u + " " + e.message); }
  await p.close();
}
await b.close();

// Everything the site does in the browser, set up once per page.
import { initWaves } from "./waves.js";
import { rise, header, bar, counters, rails, marquee, quotes, filters, steps, flow, subnav } from "./ui.js";
import { dialogs } from "./dialogs.js";
import { tryAudazzio } from "./player.js";
import { join } from "./join.js";

document.documentElement.classList.add("az-js");
const run = () => {
  for (const fn of [header, rise, bar, dialogs, initWaves, counters, rails, marquee, quotes, filters, steps, flow, subnav, tryAudazzio, join]) {
    try { fn(); } catch (e) { console.error("audazzio:", fn.name, e); }
  }
};
if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", run); else run();

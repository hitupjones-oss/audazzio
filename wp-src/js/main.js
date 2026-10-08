// Everything the site does in the browser. init(root) sets up whatever inside root is not set up yet, so it
// runs once for the page and again for each widget the Elementor editor draws.
import { initWaves } from "./waves.js";
import { rise, header, bar, counters, rails, marquee, quotes, filters, steps, flow, subnav } from "./ui.js";
import { dialogs } from "./dialogs.js";
import { tryAudazzio } from "./player.js";
import { join } from "./join.js";

const PARTS = [header, rise, bar, dialogs, initWaves, counters, rails, marquee, quotes, filters, steps, flow, subnav, tryAudazzio, join];

export function init(root = document) {
  for (const fn of PARTS) {
    try { fn(root); } catch (e) { console.error("audazzio:", fn.name, e); }
  }
}

document.documentElement.classList.add("az-js");
if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", () => init()); else init();

// In the Elementor editor every edit renders the widget again: set up each element as it lands.
let hooked = false;
const hook = () => {
  const ef = window.elementorFrontend;
  if (hooked || !ef || !ef.hooks || (ef.isEditMode && !ef.isEditMode())) return;
  hooked = true;
  ef.hooks.addAction("frontend/element_ready/global", ($el) => { if ($el && $el[0]) init($el[0]); });
};
hook();
window.addEventListener("elementor/frontend/init", hook);
if (window.jQuery) window.jQuery(window).on("elementor/frontend/init", hook);

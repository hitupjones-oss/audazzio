// Headless Chrome for the screenshots, the checks and the PDFs (playwright-core; no browser download:
// it uses the Chromium Playwright already has, or CHROME_PATH).
import fs from "node:fs";
import { chromium } from "playwright-core";

const CANDIDATES = [process.env.CHROME_PATH, "/opt/pw-browsers/chromium-1194/chrome-linux/chrome", "/usr/bin/chromium", "/usr/bin/google-chrome"];
export const CHROME = CANDIDATES.find((p) => p && fs.existsSync(p));
export const ORIGIN = process.env.AZ_ORIGIN || "http://localhost:3031";

export async function launch() {
  return chromium.launch({ executablePath: CHROME, args: ["--allow-file-access-from-files", "--autoplay-policy=no-user-gesture-required"] });
}

/** Scroll a page through once so everything that rises into view has risen, then go back to the top. */
export async function settle(page) {
  await page.evaluate(async () => {
    document.querySelectorAll("img[loading=lazy]").forEach((i) => { i.loading = "eager"; });
    const h = document.documentElement.scrollHeight;
    for (let y = 0; y < h; y += Math.round(innerHeight * 0.7)) { scrollTo(0, y); await new Promise((r) => setTimeout(r, 90)); }
    document.querySelectorAll("[data-az-rise]").forEach((e) => e.classList.add("is-in"));
    await Promise.all([...document.images].map((i) => (i.complete ? 1 : new Promise((r) => { i.onload = i.onerror = r; setTimeout(r, 4000); }))));
    await new Promise((r) => setTimeout(r, 1600)); // the counters finish
    scrollTo(0, 0);
    await document.fonts.ready;
  });
  await page.waitForTimeout(500);
}

// Pictures for the site, made from Audazzio's own files (media/src, not in the repository: run
// scripts/media/fetch.mjs to download them again from audazzio.com).
//   node scripts/media/build.mjs     -> wp-content/plugins/audazzio-core/assets/{img,people,logos,docs}
// Everything is resized for the web (JPG for pictures, WebP where a picture needs a clear background).
import fs from "node:fs";
import path from "node:path";
import sharp from "sharp";

const ROOT = path.join(import.meta.dirname, "..", "..");
const SRC = path.join(ROOT, "media", "src");
const OUT = path.join(ROOT, "wp-content", "plugins", "audazzio-core", "assets");
const S = (...p) => path.join(SRC, ...p);
const O = (...p) => { const f = path.join(OUT, ...p); fs.mkdirSync(path.dirname(f), { recursive: true }); return f; };
const have = (f) => fs.existsSync(f);
const done = [];
const jpg = (img, file, q = 82) => img.flatten({ background: "#ffffff" }).jpeg({ quality: q, mozjpeg: true, progressive: true }).toFile(O(file)).then(() => done.push(file));
const webp = (img, file, q = 84) => img.webp({ quality: q, alphaQuality: 90 }).toFile(O(file)).then(() => done.push(file));

/* ------------------------------------------------ who it is for (illustrations) */
// Audazzio's illustrations are 1920 x 800; the cards are 4 : 3.2, cut around the part that tells the story.
const SOL = [
  ["sol-broadcasters.jpg", "ILLUSTRATIONS_02.png", 0.62],
  ["sol-teams.jpg", "ILLUSTRATIONS_01.png", 0.5],
  ["sol-sponsors.jpg", "Sponsors_ILLUSTRATIONS_06_XiQ.png", 0.5],
  ["sol-beyond.jpg", "ILLUSTRATIONS_03.png", 0.78],
];
for (const [out, src, cx] of SOL) {
  const f = S("raw", src);
  if (!have(f)) continue;
  const m = await sharp(f).metadata();
  const h = m.height, w = Math.round(h * 4 / 3.2), left = Math.max(0, Math.min(m.width - w, Math.round(m.width * cx - w / 2)));
  await jpg(sharp(f).extract({ left, top: 0, width: w, height: h }).resize({ width: 1000 }), "img/" + out);
}

/* ------------------------------------------------------ case studies */
// Page 2 of every case study shows the broadcast moment and what the phone showed. The card picture puts
// the two side by side on the site's grey: the broadcast still at left, the phone at right.
const CASES = [
  ["case-naab.jpg", "Audazzio_-_Navy_All-American_Bowl_Case_Study_012326-2.png"],
  ["case-usas25.jpg", "Audazzio_-_USA_Swimming_Case_Study_0525-2.png"],
  ["case-usas24.jpg", "Audazzio_-_USA_Swimming_Case_Study_0724-2.png"],
  ["case-tdf.jpg", "Audazzio_-_NBC_Sports_Tour_de_France_Case_Study_0723-2.png"],
];
const box = (m, x0, y0, x1, y1) => ({ left: Math.round(m.width * x0), top: Math.round(m.height * y0), width: Math.round(m.width * (x1 - x0)), height: Math.round(m.height * (y1 - y0)) });
for (const [out, src] of CASES) {
  const f = S("cases", src);
  if (!have(f)) continue;
  const m = await sharp(f).metadata();
  const still = await sharp(f).extract(box(m, 0.121, 0.299, 0.389, 0.394)).resize({ width: 980 }).toBuffer();
  const phone = await sharp(f).extract(box(m, 0.712, 0.24, 0.857, 0.453)).resize({ height: 880 }).toBuffer();
  const sm = await sharp(still).metadata(), pm = await sharp(phone).metadata();
  const W = 1600, H = 1000;
  const radius = (w, h, r) => Buffer.from(`<svg width="${w}" height="${h}"><rect width="${w}" height="${h}" rx="${r}" ry="${r}"/></svg>`);
  const stillR = await sharp(still).composite([{ input: radius(sm.width, sm.height, 26), blend: "dest-in" }]).png().toBuffer();
  const shadow = (w, h, r) => sharp(Buffer.from(`<svg width="${w + 120}" height="${h + 120}"><defs><filter id="b" x="-20%" y="-20%" width="140%" height="140%"><feGaussianBlur stdDeviation="22"/></filter></defs><rect x="60" y="76" width="${w}" height="${h}" rx="${r}" fill="rgba(12,18,34,.22)" filter="url(#b)"/></svg>`)).png().toBuffer();
  await jpg(sharp({ create: { width: W, height: H, channels: 3, background: "#eceef2" } }).composite([
    { input: await shadow(sm.width, sm.height, 26), left: 70 - 60, top: Math.round((H - sm.height) / 2) - 60 },
    { input: stillR, left: 70, top: Math.round((H - sm.height) / 2) },
    { input: await shadow(pm.width - 40, pm.height - 40, 40), left: W - pm.width - 70 - 40, top: Math.round((H - pm.height) / 2) - 40 },
    { input: phone, left: W - pm.width - 70, top: Math.round((H - pm.height) / 2) },
  ]), "img/" + out, 84);
}

/* ------------------------------------------------- phone screens */
// Audazzio's second-screen mock-ups: whole phones for the galleries, the screen alone for the phone
// beside the Try Audazzio player (that phone draws its own frame).
const PHONES = [
  ["replays", "Broadcasters_Broadcaster---Replays.png"],
  ["voting", "Broadcasters_Broadcaster---Voting.png"],
  ["facts", "Broadcasters_Data_Fact_558x1112_052223_1_.png"],
  ["competition", "Broadcasters_Competition_558x1112_052423.png"],
  ["player", "Broadcasters_Olivia_558x1112_052423.png"],
  ["branded", "Broadcasters_Broadcaster---Branded-Content.png"],
  ["presented", "Broadcasters_Broadcaster---Presented-by.png"],
  ["offers", "Broadcasters_Broadcaster---Partner-Offers.png"],
  ["apps", "Broadcasters_Broadcaster---Partner-Apps.png"],
];
for (const [name, src] of PHONES) {
  const f = S("raw", src);
  if (!have(f)) continue;
  const m = await sharp(f).metadata();
  await webp(sharp(f).trim({ threshold: 1 }).resize({ height: 1080, withoutEnlargement: true }), `img/phone-${name}.webp`);
  await jpg(sharp(f).extract(box(m, 0.075, 0.072, 0.925, 0.962)).resize({ width: 480 }), `img/screen-${name}.jpg`, 84);
}

/* ------------------------------------------------- wide product pictures */
const WIDE = [
  ["teams-replay.jpg", "Rights_Holders_33-34_Teams_Page_943x640a.png"],
  ["teams-story.jpg", "Rights_Holders_27_28_Teams_Page_943x640a.png"],
  ["teams-sponsor.jpg", "Rights_Holders_29_30_Sponsors_Page_943x640a.png"],
  ["teams-stadium.jpg", "Rights_Holders_35_36_Teams_Page_943x640a.png"],
  ["teams-hospitality.jpg", "Rights_Holders_39_40_Teams_Page_943x640.png"],
  ["teams-25.jpg", "Teams_Page_25_26_796x468.png"],
  ["teams-31.jpg", "Teams_Page_31_32_796x468.png"],
  ["teams-retail.jpg", "Teams_Retail_37_38_796x468_R1.png"],
  ["sponsors-exposure.jpg", "Sponsors_Inc_Exp_17_18_943x640_r1-1.png"],
  ["sponsors-engagement.jpg", "Sponsors_enhanced_engagement_-_image.png"],
  ["sponsors-performance.jpg", "Sponsors_better_performance_-_image.png"],
];
for (const [out, src] of WIDE) {
  const f = S("raw", src);
  if (have(f)) await jpg(sharp(f).resize({ width: 1400, withoutEnlargement: true }), "img/" + out, 84);
}

/* ------------------------------------------------------ news pictures */
const NEWS = [
  ["news-naab.jpg", "NAAB_Logo.png", 1000], ["news-usas25.jpg", "USAS_Examples_Square-1.png", 800], ["news-usas24.jpg", "USA_Swimming_with_pool_background.jpg", 900],
  ["news-tdf.jpg", "TdF_Case_Study_Web_Image_1_.png", 800], ["news-klumb.jpg", "MK_Heaadshot_Square.png", 800], ["news-sabj.jpg", "SABJ-1.png", 800],
  ["news-sportel.jpg", "Sportel_2023.jpg", 800], ["news-elliot.jpg", "BryanElliot.jpg", 900], ["news-svg.jpg", "SVG_Audazzio_Sponsor.jpg", 1200],
  ["news-tribune.jpg", "The_Tech_Tribune_2022.png", 1000], ["news-raise.jpg", "1.4M_Raise_Image_1_.png", 800], ["news-disrupt.jpg", "Disrupt_Image.png", 800],
  ["news-sportstech.jpg", "Comcast_SportsTech_Square.png", 800],
];
for (const [out, src, w] of NEWS) {
  const f = S("raw", src);
  if (have(f)) await jpg(sharp(f).resize({ width: w, withoutEnlargement: true }), "img/" + out, 84);
}

/* --------------------------------------------------- the PDFs people download */
const DOCS = [
  ["case-study-navy-all-american-bowl-2026.pdf", "Audazzio_-_Navy_All-American_Bowl_Case_Study_012326.pdf"],
  ["case-study-usa-swimming-2025.pdf", "Audazzio_-_USA_Swimming_Case_Study_0525.pdf"],
  ["case-study-usa-swimming-2024.pdf", "Audazzio_-_USA_Swimming_Case_Study_0724.pdf"],
  ["case-study-nbc-sports-tour-de-france.pdf", "Audazzio_-_NBC_Sports_Tour_de_France_Case_Study_0723.pdf"],
  ["audazzio-privacy-policy.pdf", "Site_Assets_Audazzio-Privacy-Policy-043022.pdf"],
];
for (const [out, src] of DOCS) {
  const f = S("raw", src);
  if (have(f)) { fs.copyFileSync(f, O("docs", out)); done.push("docs/" + out); }
}

console.log(`media: ${done.length} files -> ${path.relative(ROOT, OUT)}`);

// Audazzio's press releases as PDFs for the newsroom, typeset from Audazzio's own Word files.
//   node scripts/media/press.mjs           -> wp-content/plugins/audazzio-core/assets/docs/press-*.pdf
//   node scripts/media/press.mjs --check   ... then proofs page 1 of each (media/work/press-proof/) and
//                                              compares the words of every PDF with its Word file.
// The words are the Word file's own, in its order: tracked changes read as accepted, trademark symbols
// kept, double spaces made single. Nothing else is changed. A "Photo file / Photo caption" pair is kept
// (as small print, with the photo) only when that photo is in media/src/raw; otherwise it is left out.
// Needs python3 (standard library only) to read the .docx files and the Chromium in scripts/shots.
// --check also needs poppler (pdftotext, pdftoppm). Static font cuts come once over curl (see "type").
import fs from "node:fs";
import os from "node:os";
import path from "node:path";
import { spawnSync } from "node:child_process";
import sharp from "sharp";
import { launch } from "../shots/browser.mjs";

const ROOT = path.join(import.meta.dirname, "..", "..");
const RAW = path.join(ROOT, "media", "src", "raw");
const OUT = path.join(ROOT, "wp-content", "plugins", "audazzio-core", "assets", "docs");
const PROOF = path.join(ROOT, "media", "work", "press-proof");
const FONTS = path.join(ROOT, "wp-content", "plugins", "audazzio-core", "assets", "fonts");
const NM = path.join(ROOT, "node_modules");

const RELEASES = [
  ["press-2025-01-21-klumb-coo.pdf", "Audazzio_Michele_Klumb_COO.docx"],
  ["press-2024-07-15-usa-swimming.pdf", "Audazzio_USASwimming_1_.docx"],
  ["press-2023-11-16-sportel.pdf", "Audazzio_SportelPitchPerfect.docx"],
  ["press-2023-02-06-elliot.pdf", "Audazzio_BryanElliot_1_.docx"],
  ["press-2023-01-19-svg-summit.pdf", "Audazzio_SVG22_1_.docx"],
  ["press-2022-11-04-tech-tribune.pdf", "Audazzio_SanAntonio_Top10.docx"],
  ["press-2022-04-25-raise.pdf", "Audazzio_funding_042522_TM_Update.docx"],
  ["press-2022-04-05-disrupt.pdf", "Audazzio_StartupPR_040522_TM_Update.docx"],
];

/* ------------------------------------------------------------ reading the Word files */
// Paragraphs in document order, each a list of runs {t, b, i, sup, href}. Insertions are read, deletions
// skipped (Word's "accept all"), Symbol-font marks become their characters, the letterhead picture is
// dropped (the PDF draws its own).
const READ_DOCX = String.raw`
import sys, json, zipfile
from xml.etree import ElementTree as ET
W = '{http://schemas.openxmlformats.org/wordprocessingml/2006/main}'
R = '{http://schemas.openxmlformats.org/officeDocument/2006/relationships}'
SYM = {'F0D2': '\u00ae', 'F0E2': '\u00ae', 'F0D3': '\u00a9', 'F0E3': '\u00a9', 'F0D4': '\u2122', 'F0E4': '\u2122'}
SKIP = {W + 'del', W + 'moveFrom', W + 'pPr', W + 'rPr', W + 'commentRangeStart', W + 'commentRangeEnd'}
DIVE = {W + 'ins', W + 'moveTo', W + 'smartTag', W + 'customXml', W + 'sdt', W + 'sdtContent', W + 'fldSimple'}
def on(rpr, tag):
    e = rpr.find(W + tag) if rpr is not None else None
    return e is not None and e.get(W + 'val') not in ('0', 'false', 'none')
def read(path):
    z = zipfile.ZipFile(path)
    rels = {r.get('Id'): r.get('Target') for r in ET.fromstring(z.read('word/_rels/document.xml.rels'))}
    body = ET.fromstring(z.read('word/document.xml')).find(W + 'body')
    paras = []
    def para(p):
        ppr = p.find(W + 'pPr')
        jc = ppr.find(W + 'jc') if ppr is not None else None
        runs = []
        def walk(node, href):
            for c in node:
                if c.tag in SKIP: continue
                if c.tag == W + 'hyperlink':
                    walk(c, rels.get(c.get(R + 'id')) or href); continue
                if c.tag in DIVE:
                    walk(c, href); continue
                if c.tag != W + 'r': continue
                rpr = c.find(W + 'rPr')
                va = rpr.find(W + 'vertAlign') if rpr is not None else None
                t = ''
                for k in c:
                    if k.tag == W + 't': t += k.text or ''
                    elif k.tag == W + 'tab': t += '\t'
                    elif k.tag in (W + 'br', W + 'cr'): t += '\n'
                    elif k.tag == W + 'noBreakHyphen': t += '\u2011'
                    elif k.tag == W + 'sym': t += SYM.get((k.get(W + 'char') or '').upper(), '')
                if t:
                    runs.append({'t': t, 'b': on(rpr, 'b'), 'i': on(rpr, 'i'), 'sup': va is not None and va.get(W + 'val') == 'superscript', 'href': href})
        walk(p, None)
        paras.append({'align': jc.get(W + 'val') if jc is not None else '', 'runs': runs})
    for el in body:
        if el.tag == W + 'p': para(el)
        elif el.tag == W + 'tbl':
            for p in el.iter(W + 'p'): para(p)
    return paras
print(json.dumps({f: read(f) for f in sys.argv[1:]}))
`;

function readDocx(files) {
  const r = spawnSync("python3", ["-I", "-c", READ_DOCX, ...files], { encoding: "utf8", maxBuffer: 64 << 20 });
  if (r.status !== 0) throw new Error("Could not read the Word files:\n" + r.stderr);
  return JSON.parse(r.stdout);
}

/** Tabs to spaces, runs of spaces to one, no space at either end; neighbouring runs that look alike merged. */
function tidy(runs) {
  const out = [];
  let prevSpace = true; // trims the start
  for (const r of runs) {
    let t = "";
    for (const ch of r.t.replace(/[\t\n]/g, " ")) {
      if (ch === " " && prevSpace) continue;
      t += ch;
      prevSpace = ch === " ";
    }
    if (!t) continue;
    const last = out[out.length - 1];
    if (last && last.b === r.b && last.i === r.i && last.sup === r.sup && last.href === r.href) last.t += t;
    else out.push({ ...r, t });
  }
  while (out.length && / $/.test(out[out.length - 1].t)) {
    const last = out[out.length - 1];
    last.t = last.t.replace(/ +$/, "");
    if (!last.t) out.pop();
  }
  return out;
}
const text = (runs) => runs.map((r) => r.t).join("");

/* ------------------------------------------------------------ the parts of a release */
const PHOTO = /^Photo (file|caption)\s*(\d*)\s*:\s*/i;

function parse(paras) {
  // Blank paragraphs become breaks between groups, as they space the Word file.
  const items = paras.map((p) => ({ align: p.align, runs: tidy(p.runs) })).map((p) => ({ ...p, text: text(p.runs) }));
  const at = items.findIndex((p) => /^FOR IMMEDIATE RELEASE$/i.test(p.text));
  if (at < 0) throw new Error("No FOR IMMEDIATE RELEASE line");
  const rest = items.slice(at + 1);
  const next = () => { while (rest.length && !rest[0].text) rest.shift(); return rest.shift(); };
  const doc = { label: items[at], headline: next(), deks: [], body: [], photos: [], contact: null, about: null };
  while (rest.length) {
    while (rest.length && !rest[0].text) rest.shift();
    if (rest[0] && rest[0].align === "center") doc.deks.push(rest.shift());
    else break;
  }
  let group = [];
  const flush = () => { if (group.length) doc.body.push(group); group = []; };
  while (rest.length && !/^FOR MORE INFORMATION PLEASE CONTACT:?$/.test(rest[0].text)) {
    const p = rest.shift();
    if (!p.text) { flush(); continue; }
    const m = p.text.match(PHOTO);
    if (m) {
      const n = m[2] || "1";
      let ph = doc.photos.find((x) => x.n === n);
      if (!ph) doc.photos.push((ph = { n, at: doc.body.length + (group.length ? 1 : 0) }));
      ph[m[1].toLowerCase()] = p;
      continue;
    }
    group.push(p);
  }
  flush();
  if (rest.length) {
    const label = rest.shift();
    const lines = [];
    while (rest.length && !/^About Audazzio/i.test(rest[0].text)) { const p = rest.shift(); if (p.text) lines.push(p); }
    doc.contact = { label, lines };
  }
  if (rest.length) {
    const head = rest.shift();
    doc.about = { head, paras: rest.filter((p) => p.text) };
  }
  return doc;
}

/** The photo a "Photo file" line names, when Audazzio's media has it (same file name, any case). */
function findPhoto(line) {
  const name = line.text.replace(PHOTO, "").trim().toLowerCase();
  if (!name || !fs.existsSync(RAW)) return null;
  const hit = fs.readdirSync(RAW).find((f) => f.toLowerCase() === name);
  return hit ? path.join(RAW, hit) : null;
}

/* ------------------------------------------------------------ HTML */
const esc = (s) => s.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
const marks = (s) => esc(s).replace(/®/g, '<span class="reg">®</span>').replace(/™/g, '<span class="tm">™</span>');

/**
 * Runs to inline HTML. `base` is the paragraph's own style ({i} for a dek set in italics in Word, {b} for a
 * bold headline): a run that differs from it is the emphasis. `dateline` is how many characters at the
 * start are the dateline. `pipes` sets the | separators of a contact line apart.
 */
function inline(runs, { base = {}, dateline = 0, pipes = false } = {}) {
  const piece = (r, t, dl) => {
    let h = r.sup && !/^[®™]+$/.test(t) ? `<sup>${esc(t)}</sup>` : marks(t);
    if (dl) return `<span class="dl">${h}</span>`;
    if (r.i !== !!base.i) h = `<em>${h}</em>`;
    if (r.b && !base.b) h = `<strong>${h}</strong>`;
    return h;
  };
  let html = "", pos = 0, link = null, inLink = "";
  const close = () => { if (link !== null) html += link ? `<a href="${esc(link)}">${inLink}</a>` : inLink; inLink = ""; };
  for (const r of runs) {
    let h = "";
    if (pos < dateline) {
      const cut = Math.min(r.t.length, dateline - pos);
      h += piece(r, r.t.slice(0, cut), true);
      if (cut < r.t.length) h += piece(r, r.t.slice(cut), false);
    } else h = piece(r, r.t, false);
    pos += r.t.length;
    const href = r.href || "";
    if (href !== link) { close(); link = href; }
    inLink += h;
  }
  close();
  // a contact line breaks only between its parts
  if (pipes) { const segs = html.split(" | "); html = segs.map((s, i) => `<span class="seg">${s}${i < segs.length - 1 ? ' <span class="pipe">|</span>' : ""}</span>`).join(" "); }
  return html;
}
const share = (runs, key) => { const n = runs.reduce((a, r) => a + r.t.length, 0); return runs.reduce((a, r) => a + (r[key] ? r.t.length : 0), 0) > n / 2; };

/* ------------------------------------------------------------ type */
// Chromium embeds a variable font as Type 3 outlines, so the PDFs use the static cuts of Inter and Inter
// Tight (the 5.3.0 release, as the variable fonts in the plugin), fetched once from the @fontsource
// packages into the system's temp folder. Without the network the plugin's variable fonts are used.
const FONT_CACHE = path.join(os.tmpdir(), "audazzio-fonts-5.3.0");
function staticCut(pkg, file) {
  const f = path.join(FONT_CACHE, file);
  if (fs.existsSync(f)) return f;
  fs.mkdirSync(FONT_CACHE, { recursive: true });
  const r = spawnSync("curl", ["-fsSL", "--max-time", "30", "-o", f + ".part", `https://cdn.jsdelivr.net/npm/@fontsource/${pkg}@5.3.0/files/${file}`]);
  if (r.status !== 0 || !fs.existsSync(f + ".part")) return null;
  fs.renameSync(f + ".part", f);
  return f;
}
const face = (family, file, weight, style = "normal") =>
  `@font-face{font-family:"${family}";src:url(data:font/woff2;base64,${fs.readFileSync(file).toString("base64")}) format("woff2");font-weight:${weight};font-style:${style};font-display:block}`;
function typeCss() {
  const css = [face("Geist Mono", path.join(FONTS, "geist-mono-latin-500-normal.woff2"), 500)];
  const cuts = [["Inter", "inter", 400, "normal"], ["Inter", "inter", 400, "italic"], ["Inter", "inter", 600, "normal"], ["Inter", "inter", 600, "italic"], ["Inter Tight", "inter-tight", 600, "normal"], ["Inter Tight", "inter-tight", 600, "italic"]];
  const files = cuts.map(([, pkg, w, s]) => staticCut(pkg, `${pkg}-latin-${w}-${s}.woff2`));
  if (files.every(Boolean)) return { css: css.concat(cuts.map(([fam, , w, s], i) => face(fam, files[i], w, s))).join("\n"), cuts: "static" };
  const v = (pkg, s) => path.join(NM, `@fontsource-variable/${pkg}/files/${pkg}-latin-wght-${s}.woff2`);
  css.push(face("Inter", path.join(FONTS, "inter-latin-wght-normal.woff2"), "100 900"), face("Inter", v("inter", "italic"), "100 900", "italic"));
  css.push(face("Inter Tight", path.join(FONTS, "inter-tight-latin-wght-normal.woff2"), "100 900"), face("Inter Tight", v("inter-tight", "italic"), "100 900", "italic"));
  return { css: css.join("\n"), cuts: "variable (offline)" };
}
const TYPE = typeCss();
const svgOf = (name, color) => fs.readFileSync(path.join(ROOT, "brand", name), "utf8").replace(/<\?xml[^>]*>/, "").replace(/currentColor/g, color);
const dataSvg = (svg) => `url("data:image/svg+xml;utf8,${encodeURIComponent(svg)}")`;

/** The foot of every page: fine dashed lines, calm at the left, a signal burst toward the right. */
function waveSvg() {
  const W = 1000, H = 40, mid = H / 2;
  const line = (amp, len, phase, at, from = 0, to = W) => {
    let d = "";
    for (let x = from; x <= to; x += 2) {
      const env = 0.1 + 0.9 * Math.exp(-(((x - W * at) / (W * 0.2)) ** 2));
      d += (x === from ? "M" : "L") + x + " " + (mid + amp * env * Math.sin((x / len) * Math.PI * 2 + phase)).toFixed(2);
    }
    return d;
  };
  const path = (d, stroke, dash, extra = "") => `<path d="${d}" fill="none" stroke="${stroke}" stroke-width=".7" stroke-dasharray="${dash}" stroke-linecap="round" vector-effect="non-scaling-stroke"${extra}/>`;
  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${W} ${H}" preserveAspectRatio="none">` +
    path(line(8, 37, 1.9, 0.58), "#9A9EAA", "1.2 4", ' stroke-opacity=".6"') +
    path(line(14, 58, 0, 0.64), "#9A9EAA", "3 3.5") +
    path(line(6, 58, 0.5, 0.66, 480, 820), "#F89C68", "2 4.5") +
    `</svg>`;
}

async function photoData(file) {
  const buf = await sharp(file).rotate().resize({ width: 1500, withoutEnlargement: true }).flatten({ background: "#ffffff" }).jpeg({ quality: 84, mozjpeg: true }).toBuffer();
  const m = await sharp(buf).metadata();
  return { src: `data:image/jpeg;base64,${buf.toString("base64")}`, w: m.width, h: m.height };
}

async function render(doc) {
  const symbol = svgOf("audazzio-symbol.svg", "#F89C68");
  const logotype = svgOf("audazzio-logotype.svg", "#0C1222");
  const inner = (svg) => svg.trim().replace(/^<svg[^>]*>/, "").replace(/<\/svg>$/, "");
  // the letterhead again, small, at the top of every page after the first (25 : 9.6 like the letterhead)
  const runningHead = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 2852 600">${inner(symbol)}<g transform="translate(768 185) scale(1.322)">${inner(logotype)}</g></svg>`;

  // the body, with the photos that came with the release where the Word file lists them
  const blocks = doc.body.map((g, gi) => {
    const ps = g.map((p, pi) => {
      const dl = gi === 0 && pi === 0 ? (p.text.match(/^.{0,100}?\([A-Z][a-z]+\.? \d{1,2}, \d{4}\):/) || [""])[0].length : 0;
      return `<p>${inline(p.runs, { dateline: dl })}</p>`;
    });
    return `<div class="grp">${ps.join("")}</div>`;
  });
  const kept = [], skipped = [];
  for (const ph of doc.photos) {
    const file = ph.file && findPhoto(ph.file);
    if (!file) { skipped.push(ph); continue; }
    const img = await photoData(file);
    kept.push(ph);
    blocks.splice(ph.at + kept.length - 1, 0, `<figure class="row photo"><div class="rail"><p class="pf">${inline(ph.file.runs)}</p></div>` +
      `<div class="main"><img src="${img.src}" width="${img.w}" height="${img.h}" alt="">${ph.caption ? `<figcaption class="pc">${inline(ph.caption.runs)}</figcaption>` : ""}</div></figure>`);
  }

  const dekBase = (p) => ({ i: share(p.runs, "i"), b: share(p.runs, "b") });
  const headBase = { b: share(doc.headline.runs, "b"), i: share(doc.headline.runs, "i") };
  const contact = doc.contact ? `<section class="row contact"><div class="rail"><p class="lbl">${inline(doc.contact.label.runs)}</p></div><div class="main">${doc.contact.lines.map((p) => `<p>${inline(p.runs, { pipes: true })}</p>`).join("")}</div></section>` : "";
  const about = doc.about ? `<section class="row about"><div class="rail"><h2>${inline(doc.about.head.runs, { base: { b: share(doc.about.head.runs, "b") } })}</h2></div><div class="main">${doc.about.paras.map((p) => `<p>${inline(p.runs)}</p>`).join("")}</div></section>` : "";

  const html = `<!doctype html><html lang="en-US"><head><meta charset="utf-8"><title>${esc(doc.headline.text)}</title><style>
${TYPE.css}
:root{--ink:#0C1222;--g1:#3B4152;--g2:#6B7080;--g3:#9A9EAA;--hair:#E3E4E8;--rail:1.2in;--gap:.28in}
@page{size:letter;margin:.62in .8in .86in .8in;
  @top-left{content:"";width:100%;background:${dataSvg(runningHead)} no-repeat left 0 bottom .26in/auto 12pt}
  @bottom-center{content:counter(page) " / " counter(pages);width:100%;text-align:right;vertical-align:middle;font:500 6.5pt/1 "Geist Mono",monospace;letter-spacing:.08em;color:var(--g2);
    background:${dataSvg(waveSvg())} no-repeat left center/calc(100% - .55in) 16pt}}
@page:first{@top-left{content:none}}
*{box-sizing:border-box}
html{-webkit-print-color-adjust:exact;print-color-adjust:exact}
body{margin:0;font:400 10.5pt/1.55 Inter,sans-serif;color:var(--ink);font-feature-settings:"kern","liga","calt";font-optical-sizing:auto;text-rendering:geometricPrecision}
p{margin:0;orphans:2;widows:2}
a{color:inherit;text-decoration:underline;text-decoration-thickness:.5pt;text-underline-offset:2pt;text-decoration-color:var(--g3)}
em{font-style:italic}
strong{font-weight:600}
sup{font-size:.62em;line-height:0;vertical-align:.62em;letter-spacing:.02em}
.reg{font-size:.6em;line-height:0;vertical-align:.52em;margin-left:.04em}
.tm{font-size:.82em;line-height:0;vertical-align:.08em;margin-left:.02em}
.row{display:grid;grid-template-columns:var(--rail) minmax(0,1fr);column-gap:var(--gap)}
.main,.body{min-width:0}
.grp{margin-left:calc(var(--rail) + var(--gap))}
.lbl,.pf{font:500 6.4pt/1.6 "Geist Mono",monospace;letter-spacing:.06em;text-transform:uppercase}
.lh{display:flex;align-items:center;gap:7.5pt;height:26pt;margin:0 0 .46in}
.lh .sym{height:25pt;width:auto;display:block}
.lh .type{height:9.6pt;width:auto;display:block}
.top .lbl{padding-top:4.4pt;color:var(--ink)}
h1{font:600 23pt/1.13 "Inter Tight",Inter,sans-serif;letter-spacing:-.018em;margin:0;text-wrap:balance}
h1 .reg{vertical-align:.62em;font-size:.5em}
.dek{font:400 12pt/1.46 Inter,sans-serif;color:var(--g1);margin:10pt 0 0;letter-spacing:-.003em;text-wrap:pretty}
.dek+.dek{margin-top:7pt}
.rule{border:0;border-top:.5pt solid var(--hair);margin:20pt 0 17pt}
.grp+.grp,.grp+.photo,.photo+.grp{margin-top:7.5pt}
.dl{font-weight:600}
.photo{margin:16pt 0 4pt;break-inside:avoid;align-items:start}
.photo img{display:block;width:3.6in;height:auto;border-radius:3pt}
.pf{color:var(--g2);font-size:6.2pt;text-transform:none;letter-spacing:0;padding-top:1pt}
.pc{width:3.6in;margin-top:7pt;font-size:8pt;line-height:1.45;color:var(--g1);text-wrap:pretty}
.contact{border-top:.5pt solid var(--hair);margin-top:20pt;padding-top:12pt;break-inside:avoid}
.contact .lbl{color:var(--ink);padding-top:2.6pt}
.contact .main p{font-size:9.5pt;line-height:1.5;font-feature-settings:"kern","tnum"}
.contact .main p+p{margin-top:4pt}
.seg{white-space:nowrap}
.pipe{color:var(--g3)}
.about{margin-top:12pt;break-inside:avoid}
.about h2{font:600 9.5pt/1.4 "Inter Tight",Inter,sans-serif;letter-spacing:-.005em;margin:1.4pt 0 0}
.about .main p{font-size:8.8pt;line-height:1.5;color:var(--g1)}
</style></head><body>
<header class="lh">${symbol.replace("<svg ", '<svg class="sym" ')}${logotype.replace("<svg ", '<svg class="type" ')}</header>
<section class="row top"><div class="rail"><p class="lbl">${inline(doc.label.runs)}</p></div><div class="main">
<h1>${inline(doc.headline.runs, { base: headBase })}</h1>
${doc.deks.map((p) => `<p class="dek">${inline(p.runs, { base: dekBase(p) })}</p>`).join("\n")}
</div></section>
<hr class="rule">
<div class="body">${blocks.join("\n")}</div>
${contact}
${about}
</body></html>`;
  return { html, kept, skipped };
}

/* ------------------------------------------------------------ checking */
// Words of a text, with the ® and ™ that sit raised kept on the word they follow.
const LIG = { "\ufb00": "ff", "\ufb01": "fi", "\ufb02": "fl", "\ufb03": "ffi", "\ufb04": "ffl" };
const words = (s) => s.replace(/\s*([\u2014])\s*/g, " $1 ").replace(/[\ufb00-\ufb04]/g, (c) => LIG[c]).replace(/\u00ad/g, "").replace(/[\u00a0\u2009\u202f]/g, " ").replace(/\s+([®™])/g, "$1").replace(/([®™])\s+(?=[,.;:’'])/g, "$1").split(/\s+/).filter(Boolean);

/** Words in a but not in b, and in b but not in a, in order (longest common subsequence). */
function diff(a, b) {
  const n = a.length, m = b.length, L = Array.from({ length: n + 1 }, () => new Uint16Array(m + 1));
  for (let i = n - 1; i >= 0; i--) for (let j = m - 1; j >= 0; j--) L[i][j] = a[i] === b[j] ? L[i + 1][j + 1] + 1 : Math.max(L[i + 1][j], L[i][j + 1]);
  const gone = [], added = [];
  let i = 0, j = 0;
  while (i < n && j < m) {
    if (a[i] === b[j]) { i++; j++; } else if (L[i + 1][j] >= L[i][j + 1]) gone.push(a[i++]); else added.push(b[j++]);
  }
  while (i < n) gone.push(a[i++]);
  while (j < m) added.push(b[j++]);
  return { gone, added };
}

/* ------------------------------------------------------------ run */
const check = process.argv.includes("--check");
const todo = RELEASES.filter(([, src]) => fs.existsSync(path.join(RAW, src)));
for (const [, src] of RELEASES) if (!todo.find((t) => t[1] === src)) console.warn(`skip: media/src/raw/${src} is missing`);
if (!todo.length) process.exit(0);
const sources = readDocx(todo.map(([, src]) => path.join(RAW, src)));
fs.mkdirSync(OUT, { recursive: true });

const browser = await launch();
const report = [];
try {
  const page = await browser.newPage();
  for (const [out, src] of todo) {
    const paras = sources[path.join(RAW, src)];
    const doc = parse(paras);
    const { html, kept, skipped } = await render(doc);
    await page.setContent(html, { waitUntil: "load" });
    await page.evaluate(async () => { await document.fonts.ready; });
    const file = path.join(OUT, out);
    await page.pdf({ path: file, preferCSSPageSize: true, printBackground: true, tagged: true, outline: false });
    const note = skipped.length ? ` (left out ${skipped.map((p) => "photo " + p.n).join(", ")}: not in media/src/raw)` : "";
    console.log(`${out}  <- ${src}  [${TYPE.cuts}]${kept.length ? `  with ${kept.map((p) => "photo " + p.n).join(", ")}` : ""}${note}`);
    report.push({ out, src, paras, skipped });
  }
} finally {
  await browser.close();
}

if (check) {
  fs.mkdirSync(PROOF, { recursive: true });
  console.log("\nwords: Word file -> PDF (pdftotext)");
  for (const { out, src, paras, skipped } of report) {
    const file = path.join(OUT, out);
    spawnSync("pdftoppm", ["-r", "60", "-f", "1", "-l", "1", "-png", "-singlefile", file, path.join(PROOF, out.replace(/\.pdf$/, ""))]);
    // -raw reads the text in the order it is drawn; a line that ends in a hyphen is joined to the next
    // (the PDF never hyphenates on its own, so such a hyphen is the Word file's own); the page numbers go.
    const pdf = spawnSync("pdftotext", ["-raw", "-enc", "UTF-8", file, "-"], { encoding: "utf8" }).stdout
      .split("\n").filter((l) => !/^\s*\d+ \/ \d+\s*$/.test(l)).join("\n").replace(/-\n/g, "-");
    const all = paras.map((p) => text(tidy(p.runs))).filter(Boolean);
    const left = new Set(skipped.flatMap((p) => [p.file, p.caption]).filter(Boolean).map((p) => p.text));
    const want = all.filter((t) => !left.has(t));
    const raw = diff(words(all.join("\n")), words(pdf));
    const net = diff(words(want.join("\n")), words(pdf));
    const pages = spawnSync("pdfinfo", [file], { encoding: "utf8" }).stdout.match(/Pages:\s+(\d+)/)?.[1];
    console.log(`${out.padEnd(36)} ${String(pages).padStart(2)} pp  words ${String(words(all.join(" ")).length).padStart(4)}  ` +
      `vs Word: -${raw.gone.length} +${raw.added.length}  vs Word less photo lines left out: -${net.gone.length} +${net.added.length}` +
      (net.gone.length || net.added.length ? `\n    missing: ${net.gone.join(" ")}\n    extra:   ${net.added.join(" ")}` : ""));
  }
}

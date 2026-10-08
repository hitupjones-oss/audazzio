// The site guide: how Audazzio's team runs the new site day to day, in plain words.
//   node docs/build.mjs   -> docs/guide.html, docs/Audazzio-Site-Guide-RSPKT.pdf, proofs in scripts/shots/out/guide-pdf-NN.jpg
// Pictures of the site and its back office come from scripts/shots/deck.mjs (run it first).
import fs from "node:fs";
import path from "node:path";
import { BASE_CSS, LIVE, esc, hl, joule, marks, print, shot, lockup, wave } from "../scripts/docs/lib.mjs";

const W = 1240, H = 1754;
const M = await marks();
const pic = (name, cls = "") => `<div class="shot ${cls}"><img src="${shot("d-" + name)}" alt=""></div>`;
const steps = (items) => `<ol class="st">${items.map((t) => `<li>${t}</li>`).join("")}</ol>`;
const list = (items) => `<ul class="bl">${items.map((t) => `<li>${t}</li>`).join("")}</ul>`;
const rows = (r) => `<table class="tb">${r.map(([a, b]) => `<tr><th>${a}</th><td>${b}</td></tr>`).join("")}</table>`;
let n = 1;
const TOTAL = 10;
const page = (eb, title, body) => `<section class="pg"><header><p class="eb"><b>${String(++n).padStart(2, "0")}</b><span>${esc(eb)}</span><i></i></p><h2 class="d">${hl(title)}</h2></header><div class="body">${body}</div><footer>${lockup(18)}<span>Site guide</span><span>${String(n).padStart(2, "0")} / ${TOTAL}</span></footer></section>`;

const P = [];
P.push(`<section class="pg cover"><div class="cover__in"><p class="eb"><span>Site guide · October 2026</span><i></i></p>${lockup(40)}<h1 class="d">${hl("How to run\n*the new site.*")}</h1><p class="lead">For the Audazzio team: where things are, what looks after itself, and what to do when something needs changing.</p></div>${pic("home", "cover__pic")}<p class="credit"><span>Designed by</span><img src="${M.rspkt}" alt="RSPKT">${joule(22)}</p></section>`);

P.push(page("The site", "Six pages,\n*one minute.*", `<p class="lead">The preview is at <u>${LIVE}</u>. It is a copy of the front of the site for looking at; the real site runs on WordPress, where the inbox and the settings are.</p>${rows([
  ["Home", "It comes in waves, three steps, Try Audazzio now, why second screens, films, who it is for, case studies, press, Join the Wave."],
  ["Live QR", "How the technology works: the seven steps for broadcast and live events, what phones can show, numbers, questions, the explainer, how we work."],
  ["Solutions", "Broadcasters, teams and leagues, sponsors and brands, and everything beyond sport, with jump links."],
  ["Newsroom", "Case studies, then every press release, award and article, with filters."],
  ["About", "The SportsTech story, leadership, the board, quotes, how we work."],
  ["Join the Wave", "The four-step form. Also opens from every Join the Wave button."],
  ["Try Audazzio", "The demo on its own page: audazzio.com/try, for cards and emails."],
  ["Privacy", "The privacy policy, with the PDF."],
])}<p class="note">Old addresses still work: /liveqr, /broadcasters, /teams-leagues, /sponsors-brands, /applications, /resources-audazzio, /about-0-0, /contact, /demo and /explainer go to their new pages.</p>`));

P.push(page("Signing in", "Start at the\n*Audazzio menu.*", `${pic("inbox", "wide")}${steps([
  "Go to the site’s address followed by <b>/wp-admin</b> and sign in.",
  "In the left menu, <b>Audazzio</b> has two parts: <b>Inbox</b> (every Join the Wave inquiry) and <b>Settings</b>.",
  "<b>Pages</b> lists the eight pages. Hover one and choose <b>Edit with Elementor</b> to change it.",
  "<b>Media</b> holds every picture, film and PDF you upload.",
])}`));

P.push(page("Inquiries", "Every lead,\n*already graded.*", `${pic("lead", "wide")}${rows([
  ["A · Priority", "Strong fit, real scale, a near date. Call within one business day. Grade A inquiries can also go to a second address (Settings)."],
  ["B · Qualified", "Good fit. Book a discovery call."],
  ["C · Nurture", "Early or small. Send the case studies and stay in touch."],
  ["D · Early", "Exploring. Answer the question and add to updates."],
])}${list([
  "The grade comes from the answers: organization, what they want to do, audience, events a year, budget, timing, their part in the decision, plus a work email and a detailed description. Visitors never see it.",
  "Each inquiry is also named: <b>Broadcast partnership · Enterprise scale</b>, <b>Sponsor campaign</b>, <b>Live event activation · Single event</b>.",
  "Open one to read every answer and its points, set a status (new, contacted, meeting booked, proposal sent, won, closed) and keep notes. <b>Export CSV</b> downloads the list.",
])}`));

P.push(page("Settings", "Change these\n*without us.*", `${pic("settings", "wide")}${rows([
  ["Join the Wave", "Where inquiries are emailed, a second address for grade A, the HubSpot portal and form IDs (optional: every inquiry is also sent there), the thank-you line."],
  ["Try Audazzio", "The phone listener link, App Store and Google Play links if an app is published, the demo clips (YouTube or MP4) and their names, the poster, and the notification on the home page."],
  ["Company", "LinkedIn, the privacy policy link, and the email, phone and location shown on the Join the Wave page."],
])}<p class="note">An empty box uses the value shown in grey. For email to arrive reliably, the host needs an email delivery plugin (for example WP Mail SMTP with your Microsoft 365 account). We set it up at launch.</p>`));

P.push(page("Editing a page", "Every section\n*is a widget.*", `${pic("editor", "wide")}${steps([
  "Open a page with <b>Edit with Elementor</b>.",
  "Click any section. Its fields open on the left: the small line, the headline, the text, buttons, pictures, and lists such as case studies, logos or people.",
  "In a headline, a new line starts a new line on the page, and <b>*asterisks*</b> set words in the lighter grey: <b>It’s like a QR code. *No one has to scan it.*</b>",
  "Add a section from the <b>Audazzio</b> group in the widget list: 21 kinds, from the hero to the form. Drag to reorder.",
  "Press <b>Publish</b>. The change is live at once.",
])}`));

P.push(page("Keeping it fresh", "News, logos,\n*people and films.*", `${rows([
  ["A press release", "Newsroom page, <b>News and press</b> widget: add a row with the date, the kind, the headline, one line and the link or PDF. The home page shows the latest four from its own copy of the list."],
  ["A case study", "<b>Case studies</b> widget: partner, headline, year, where it played, a picture, up to two results (number | what it counts) and the PDF."],
  ["A logo", "<b>Logo carousel</b> widget: the company name and the logo. Transparent PNG or SVG; the site turns it grey and sizes it to match the others."],
  ["A person", "<b>People</b> widget (Leadership or Board): name, title, portrait (4:5, about 1200 × 1500, person centred), one-line summary, full biography, highlights, LinkedIn."],
  ["A new film", "<b>Films</b> widget: a YouTube link or an MP4 from Media, a title, a label and a poster. Today’s films are placeholders for the new ones."],
])}`));

P.push(page("Try Audazzio", "Speakers on.\n*Phone out.*", `${pic("try-playing", "wide")}${list([
  "There is no Audazzio app in the stores today, so phones listen in the browser through Audazzio’s web listener (the page today’s /demo sends people to). In a live deployment the same listening runs inside a partner’s app, like the USA Swimming app. The listener link is in Settings; the QR code and the button follow it.",
  "If an Audazzio app is published, paste its App Store and Google Play links in Settings and the buttons appear beside the listener.",
  "The demo clips must carry the signal in their soundtrack. YouTube re-encodes sound and can strip high frequencies: an MP4 uploaded to Media is the safer home for the real demo.",
  "Test after any change: a phone with the listener open, a laptop playing the demo, volume up. The phone should ding and show new content.",
])}`));

P.push(page("Launch", "Five to seven\n*business days.*", `<p class="lead">Counted from the day we receive your hosting and domain credentials.</p>${rows([
  ["Day 1", "Access to hosting and the domain’s DNS (Route 53), and a full backup of today’s site."],
  ["Days 2 and 3", "WordPress, Elementor, the theme and the plugin on your host; content, email delivery and HubSpot."],
  ["Day 4", "Every page on phones and desktops, the form end to end, redirects, speed."],
  ["Day 5", "The DNS switch. Email (Microsoft 365) records are left untouched, and audazz.io keeps serving the listener."],
  ["Days 6 and 7", "Checks after launch, fixes, and the walkthrough with your team."],
])}<p class="note"><b>Before launch, from you:</b> the new films and the demo files; higher-resolution portraits for Roy, Michele, Greg and Larry; HubSpot access if leads should go there; your counsel’s yes before Live QR™ becomes ® (its registration is on the Supplemental Register); and a line in the privacy policy about microphone use. Press logos come from public sources: confirm you may show each one.</p>`));

P.push(`<section class="pg end"><div class="end__in"><p class="eb"><span>Help</span><i></i></p><h2 class="d">${hl("Stuck?\n*Ask us.*")}</h2><p class="lead">Anything in this guide, anything not in it, and anything that looks wrong on the site: write to RSPKT at <u>rspkt.co</u> and say which page you were on.</p><p class="credit"><span>Designed by</span><img src="${M.rspkt}" alt="RSPKT">${joule(26)}</p></div><div class="end__wave">${wave(1240, 160, { lines: 7, amp: 30 })}</div></section>`);

const css = `${BASE_CSS}
@page{size:${W}px ${H}px;margin:0}
.pg{width:${W}px;height:${H}px;position:relative;overflow:hidden;page-break-after:always;padding:90px 96px 0;display:flex;flex-direction:column;background:#fff}
.eb{font-size:14px;margin-bottom:22px}
h2.d{font-size:76px}
.body{flex:1;min-height:0;padding:40px 0 110px;display:flex;flex-direction:column;gap:28px}
footer{position:absolute;left:96px;right:96px;bottom:44px;display:flex;align-items:center;gap:22px;font-family:"Geist Mono",monospace;font-size:12px;font-weight:500;letter-spacing:.14em;text-transform:uppercase;color:var(--ink3);border-top:1px solid var(--line);padding-top:16px}
footer span:last-child{margin-left:auto}
.shot.wide{aspect-ratio:16/9.6;flex:none}
.lead{font-size:28px;line-height:1.4;color:var(--ink2)}
.lead u,.end u{text-decoration:none;color:var(--ink);font-weight:600;border-bottom:2px solid var(--wave)}
.bl,.st{padding:0;display:grid;gap:18px;font-size:23px;line-height:1.45;color:var(--ink2);list-style:none}
.bl li{padding-left:28px;position:relative}
.bl li::before{content:"";position:absolute;left:0;top:.68em;width:12px;height:1.5px;background:var(--ink)}
.st{counter-reset:s}
.st li{counter-increment:s;padding-left:54px;position:relative}
.st li::before{content:counter(s,decimal-leading-zero);position:absolute;left:0;top:.2em;color:var(--ink3);font-family:"Geist Mono",monospace;font-weight:500;font-size:.75em;letter-spacing:.08em}
.tb{border-collapse:collapse;width:100%}
.tb th{font-family:"Inter Tight";font-size:22px;font-weight:600;letter-spacing:-.01em;text-align:left;vertical-align:top;padding:18px 28px 18px 0;white-space:nowrap;color:var(--ink);border-top:1px solid var(--line);width:1%}
.tb td{font-size:22px;line-height:1.45;padding:18px 0;color:var(--ink2);border-top:1px solid var(--line)}
.note{font-size:20px;line-height:1.5;color:var(--ink2);border-radius:16px;padding:22px 26px;background:var(--mist)}
.cover{padding:0}
.cover__in{padding:110px 96px 56px;display:grid;gap:30px}
.cover h1{font-size:120px;letter-spacing:-.05em;line-height:.92}
.cover .lead{max-width:24em}
.cover__pic{flex:1;border-radius:0;border:0;border-top:1px solid var(--line);min-height:0}
.cover__pic img{object-position:50% 0}
.credit{display:flex;align-items:center;gap:14px;font-family:"Geist Mono",monospace;font-size:13px;color:var(--ink3);font-weight:500;letter-spacing:.12em;text-transform:uppercase}
.cover .credit{position:absolute;left:96px;bottom:44px;background:#fff;padding:12px 18px;border-radius:999px;border:1px solid var(--line)}
.credit img{height:20px;width:auto}
.end{justify-content:center}
.end__in{display:grid;gap:34px;position:relative;z-index:1}
.end h2.d{font-size:120px}
.end__wave{position:absolute;left:0;right:0;bottom:120px}
`;
const html = `<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Audazzio: site guide</title><style>${css}</style></head><body>${P.join("\n")}</body></html>`;
const out = path.join(import.meta.dirname, "guide.html");
fs.writeFileSync(out, html);
if (P.length !== TOTAL) console.warn(`guide: ${P.length} pages, the footer says ${TOTAL}`);
await print(out, path.join(import.meta.dirname, "Audazzio-Site-Guide-RSPKT.pdf"), { w: W, h: H, name: "guide-pdf" });

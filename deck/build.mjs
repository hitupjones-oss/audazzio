// The proposal deck: what audazzio.com is today, the redesign, the price and the timeline.
//   node deck/build.mjs   -> deck/deck.html, deck/Audazzio-Site-Proposal-RSPKT.pdf, proofs in scripts/shots/out/deck-pdf-NN.jpg
// Pictures of the site come from scripts/shots/deck.mjs and scripts/shots/before.mjs (run them first).
import fs from "node:fs";
import path from "node:path";
import { BASE_CSS, LIVE, PRICE, esc, hl, joule, marks, print, shot, lockup, wave } from "../scripts/docs/lib.mjs";

const W = 1920, H = 1080;
const M = await marks();
const pic = (name, cls = "") => `<div class="shot ${cls}"><img src="${shot("d-" + name)}" alt=""></div>`;
const phone = (name) => `<div class="shot phone"><img src="${shot("d-" + name)}" alt=""></div>`;
const bullets = (items) => `<ul class="bl">${items.map((t) => `<li>${t}</li>`).join("")}</ul>`;
let n = 1;
const TOTAL = 16;
const slide = (eb, title, body, cls = "") => `<section class="s ${cls}"><header><p class="eb"><b>${String(++n).padStart(2, "0")}</b><span>${esc(eb)}</span><i></i></p><h2 class="d">${hl(title)}</h2></header><div class="body">${body}</div><footer>${lockup(22)}<span>A proposal from RSPKT</span><span>${String(n).padStart(2, "0")} / ${TOTAL}</span></footer></section>`;

const S = [];
S.push(`<section class="s cover"><div class="cover__in"><p class="eb"><span>A proposal from RSPKT · October 2026</span><i></i></p>${lockup(46)}<h1 class="d">${hl("It comes\n*in waves.*")}</h1><p class="lead">A new audazzio.com that explains Live QR in one minute, lets anyone try it on their own phone, and turns every inquiry into a graded lead. Built in WordPress and Elementor.</p><p class="by"><span>Designed by</span><img src="${M.rspkt}" alt="RSPKT">${joule(24)}</p></div>${pic("home", "cover__pic")}<div class="cover__wave">${wave(1920, 150, { lines: 7, amp: 30 })}</div></section>`);

S.push(slide("Today", "Nine pages.\n*One story, scattered.*", `<div class="cols"><div>${bullets([
  "<b>The idea takes a while to find.</b> What Audazzio does is spread across Live QR, Broadcasters, Teams &amp; Leagues, Sponsors and Applications.",
  "<b>The demo is hidden.</b> audazzio.com/demo is linked from nowhere, its steps are pictures of text, and it asks a phone visitor to scan a code on their own phone.",
  "<b>Every button goes to one form.</b> Book a Demo and Contact Sales open the same five fields: no company, no phone, nothing that tells a network from a student.",
  "<b>The proof is buried.</b> NBC Sports, USA Swimming and the Navy All-American Bowl sit as downloads on a Resources page.",
])}<div class="nums"><div><p class="big d">9</p><p>pages on HubSpot today</p></div><div><p class="big d">0</p><p>links to the demo page</p></div><div><p class="big d">5</p><p>fields on the contact form</p></div><div><p class="big d">4</p><p>case studies behind a download</p></div></div></div><div class="stack">${pic("before-home", "before")}${pic("before-demo", "before before--b")}</div></div>`));

S.push(slide("The idea", "One minute\n*to get it.*", `<div class="cols cols--idea"><div><p class="lead">The home page tells the story in the order a visitor needs it, and every section answers one question.</p><table class="map">
<tr><th class="d">0:05</th><td><b>What is it?</b> It comes in waves: inaudible signals in the sound of a broadcast or event put the right content on phones. No scanning.</td></tr>
<tr><th class="d">0:15</th><td><b>How does it work?</b> Embed, play, deliver, with a diagram that plays the three steps.</td></tr>
<tr><th class="d">0:30</th><td><b>Can I see it?</b> Try Audazzio now, right on the page.</td></tr>
<tr><th class="d">0:45</th><td><b>Does it work?</b> NBC Sports, USA Swimming, 74% microphone opt-in, the press.</td></tr>
<tr><th class="d">1:00</th><td><b>What next?</b> Join the Wave.</td></tr></table><p class="under">Six pages instead of nine: Home, Live QR, Solutions, Newsroom, About and Join the Wave, plus Try Audazzio and Privacy. Every old address is redirected.</p></div>${pic("home-full", "flow")}</div>`));

S.push(slide("The look", "White, quiet\n*and in waves.*", `<div class="cols cols--3"><div class="sws">
<div class="sw"><i style="background:#0C1222"></i><span><b>Ink</b>#0C1222 · text</span></div>
<div class="sw"><i style="background:#3B4152"></i><span><b>Slate</b>#3B4152 · body</span></div>
<div class="sw"><i style="background:#868A96"></i><span><b>Grey</b>#868A96 · the second tone</span></div>
<div class="sw"><i style="background:#F5F5F7;border:1px solid #E3E4E8"></i><span><b>Mist</b>#F5F5F7 · panels</span></div>
<div class="sw"><i style="background:#fff;border:1px solid #E3E4E8"></i><span><b>White</b>#FFFFFF · the page</span></div>
<div class="sw"><i style="background:#F26A2E"></i><span><b>Wave</b>#F26A2E · Join the Wave only</span></div>
<div class="sw"><i style="background:#F89C68"></i><span><b>Peach</b>#F89C68 · the logo</span></div></div>
<div><p class="type d">Inter Tight</p><p class="cap">Headlines, set tight, in two tones</p><p class="type type--text">Inter</p><p class="cap">Text, 17 to 23 px</p><p class="type type--mono">GEIST MONO</p><p class="cap">Labels and readouts</p>${bullets(["<b>One accent.</b> The orange is kept for Join the Wave and for the signal itself.", "<b>Two-tone headlines,</b> ink then grey, as Apple does.", "<b>Frosted, floating surfaces:</b> the header, the bar, the notification."])}</div>${pic("home", "tall")}</div>`));

S.push(slide("The waves", "Sound, drawn\n*in fine lines.*", `<div class="cols cols--3x">${pic("home", "third")}${pic("liveqr", "third")}${pic("story", "third")}</div><p class="under"><b>Hairline dashes that move like sound, after klyro.security.</b> A frequency curtain glows over the home page, wave lines breathe and carry an orange signal pulse, signal emitters ring out, and on the Live QR page a spectrum shows the inaudible top band as the one thing in colour. Drawn live, paused when off screen, held still for anyone who asks for less motion.</p>`));

S.push(slide("Live QR, explained", "It’s like a QR code.\n*No one has to scan it.*", `<div class="cols cols--2">${pic("steps", "tall")}${pic("flow", "tall flow--sec")}</div><p class="under">Three steps on the home page (embed, play, deliver) with a diagram that plays them. Seven steps on the Live QR page with a switch between <b>Broadcast</b> and <b>Live event</b>, then a canvas of what phones can show, straight answers to the questions people ask, and the explainer film.</p>`));

S.push(slide("Try Audazzio now", "Speakers on.\n*Phone out.*", `<div class="cols cols--wide">${pic("try-playing", "wide")}<div>${pic("notify", "strip notify")}${bullets([
  "<b>A push-style notification</b> slides in on the home page: Try Audazzio now.",
  "<b>Four steps:</b> open the listener on your phone (a QR code from a computer, nothing to download), allow the microphone, speakers on, press play.",
  "<b>Pick a demo:</b> rugby sevens or USA Swimming, from today’s hidden demo page. Its Formula 1 clip is left out: the rights holder blocks it on other sites.",
  "<b>No phone handy?</b> The phone beside the player shows what would land.",
])}</div></div>`));

S.push(slide("Join the Wave", "Every inquiry,\n*qualified and graded.*", `<div class="cols cols--2">${pic("join-3", "tall")}${pic("inbox", "tall")}</div><p class="under"><b>Join the Wave</b> is one tap away on every page, in the header and in a bar at the foot of the screen, and opens a four-step form: who (name, position, company, phone, email), what (organization, uses, details), scale (audience, frequency, budget, timing, role) and send. Every answer carries points: the site grades <b>A to D</b> on the server and names the engagement, like <b>Broadcast partnership · Enterprise scale</b>. The team gets an email, an inbox with statuses and notes, a CSV export, and the same lead in HubSpot if you want it there. The questions, the points and the grade lines can be changed in WordPress.</p>`));

S.push(slide("Proof and press", "NBC Sports.\n*USA Swimming. On air.*", `<div class="cols cols--wide">${pic("cases", "wide")}<div>${pic("logos", "strip")}${bullets([
  "<b>Four case studies up front,</b> each with its numbers: 74% microphone opt-in, +200% app downloads, +145% app open rate.",
  "<b>A grey logo carousel</b> of the companies Audazzio has worked with and been featured by.",
  "<b>A newsroom</b> with every release as a clean PDF, filtered by case study, press release, award and coverage.",
])}</div></div>`));

S.push(slide("Leadership", "Better portraits.\n*Shorter bios.*", `<div class="cols cols--wide">${pic("leadership", "wide")}${pic("bio", "tall")}</div><p class="under">Six portraits cut out and set on one backdrop, so the team looks like a team. A one-line summary on each card, the full biography and its highlights one click away, and a link to each person’s LinkedIn.</p>`));

S.push(slide("Behind it", "WordPress and Elementor,\n*made for the team.*", `<div class="cols cols--3x">${pic("editor", "third")}${pic("lead", "third")}${pic("settings", "third")}</div><p class="under">Every section is an <b>Audazzio widget in Elementor</b>: 21 kinds, from the hero to the form. Headlines, pictures, films and people are fields to fill, not code. News, case studies and logos are one list each: add a press release once and every page that shows news has it. The inbox, the form’s questions and points, and the settings (the listener link, the demo clips, the popup and notification text, where inquiries go, HubSpot) live under one Audazzio menu.</p>`));

S.push(slide("On a phone", "Built for\n*the thumb.*", `<div class="cols cols--phones">${phone("m-home")}${phone("m-try")}${phone("m-join")}<div>${bullets([
  "The header folds into a full-screen menu; Join the Wave sits at the foot of the screen.",
  "On a phone, Try Audazzio opens the listener in one tap and asks for a second screen to play the demo.",
  "The form is one question group at a time, with big targets.",
  "Every section reflows, nothing scrolls sideways.",
])}</div></div>`));

S.push(slide("What is real", "What is ready,\n*and what we need from you.*", `<div class="cols cols--3"><div><p class="tag">Ready</p>${bullets(["All copy from today’s site and the case studies", "Four case studies with their PDFs", "Eight press releases as clean PDFs", "Leadership, board and LinkedIn", "The listener and the demo clips", "Redirects from every old address"])}</div><div><p class="tag">Placeholders</p>${bullets(["Films: today’s YouTube videos, until the new ones arrive", "Demo clips on YouTube (an MP4 keeps the signal safest)", "Portraits from today’s site, low resolution for four people", "Press logos from public sources"])}</div><div><p class="tag">From you</p>${bullets(["The new films and the demo files", "Higher-resolution portraits", "Hosting and domain access (DNS on Route 53)", "HubSpot access, if leads should land there", "Your counsel’s yes before Live QR™ becomes ®","A privacy policy line about the microphone"])}</div></div>`));

S.push(slide("Investment", "One price.\n*Everything here.*", `<div class="cols cols--price"><div class="price"><p class="eb"><span>The new audazzio.com</span><i></i></p><p class="price__n d">${PRICE}</p><p class="price__sub">Design, build and launch, fixed.</p><p class="price__more"><b>Additional support is available if needed:</b> updates, new pages, films and campaigns after launch, on request.</p></div><div>${bullets([
  "Design and build in WordPress and Elementor: the theme, the Audazzio plugin and 21 editable sections",
  "The wave motion system across the site",
  "Try Audazzio now: the notification, the player, the listener steps and the demo picker",
  "Join the Wave: the four-step form, grading, inbox, email alerts and the HubSpot connection",
  "Content moved over: copy, case studies, press releases, leadership and board, redirects",
  "Launch on your hosting and domain, the site guide, and a walkthrough with your team",
])}</div></div>`));

S.push(slide("Timeline", "Live in\n*5 to 7 business days.*", `<p class="lead lead--t">Counted from the day we receive your existing hosting and domain credentials.</p><ol class="days">
<li><b class="d">Day 1</b><span><b>Access and backup.</b> Hosting, the domain’s DNS (Route 53) and a full backup of today’s site.</span></li>
<li><b class="d">Days 2 and 3</b><span><b>Build on your host.</b> WordPress, Elementor, the theme and plugin, content, email delivery and HubSpot.</span></li>
<li><b class="d">Day 4</b><span><b>Test everything.</b> Every page on phones and desktops, the form end to end, redirects and speed.</span></li>
<li><b class="d">Day 5</b><span><b>Go live.</b> The DNS switch, with your email records left untouched.</span></li>
<li><b class="d">Days 6 and 7</b><span><b>Settle in.</b> Checks after launch, fixes, and the handover with your team.</span></li></ol>`));

S.push(`<section class="s end"><div class="end__in"><p class="eb"><span>Next</span><i></i></p><h2 class="d">${hl("Walk through it.\n*Then tell us what to change.*")}</h2><ol class="steps"><li><b>See it.</b> The preview is live at <u>${LIVE}</u>, on a phone and on a desktop.</li><li><b>Mark it up.</b> Words, pictures, what is missing, what should go.</li><li><b>Hand us the keys.</b> Hosting, the domain, HubSpot, the new films.</li><li><b>Launch.</b> On audazzio.com, 5 to 7 business days later.</li></ol><p class="credit"><span>Designed by</span><img src="${M.rspkt}" alt="RSPKT">${joule(30)}</p></div><div class="end__wave">${wave(1920, 180, { lines: 9, amp: 40 })}</div></section>`);

const css = `${BASE_CSS}
@page{size:${W}px ${H}px;margin:0}
.s{width:${W}px;height:${H}px;position:relative;overflow:hidden;page-break-after:always;padding:76px 96px 0;display:flex;flex-direction:column;background:#fff}
.s header{flex:none}
.eb{font-size:16px;margin-bottom:26px}
h2.d{font-size:80px}
.body{flex:1;min-height:0;padding:40px 0 100px;display:flex;flex-direction:column;gap:28px}
footer{position:absolute;left:96px;right:96px;bottom:34px;display:flex;align-items:center;gap:26px;font-family:"Geist Mono",monospace;font-size:13px;font-weight:500;letter-spacing:.14em;text-transform:uppercase;color:var(--ink3);border-top:1px solid var(--line);padding-top:18px}
footer span:last-child{margin-left:auto}
.cols{display:grid;grid-template-columns:1fr 1fr;gap:56px;flex:1;min-height:0;align-items:start}
.cols--wide{grid-template-columns:1.45fr 1fr}
.cols--idea{grid-template-columns:1.25fr .75fr}
.cols--2{grid-template-columns:1fr 1fr;gap:32px}
.body>.cols--2,.body>.cols--wide{align-items:stretch;grid-template-rows:minmax(0,1fr)}
.body>.cols--2>.shot,.body>.cols--wide>.shot{aspect-ratio:auto;height:100%;min-height:0}
.cols--3{grid-template-columns:.85fr 1fr 1.15fr;gap:48px}
.cols--3x{grid-template-columns:1fr 1fr 1fr;gap:24px}
.cols--phones{grid-template-columns:auto auto auto 1fr;gap:32px;align-items:center}
.cols--price{grid-template-columns:.9fr 1.1fr;gap:72px;align-items:center}
.shot.wide{aspect-ratio:16/10}.shot.tall{aspect-ratio:16/10}.shot.third{aspect-ratio:16/10}.shot.strip{aspect-ratio:16/5.4;margin-bottom:28px}
.shot.notify img{object-position:right top}
.shot.flow{height:640px;aspect-ratio:auto}
.shot.flow img{object-position:top center}
.flow--sec img{object-position:center}
.shot.phone{width:290px;aspect-ratio:390/844;border-radius:34px;border:6px solid var(--ink)}
.stack{position:relative;height:100%}
.shot.before{position:absolute;left:0;top:0;width:88%;aspect-ratio:16/10}
.shot.before--b{left:auto;right:0;top:auto;bottom:40px;width:62%;border:1px solid var(--line)}
.bl{list-style:none;padding:0;display:grid;gap:18px;font-size:24px;line-height:1.38;color:var(--ink2)}
.bl li{padding-left:30px;position:relative}
.bl li::before{content:"";position:absolute;left:0;top:.62em;width:12px;height:1.5px;background:var(--ink)}
.lead{font-size:30px;line-height:1.35;color:var(--ink2);max-width:26em}
.lead--t{margin-bottom:8px}
.under{font-size:23px;line-height:1.45;color:var(--ink2);max-width:72em}
.nums{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-top:40px}
.nums>div{border-top:1px solid var(--ink);padding-top:16px}
.nums p{font-size:16px;color:var(--ink3);line-height:1.3}
.nums .big{font-size:72px;color:var(--ink);margin-bottom:6px}
.map{border-collapse:collapse;margin:30px 0 26px;width:100%}
.map th{font-size:34px;text-align:left;padding:18px 32px 18px 0;vertical-align:top;white-space:nowrap;color:var(--ink);border-top:1px solid var(--line);width:1%}
.map td{font-size:23px;line-height:1.4;padding:20px 0;color:var(--ink2);border-top:1px solid var(--line)}
.sws{display:grid;gap:12px;align-content:start}
.sw{display:flex;align-items:center;gap:18px;font-size:15px;color:var(--ink3)}
.sw i{width:96px;height:48px;border-radius:12px;flex:none}
.sw b{display:block;font-size:18px;color:var(--ink)}
.type{font-size:64px;color:var(--ink);margin-top:6px}
.type--text{font-family:"Inter";font-weight:400;letter-spacing:-.02em;margin-top:22px}
.type--mono{font-family:"Geist Mono";font-weight:500;font-size:40px;letter-spacing:.12em;margin-top:22px}
.cap{font-size:15px;color:var(--ink3);margin-bottom:6px}
.cols--3 .bl{font-size:20px;gap:12px;margin-top:26px}
.tag{font-size:14px;margin-bottom:22px}
.cover{flex-direction:row;padding:0;align-items:stretch}
.cover__in{width:880px;padding:96px 0 120px 96px;display:flex;flex-direction:column;justify-content:center;gap:30px;position:relative;z-index:1}
.cover h1{font-size:150px;letter-spacing:-.055em;line-height:.9}
.cover .lead{font-size:26px;max-width:24em}
.by,.credit{display:flex;align-items:center;gap:16px;font-family:"Geist Mono",monospace;font-size:14px;color:var(--ink3);font-weight:500;letter-spacing:.12em;text-transform:uppercase}
.by img,.credit img{height:22px;width:auto}
.cover__pic{flex:1;border-radius:0;border:0;border-left:1px solid var(--line);margin:0}
.cover__pic img{object-position:50% 0}
.cover__wave,.end__wave{position:absolute;left:0;right:0;bottom:24px;z-index:0}
.price{border-radius:28px;background:var(--mist);padding:56px 56px 52px}
.price__n{font-size:180px;letter-spacing:-.06em;margin-top:8px}
.price__sub{font-size:24px;color:var(--ink2);margin-top:8px}
.price__more{margin-top:36px;padding-top:24px;border-top:1px solid var(--line-2,#D2D4DA);font-size:21px;line-height:1.45;color:var(--ink2)}
.days{list-style:none;padding:0;display:grid;grid-template-columns:repeat(5,1fr);gap:20px;margin-top:20px}
.days li{border-top:2px solid var(--ink);padding-top:22px;display:grid;gap:16px;align-content:start}
.days li:first-child{border-color:var(--wave)}
.days .d{font-size:36px}
.days span{font-size:21px;line-height:1.42;color:var(--ink2)}
.end{justify-content:center}
.end__in{display:grid;gap:34px;max-width:1400px;position:relative;z-index:1}
.end h2.d{font-size:104px}
.steps{list-style:none;padding:0;counter-reset:s;display:grid;gap:16px;font-size:27px;color:var(--ink2);line-height:1.34}
.steps li{counter-increment:s;padding-left:64px;position:relative}
.steps li::before{content:counter(s,decimal-leading-zero);position:absolute;left:0;top:.18em;color:var(--ink3);font-family:"Geist Mono",monospace;font-weight:500;font-size:.72em;letter-spacing:.1em}
.steps u{text-decoration:none;color:var(--ink);font-weight:600;border-bottom:2px solid var(--wave)}
`;
const html = `<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Audazzio: it comes in waves. A proposal from RSPKT</title><style>${css}</style></head><body>${S.join("\n")}</body></html>`;
const out = path.join(import.meta.dirname, "deck.html");
fs.writeFileSync(out, html);
if (S.length !== TOTAL) console.warn(`deck: ${S.length} slides, the footer says ${TOTAL}`);
await print(out, path.join(import.meta.dirname, "Audazzio-Site-Proposal-RSPKT.pdf"), { w: W, h: H, name: "deck-pdf" });

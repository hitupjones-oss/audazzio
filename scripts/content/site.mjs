// The facts the site is built from, and where each came from. scripts/wp/build.mjs writes this to
// wp-content/plugins/audazzio-core/seed/site.json; the sections use it as their starting copy, and
// everything can then be edited in Elementor.
//
// Sources: today's audazzio.com (crawled October 2026), the four case-study PDFs and the press releases
// on its Resources page, and the YouTube channel. Numbers are quoted as Audazzio published them.
// House rules for copy: short sentences, no exclamation marks, no long dashes.

export const COMPANY = {
  email: "info@audazzio.com", // case-study PDF footers and the 2025 press release
  phone: "855.697.6627", // 2025 press release; privacy policy letterhead "1.855.697.6627"
  location: "San Antonio, Texas", // privacy policy: 7900 Callahan Road, Suite 128, San Antonio, Texas 78229
  linkedin: "https://www.linkedin.com/company/audazzio",
};

// The five films on today's home page, kept as placeholders for the new ones (youtube.com/@audazzio).
// The one-minute overview leads.
export const VIDEOS = [
  { id: "UhPIcspc2-k", title: "Audazzio in action", label: "Overview", length: "About 1 min" },
  { id: "oxwBbfqcnGY", title: "Tour de France live demonstration", label: "Example · Broadcast", length: "About 2 min" },
  { id: "_ZWP1nY4rws", title: "Audazzio in action: US football", label: "Example · Broadcast", length: "About 2 min" },
  { id: "FDXRxD3zHm4", title: "Rugby sevens demonstration", label: "Example · Broadcast", length: "About 2 min" },
  { id: "PL6hqRwk830", title: "The Audazzio explainer", label: "How it works", length: "2 min" },
];
// Try Audazzio. There is no public Audazzio app in the stores (October 2026): phones listen through
// Audazzio's web listener, the page today's audazzio.com/demo sends people to with its QR code.
export const LISTENER = "https://cdn.audazz.io/tools/browser-decoder/viewer.html?clientId=demo&clientKey=EB";
// The demo clips from audazzio.com/demo (their soundtracks carry the signal), placeholders for new ones.
// Its Formula 1 clip (C8tkbLRltjA) is left out: Formula One Management blocks it from playing on other sites.
export const DEMOS = [
  { id: "FDXRxD3zHm4", label: "Rugby sevens", title: "Audazzio rugby demonstration" },
  { id: "JBQM5iC7CpQ", label: "USA Swimming", title: "Audazzio at USA Swimming" },
];
export const DEMO = DEMOS[0];

// What the phone beside the player shows, one screen per signal (Audazzio's own mock-ups).
export const SCREENS = [
  { file: "screen-replays.jpg", label: "Instant replay" },
  { file: "screen-voting.jpg", label: "Vote: player of the match" },
  { file: "screen-facts.jpg", label: "Data and facts" },
  { file: "screen-competition.jpg", label: "Win free tickets" },
  { file: "screen-player.jpg", label: "Player profile" },
];

// Case studies, newest first. Metrics are the big-number callouts in each PDF.
export const CASES = [
  {
    org: "NBC · Navy All-American Bowl", year: "2026", where: "Venue", image: "case-naab.jpg", pdf: "case-study-navy-all-american-bowl-2026.pdf",
    title: "NBC deploys Audazzio during the Navy All-American Bowl, engaging venue fans",
    metrics: ["74% | microphone opt-in, against an industry standard of 20%", "63% | audience participation"],
    summary: "Within seconds of each play, fans in the Alamodome saw the players who made it: photos, hometowns, stats and college commitments.",
  },
  {
    org: "USA Swimming · TYR Pro Swim Series", year: "2025", where: "Venue and home", image: "case-usas25.jpg", pdf: "case-study-usa-swimming-2025.pdf",
    title: "USA Swimming engages venue and home audiences during the TYR Pro Swim Series",
    metrics: ["+50% | microphone opt-in", "+200% | app downloads"],
    summary: "Four days of interactive campaigns, athlete profiles, trivia and records, synchronized with the USA Network stream.",
  },
  {
    org: "USA Swimming · NBC Sports", year: "2024", where: "Broadcast", image: "case-usas24.jpg", pdf: "case-study-usa-swimming-2024.pdf",
    title: "USA Swimming deploys Audazzio during the Olympic Trials to engage the home audience",
    metrics: ["+50% | microphone opt-in", "+145% | app open rate"],
    summary: "Profiles, record holders' times and interactive campaigns, synchronized with the broadcasts on USA Network and Peacock.",
  },
  {
    org: "NBC Sports · Tour de France", year: "2023", where: "Broadcast", image: "case-tdf.jpg", pdf: "case-study-nbc-sports-tour-de-france.pdf",
    title: "NBC Sports trials Audazzio second-screen technology on its Tour de France broadcast",
    metrics: ["< 1 sec | from the signal to the phone"],
    summary: "Audazzio bridged the primary screen with the home audience and survived the rigors of signal transcoding and transmission.",
  },
];

// Newsroom, newest first. "pdf" files ship with the site; "url" points elsewhere. A date without a day
// (YYYY-MM) is shown as the month: the case-study PDFs carry only a month code (0525 = May 2025).
export const PRESS = [
  { date: "2026-01-23", kind: "Case study", outlet: "Audazzio", title: "NBC deploys Audazzio during the Navy All-American Bowl, engaging venue fans", summary: "74% microphone opt-in and 63% audience participation in the Alamodome.", pdf: "case-study-navy-all-american-bowl-2026.pdf", image: "news-naab.jpg" },
  { date: "2025-05", kind: "Case study", outlet: "Audazzio", title: "USA Swimming deploys Audazzio during the TYR Pro Swim Series, engaging venue and home audiences", summary: "Over 50% microphone opt-in and +200% app downloads.", pdf: "case-study-usa-swimming-2025.pdf", image: "news-usas25.jpg" },
  { date: "2025-01-21", kind: "Press release", outlet: "Audazzio", title: "Audazzio promotes Michele Klumb to Chief Operating Officer", summary: "The seasoned entrepreneur, engineer and marketer now oversees day-to-day operations.", pdf: "press-2025-01-21-klumb-coo.pdf", image: "news-klumb.jpg" },
  { date: "2024-08-01", kind: "Coverage", outlet: "Rethink (Faultline)", title: "Audazzio dazzles with 50% take-up in second-screen monetization trials", summary: "Industry analysis of inaudible micro-signaling as an alternative to QR codes.", url: "https://rethinkresearch.biz/articles/audazzio-dazzles-with-50-take-up-in-2nd-screen-monetization-trials/", image: "news-usas24.jpg" },
  { date: "2024-07", kind: "Case study", outlet: "Audazzio", title: "USA Swimming deploys Audazzio during the Olympic Trials to engage the home audience", summary: "Over 50% microphone opt-in and +145% app open rate on USA Network and Peacock.", pdf: "case-study-usa-swimming-2024.pdf", image: "news-usas24.jpg" },
  { date: "2024-07-16", kind: "Coverage", outlet: "San Antonio Business Journal", title: "SA startup gets Olympic lift", summary: "Audazzio is drawing interest from multiple major sports organizations for its second-screen technology.", url: "https://www.bizjournals.com/sanantonio/news/2024/07/16/sa-startup-gets-olympic-lift-after-tragic-loss.html", image: "news-sabj.jpg" },
  { date: "2024-07-15", kind: "Press release", outlet: "Audazzio", title: "Audazzio engages the audience of the USA Swimming Olympic Trials with real-time, interactive content", summary: "Deployed for the national broadcast audiences on USA Network and Peacock.", pdf: "press-2024-07-15-usa-swimming.pdf", image: "news-usas24.jpg" },
  { date: "2023-11-16", kind: "Award", outlet: "SPORTEL Monaco", title: "Audazzio honored at SPORTEL Monaco", summary: "Live QR received runner-up honors at the Pitch Perfect session.", pdf: "press-2023-11-16-sportel.pdf", image: "news-sportel.jpg" },
  { date: "2023-07", kind: "Case study", outlet: "Audazzio", title: "NBC Sports trials Audazzio second-screen technology on its Tour de France broadcast", summary: "Bridged the primary screen with the home audience across MVPD, vMVPD and DTC.", pdf: "case-study-nbc-sports-tour-de-france.pdf", image: "news-tdf.jpg" },
  { date: "2023-02-06", kind: "Press release", outlet: "Audazzio", title: "Audazzio appoints Bryan Elliot as VP of Engineering", summary: "The Ping Identity co-founder leads all technology development.", pdf: "press-2023-02-06-elliot.pdf", image: "news-elliot.jpg" },
  { date: "2023-01-19", kind: "Press release", outlet: "Audazzio", title: "Audazzio has a strong presence at the 16th annual Sports Video Group Summit", summary: "Lead sponsor of the OTT, Streaming and Digital Workshop in New York City.", pdf: "press-2023-01-19-svg-summit.pdf", image: "news-svg.jpg" },
  { date: "2022-11-04", kind: "Award", outlet: "The Tech Tribune", title: "Audazzio named to The Tech Tribune’s ten best tech startups in San Antonio", summary: "Recognized on the annual list of the city’s best tech startups.", pdf: "press-2022-11-04-tech-tribune.pdf", image: "news-tribune.jpg" },
  { date: "2022-04-25", kind: "Press release", outlet: "Audazzio", title: "Audazzio announces a $1.4 million capital raise for accelerated growth", summary: "A second round of funding, largely from existing shareholders.", pdf: "press-2022-04-25-raise.pdf", image: "news-raise.jpg" },
  { date: "2022-04-05", kind: "Press release", outlet: "Audazzio", title: "Audazzio seeks to disrupt sports broadcasting and beyond with game-changing technology", summary: "The first wave of the Live QR rollout.", pdf: "press-2022-04-05-disrupt.pdf", image: "news-disrupt.jpg" },
  { date: "2021-02-22", kind: "Award", outlet: "Comcast NBCUniversal SportsTech", title: "One of ten companies selected for the inaugural Comcast NBCUniversal SportsTech Accelerator", summary: "Chosen from over 1,000 applicants.", url: "https://www.comcastsportstech.com/portfolio-2024/", image: "news-sportstech.jpg" },
];

// The four quotes on today's About page, word for word (shortened to their key sentences; a cut inside a quote is marked …).
// Each role line says how the speaker is tied to Audazzio: Aldous, Charlesworth and Abbott are its advisory team
// (04.05.22 release, SVG 2022-07-14), Charlesworth also its Chief Technical Liaison (01.19.23 release), and
// Boomtown, Faulkenberg's company, an investor (today's About page: "New investors including Comcast and Boomtown").
export const QUOTES = [
  { quote: "I am blown away by Audazzio's ability to tightly coordinate second-screen experiences with linear broadcasting. The seamless and undetectable embedding and acoustic detection of micro-signaling that underlies this technology is nothing short of magic.", name: "Roger Charlesworth", role: "Audazzio adviser and Chief Technical Liaison · Executive Director, DTV Audio Group" },
  { quote: "Audazzio’s unique technology is like no other signaling technology. The technical development gives the broadcaster endless possibilities to enhance the second screen viewer’s experience with ease.", name: "Fred Aldous", role: "Audazzio adviser · Sports broadcast audio consultant" },
  { quote: "The Audazzio team is a dedicated group with great passion for their business. … Boomtown is proud to support Audazzio, beyond the accelerator program, as they form new strategic partnerships.", name: "Mark A. Faulkenberg", role: "Managing Director, Comcast NBCUniversal SportsTech powered by Boomtown, an Audazzio investor" },
  { quote: "As Producers begin to leverage this solution, they will quickly appreciate there are no limits for creating innovative, engaging content with Audazzio’s technology.", name: "Michael Abbott", role: "Audazzio adviser · Audio Engineering and Consulting, All Ears Inc." },
];

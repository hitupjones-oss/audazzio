// Every page's sections and copy. scripts/wp/build.mjs writes this to seed/pages.json and the importer
// turns each section into an Elementor widget (fields not listed here use the widget's own defaults, in
// wp-content/plugins/audazzio-core/includes/components/).
//
// News, case studies and logos are not copied into the pages: those widgets show the shared lists
// (source: "shared", edited under Audazzio > News, Case studies and Logos), which the importer fills once
// from scripts/content/site.mjs and content/logos.json (az_seed_lists in includes/seed.php).
//
// The order of the home page is the one-minute story: what it is (hero), how it works (three steps),
// try it (the player), why it matters (numbers), see it (films), who it is for, proof (case studies,
// press), then Join the Wave.
//
// Headlines: a line break starts a new line; *asterisks* set words in the lighter second tone.
// House rules for copy: short sentences, no exclamation marks, no long dashes. Facts only
// (scripts/content/site.mjs lists where each one came from).

const cta = ["cta", {}];

export const PAGES = [
  {
    slug: "home", title: "Home", order: 0,
    doc_title: "Audazzio | It comes in waves.",
    description: "Audazzio’s Live QR™ technology hides inaudible signals in the sound of a broadcast or live event, so every phone that hears one shows the right content at the exact moment. No scanning.",
    widgets: [
      ["hero", {}],
      ["steps", { tone: "white" }],
      ["try", { tone: "mist" }],
      ["numbers", { tone: "white" }],
      ["videos", { tone: "white" }],
      ["solutions", { tone: "mist" }],
      ["cases", { tone: "white", source: "shared" }],
      ["logos", { tone: "white", source: "shared" }],
      ["press", { tone: "white", source: "shared", limit: 4, eyebrow: "Newsroom", title: "The latest\n*from Audazzio.*", cta: "All news", cta_url: "/newsroom/" }],
      cta,
    ],
  },
  {
    slug: "live-qr", title: "Live QR", order: 1,
    doc_title: "How Live QR™ works | Audazzio",
    description: "Live QR™ embeds inaudible micro-signals in the audio of a broadcast or live event. Phones that hear them load the content you chose, in less than a second. It’s like a QR code, but no one has to scan it.",
    widgets: [
      ["page-hero", { eyebrow: "Live QR™ technology", title: "It’s like a QR code.\n*No one has to scan it.*", intro: "Our Live QR technology embeds inaudible micro-signals in the audio of a broadcast or live event. Every phone that hears one loads the content you chose, in less than a second.", cta: "Try it now", cta_url: "/try/", alt: "Talk to us", alt_url: "/join/", waves: "spectrum" }],
      ["flow", { tone: "mist" }],
      ["canvas", { tone: "white" }],
      ["videos", { tone: "white", anchor: "explainer", eyebrow: "The explainer", title: "Two minutes.\n*The whole idea.*", layout: "feature", items: [{ title: "The Audazzio explainer", label: "How it works", video: "https://www.youtube.com/watch?v=PL6hqRwk830", poster: "asset:img/poster-PL6hqRwk830.jpg", length: "2 min" }] }],
      ["numbers", {
        tone: "mist", size: "compact", eyebrow: "Proven live", title: "Fans opt in.\n*Then they act.*",
        items: [
          { value: "74", unit: "%", label: "microphone opt-in at the 2026 Navy All-American Bowl, against an industry standard of 20%" },
          { value: "200", pre: "+", unit: "%", label: "app downloads for USA Swimming at the 2025 TYR Pro Swim Series" },
          { value: "1", pre: "<", unit: "sec", label: "from the signal to the content on the phone" },
        ],
      }],
      ["faq", {
        tone: "white",
        items: [
          { q: "Can people hear the signal?", a: "No. The micro-signals are inaudible to the human ear and ride inside the normal audio of the broadcast or the venue’s sound system. Phones hear them; people hear the show." },
          { q: "What does a viewer need?", a: "A phone or tablet listening through Audazzio, with its microphone on. That can be a partner’s own app (at the Olympic Trials, NBC Sports invited fans to simply download or open the USA Swimming app) or the browser, like the demo on this site." },
          { q: "How fast does content arrive?", a: "In less than a second, fully synchronized with the broadcast or the event, regardless of when or where it is watched." },
          { q: "Does it survive broadcast and streaming?", a: "Yes. On NBC Sports’ Tour de France broadcast, Audazzio survived the rigors of signal transcoding and transmission, and worked across traditional MVPD, vMVPD and DTC." },
          { q: "What can it put on the phone?", a: "Any web page, at any time, on any device within range of the signal: player profiles, stats, replays, polls and votes, contests, offers, shopping and sponsored content." },
          { q: "Can we measure what works?", a: "Yes. Audazzio tracks engagement and preferences, so you see who engaged and which content resonated. At the 2024 Olympic Trials, USA Swimming used that feedback to change its content while the event was still on." },
          { q: "Where does it work?", a: "Anywhere there’s a sound system: TV and streams at home, stadiums and arenas, concerts, studio shows, corporate meetings, casinos and theme parks." },
        ],
      }],
      ["values", { tone: "mist" }],
      cta,
    ],
  },
  {
    slug: "solutions", title: "Solutions", order: 2,
    doc_title: "Solutions for broadcasters, teams, sponsors and live events | Audazzio",
    description: "How broadcasters, teams, leagues, federations, sponsors and live event producers use Audazzio to put the right content on fans’ phones at the right moment, and sell new inventory.",
    widgets: [
      ["page-hero", {
        eyebrow: "Solutions", title: "One signal.\n*Every kind of audience.*",
        intro: "Broadcasters, rights holders and brands use Audazzio to control what fans see on their phones during the moments that matter, and to open new engagement and revenue.",
        waves: "lines", links: "Broadcasters | #broadcasters\nTeams and leagues | #teams\nSponsors and brands | #sponsors\nBeyond sport | #beyond",
      }],
      ["solution", {
        tone: "white", anchor: "broadcasters", eyebrow: "Broadcasters", title: "Own the second screen\n*during your broadcast.*",
        intro: "Directly influence your audience’s second-screen experience, unlocking new engagement and revenue opportunities with the Audazzio® technology.",
        shape: "phone",
        gallery: [
          { image: "asset:img/phone-replays.webp", caption: "Replays" }, { image: "asset:img/phone-facts.webp", caption: "Data and facts" }, { image: "asset:img/phone-voting.webp", caption: "Voting" },
          { image: "asset:img/phone-competition.webp", caption: "Competitions" }, { image: "asset:img/phone-branded.webp", caption: "Branded content" }, { image: "asset:img/phone-presented.webp", caption: "Presented by" },
          { image: "asset:img/phone-offers.webp", caption: "Partner offers" }, { image: "asset:img/phone-apps.webp", caption: "Partner apps" }, { image: "asset:img/phone-player.webp", caption: "Player profiles" },
        ],
        features: [
          { group: "Enhanced storytelling", icon: "info", title: "Facts and rules", text: "Match facts and rules that deepen fans’ understanding of the game." },
          { group: "Enhanced storytelling", icon: "replay", title: "Replays", text: "Exclusive camera angles and replays, so fans get the best view of crucial moments." },
          { group: "Enhanced storytelling", icon: "users", title: "Exclusive content", text: "The background on new players as they enter the field." },
          { group: "Advertising and sponsorship", icon: "chart", title: "Additional inventory", text: "Advertising or sponsorship built into the companion content you push to devices." },
          { group: "Advertising and sponsorship", icon: "spark", title: "Branded content", text: "Branded content at key moments that adds value to the viewing experience." },
          { group: "Advertising and sponsorship", icon: "app", title: "Partner apps", text: "Direct traffic to a brand’s app during an advertisement or sponsor message." },
          { group: "Direct response", icon: "bag", title: "Partner offers", text: "Exclusive offers for your own or your partners’ products, like a new kit." },
          { group: "Direct response", icon: "trophy", title: "Competitions", text: "Entry links pushed the moment a promotion appears on screen." },
          { group: "Direct response", icon: "vote", title: "Voting", text: "A real two-way dialogue with the audience during the live broadcast." },
        ],
      }],
      ["solution", {
        tone: "mist", anchor: "teams", eyebrow: "Teams, leagues and federations", title: "Fans at home.\n*Fans in the stands.*",
        intro: "Audazzio® drives engagement and revenue for sports rights holders and their partners, through your broadcast partners or your own channels.",
        shape: "wide",
        gallery: [
          { image: "asset:img/teams-replay.jpg", caption: "Exclusive replays" }, { image: "asset:img/teams-story.jpg", caption: "Storytelling" }, { image: "asset:img/teams-sponsor.jpg", caption: "Sponsor exposure" },
          { image: "asset:img/teams-stadium.jpg", caption: "In the stadium" }, { image: "asset:img/teams-retail.jpg", caption: "Retail offers" }, { image: "asset:img/teams-hospitality.jpg", caption: "Hospitality" },
        ],
        features: [
          { group: "Connect with fans at home", icon: "tv", title: "Enhanced storytelling", text: "Timely delivery of rich, contextual content to fans’ second screens." },
          { group: "Connect with fans at home", icon: "chart", title: "Expand inventory", text: "New revenue from more exposure and engagement for sponsors." },
          { group: "Connect with fans at home", icon: "spark", title: "Improve performance", text: "A fast, frictionless direct-response mechanism for sponsors and partners." },
          { group: "Connect with fans at home", icon: "app", title: "Drive traffic", text: "Grow your official app audience and secure first-party engagement." },
          { group: "Enhance the experience in stadia", icon: "replay", title: "Exclusive content", text: "Additional stats and exclusive replays that enhance the live match." },
          { group: "Enhance the experience in stadia", icon: "users", title: "Partner content", text: "More in-stadium sponsorship inventory, and partners closer to fans." },
          { group: "Enhance the experience in stadia", icon: "bag", title: "Retail offers", text: "Retail and merchandise offers that lift match-day sales." },
          { group: "Enhance the experience in stadia", icon: "pin", title: "Hospitality", text: "Food and drink promotions that move fans to specific areas and improve traffic flow." },
        ],
      }],
      ["solution", {
        tone: "white", anchor: "sponsors", eyebrow: "Sponsors and brands", title: "More exposure.\n*Better performance.*",
        intro: "The Audazzio tech unlocks increased exposure, engagement and performance around your sports sponsorships.",
        shape: "wide",
        gallery: [
          { image: "asset:img/sponsors-exposure.jpg", caption: "Increased exposure" }, { image: "asset:img/sponsors-engagement.jpg", caption: "Enhanced engagement" }, { image: "asset:img/sponsors-performance.jpg", caption: "Better performance" },
        ],
        features: [
          { group: "Increased exposure", icon: "users", title: "In the moment, in-game", text: "A player making their debut enters the game. Via Audazzio, a pre-loaded profile featuring sponsor content is instantly on the audience’s second screens." },
          { group: "Enhanced engagement", icon: "app", title: "Contextual branded content", text: "A sponsor message appears on screen. Via Audazzio, rich sponsor content is pushed to the media partner’s app or the brand’s own." },
          { group: "Better performance", icon: "trophy", title: "Frictionless direct response", text: "A sponsor competition is broadcast. Via Audazzio, the branded entry is on the audience’s phones in under a second." },
          { group: "Measurable results", icon: "chart", title: "Insights, live", text: "The campaign is on air. Via Audazzio, you see who engaged and which content resonated, and can adjust it while the event is still on." },
        ],
      }],
      ["solution", {
        tone: "mist", anchor: "beyond", eyebrow: "Beyond sport", cta: "Join the Wave", cta_url: "/join/", title: "Creative opportunities,\n*limited only by your imagination.*",
        intro: "Audazzio works anywhere there is a sound system. A few of the places it benefits advertisers, event promoters, broadcasters, concert promoters and gaming organizations:",
        uses: "Broadcast | A second creative canvas; Storytelling; Sponsored content; Voting and polling; Replays and player tracking\nLive events | Schedules and info; Sponsored content; Safety announcements; Contests; Voting and polling\nStudio shows | Additional advertising channel; Educate viewers; Sponsored content; Voting and polling\nConcerts | Encore selection; Scenic extension; Merch promotions; Concession updates; Safety announcements\nCorporate meetings | Coordinate every participant; Speaker bios, announcements and documents; Team promotions and incentives; Ideal for plenary sessions\nCasino resorts | Frictionless wagering; Promotions and incentives; Rewards\nAudience polling | Contest participation; TV alternate endings; Concert encore selections; Merchandise\nTheme parks | Additional advertising channel; Educate and guide guests; Sponsored content; Voting and polling; Merch promotions; Rewards and incentives; Safety announcements; Scavenger hunts",
      }],
      ["cases", { tone: "white", source: "shared" }],
      cta,
    ],
  },
  {
    slug: "newsroom", title: "Newsroom", order: 3,
    doc_title: "Newsroom: case studies, press releases and coverage | Audazzio",
    description: "Audazzio case studies with NBC Sports, USA Swimming and the Navy All-American Bowl, press releases, awards and coverage.",
    widgets: [
      ["page-hero", { eyebrow: "Newsroom", title: "Case studies,\n*news and coverage.*", intro: "From the Tour de France on NBC Sports to the Navy All-American Bowl: what Audazzio has done, and what people are saying.", waves: "rings" }],
      ["cases", { tone: "mist", source: "shared", eyebrow: "Case studies", title: "Proven on air\n*and in the stands.*", intro: "", cta: "", cta_url: "" }],
      ["press", { tone: "white", source: "shared", eyebrow: "All news", title: "Press releases,\n*awards and coverage.*", filters: "yes", layout: "list", limit: 0 }],
      ["logos", { tone: "white", source: "shared" }],
      cta,
    ],
  },
  {
    slug: "about", title: "About", order: 4,
    doc_title: "About Audazzio: leadership and board",
    description: "Audazzio is a San Antonio company and a product of the inaugural Comcast NBCUniversal SportsTech Accelerator. Meet the leadership team and the board.",
    widgets: [
      ["page-hero", { eyebrow: "About Audazzio", title: "Timing is\n*everything.*", intro: "Audazzio lets you capitalize when audience interest is piqued, by frictionlessly signaling second-screen content in those exact moments. We are acoustical, computer science and audio engineers committed to making a positive impact on people’s lives.", waves: "rings", links: "Story | #story\nLeadership | #leadership\nBoard | #board\nHow we work | #values" }],
      ["story", { tone: "white" }],
      ["people", { tone: "mist", group: "leadership", eyebrow: "Leadership", title: "The people\n*behind the signal.*" }],
      ["people", { tone: "white", group: "board", anchor: "board", eyebrow: "The board", title: "Meet the\n*Audazzio board.*" }],
      ["quotes", { tone: "mist" }],
      ["values", { tone: "white", anchor: "values" }],
      cta,
    ],
  },
  {
    slug: "try", title: "Try Audazzio", order: 5,
    doc_title: "Try Audazzio now: turn your speakers on",
    description: "Open the Audazzio listener in your phone’s browser, turn your speakers on and press play. Your phone shows the content in sync: Live QR™, working right here.",
    widgets: [
      ["page-hero", { eyebrow: "Try Audazzio now", title: "Turn your speakers on.\n*Watch your phone.*", intro: "Four steps, about a minute, nothing to download. Your phone listens in the browser, hears the signal in this page’s sound and shows the content in sync.", waves: "spectrum" }],
      ["try", { tone: "mist", eyebrow: "", title: "", intro: "" }],
      ["steps", { tone: "white", eyebrow: "What just happened", title: "The sound carried\n*a signal.*", cta: "How Live QR works", cta_url: "/live-qr/" }],
      cta,
    ],
  },
  {
    slug: "join", title: "Join the Wave", order: 6,
    doc_title: "Join the Wave: talk to Audazzio",
    description: "Tell Audazzio about your broadcast, venue or brand, and see what Live QR™ can do for your audience. Four short steps.",
    widgets: [
      ["join", {}],
      ["logos", { tone: "white", source: "shared" }],
    ],
  },
  {
    slug: "privacy", title: "Privacy Policy", order: 7,
    doc_title: "Privacy Policy | Audazzio",
    description: "How Audazzio, Inc. treats personal information.",
    widgets: [
      ["page-hero", { eyebrow: "Legal", title: "Privacy Policy", intro: "Effective date: April 10, 2023.", waves: "none", cta: "Download the PDF", cta_url: "asset:docs/audazzio-privacy-policy.pdf" }],
      ["prose", { tone: "white", body: "@privacy" }],
    ],
  },
];

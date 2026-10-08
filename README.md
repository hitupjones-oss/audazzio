# Audazzio redesign

A new [audazzio.com](https://www.audazzio.com/) on WordPress + Elementor: white, Apple-clean, with fine dashed sound waves after klyro.security, built so a visitor understands Live QR within a minute. Slogan: **It comes in waves.** Designed by RSPKT.

- **Preview (Vercel):** `site/` is a static copy of the WordPress front end. Import `hitupjones-oss/audazzio` at vercel.com/new as the project **audazzio-rspkt** and keep the defaults (`vercel.json` serves `site/`, no build). The documents point to `audazzio-rspkt.vercel.app` (`scripts/docs/lib.mjs`, `LIVE`).
- **Proposal:** `deck/Audazzio-Site-Proposal-RSPKT.pdf` (16 slides; one price, $23,500, with support offered on request; launch 5 to 7 business days from receiving hosting and domain credentials).
- **Site guide:** `docs/Audazzio-Site-Guide-RSPKT.pdf` (10 pages).
- **To install on WordPress:** `node scripts/wp/build.mjs --zip` writes `dist/audazzio.zip` (theme) and `dist/audazzio-core.zip` (plugin).

## Pages

| Page | Sections |
| --- | --- |
| Home | Hero over the wave field · three steps (embed, play, deliver) with a live diagram · Try Audazzio now · why second screens · films · who it is for · case studies · logo carousel · latest news · Join the Wave |
| Live QR | Spectrum hero · the seven steps (Broadcast / Live event switch) · what phones can show · proven numbers · the explainer · questions · how we work |
| Solutions | Broadcasters · Teams, leagues and federations · Sponsors and brands · Beyond sport (jump links) · case studies |
| Newsroom | Case studies · every release, award and article with filters · logos |
| About | SportsTech story · leadership (portraits, bios, LinkedIn) · board · quotes · how we work |
| Try Audazzio | The demo on its own page (audazzio.com/try) |
| Join the Wave | The four-step form |
| Privacy | The policy, with the PDF |

Always there: the floating header, **Join the Wave** at the foot of every screen, the **Try Audazzio now** notification on the home page, and the Join the Wave and Try Audazzio dialogs. Old addresses (`/liveqr`, `/broadcasters`, `/teams-leagues`, `/sponsors-brands`, `/applications`, `/resources-audazzio`, `/about-0-0`, `/contact`, `/demo`, `/explainer`) redirect in WordPress (`includes/redirects.php`) and on Vercel (`vercel.json`).

## Try Audazzio now

There is no Audazzio app in the App Store or Google Play (checked October 2026). Phones listen in the browser through Audazzio's web listener (`cdn.audazz.io/tools/browser-decoder/viewer.html?clientId=demo&clientKey=EB`, the page today's hidden `/demo` page sends people to). So the flow is: open the listener on your phone (QR code from a computer, one tap on a phone), tap the logo and allow the microphone, speakers on, press play. The demo picker plays the three clips from `/demo` (Formula 1, rugby sevens, USA Swimming); a phone beside the player shows what would land. Store buttons appear by themselves when App Store / Google Play links are entered under Audazzio > Settings.

## Join the Wave

Four steps: who (name, position, company, email, phone), what (organization type, uses, details), scale (audience, events a year, budget, timing, decision role), review and send. `POST /wp-json/audazzio/v1/join` checks everything again, grades the inquiry A to D from the points in `az_join_questions()` (`includes/components/join.php`), names the engagement ("Broadcast partnership · Enterprise scale"), stores it as a private `az_lead`, emails it (grade A optionally to a second address), and optionally sends it to a HubSpot form (today's site uses HubSpot portal 23876433). Honeypot, a time check and five inquiries an hour per connection keep bots out. Audazzio > Inbox lists, filters, annotates and exports them. On the static preview the form grades in the browser with the same table and says nothing was sent.

## Run it

```bash
scripts/wp/dev.sh          # local WordPress (PHP's server + SQLite + Elementor) at http://localhost:3031
scripts/wp/dev.sh reset    # fresh database, pages imported again (after changing scripts/wp/pages.mjs)
```

Sign in at `/wp-admin` as `rspkt` / `rspkt-local`. Needs PHP 8.1+ with pdo_sqlite, gd and zip.

## Build

```bash
node scripts/media/build.mjs     # pictures and PDFs from media/src (Audazzio's own files) into the plugin
node scripts/media/posters.mjs   # film posters in the site's look
node scripts/media/press.mjs     # the press releases (Word) as clean PDFs
python3 scripts/media/portraits.py   # leadership portraits: cut out, framed alike
node scripts/wp/build.mjs        # CSS, JS, fonts, brand files and seed data into the plugin (--zip for dist/)
node scripts/deploy/export.mjs   # site/ from the running dev site, and vercel.json
node scripts/deploy/serve.mjs    # site/ on http://localhost:3051, as Vercel serves it
node scripts/shots/check.mjs     # end-to-end checks (AZ_ORIGIN=http://localhost:3051 for the static copy)
node scripts/shots/deck.mjs      # pictures for the documents (needs scripts/wp/samples.php run first)
node deck/build.mjs              # the proposal PDF
node docs/build.mjs              # the site guide PDF
```

Edit `wp-src/css` and `wp-src/js`, never the built files in `wp-content/plugins/audazzio-core/assets/{css,js}`. `media/src`, `media/work` and `.wp` are not in the repository.

## Where things are

- `wp-content/themes/audazzio`: the shell (header, footer with "Designed by RSPKT", page templates).
- `wp-content/plugins/audazzio-core`: the design system and every section, each one an Elementor widget built from one schema (`includes/components/*.php`, `elementor/widgets.php`); settings, the inbox and grading (`includes/leads.php`), the importer (`includes/seed.php`).
- `wp-src/js/waves.js`: the wave motion (frequency curtain, dashed wave lines with a signal pulse, signal-emitter rings, spectrum), one animation loop, paused off screen, still for reduced motion.
- `scripts/wp/pages.mjs`: every page as a list of sections. `scripts/content/site.mjs`: films, demos, case studies, news, quotes, with sources. `content/people.json`, `content/logos.json`, `content/privacy.html`.

## Brand system

| Token | Hex | Role |
| --- | --- | --- |
| Ink | `#0C1222` | Text, buttons |
| Slate | `#3B4152` | Body copy |
| Grey | `#6B7080` / `#868A96` | Secondary text, the second tone of headlines |
| Mist | `#F5F5F7` | Alternate sections, panels |
| Hairline | `#E3E4E8` | Rules |
| Wave | `#F26A2E` | Join the Wave and the signal, nothing else |
| Peach | `#F89C68` | The Audazzio symbol |

Type: Inter Tight 600 for headlines (tight, in two tones), Inter for text, Geist Mono for labels. All self-hosted.

## Before this goes public

- The new films and the signal-carrying demo files (MP4 is safest: video sites can strip the high frequencies).
- Higher-resolution portraits for Roy Terracina, Michele Klumb, Greg Flores and Larry Mills. Greg Flores's LinkedIn match is medium confidence; no LinkedIn profile was found for Larry Mills.
- Permission to show each press and partner logo.
- A yes on ® for Live QR and It Comes in Waves (USPTO registrations 7,464,239 and 7,930,855).
- A privacy policy line about microphone use (the listener uses the microphone; the April 2023 policy does not mention it).
- The street name (privacy policy: Callahan Road; USPTO: Callaghan Rd).
- For email delivery on the host, an SMTP plugin with the Microsoft 365 account. DNS is on Route 53; the MX records and audazz.io stay untouched.

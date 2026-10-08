# Handoff: audazzio.com redesign (RSPKT)

Last updated October 8, 2026. Branch `claude/audazzio-site-redesign-zc13lm` on `hitupjones-oss/audazzio` (latest commit `a42792c`). No pull request has been opened.

## Status

**Built:**
- A WordPress + Elementor version of audazzio.com: a theme, the Audazzio Core plugin with 21 Elementor sections, and 8 pages.
- A static copy of the site in `site/` for Vercel.
- The proposal deck and the site guide as PDFs.

**Tested:** the end-to-end checks pass 42/42 on the WordPress site and 42/42 on the static copy. The last full run was after the final fixes.

**Not done:**
- **Live link.** The Vercel project still has to be created (see [Next steps](#next-steps)). Until then audazzio-rspkt.vercel.app returns 404.
- **Second review pass, half not run.** The verifiers for server/admin, dialogs/keyboard and motion/Elementor ran, and their findings are fixed. The verifiers for layout, copy accuracy and fit with the brief stopped at the usage limit. Their ground was covered only by the 42 checks, the deck screenshots and a review of every PDF page.

## Deliverables

| What | Where |
| --- | --- |
| Proposal deck (16 slides, $23,500, support on request, 5–7 business days from receiving credentials) | `deck/Audazzio-Site-Proposal-RSPKT.pdf` |
| Site guide for Audazzio's team (10 pages) | `docs/Audazzio-Site-Guide-RSPKT.pdf` |
| Static site for Vercel | `site/` + `vercel.json` (serves `site/`, noindex, redirects) |
| WordPress theme | `wp-content/themes/audazzio` (zip: `node scripts/wp/build.mjs --zip` → `dist/audazzio.zip`) |
| WordPress plugin | `wp-content/plugins/audazzio-core` (zip: `dist/audazzio-core.zip`, 23.5 MB, mostly case-study PDFs) |
| How to run everything | `README.md` |

`dist/` is gitignored; rebuild the zips with the command above.

## What the site does (the brief, item by item)

- **Look:** white, Apple-like. Ink, slate and two greys; the orange (`#F26A2E`) only for Join the Wave and the signal.
- **Motion:** Klyro-style dashed hairlines drawn on canvas in `wp-src/js/waves.js`:
  - a frequency curtain;
  - wave lines with an orange pulse;
  - signal-emitter rings;
  - a spectrum;
  - Klyro's dashed bracket over the logo carousel.

  It pauses off screen and is still under reduced motion.
- **"It comes in waves."** is the home hero. The **Live QR explanation** is in two places: three steps on the home page with an animated diagram, and the Live QR page (seven steps, Broadcast / Live event).
- **Try Audazzio now:** a push-style notification on the home page opens a sheet with four steps (open the listener, allow the microphone, speakers on, press play). It also has a demo player, a phone mock-up that shows what lands, and a QR code to the listener. There is no public Audazzio app, so phones use Audazzio's web listener (`cdn.audazz.io/.../viewer.html?clientId=demo&clientKey=EB`). Store buttons appear once links are entered in Settings.
- **Join the Wave:**
  - It sits in the header, and in a sticky bar at the foot of the screen from the first screen on phones. The bar steps aside while another Join the Wave button is in view.
  - It opens a four-step funnel: who, what, scale/budget, send.
  - It is graded A–D on the server and stored as private posts.
  - The inbox has statuses, notes and a CSV export. Each inquiry is emailed, and optionally sent to HubSpot.
  - Questions, points and grade lines are editable under Audazzio > Join the Wave form.
- **Press:**
  - the latest four news items on the home page;
  - the Newsroom with filters, 15 items;
  - a greyscale logo carousel with 15 logos.

  News, Case studies and Logos are one shared list each under the Audazzio menu.
- **About:** six cut-out portraits on one backdrop, bios in a dialog, LinkedIn links.
- **Films:** today's YouTube videos as placeholders, five on the home page (the lead is "Audazzio in action", about one minute).

## Decisions made (tell the client)

- **No Audazzio app exists** (checked October 2026), so Try Audazzio uses the web listener. The wording everywhere: the listener today, a partner's app in production.
- **Formula 1 demo clip removed.** Formula One Management blocks it from playing outside YouTube. The demo starts on rugby sevens; USA Swimming is the second clip. An MP4 of the F1 clip would work.
- **Live QR™ kept** (as Audazzio writes it) until counsel approves ®. Its registration is on the Supplemental Register. Audazzio® and It Comes in Waves® keep ®.
- **Removed until confirmed:**
  - "8,000+ live events" and "160+ years" (no source; from an old graphic);
  - the Danny Abelson SVG interview (needs approval);
  - the Tour de France logo (the relationship is only through NBC).
- **Quotes** are labelled "From our advisers and partners", with each speaker's tie to Audazzio in the role line.
- **Award items** show the awarding body (SPORTEL Monaco, The Tech Tribune, Comcast NBCUniversal SportsTech) in the source slot.

## Waiting on the client

The new films and the demo as MP4s. Sharper portraits (Roy, Michele, Greg, Larry). Permission for each logo. Counsel on Live QR®. A privacy-policy line about the microphone. The street name (Callahan vs Callaghan). The two removed stats. The advisers' current roles and the investor names. HubSpot access. Hosting and domain access (DNS on Route 53; leave the MX records and audazz.io alone).

## Next steps

1. **Vercel**
   - At vercel.com/new (team pitch-check), import `hitupjones-oss/audazzio` and name the project `audazzio-rspkt`. Leave the build settings empty.
   - Set the Production Branch to `claude/audazzio-site-redesign-zc13lm`, or merge it into `main`.
   - If the URL ends up different, rebuild the PDFs with `AZ_LIVE=<host> node deck/build.mjs && AZ_LIVE=<host> node docs/build.mjs`.
2. **Optional:** re-run the review pass that did not finish (layout at 1440/1024/768/390, copy against sources, the brief). The findings list and its method are below.
3. **Launch on the client's host** (deck timeline):
   - Day 1: access and a backup.
   - Days 2–3: build on their host (install the theme, the plugin and Elementor; set up an SMTP plugin with Microsoft 365, and HubSpot).
   - Day 4: test.
   - Day 5: DNS switch.
   - Days 6–7: settle in.

## Running it locally

```bash
npm install
scripts/wp/dev.sh            # WordPress on http://localhost:3031 (PHP server + SQLite; admin rspkt / rspkt-local, local only)
scripts/wp/dev.sh reset      # fresh database: install, activate, seed pages, lists and settings
node scripts/wp/build.mjs    # fonts, CSS, JS, images, seed JSON into the plugin (--zip for dist/)
php .wp/wp-cli.phar --allow-root --path=.wp/wordpress eval-file scripts/wp/samples.php   # 5 sample inquiries
node scripts/deploy/export.mjs   # site/ from the running dev site
node scripts/deploy/serve.mjs    # site/ on http://localhost:3051
node scripts/shots/check.mjs                                  # 42 checks on :3031
AZ_ORIGIN=http://localhost:3051 node scripts/shots/check.mjs  # same on the static copy
node scripts/shots/deck.mjs && node deck/build.mjs && node docs/build.mjs   # pictures, then the PDFs (proofs in scripts/shots/out/*-pdf-NN.jpg)
```

## Where things are

- `wp-content/plugins/audazzio-core/includes/`
  - `components/*.php`: one schema plus renderer per section; each is both an Elementor widget and an `[az]` shortcode.
  - `leads.php`: the REST intake (`POST /wp-json/audazzio/v1/join`), grading, email, HubSpot, inbox and CSV.
  - `join-settings.php`: the form editor.
  - `settings.php`: Audazzio > Settings.
  - `lists.php`: News, Case studies and Logos.
  - `meta.php`: the Search and sharing box.
  - `privacy.php`: the exporter and eraser.
  - `seed.php`: the importer.
  - `redirects.php`: the old URLs.
- `wp-src/js/`:
  - `main.js`: `init(root)`, which also hooks Elementor's editor.
  - `ui.js`, `dialogs.js`, `player.js`, `join.js`, `waves.js`.
- `wp-src/css/01–08` (08 holds the reduced-motion rules).
- Content: `scripts/content/site.mjs` (films, demos, cases, news, quotes), `scripts/wp/pages.mjs` (page layouts), `content/people.json`, `content/logos.json`, `content/privacy.html`.
- Docs tooling: `scripts/docs/lib.mjs` (shared PDF code), `deck/build.mjs`, `docs/build.mjs`.
- Test tooling: `scripts/shots/check.mjs`, `deck.mjs`, `ytstub.mjs` (stands in for YouTube in headless runs).

## Gotchas

- **Seeding runs once.** Settings are written once (`az_settings_seeded`) and lists once (`az_lists_seeded`).
  - After changing defaults or `site.mjs`, either run `dev.sh reset`, or:
    - for settings, run `wp option delete az_settings az_settings_seeded`;
    - for lists, run `wp eval 'az_seed_lists( true );'`.
  - In both cases, run `build.mjs` first.
  - Pages only pick up `pages.mjs` changes on a reset.
- **The exporter sends `x-az-static: 1`** so localhost receives the full grading table, which the static preview uses to grade in the browser. Live pages carry labels only.
- **YouTube won't play in headless Chromium** (bot check). The checks and screenshots route through `scripts/shots/ytstub.mjs`. Test real playback in a normal browser.
- **WP-CLI needs `--allow-root`.**
- **`pkill -f <pattern>` can kill your own shell** when the pattern matches the command line. Kill by PID instead.
- **Not in git:** `.wp/`, `media/src/`, `media/work/`, `dist/`, `node_modules/` and the research notes. The research notes lived in the session scratchpad and are gone with this session. The facts they supported are recorded as comments and sources in `scripts/content/site.mjs`, `content/*.json` and the README.

## Review history

- **First pass:** 76 findings across five areas: PHP, JS, copy, layout, brief.
- **Fixes:** made by six parallel fixers, commit `641852e`.
- **Second pass:** verified the server, dialogs and motion areas. Its findings were fixed in `a42792c`:
  - the hourly cap keeps the inquiry and holds only the email;
  - the bar yields to the hero's Join the Wave;
  - the Join sheet leaves room for its sticky buttons;
  - reduced-motion scrolling;
  - no dialogs in the Elementor preview.

**Known low-priority items left:**
- Focus drops to the page body when the open phone menu closes because the window widened past 1000 px.
- Site search matches the seeded shortcode text, not the page copy.
- Renaming a top answer in the form editor drops the inquiry's scale tag.
- The Live QR diagram labels and the "Since SportsTech" line are fixed in code.
- The intake has no CAPTCHA. Consider Cloudflare Turnstile if spam appears.

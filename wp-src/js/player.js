// Try Audazzio: the demo player (with a choice of demo clips), the phone beside it, the four-step
// checklist, the QR code that opens the listener on a phone, and the "Try Audazzio now" notification that
// slides in on the home page. One demo plays at a time: starting one pauses the others and sends the
// notification away.
import qrcode from "qrcode-generator";
import { $, $$, reduced, fresh, holdOn } from "./ui.js";
import { openDialog } from "./dialogs.js";

const CFG = () => window.AZ_CONFIG || {};
let ytApi = null;
/** YouTube's player API. Rejects when it is blocked or slower than ms, and the plain embed plays instead. */
function loadYT(ms = 4000) {
  if (window.YT && window.YT.Player) return Promise.resolve(window.YT);
  ytApi ||= new Promise((resolve, reject) => {
    const prev = window.onYouTubeIframeAPIReady;
    window.onYouTubeIframeAPIReady = () => { prev && prev(); resolve(window.YT); };
    const s = document.createElement("script");
    s.src = "https://www.youtube.com/iframe_api"; s.async = true; s.onerror = reject;
    document.head.appendChild(s);
  });
  return Promise.race([ytApi, new Promise((_, no) => setTimeout(() => no(new Error("timeout")), ms))]);
}

/** The phone simulation: each time the signal lands, the next screen slides in with a toast. */
function Sim(el) {
  if (!el) return { start() {}, stop() {}, clear() {} };
  const shots = $$("[data-az-shot]", el), label = $("[data-az-sim-label]", el);
  let i = -1, first = 0, timer = 0;
  const show = () => {
    i = (i + 1) % shots.length;
    el.classList.add("is-active");
    shots.forEach((s, j) => s.classList.toggle("is-on", j === i));
    if (label) label.textContent = shots[i] && shots[i].alt ? shots[i].alt : "Content received";
    el.classList.remove("is-toast"); void el.offsetWidth; el.classList.add("is-toast");
  };
  const stop = () => { clearTimeout(first); clearInterval(timer); first = timer = 0; };
  return {
    start() { if (timer) return; first = setTimeout(show, 1600); timer = setInterval(show, 6500); },
    stop,
    clear() { stop(); i = -1; el.classList.remove("is-active", "is-toast"); shots.forEach((s) => s.classList.remove("is-on")); },
  };
}

// The demo that is playing or loading, whether any demo has started on this page, and what sends the
// notification away.
let current = null, tried = false, hush = () => {};
function claim(player) {
  if (current && current !== player) current.pause();
  current = player;
  if (!tried) { tried = true; hush(); }
}

const ICON = { play: "M8 5.5v13l11-6.5-11-6.5Z", pause: "M8 5h3v14H8zM13 5h3v14h-3z", stop: "M7 7h10v10H7z" };

function setupPlayer(p) {
  const media = $("[data-az-player-media]", p), stage = p.closest(".az-try__stage, .az-sheet__stage");
  const sim = Sim(stage ? $("[data-az-sim]", stage) : null);
  const scope = p.closest(".az-try, .az-dialog");
  const steps = scope ? $("[data-az-trysteps]", scope) : null;
  const big = $("[data-az-play]", p), toggle = $("[data-az-toggle]", p);
  const clips = CFG().demos && CFG().demos.length ? CFG().demos : [CFG().demo || {}];
  // run counts loads: anything that answers for an older one (a late API, a destroyed player) is ignored
  let clip = 0, yt = null, video = null, frame = null, started = false, playing = false, pending = false, run = 0, wait = 0;
  const me = { pause: () => pause(), reset: () => reset() };

  // the control under the screen says what pressing it does (the plain embed cannot pause, only stop)
  function label() {
    if (!toggle) return;
    const k = frame ? "stop" : playing || pending ? "pause" : "play";
    $("span", toggle).textContent = k === "stop" ? "Stop" : k === "pause" ? "Pause" : "Play";
    const ico = $("path", toggle);
    ico && ico.setAttribute("d", ICON[k]);
  }
  function state(on) {
    playing = on; pending = false;
    p.classList.toggle("is-playing", on);
    stage && stage.classList.toggle("is-playing", on);
    on ? sim.start() : sim.stop();
    if (on) { claim(me); steps && markSteps(steps, 3); }
    label();
  }
  const busy = (on) => p.classList.toggle("is-loading", on);
  // empty the screen: the clip, its player and any message go
  function clear() {
    run++; clearTimeout(wait);
    try { yt && yt.destroy && yt.destroy(); } catch {}
    try { video && video.pause(); } catch {}
    yt = video = frame = null;
    media.replaceChildren();
    busy(false);
    p.classList.remove("is-error");
  }
  // back to the poster and the big play button
  function reset() {
    const back = !!toggle && (toggle === document.activeElement || media.contains(document.activeElement));
    clear(); state(false); sim.clear();
    started = false;
    p.classList.remove("is-started");
    if (big) { big.removeAttribute("tabindex"); big.removeAttribute("aria-hidden"); }
    if (toggle) toggle.hidden = true;
    if (back && big) big.focus({ preventScroll: true });
    if (current === me) current = null;
  }
  function pause() {
    if (frame || p.classList.contains("is-loading")) return reset();
    try { video && video.pause(); yt && yt.pauseVideo && yt.pauseVideo(); } catch {}
    state(false);
  }
  function fail(d) {
    const back = !!toggle && (toggle === document.activeElement || media.contains(document.activeElement));
    clear(); state(false);
    p.classList.add("is-error");
    if (toggle) toggle.hidden = true;
    const box = document.createElement("div"), t = document.createElement("p");
    box.className = "az-player__err"; box.setAttribute("role", "status");
    box.append(t);
    const href = d.type === "youtube" ? `https://www.youtube.com/watch?v=${encodeURIComponent(d.id)}` : d.src;
    let a = null;
    if (href) {
      a = document.createElement("a");
      a.className = "az-btn az-btn--sm az-player__out"; a.href = href; a.target = "_blank"; a.rel = "noopener";
      a.textContent = d.type === "youtube" ? "Watch on YouTube" : "Open the video";
      box.append(a);
    }
    media.replaceChildren(box);
    // filled a moment later, so screen readers announce it
    setTimeout(() => { t.textContent = "This demo can’t play here."; }, 60);
    if (back) { if (!a) box.tabIndex = -1; (a || box).focus({ preventScroll: true }); }
    if (current === me) current = null;
  }
  async function load() {
    const d = clips[clip] || {}, my = ++run, live = (fn) => (e) => { if (my === run) fn(e); };
    started = true; pending = true;
    claim(me);
    p.classList.add("is-started");
    if (big) { big.tabIndex = -1; big.setAttribute("aria-hidden", "true"); }
    if (toggle) { toggle.hidden = false; if (document.activeElement === big) toggle.focus({ preventScroll: true }); }
    label();
    if (d.type === "file" && d.src) {
      video = document.createElement("video");
      video.src = d.src; video.playsInline = true; video.controls = true; video.autoplay = true;
      video.addEventListener("playing", live(() => { busy(false); state(true); }));
      video.addEventListener("pause", live(() => state(false)));
      video.addEventListener("ended", live(() => state(false)));
      video.addEventListener("error", live(() => fail(d)));
      busy(true);
      media.replaceChildren(video);
      video.play().catch(live(() => { busy(false); state(false); }));
    } else if (d.type === "youtube" && d.id) {
      const host = document.createElement("div");
      media.replaceChildren(host);
      busy(true);
      const YT = await loadYT().catch(() => null);
      // the sheet closed, the clip changed or another demo started while the API loaded
      if (my !== run) return;
      if (YT) {
        yt = new YT.Player(host, {
          host: "https://www.youtube-nocookie.com", videoId: d.id,
          playerVars: { autoplay: 1, playsinline: 1, rel: 0, modestbranding: 1 },
          events: {
            // where autoplay is not allowed, YouTube's own play button takes over after a moment
            onReady: live(() => { busy(false); wait = setTimeout(live(() => { if (pending) { pending = false; label(); } }), 2500); }),
            onStateChange: live((e) => { if (e.data === 1) state(true); else if (e.data === 0 || e.data === 2 || e.data === 5) state(false); }),
            onError: live(() => fail(d)),
          },
        });
      } else {
        frame = document.createElement("iframe");
        frame.src = `https://www.youtube-nocookie.com/embed/${encodeURIComponent(d.id)}?autoplay=1&rel=0&playsinline=1`;
        frame.allow = "autoplay; encrypted-media; fullscreen"; frame.title = "Audazzio demo";
        media.replaceChildren(frame);
        busy(false);
        state(true);
      }
    } else {
      // no clip set yet: the phone still shows what would arrive
      state(true);
    }
  }
  big && big.addEventListener("click", () => { if (!started) load(); });
  toggle && toggle.addEventListener("click", () => {
    if (frame || p.classList.contains("is-loading")) return reset();
    if (playing || pending) return pause();
    pending = true; claim(me); label();
    if (video) video.play().catch(() => { pending = false; label(); });
    else if (yt && yt.playVideo) yt.playVideo();
    else state(true);
  });
  $$("[data-az-clip]", p).forEach((b) => b.addEventListener("click", () => {
    clip = +b.dataset.azClip;
    $$("[data-az-clip]", p).forEach((x) => x.setAttribute("aria-pressed", x === b ? "true" : "false"));
    if (started) { reset(); load(); }
  }));
  // nothing plays on in a closed sheet
  const dlg = p.closest("dialog");
  if (dlg) dlg.addEventListener("az:closed", reset);
}

function markSteps(list, upTo) {
  $$("[data-az-trystep]", list).forEach((s, j) => { s.classList.toggle("is-done", j < upTo); s.classList.toggle("is-on", j === upTo); });
}

function setupSteps(list) {
  $$("[data-az-trystep]", list).forEach((s, j) => {
    s.addEventListener("click", (e) => {
      if (e.target.closest("a, button, .az-qr")) return;
      const done = s.classList.contains("is-done");
      markSteps(list, done ? j : j + 1);
    });
  });
}

function qr(el) {
  const box = $(".az-qr__code", el);
  if (!box || box.firstChild) return;
  try {
    const q = qrcode(0, "M");
    q.addData(el.dataset.azQr);
    q.make();
    box.innerHTML = q.createSvgTag({ cellSize: 3, margin: 0, scalable: true });
  } catch { el.hidden = true; }
}

/** /try/?get=app on a phone (older printed QR codes): straight to the listener, or the store when an app exists. */
function storeHop() {
  const p = new URLSearchParams(location.search);
  if (p.get("get") !== "app") return;
  const ua = navigator.userAgent || "", apps = CFG().apps || {};
  const ios = /iPhone|iPad|iPod/i.test(ua) || (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1);
  const url = (ios ? apps.ios : /Android/i.test(ua) ? apps.android : "") || (/Mobi|Android|iPhone|iPad/i.test(ua) ? CFG().listener : "");
  if (url) location.replace(url);
}

/**
 * The "Try Audazzio now" card on the home page. It comes in after the delay set under Settings, goes after
 * eight seconds (not while the pointer or keyboard is on it), when the visitor scrolls past the hero, or when
 * a demo starts, and once gone it stays gone for the visit. A status line tells screen readers it arrived.
 */
function notify() {
  const n = $("[data-az-notify]"), say = $("[data-az-notify-say]"), cfg = CFG().notify || {};
  if (!n || !cfg.on || !document.body.classList.contains("az-home")) return;
  const seen = () => { try { return sessionStorage.getItem("az-notify") === "x"; } catch { return false; } };
  const note = () => { try { sessionStorage.setItem("az-notify", "x"); } catch {} };
  if (seen()) return;
  const html = document.documentElement, hero = $(".az-hero");
  const past = () => (hero ? hero.getBoundingClientRect().bottom < innerHeight / 2 : scrollY > innerHeight / 2);
  let on = false, timer = 0;
  const later = (ms) => { clearTimeout(timer); timer = setTimeout(hide, ms); };
  const scrolled = () => { if (past() && !n.contains(document.activeElement)) hide(); };
  function show() {
    if (tried || seen() || past()) return;
    if (html.classList.contains("az-modal") || html.classList.contains("az-lock")) { timer = setTimeout(show, 3000); return; }
    on = true;
    n.hidden = false;
    html.classList.add("az-notifying");
    requestAnimationFrame(() => requestAnimationFrame(() => n.classList.add("is-in")));
    const t = ($(".az-notify__t", n) || {}).textContent || "", p = ($(".az-notify__p", n) || {}).textContent || "";
    setTimeout(() => { if (on && say) say.textContent = t && p ? `${t}${/[.?!:]$/.test(t) ? "" : "."} ${p}` : t || p; }, 300);
    addEventListener("scroll", scrolled, { passive: true });
    later(8000);
  }
  function hide() {
    clearTimeout(timer);
    if (!on) return;
    on = false;
    note();
    removeEventListener("scroll", scrolled);
    n.classList.remove("is-in");
    html.classList.remove("az-notifying");
    if (say) say.textContent = "";
    setTimeout(() => { if (!on) n.hidden = true; }, 400);
  }
  hush = () => { note(); hide(); };
  holdOn(n, (held) => { if (on) held ? clearTimeout(timer) : later(4000); });
  timer = setTimeout(show, Math.max(0, cfg.delay || 0) * 1000);
  const x = $("[data-az-notify-x]", n);
  x && x.addEventListener("click", () => {
    const kb = x.matches(":focus-visible");
    hide();
    // the card sits first on the page: a keyboard carries on from the skip link
    const next = kb && $(".az-skip");
    next && next.focus();
  });
  $(".az-notify__card", n).addEventListener("click", () => hide());
}

let booted = false;
export function tryAudazzio(root = document) {
  fresh(root, "[data-az-player]", "__az").forEach(setupPlayer);
  fresh(root, "[data-az-trysteps]", "__az").forEach(setupSteps);
  fresh(root, "[data-az-qr]", "__az").forEach(qr);
  if (booted) return;
  booted = true;
  storeHop();
  notify();
  // a link to /try/#try-now from elsewhere opens the sheet straight away
  if (location.hash === "#try-now") openDialog("az-try");
  if (reduced()) document.documentElement.classList.add("az-reduced");
}

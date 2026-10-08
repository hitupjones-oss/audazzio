// Try Audazzio: the demo player, the phone beside it, the four-step checklist, the QR code to get the
// app, and the "Try Audazzio now" notification that slides in on the home page.
import qrcode from "qrcode-generator";
import { $, $$, reduced } from "./ui.js";
import { openDialog } from "./dialogs.js";

const CFG = () => window.AZ_CONFIG || {};
let ytApi = null;
function loadYT() {
  if (ytApi) return ytApi;
  ytApi = new Promise((resolve, reject) => {
    if (window.YT && window.YT.Player) return resolve(window.YT);
    const prev = window.onYouTubeIframeAPIReady;
    window.onYouTubeIframeAPIReady = () => { prev && prev(); resolve(window.YT); };
    const s = document.createElement("script");
    s.src = "https://www.youtube.com/iframe_api"; s.async = true; s.onerror = reject;
    document.head.appendChild(s);
    setTimeout(() => reject(new Error("timeout")), 8000);
  });
  return ytApi;
}

/** The phone simulation: each time the signal lands, the next screen slides in with a toast. */
function Sim(el) {
  if (!el) return { start() {}, stop() {} };
  const shots = $$("[data-az-shot]", el), label = $("[data-az-sim-label]", el);
  let i = -1, timer = 0;
  const show = () => {
    i = (i + 1) % shots.length;
    el.classList.add("is-active");
    shots.forEach((s, j) => s.classList.toggle("is-on", j === i));
    if (label) label.textContent = shots[i] && shots[i].alt ? shots[i].alt : "Content received";
    el.classList.remove("is-toast"); void el.offsetWidth; el.classList.add("is-toast");
  };
  return {
    start() { clearInterval(timer); setTimeout(show, 1600); timer = setInterval(show, 6500); },
    stop() { clearInterval(timer); },
  };
}

function setupPlayer(p) {
  const media = $("[data-az-player-media]", p), stage = p.closest(".az-try__stage, .az-sheet__stage");
  const sim = Sim(stage ? $("[data-az-sim]", stage) : null);
  const scope = p.closest(".az-try, .az-dialog");
  const steps = scope ? $("[data-az-trysteps]", scope) : null;
  let yt = null, video = null, started = false;
  const state = (playing) => {
    p.classList.toggle("is-playing", playing);
    stage && stage.classList.toggle("is-playing", playing);
    playing ? sim.start() : sim.stop();
    if (playing && steps) markSteps(steps, 3);
  };
  const start = async () => {
    const d = CFG().demo || {};
    if (!started) {
      started = true;
      p.classList.add("is-started");
      if (d.type === "file" && d.src) {
        video = document.createElement("video");
        video.src = d.src; video.playsInline = true; video.controls = true; video.autoplay = true;
        video.addEventListener("play", () => state(true));
        video.addEventListener("pause", () => state(false));
        video.addEventListener("ended", () => state(false));
        media.replaceChildren(video);
        video.play().catch(() => {});
      } else if (d.type === "youtube" && d.id) {
        const host = document.createElement("div");
        media.replaceChildren(host);
        try {
          const YT = await loadYT();
          yt = new YT.Player(host, {
            host: "https://www.youtube-nocookie.com", videoId: d.id,
            playerVars: { autoplay: 1, playsinline: 1, rel: 0, modestbranding: 1 },
            events: { onStateChange: (e) => state(e.data === 1 || e.data === 3) },
          });
          state(true);
        } catch {
          const f = document.createElement("iframe");
          f.src = `https://www.youtube-nocookie.com/embed/${encodeURIComponent(d.id)}?autoplay=1&rel=0&playsinline=1`;
          f.allow = "autoplay; encrypted-media; fullscreen"; f.title = "Audazzio demo";
          media.replaceChildren(f);
          state(true);
        }
      } else {
        // no clip set yet: the phone still shows what would arrive
        state(true);
      }
    } else if (video) { video.paused ? video.play() : video.pause(); }
    else if (yt && yt.getPlayerState) { yt.getPlayerState() === 1 ? yt.pauseVideo() : yt.playVideo(); }
    else state(!p.classList.contains("is-playing"));
  };
  $$("[data-az-play]", p).forEach((b) => b.addEventListener("click", start));
  // stop the sound when the sheet closes
  const dlg = p.closest("dialog");
  if (dlg) dlg.addEventListener("az:closed", () => { try { video && video.pause(); yt && yt.pauseVideo && yt.pauseVideo(); } catch {} state(false); });
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

function qr() {
  $$("[data-az-qr]").forEach((el) => {
    const box = $(".az-qr__code", el);
    if (!box || box.firstChild) return;
    try {
      const q = qrcode(0, "M");
      q.addData(el.dataset.azQr);
      q.make();
      box.innerHTML = q.createSvgTag({ cellSize: 3, margin: 0, scalable: true });
    } catch { el.hidden = true; }
  });
}

/** /try/?get=app on a phone: straight to the right store. */
function storeHop() {
  const p = new URLSearchParams(location.search);
  if (p.get("get") !== "app") return;
  const ua = navigator.userAgent || "", apps = CFG().apps || {};
  const ios = /iPhone|iPad|iPod/i.test(ua) || (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1);
  const url = ios ? apps.ios : /Android/i.test(ua) ? apps.android : "";
  if (url) location.replace(url);
}

function notify() {
  const n = $("[data-az-notify]"), cfg = (CFG().notify || {});
  if (!n || !cfg.on || !document.body.classList.contains("az-home")) return;
  let gone = false;
  try { gone = sessionStorage.getItem("az-notify") === "x"; } catch {}
  if (gone) return;
  const show = () => {
    if (document.documentElement.classList.contains("az-modal")) return setTimeout(show, 3000);
    n.hidden = false;
    requestAnimationFrame(() => requestAnimationFrame(() => n.classList.add("is-in")));
  };
  const hide = () => { n.classList.remove("is-in"); setTimeout(() => { n.hidden = true; }, 400); try { sessionStorage.setItem("az-notify", "x"); } catch {} };
  setTimeout(show, Math.max(0, cfg.delay || 0) * 1000);
  const x = $("[data-az-notify-x]", n);
  x && x.addEventListener("click", hide);
  $(".az-notify__card", n).addEventListener("click", () => hide());
}

export function tryAudazzio() {
  storeHop();
  $$("[data-az-player]").forEach(setupPlayer);
  $$("[data-az-trysteps]").forEach(setupSteps);
  qr();
  notify();
  // a link to /try/#now from elsewhere opens the sheet straight away
  if (location.hash === "#try-now") openDialog("az-try");
  if (reduced()) document.documentElement.classList.add("az-reduced");
}

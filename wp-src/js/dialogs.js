// The dialogs: Try Audazzio, Join the Wave, a film, a biography. Native <dialog>, opened by any link to
// /try/ or /join/ (plain links still work without script), by film cards and by "Read bio".
import { $, $$ } from "./ui.js";

let lastFocus = null;

export function openDialog(id, opener) {
  const d = document.getElementById(id);
  if (!d || typeof d.showModal !== "function") return false;
  if (d.open) return true;
  $$("dialog[open]").forEach((o) => closeDialog(o));
  lastFocus = opener || document.activeElement;
  d.showModal();
  document.documentElement.classList.add("az-modal");
  document.dispatchEvent(new CustomEvent("az:modal", { detail: { id, open: true } }));
  requestAnimationFrame(() => d.classList.add("is-open"));
  return true;
}

export function closeDialog(d) {
  if (!d || !d.open) return;
  d.classList.remove("is-open");
  const done = () => {
    d.close();
    document.documentElement.classList.remove("az-modal");
    document.dispatchEvent(new CustomEvent("az:modal", { detail: { id: d.id, open: false } }));
    d.dispatchEvent(new Event("az:closed"));
    if (lastFocus && lastFocus.focus) lastFocus.focus({ preventScroll: true });
  };
  setTimeout(done, window.matchMedia("(prefers-reduced-motion: reduce)").matches ? 0 : 220);
}

const plainClick = (e) => !(e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button > 0);
const here = (path) => location.pathname.replace(/\/+$/, "/") === path;

export function dialogs() {
  $$("dialog[data-az-dialog]").forEach((d) => {
    d.addEventListener("cancel", (e) => { e.preventDefault(); closeDialog(d); });
    d.addEventListener("click", (e) => { if (e.target === d || e.target.closest("[data-az-close]")) closeDialog(d); });
  });

  document.addEventListener("click", (e) => {
    const t = e.target.closest("[data-az-try], [data-az-join], [data-az-video], [data-az-bio]");
    if (!t || !plainClick(e)) return;
    if (t.hasAttribute("data-az-try")) {
      if (here("/try/")) { const s = $("[data-az-try-section]"); if (s) { e.preventDefault(); s.scrollIntoView({ behavior: "smooth" }); } return; }
      if (openDialog("az-try", t)) e.preventDefault();
    } else if (t.hasAttribute("data-az-join")) {
      if (here("/join/") || !document.getElementById("az-join")) { const f = $(".az-joinsec"); if (f) { e.preventDefault(); f.scrollIntoView({ behavior: "smooth" }); } return; }
      if (openDialog("az-join", t)) { e.preventDefault(); setTimeout(() => { const i = $("#az-join input:not([type=hidden])"); i && i.focus({ preventScroll: true }); }, 260); }
    } else if (t.hasAttribute("data-az-video")) {
      e.preventDefault();
      film(t);
    } else if (t.hasAttribute("data-az-bio")) {
      e.preventDefault();
      const tpl = document.getElementById(t.dataset.azBio), box = $("[data-az-biobox]");
      if (!tpl || !box) return;
      box.replaceChildren(tpl.content.cloneNode(true));
      const name = $(".az-bio__name", box);
      const title = $("#az-bio-title");
      if (name && title) title.textContent = name.textContent;
      openDialog("az-bio", t);
    }
  });

  // a film's dialog empties when it closes, so the sound stops
  const fd = document.getElementById("az-film");
  if (fd) fd.addEventListener("az:closed", () => { const b = $("[data-az-filmbox]", fd); b && b.replaceChildren(); });
}

function film(btn) {
  const box = $("[data-az-filmbox]");
  if (!box) return;
  const kind = btn.dataset.azVideo, title = btn.dataset.azTitle || "Film";
  let el;
  if (kind === "youtube") {
    el = document.createElement("iframe");
    el.src = `https://www.youtube-nocookie.com/embed/${encodeURIComponent(btn.dataset.azId)}?autoplay=1&rel=0&playsinline=1&modestbranding=1`;
    el.allow = "autoplay; encrypted-media; picture-in-picture; fullscreen";
    el.allowFullscreen = true;
    el.title = title;
  } else if (kind === "file") {
    el = document.createElement("video");
    el.src = btn.dataset.azSrc; el.controls = true; el.autoplay = true; el.playsInline = true;
  } else return;
  box.replaceChildren(el);
  const t = $("#az-film-title"); if (t) t.textContent = title;
  openDialog("az-film", btn);
}

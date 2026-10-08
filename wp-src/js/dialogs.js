// The dialogs: Try Audazzio, Join the Wave, a film, a biography. Native <dialog>, opened by any link to
// /try/ or /join/ (plain links still work without script), by film cards and by "Read bio".
import { $, $$, reduced, fresh } from "./ui.js";

// Where focus goes when a dialog closes, best first: what opened it, then (for a dialog opened from inside
// another) what opened that one. When none of them is on screen any more, a control in the header.
const back = new Map();
const shown = (el) => !!(el && el.focus && el.isConnected && el.getClientRects().length && !el.closest("[inert], [aria-hidden='true']"));
function fallback(d, chain) {
  const fromMenu = chain.some((el) => el && el.closest && el.closest("#az-menu"));
  const own = d.id === "az-join" ? ".az-nav__join" : d.id === "az-try" ? ".az-nav__try" : "";
  return (fromMenu ? [".az-nav__burger", own] : [own, ".az-nav__burger"]).concat(".az-nav__brand").filter(Boolean).map((s) => $(s)).find(shown);
}

export function openDialog(id, opener) {
  const d = document.getElementById(id);
  if (!d || typeof d.showModal !== "function") return false;
  if (d.open) {
    if (d.__azClosing) { clearTimeout(d.__azClosing); d.__azClosing = 0; d.classList.add("is-open"); }
    return true;
  }
  const from = opener || document.activeElement, chain = [from];
  // opened from inside another dialog: that one closes, and this one hands focus back to where that one came from
  $$("dialog[open]").forEach((o) => { if (o.contains(from)) chain.push(...(back.get(o) || [])); closeDialog(o); });
  back.set(d, chain);
  d.showModal();
  document.documentElement.classList.add("az-modal");
  document.dispatchEvent(new CustomEvent("az:modal", { detail: { id, open: true } }));
  requestAnimationFrame(() => d.classList.add("is-open"));
  return true;
}

export function closeDialog(d) {
  if (!d || !d.open || d.__azClosing) return;
  d.classList.remove("is-open");
  const done = () => {
    d.__azClosing = 0;
    d.close();
    const chain = back.get(d) || [];
    back.delete(d);
    // another dialog may have opened in the meantime: the page stays locked and focus stays there
    const last = !$("dialog[open]");
    if (last) {
      document.documentElement.classList.remove("az-modal");
      document.dispatchEvent(new CustomEvent("az:modal", { detail: { id: d.id, open: false } }));
    }
    d.dispatchEvent(new Event("az:closed"));
    if (!last) return;
    const to = chain.find(shown) || fallback(d, chain);
    if (to) to.focus({ preventScroll: true });
  };
  d.__azClosing = setTimeout(done, reduced() ? 0 : 220);
}

const plainClick = (e) => !(e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button > 0);
const here = (path) => location.pathname.replace(/\/+$/, "/") === path;
let delegated = false;

export function dialogs(root = document) {
  fresh(root, "dialog[data-az-dialog]", "__azDialog").forEach((d) => {
    d.addEventListener("cancel", (e) => { e.preventDefault(); closeDialog(d); });
    // the backdrop closes the dialog only when the press started there too (not a text selection dragged out)
    let down = null;
    d.addEventListener("pointerdown", (e) => { down = e.target; });
    d.addEventListener("click", (e) => {
      if (e.target.closest("[data-az-close]") || (e.target === d && down === d)) closeDialog(d);
      down = null;
    });
    // a film's dialog empties when it closes, so the sound stops
    if (d.id === "az-film") d.addEventListener("az:closed", () => { const b = $("[data-az-filmbox]", d); b && b.replaceChildren(); });
  });
  if (delegated) return;
  delegated = true;

  document.addEventListener("click", (e) => {
    const t = e.target.closest("[data-az-try], [data-az-join], [data-az-video], [data-az-bio]");
    if (!t || !plainClick(e) || document.body.classList.contains("elementor-editor-active")) return;
    if (t.hasAttribute("data-az-try")) {
      if (here("/try/")) { const s = $("[data-az-try-section]"); if (s) { e.preventDefault(); s.scrollIntoView({ behavior: reduced() ? "auto" : "smooth" }); } return; }
      if (openDialog("az-try", t)) e.preventDefault();
    } else if (t.hasAttribute("data-az-join")) {
      if (here("/join/") || !document.getElementById("az-join")) { const f = $(".az-joinsec"); if (f) { e.preventDefault(); f.scrollIntoView({ behavior: reduced() ? "auto" : "smooth" }); } return; }
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

// Small behaviours shared by every page: reveal on scroll, the header, the menu, the Join the Wave bar,
// counters, rails, the logo marquee, quotes, filters, the step diagram and the Live QR flow.
export const $ = (s, r = document) => r.querySelector(s);
export const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
export const reduced = () => window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

/** Run fn once when el is on screen. */
export function onSeen(el, fn, margin = "0px 0px -12% 0px") {
  if (!("IntersectionObserver" in window)) return fn(el);
  const io = new IntersectionObserver((es) => es.forEach((e) => { if (e.isIntersecting) { io.unobserve(e.target); fn(e.target); } }), { rootMargin: margin });
  io.observe(el);
}

/** Keep a flag on el while it is on screen. */
export function whileSeen(el, cb) {
  if (!("IntersectionObserver" in window)) return cb(true);
  new IntersectionObserver((es) => es.forEach((e) => cb(e.isIntersecting)), { threshold: 0.2 }).observe(el);
}

export function rise() {
  // siblings rise one after another
  $$("[data-az-rise]").forEach((el) => {
    const sibs = el.parentElement ? Array.from(el.parentElement.children).filter((c) => c.hasAttribute("data-az-rise")) : [el];
    el.style.setProperty("--az-i", String(Math.min(sibs.indexOf(el), 8)));
    onSeen(el, (t) => t.classList.add("is-in"));
  });
}

export function header() {
  const nav = $("[data-az-nav]");
  if (!nav) return;
  const set = () => nav.classList.toggle("is-scrolled", window.scrollY > 8);
  set();
  window.addEventListener("scroll", set, { passive: true });
  const burger = $(".az-nav__burger", nav), menu = $("#az-menu");
  if (!burger || !menu) return;
  const open = (on) => {
    burger.setAttribute("aria-expanded", on ? "true" : "false");
    burger.setAttribute("aria-label", on ? "Close the menu" : "Open the menu");
    menu.hidden = !on;
    nav.classList.toggle("is-open", on);
    document.documentElement.classList.toggle("az-lock", on);
  };
  burger.addEventListener("click", () => open(burger.getAttribute("aria-expanded") !== "true"));
  menu.addEventListener("click", (e) => { if (e.target.closest("a")) open(false); });
  document.addEventListener("keydown", (e) => { if (e.key === "Escape" && !menu.hidden) { open(false); burger.focus(); } });
  window.addEventListener("resize", () => { if (window.innerWidth > 900 && !menu.hidden) open(false); });
}

/** The Join the Wave bar: in after the first screen, out when the closing band or the footer is on screen. */
export function bar() {
  const el = $("[data-az-bar]");
  if (!el) return;
  if (document.body.classList.contains("az-page-join")) { el.remove(); return; }
  const cta = el.querySelector("a");
  let blockers = 0, past = false;
  const sync = () => {
    const on = past && blockers === 0 && !document.documentElement.classList.contains("az-modal");
    el.classList.toggle("is-on", on);
    el.setAttribute("aria-hidden", on ? "false" : "true");
    if (cta) cta.tabIndex = on ? 0 : -1;
  };
  const check = () => { past = window.scrollY > Math.min(window.innerHeight * 0.6, 520); sync(); };
  window.addEventListener("scroll", check, { passive: true });
  check();
  $$(".az-cta, .az-foot, .az-joinsec").forEach((b) => {
    let seen = false;
    new IntersectionObserver((es) => es.forEach((e) => {
      if (e.isIntersecting && !seen) { seen = true; blockers++; }
      else if (!e.isIntersecting && seen) { seen = false; blockers--; }
      sync();
    }), { threshold: 0.15 }).observe(b);
  });
  document.addEventListener("az:modal", sync);
}

export function counters() {
  $$("[data-az-count]").forEach((el) => {
    const end = parseFloat(el.dataset.azCount);
    if (!isFinite(end) || reduced()) return;
    const txt = el.textContent, dec = (txt.split(".")[1] || "").length, comma = /,/.test(txt);
    const fmt = (v) => { const s = v.toFixed(dec); return comma ? Number(s).toLocaleString("en-US", { minimumFractionDigits: dec }) : s; };
    el.textContent = fmt(0);
    onSeen(el, () => {
      const t0 = performance.now(), dur = 1400;
      const step = (now) => { const p = Math.min(1, (now - t0) / dur), e = 1 - Math.pow(1 - p, 4); el.textContent = fmt(end * e); if (p < 1) requestAnimationFrame(step); else el.textContent = txt; };
      requestAnimationFrame(step);
    });
  });
}

export function rails() {
  $$("[data-az-rail]").forEach((r) => {
    const track = $(".az-rail__track", r), prev = $("[data-az-rail-prev]", r), next = $("[data-az-rail-next]", r);
    if (!track) return;
    const by = (d) => track.scrollBy({ left: d * Math.max(280, track.clientWidth * 0.8), behavior: reduced() ? "auto" : "smooth" });
    prev && prev.addEventListener("click", () => by(-1));
    next && next.addEventListener("click", () => by(1));
    const sync = () => {
      const max = track.scrollWidth - track.clientWidth - 2;
      r.classList.toggle("is-scrollable", max > 0);
      prev && (prev.disabled = track.scrollLeft <= 2);
      next && (next.disabled = track.scrollLeft >= max);
    };
    track.addEventListener("scroll", sync, { passive: true });
    window.addEventListener("resize", sync);
    sync();
  });
}

export function marquee() {
  $$("[data-az-marquee]").forEach((m) => whileSeen(m, (on) => m.classList.toggle("is-running", on)));
}

export function quotes() {
  $$("[data-az-quotes]").forEach((sec) => {
    const qs = $$("[data-az-quote]", sec), dots = $$("[data-az-quote-go]", sec);
    if (qs.length < 2) return;
    let i = 0, timer = 0, visible = false;
    const go = (n) => {
      i = (n + qs.length) % qs.length;
      qs.forEach((q, j) => { q.classList.toggle("is-on", j === i); q.setAttribute("aria-hidden", j === i ? "false" : "true"); });
      dots.forEach((d, j) => d.setAttribute("aria-selected", j === i ? "true" : "false"));
    };
    const loop = () => { clearTimeout(timer); if (visible && !reduced()) timer = setTimeout(() => { go(i + 1); loop(); }, 7000); };
    dots.forEach((d) => d.addEventListener("click", () => { go(+d.dataset.azQuoteGo); loop(); }));
    whileSeen(sec, (on) => { visible = on; loop(); });
  });
}

export function filters() {
  $$("[data-az-press]").forEach((sec) => {
    const bs = $$("[data-az-filter]", sec), items = $$(".az-new", sec);
    bs.forEach((b) => b.addEventListener("click", () => {
      bs.forEach((x) => x.setAttribute("aria-pressed", x === b ? "true" : "false"));
      const k = b.dataset.azFilter;
      items.forEach((it) => { it.hidden = !!k && it.dataset.kind !== k; });
    }));
  });
}

/** The three-step explainer: the diagram plays embed, play, deliver, and the list follows. */
export function steps() {
  $$("[data-az-steps]").forEach((sec) => {
    const items = $$("[data-az-step]", sec), fig = $(".az-steps__fig", sec);
    if (!items.length) return;
    let i = 0, timer = 0, visible = false;
    const set = (n) => {
      i = n % items.length;
      items.forEach((it, j) => { it.classList.toggle("is-on", j === i); it.classList.toggle("is-done", j < i); });
      if (fig) fig.dataset.phase = String(i + 1);
    };
    const loop = () => { clearTimeout(timer); if (visible) timer = setTimeout(() => { set(i + 1); loop(); }, reduced() ? 6000 : 2800); };
    items.forEach((it, j) => it.addEventListener("click", () => { set(j); loop(); }));
    set(0);
    whileSeen(sec, (on) => { visible = on; sec.classList.toggle("is-live", on); loop(); });
  });
}

/** Live QR flow: Broadcast / Live event switch, and the steps light up one after another. */
export function flow() {
  $$("[data-az-flow]").forEach((sec) => {
    const tabs = $$('[role="tab"]', sec), panels = $$('[role="tabpanel"]', sec), seg = $(".az-seg", sec);
    let timer = 0, visible = false, k = 0;
    const panel = () => panels.find((p) => !p.hidden);
    const light = () => {
      const p = panel(); if (!p) return;
      const st = $$(".az-flow__step", p);
      st.forEach((s, j) => { s.classList.toggle("is-on", j === k); s.classList.toggle("is-done", j < k); });
      p.style.setProperty("--az-flow-p", String(st.length > 1 ? k / (st.length - 1) : 1));
    };
    const loop = () => { clearTimeout(timer); if (!visible) return; timer = setTimeout(() => { const n = $$(".az-flow__step", panel()).length; k = (k + 1) % n; light(); loop(); }, reduced() ? 4000 : 1700); };
    const select = (tab) => {
      tabs.forEach((t, j) => { const on = t === tab; t.setAttribute("aria-selected", on ? "true" : "false"); t.tabIndex = on ? 0 : -1; panels[j].hidden = !on; });
      if (seg) seg.dataset.on = String(tabs.indexOf(tab));
      k = 0; light(); loop();
    };
    tabs.forEach((t, j) => {
      t.addEventListener("click", () => select(t));
      t.addEventListener("keydown", (e) => { if (e.key === "ArrowRight" || e.key === "ArrowLeft") { const n = tabs[(j + (e.key === "ArrowRight" ? 1 : -1) + tabs.length) % tabs.length]; n.focus(); select(n); } });
    });
    $$(".az-flow__step", sec).forEach((s) => s.addEventListener("click", () => { const st = $$(".az-flow__step", panel()); const n = st.indexOf(s); if (n >= 0) { k = n; light(); loop(); } }));
    if (seg) seg.dataset.on = "0";
    light();
    whileSeen(sec, (on) => { visible = on; loop(); });
  });
}

/** Solutions page: the jump links follow the section on screen. */
export function subnav() {
  const nav = $("[data-az-subnav]");
  if (!nav) return;
  const links = $$("a[href^='#']", nav);
  const map = new Map();
  links.forEach((a) => { const t = document.getElementById(a.getAttribute("href").slice(1)); if (t) map.set(t, a); });
  const io = new IntersectionObserver((es) => es.forEach((e) => { if (e.isIntersecting) links.forEach((a) => a.classList.toggle("is-on", a === map.get(e.target))); }), { rootMargin: "-45% 0px -50% 0px" });
  map.forEach((a, t) => io.observe(t));
}

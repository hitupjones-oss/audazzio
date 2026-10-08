// Small behaviours shared by every page: reveal on scroll, the header, the menu, the Join the Wave bar,
// counters, rails, the logo marquee, quotes, filters, the step diagram and the Live QR flow.
export const $ = (s, r = document) => r.querySelector(s);
export const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
export const reduced = () => window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
/** Call cb(isReduced) whenever the "reduce motion" setting changes. */
export function onReduced(cb) {
  const m = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)");
  if (m && m.addEventListener) m.addEventListener("change", () => cb(m.matches));
}
/** The elements matching sel in root (root itself included) that have not been set up under key yet; marks them. */
export function fresh(root, sel, key) {
  const all = $$(sel, root);
  if (root !== document && root.matches && root.matches(sel)) all.unshift(root);
  return all.filter((el) => !el[key] && (el[key] = 1));
}
/** cb(true) while the mouse is over el or keyboard focus is inside it, cb(false) when neither is. */
export function holdOn(el, cb) {
  let hover = false, focus = false;
  const set = () => cb(hover || focus);
  el.addEventListener("pointerenter", (e) => { if (e.pointerType === "mouse") { hover = true; set(); } });
  el.addEventListener("pointerleave", (e) => { if (e.pointerType === "mouse") { hover = false; set(); } });
  el.addEventListener("focusin", (e) => { try { focus = e.target.matches(":focus-visible"); } catch { focus = true; } set(); });
  el.addEventListener("focusout", (e) => { focus = !!(e.relatedTarget && el.contains(e.relatedTarget)); set(); });
}

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

export function rise(root = document) {
  // siblings rise one after another
  fresh(root, "[data-az-rise]", "__azRise").forEach((el) => {
    const sibs = el.parentElement ? Array.from(el.parentElement.children).filter((c) => c.hasAttribute("data-az-rise")) : [el];
    el.style.setProperty("--az-i", String(Math.min(sibs.indexOf(el), 8)));
    onSeen(el, (t) => t.classList.add("is-in"));
  });
}

export function header(root = document) {
  const nav = fresh(root, "[data-az-nav]", "__azNav")[0];
  if (!nav) return;
  const set = () => nav.classList.toggle("is-scrolled", window.scrollY > 8);
  set();
  window.addEventListener("scroll", set, { passive: true });
  const burger = $(".az-nav__burger", nav), menu = $("#az-menu");
  if (!burger || !menu) return;
  // while the menu is open, everything behind it is out of reach (inert)
  let behind = [];
  const open = (on) => {
    if (on === !menu.hidden) return;
    if (!on && menu.contains(document.activeElement)) burger.focus({ preventScroll: true });
    burger.setAttribute("aria-expanded", on ? "true" : "false");
    burger.setAttribute("aria-label", on ? "Close the menu" : "Open the menu");
    menu.hidden = !on;
    nav.classList.toggle("is-open", on);
    document.documentElement.classList.toggle("az-lock", on);
    if (on) {
      behind = Array.from(document.body.children).filter((el) => !el.contains(nav) && !el.inert && !/^(SCRIPT|STYLE|TEMPLATE|DIALOG)$/.test(el.tagName));
      behind.forEach((el) => { el.inert = true; });
    } else {
      behind.forEach((el) => { el.inert = false; });
      behind = [];
    }
    document.dispatchEvent(new CustomEvent("az:menu", { detail: { open: on } }));
  };
  burger.addEventListener("click", () => open(menu.hidden));
  menu.addEventListener("click", (e) => { if (e.target.closest("a")) open(false); });
  document.addEventListener("keydown", (e) => { if (e.key === "Escape" && !menu.hidden) { open(false); burger.focus(); } });
  // the burger shows up to 1000px (02-chrome.css): wider than that, the menu closes
  const wide = window.matchMedia("(min-width: 1001px)");
  const fit = () => { if (wide.matches) open(false); };
  wide.addEventListener ? wide.addEventListener("change", fit) : wide.addListener(fit);
}

/**
 * The Join the Wave bar. Where the header shows its own Join the Wave (desktop) the bar comes in after the
 * first screen; where it does not (phones) the bar is there from the start. It steps aside while the closing
 * band, the footer or the form is on screen, and while a dialog or the menu is open.
 */
export function bar(root = document) {
  const el = fresh(root, "[data-az-bar]", "__azBar")[0];
  if (!el) return;
  if (document.body.classList.contains("az-page-join")) { el.remove(); return; }
  const cta = el.querySelector("a"), html = document.documentElement, head = $(".az-nav__join");
  let blockers = 0, past = false;
  const sync = () => {
    const on = past && blockers === 0 && !html.classList.contains("az-modal") && !html.classList.contains("az-lock");
    el.classList.toggle("is-on", on);
    el.setAttribute("aria-hidden", on ? "false" : "true");
    if (cta) cta.tabIndex = on ? 0 : -1;
  };
  const check = () => { past = !head || !head.getClientRects().length || window.scrollY > Math.min(window.innerHeight * 0.6, 520); sync(); };
  window.addEventListener("scroll", check, { passive: true });
  window.addEventListener("resize", check);
  check();
  document.addEventListener("az:menu", sync);
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

export function counters(root = document) {
  fresh(root, "[data-az-count]", "__azCount").forEach((el) => {
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

export function rails(root = document) {
  fresh(root, "[data-az-rail]", "__azRail").forEach((r) => {
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

export function marquee(root = document) {
  fresh(root, "[data-az-marquee]", "__azMarquee").forEach((m) => whileSeen(m, (on) => m.classList.toggle("is-running", on)));
}

/** Quotes: one at a time, turning every 7 s; held while the pointer or focus is on them, with a pause button. */
export function quotes(root = document) {
  fresh(root, "[data-az-quotes]", "__azQuotes").forEach((sec) => {
    const qs = $$("[data-az-quote]", sec), dots = $$("[data-az-quote-go]", sec), pp = $("[data-az-quotes-pause]", sec), stage = $(".az-quotes__stage", sec);
    if (qs.length < 2) return;
    let i = 0, timer = 0, visible = false, held = false, stopped = reduced();
    const go = (n) => {
      i = (n + qs.length) % qs.length;
      qs.forEach((q, j) => { q.classList.toggle("is-on", j === i); q.setAttribute("aria-hidden", j === i ? "false" : "true"); });
      dots.forEach((d, j) => d.setAttribute("aria-pressed", j === i ? "true" : "false"));
    };
    const loop = () => {
      clearTimeout(timer);
      if (stage) stage.setAttribute("aria-live", stopped ? "polite" : "off");
      if (pp) { pp.hidden = reduced(); pp.classList.toggle("is-paused", stopped); pp.setAttribute("aria-label", stopped ? "Play the quotes" : "Pause the quotes"); }
      if (visible && !held && !stopped) timer = setTimeout(() => { go(i + 1); loop(); }, 7000);
    };
    dots.forEach((d) => d.addEventListener("click", () => { go(+d.dataset.azQuoteGo); loop(); }));
    pp && pp.addEventListener("click", () => { stopped = !stopped; loop(); });
    holdOn(sec, (h) => { held = h; loop(); });
    onReduced((r) => { stopped = r; loop(); });
    whileSeen(sec, (on) => { visible = on; loop(); });
  });
}

export function filters(root = document) {
  fresh(root, "[data-az-press]", "__azPress").forEach((sec) => {
    const bs = $$("[data-az-filter]", sec), items = $$(".az-new", sec);
    bs.forEach((b) => b.addEventListener("click", () => {
      bs.forEach((x) => x.setAttribute("aria-pressed", x === b ? "true" : "false"));
      const k = b.dataset.azFilter;
      items.forEach((it) => { it.hidden = !!k && it.dataset.kind !== k; });
    }));
  });
}

/**
 * The three-step explainer: the diagram plays embed, play, deliver and the list follows. The pointer or focus
 * on it holds the step; choosing a step stops the turning. With reduced motion nothing turns by itself and
 * every step's text stays open.
 */
export function steps(root = document) {
  fresh(root, "[data-az-steps]", "__azSteps").forEach((sec) => {
    const items = $$("[data-az-step]", sec), fig = $(".az-steps__fig", sec), svg = $("svg.az-link", sec), DUR = 4500;
    if (!items.length) return;
    let i = 0, timer = 0, due = 0, left = DUR, visible = false, held = false, chosen = false;
    sec.style.setProperty("--az-steps-t", DUR + "ms");
    const set = (n) => {
      i = (n + items.length) % items.length;
      const all = sec.classList.contains("is-all");
      items.forEach((it, j) => {
        it.classList.toggle("is-on", j === i);
        it.classList.toggle("is-done", j < i);
        const b = $(".az-steps__b", it);
        if (!b) return;
        b.setAttribute("aria-expanded", all || j === i ? "true" : "false");
        j === i ? b.setAttribute("aria-current", "step") : b.removeAttribute("aria-current");
      });
      if (fig) fig.dataset.phase = String(i + 1);
    };
    // what is left of a step survives a hold, as the progress line does (paused in CSS)
    const play = () => {
      clearTimeout(timer);
      if (due) { left = Math.max(0, due - performance.now()); due = 0; }
      const auto = visible && !chosen && !reduced();
      sec.classList.toggle("is-live", visible && !reduced()); // the diagram's dashed lines march
      sec.classList.toggle("is-auto", auto);
      sec.classList.toggle("is-held", held);
      if (auto && !held) { due = performance.now() + left; timer = setTimeout(() => { due = 0; left = DUR; set(i + 1); play(); }, left); }
    };
    const restart = () => { due = 0; left = DUR; play(); };
    // the signal in the diagram travels only while the section is on screen and motion is welcome
    const smil = () => {
      if (!svg || !svg.pauseAnimations) return;
      if (visible && !reduced()) return svg.unpauseAnimations();
      if (reduced()) svg.setCurrentTime(1.2);
      svg.pauseAnimations();
    };
    items.forEach((it, j) => it.addEventListener("click", () => { chosen = true; set(j); restart(); }));
    holdOn(sec, (h) => { held = h; play(); });
    onReduced((r) => { sec.classList.toggle("is-all", r); set(i); restart(); smil(); });
    sec.classList.toggle("is-all", reduced());
    set(0);
    smil();
    whileSeen(sec, (on) => { visible = on; restart(); smil(); });
  });
}

/**
 * Live QR flow: Broadcast / Live event tabs, and the steps light up one after another, held while the pointer
 * or focus is on them. With reduced motion nothing turns by itself and the whole path stays lit.
 */
export function flow(root = document) {
  fresh(root, "[data-az-flow]", "__azFlow").forEach((sec) => {
    const tabs = $$('[role="tab"]', sec), panels = $$('[role="tabpanel"]', sec), seg = $(".az-seg", sec);
    if (!tabs.length) return;
    let timer = 0, visible = false, held = false, k = 0;
    const panel = () => panels.find((p) => !p.hidden);
    const count = () => { const p = panel(); return p ? $$(".az-flow__step", p).length : 0; };
    const light = () => {
      const p = panel(); if (!p) return;
      const st = $$(".az-flow__step", p);
      st.forEach((s, j) => { s.classList.toggle("is-on", j === k); s.classList.toggle("is-done", j < k); });
      p.style.setProperty("--az-flow-p", String(st.length > 1 ? k / (st.length - 1) : 1));
    };
    const loop = () => { clearTimeout(timer); if (visible && !held && !reduced() && count()) timer = setTimeout(() => { k = (k + 1) % count(); light(); loop(); }, 1700); };
    const select = (tab) => {
      tabs.forEach((t, j) => { const on = t === tab; t.setAttribute("aria-selected", on ? "true" : "false"); t.tabIndex = on ? 0 : -1; if (panels[j]) panels[j].hidden = !on; });
      if (seg) seg.dataset.on = String(tabs.indexOf(tab));
      k = reduced() ? Math.max(0, count() - 1) : 0; light(); loop();
    };
    const current = () => tabs.find((t) => t.getAttribute("aria-selected") === "true") || tabs[0];
    const KEYS = { ArrowRight: 1, ArrowLeft: -1, Home: "first", End: "last" };
    tabs.forEach((t, j) => {
      t.addEventListener("click", () => select(t));
      t.addEventListener("keydown", (e) => {
        const d = KEYS[e.key];
        if (!d) return;
        e.preventDefault();
        const n = tabs[d === "first" ? 0 : d === "last" ? tabs.length - 1 : (j + d + tabs.length) % tabs.length];
        n.focus(); select(n);
      });
    });
    $$(".az-flow__step", sec).forEach((s) => s.addEventListener("click", () => { const st = $$(".az-flow__step", panel()); const n = st.indexOf(s); if (n >= 0) { k = n; light(); loop(); } }));
    holdOn(sec, (h) => { held = h; loop(); });
    onReduced(() => select(current()));
    select(current());
    whileSeen(sec, (on) => { visible = on; loop(); });
  });
}

/** Solutions page: the jump links follow the section on screen. */
export function subnav(root = document) {
  fresh(root, "[data-az-subnav]", "__azSubnav").forEach((nav) => {
    const links = $$("a[href^='#']", nav);
    const map = new Map();
    links.forEach((a) => { const t = document.getElementById(a.getAttribute("href").slice(1)); if (t) map.set(t, a); });
    const io = new IntersectionObserver((es) => es.forEach((e) => { if (e.isIntersecting) links.forEach((a) => a.classList.toggle("is-on", a === map.get(e.target))); }), { rootMargin: "-45% 0px -50% 0px" });
    map.forEach((a, t) => io.observe(t));
  });
}

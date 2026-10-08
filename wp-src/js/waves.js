// The wave motion: hairline drawings on canvas, after Klyro's fine dashed lines, in Audazzio's terms.
//   <canvas data-az-waves="hero|lines|rings|spectrum|cta|footer|mini">
// Each preset is a stack of layers:
//   curtain  : fine vertical lines glowing in the brand peach, their lengths moving like a spectrum (the hero's light)
//   lines    : dashed sound-wave lines that breathe out from a source; one carries an orange signal pulse
//   rings    : a signal emitter: dashed wavefronts expanding from a point, dotted guide rings turning slowly
//   spectrum : a dashed frequency analyser; the inaudible band at the top end is drawn in the accent
// One shared animation loop; a canvas off screen, a hidden tab or "reduce motion" stops it (a still frame stays).
const TAU = Math.PI * 2;
const reduce = window.matchMedia ? window.matchMedia("(prefers-reduced-motion: reduce)") : { matches: false };

const hash = (i, j) => { const s = Math.sin(i * 127.1 + j * 311.7) * 43758.5453; return s - Math.floor(s); };
const vnoise = (x, y) => {
  const ix = Math.floor(x), iy = Math.floor(y), fx = x - ix, fy = y - iy;
  const ux = fx * fx * (3 - 2 * fx), uy = fy * fy * (3 - 2 * fy);
  const a = hash(ix, iy), b = hash(ix + 1, iy), c = hash(ix, iy + 1), d = hash(ix + 1, iy + 1);
  return a + (b - a) * ux + (c - a + (a - b + d - c) * ux) * uy;
};
const smooth = (x) => { x = Math.max(0, Math.min(1, x)); return x * x * (3 - 2 * x); };
const rgba = (c, a) => `rgba(${c[0]},${c[1]},${c[2]},${Math.max(0, Math.min(1, a)).toFixed(3)})`;
function rgb(v, fallback) {
  v = (v || "").trim();
  const m = /^#?([0-9a-f]{6})$/i.exec(v);
  if (!m) return fallback;
  const n = parseInt(m[1], 16);
  return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
}

/* ------------------------------------------------------------------ layers */
// Every layer gets (ctx, t, dt, S) where S = { w, h, hair, ink, accent, soft, narrow }.

function curtain(o = {}) {
  const pitch = o.pitch || 7;
  return (ctx, t, dt, S) => {
    const n = Math.ceil(S.w / pitch) + 1;
    const top = 0, H = S.h * (o.height || 0.62);
    ctx.lineWidth = S.hair;
    ctx.setLineDash([]);
    for (let i = 0; i < n; i++) {
      const x = i * pitch + 0.5;
      const f = x / S.w;
      // the glow sits in a soft hump across the middle, and each line breathes with its own noise
      const hump = Math.exp(-Math.pow((f - (o.cx ?? 0.5)) / (o.spread || 0.28), 2));
      const lv = hump * (0.35 + 0.65 * vnoise(i * 0.18, t * 0.35)) * (0.85 + 0.15 * Math.sin(t * 0.9 + i * 0.07));
      const len = H * lv;
      if (len < 2) continue;
      const g = ctx.createLinearGradient(0, top, 0, top + len);
      g.addColorStop(0, rgba(S.accent, (o.alpha || 0.78) * lv));
      g.addColorStop(0.5, rgba(S.soft, (o.alpha || 0.78) * 0.42 * lv));
      g.addColorStop(1, rgba(S.soft, 0));
      ctx.strokeStyle = g;
      ctx.beginPath(); ctx.moveTo(x, top); ctx.lineTo(x, top + len); ctx.stroke();
    }
  };
}

function lines(o = {}) {
  return (ctx, t, dt, S) => {
    const count = S.narrow ? (o.countM || 6) : (o.count || 9);
    const spacing = S.narrow ? (o.spacingM || 11) : (o.spacing || 14);
    const amp = (S.narrow ? (o.ampM || 22) : (o.amp || 40)) * (o.ampScale || 1);
    const wl = S.narrow ? 180 : (o.wavelength || 300);
    const cx = S.w * (o.cx ?? 0.5), cy = S.h * (o.cy ?? 0.5);
    const sig = S.w * (o.env || 0.32);
    const breath = 0.62 + 0.38 * Math.sin(t * TAU / (o.breath || 7.5));
    const mid = (count - 1) / 2, half = mid + 1;
    const k = TAU / wl, step = S.narrow ? 5 : 4;
    const y = (x, i) => {
      const c = i - mid, wi = 1 - Math.abs(c) / half * 0.55;
      const d = x - cx, env = Math.exp(-(d * d) / (sig * sig)), r = Math.sqrt(d * d + 400), ph = i * 0.55;
      const s = 0.72 * Math.sin(k * r - t * 0.9 + ph) + 0.28 * Math.sin(2.3 * k * r - t * 1.45 + ph * 1.7);
      return cy + c * spacing * (1 + 0.12 * breath) + env * amp * breath * wi * s;
    };
    const trace = (i) => {
      ctx.beginPath();
      ctx.moveTo(cx, y(cx, i));
      for (let x = cx - step; x > -step * 2; x -= step) ctx.lineTo(x, y(x, i));
      ctx.moveTo(cx, y(cx, i));
      for (let x = cx + step; x < S.w + step * 2; x += step) ctx.lineTo(x, y(x, i));
    };
    ctx.lineCap = "butt"; ctx.lineJoin = "round"; ctx.lineWidth = S.hair;
    ctx.setLineDash([o.dash || 6, o.gap || 6]);
    ctx.lineDashOffset = -t * (o.dashSpeed || 26);
    for (let i = 0; i < count; i++) {
      ctx.strokeStyle = rgba(S.ink, (o.alpha || 0.3) * (1 - Math.abs(i - mid) / half * 0.6));
      trace(i); ctx.stroke();
    }
    if (o.accent !== false) {
      const len = o.pulseLen || 90, period = S.narrow ? 420 : (o.pulsePeriod || 680);
      trace(Math.round(mid));
      ctx.setLineDash([len, period - len]);
      ctx.lineDashOffset = -t * (o.pulseSpeed || 150);
      ctx.lineCap = "round";
      ctx.lineWidth = 6; ctx.strokeStyle = rgba(S.soft, 0.16); ctx.stroke();
      ctx.lineWidth = Math.max(1.25, S.hair * 1.6); ctx.strokeStyle = rgba(S.accent, 0.92); ctx.stroke();
      ctx.lineCap = "butt";
    }
  };
}

function rings(o = {}) {
  return (ctx, t, dt, S) => {
    const ox = S.w * (o.ox ?? 0.5), oy = S.h * (o.oy ?? 0.55);
    const count = S.narrow ? 5 : (o.count || 7), period = o.period || 7, r0 = o.r0 || 16;
    const rMax = o.rMax || Math.min(Math.hypot(Math.max(ox, S.w - ox), Math.max(oy, S.h - oy)), o.limit || 900);
    ctx.lineWidth = S.hair; ctx.lineCap = "round";
    ctx.setLineDash([0.5, 6]);
    (o.guides || [0.32, 0.62]).forEach((g, j) => {
      const rot = (j % 2 ? -1 : 1) * t * TAU / 40;
      ctx.strokeStyle = rgba(S.ink, 0.26);
      ctx.beginPath(); ctx.arc(ox, oy, g * rMax, rot, rot + TAU); ctx.stroke();
    });
    ctx.lineCap = "butt";
    ctx.setLineDash([o.dash || 3, o.gap || 5]);
    for (let j = 0; j < count; j++) {
      const p = (t / period + j / count) % 1;
      const r = r0 + (rMax - r0) * p;
      const a = Math.pow(1 - p, 1.5) * smooth(p / 0.06);
      const acc = o.accent !== false && j === 0;
      ctx.strokeStyle = acc ? rgba(S.accent, 0.85 * a) : rgba(S.ink, (o.alpha || 0.3) * a);
      ctx.lineWidth = acc ? Math.max(1, S.hair * 1.4) : S.hair;
      const spin = (j % 2 ? 1 : -1) * t * 0.08;
      ctx.beginPath(); ctx.arc(ox, oy, r, spin, spin + TAU); ctx.stroke();
    }
    if (o.source !== false) {
      const beat = (t / period * count) % 1;
      ctx.setLineDash([]);
      ctx.fillStyle = rgba(S.soft, 0.35 * (1 - beat));
      ctx.beginPath(); ctx.arc(ox, oy, 4 + 14 * smooth(beat), 0, TAU); ctx.fill();
      ctx.fillStyle = rgba(S.accent, 1);
      ctx.beginPath(); ctx.arc(ox, oy, 4, 0, TAU); ctx.fill();
    }
  };
}

function spectrum(o = {}) {
  let levels = null, primed = false;
  return (ctx, t, dt, S) => {
    const B = Math.max(24, Math.min(140, Math.round(S.w / (S.narrow ? 10 : 12))));
    if (!levels || levels.length !== B) { levels = new Float32Array(B); primed = false; }
    const pitch = S.w / B, base = S.h * (o.baseline || 0.92), H = S.h * (o.height || 0.62);
    const bandStart = Math.floor(B * (1 - (o.band || 0.16)));
    const k = primed && dt > 0 ? 1 - Math.exp(-dt * 10) : 1;
    for (let b = 0; b < B; b++) {
      let target;
      if (b >= bandStart) target = hash(Math.floor(t / 0.16), b) > 0.42 ? 0.34 : 0.12;
      else {
        const f = b / bandStart, profile = 0.25 + 0.75 * Math.pow(1 - f, 0.7);
        const beat = 0.82 + 0.18 * Math.pow(Math.max(0, Math.sin(t * TAU / 1.15)), 8);
        target = profile * beat * (0.25 + 0.75 * vnoise(b * 0.22, t * 1.1));
      }
      levels[b] += (target - levels[b]) * k;
    }
    primed = true;
    ctx.lineWidth = S.hair; ctx.lineCap = "butt";
    ctx.setLineDash([]); ctx.strokeStyle = rgba(S.ink, 0.16);
    ctx.beginPath(); ctx.moveTo(0, base + 0.5); ctx.lineTo(S.w, base + 0.5); ctx.stroke();
    ctx.setLineDash([2, 2]); ctx.strokeStyle = rgba(S.ink, o.alpha || 0.3);
    ctx.beginPath();
    for (let b = 0; b < bandStart; b++) { const x = Math.round((b + 0.5) * pitch) + 0.5; ctx.moveTo(x, base); ctx.lineTo(x, base - levels[b] * H); }
    ctx.stroke();
    ctx.strokeStyle = rgba(S.accent, 0.9); ctx.lineWidth = Math.max(1, S.hair * 1.4);
    ctx.beginPath();
    for (let b = bandStart; b < B; b++) { const x = Math.round((b + 0.5) * pitch) + 0.5; ctx.moveTo(x, base); ctx.lineTo(x, base - levels[b] * H); }
    ctx.stroke();
    const x0 = bandStart * pitch + 2, x1 = S.w - 2, yb = base - H * 0.48;
    ctx.setLineDash([2, 3]); ctx.lineWidth = S.hair; ctx.strokeStyle = rgba(S.accent, 0.6);
    ctx.beginPath(); ctx.moveTo(x0, yb + 6); ctx.lineTo(x0, yb); ctx.lineTo(x1, yb); ctx.lineTo(x1, yb + 6); ctx.stroke();
  };
}

/* ----------------------------------------------------------------- presets */
const PRESETS = {
  hero: (S) => [curtain({ height: S.narrow ? 0.34 : 0.46, spread: S.narrow ? 0.42 : 0.34, pitch: S.narrow ? 6 : 7 }), lines({ cy: S.narrow ? 0.9 : 0.885, alpha: 0.28, amp: 34 })],
  lines: () => [lines({ cy: 0.78, amp: 30, alpha: 0.24, count: 7 })],
  rings: (S) => [rings({ ox: S.narrow ? 0.5 : 0.78, oy: 0.5, alpha: 0.26 })],
  card: () => [rings({ ox: 0.94, oy: 0.06, alpha: 0.2, count: 5, period: 8 })],
  spectrum: () => [spectrum({ baseline: 0.96, height: 0.46, alpha: 0.26 })],
  cta: (S) => [curtain({ height: 0.55, spread: 0.36, alpha: 0.6 }), lines({ cy: 0.88, amp: 26, alpha: 0.22, count: 7 })],
  footer: () => [lines({ cy: 0.5, amp: 18, alpha: 0.16, count: 5, spacing: 12, pulsePeriod: 900 })],
  mini: () => [lines({ cy: 0.5, amp: 5, ampM: 5, alpha: 0.5, count: 1, countM: 1, spacing: 0, spacingM: 0, wavelength: 26, env: 0.6, dash: 2, gap: 2, pulseLen: 10, pulsePeriod: 44, pulseSpeed: 30, dashSpeed: 10 })],
};

/* -------------------------------------------------------------------- loop */
const scenes = new Set();
let raf = 0, last = 0;

const html = document.documentElement;
class Scene {
  constructor(canvas) {
    this.c = canvas; this.ctx = canvas.getContext("2d");
    this.kind = canvas.dataset.azWaves; this.t = 2.4 + Math.random() * 3; this.visible = false;
    this.box = canvas.parentElement;
    this.ro = new ResizeObserver(() => { this.resize(); if (!raf) this.draw(0); });
    this.ro.observe(this.box);
    this.io = new IntersectionObserver((e) => { this.visible = !!(e[0] && e[0].isIntersecting); if (this.visible && !raf) this.draw(0); kick(); }, { rootMargin: "100px 0px" });
    this.io.observe(this.box);
    // the Join the Wave bar's wave only moves while the bar is in
    this.bar = canvas.closest("[data-az-bar]");
    if (this.bar) new MutationObserver(kick).observe(this.bar, { attributes: true, attributeFilter: ["class"] });
    this.resize();
  }
  /** On screen, and not under a dialog or the menu. */
  live() {
    if (!this.visible || (this.bar && !this.bar.classList.contains("is-on"))) return false;
    return !(html.classList.contains("az-modal") || html.classList.contains("az-lock")) || !!this.c.closest("dialog[open]");
  }
  drop() { this.ro.disconnect(); this.io.disconnect(); scenes.delete(this); }
  resize() {
    const r = this.box.getBoundingClientRect();
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    const cs = getComputedStyle(this.box);
    this.S = {
      w: Math.max(1, r.width), h: Math.max(1, r.height), hair: dpr >= 2 ? 0.75 : 1, narrow: r.width < 768,
      ink: rgb(cs.getPropertyValue("--az-wave-ink"), [12, 18, 34]),
      accent: rgb(cs.getPropertyValue("--az-wave-accent"), [242, 106, 46]),
      soft: rgb(cs.getPropertyValue("--az-wave-soft"), [248, 156, 104]),
    };
    const W = Math.round(this.S.w * dpr), H = Math.round(this.S.h * dpr);
    if (this.c.width !== W || this.c.height !== H) { this.c.width = W; this.c.height = H; }
    this.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    this.layers = (PRESETS[this.kind] || PRESETS.lines)(this.S);
  }
  draw(dt) {
    this.ctx.clearRect(0, 0, this.S.w, this.S.h);
    for (const L of this.layers) { this.ctx.save(); L(this.ctx, this.t, dt, this.S); this.ctx.restore(); }
  }
}

function frame(now) {
  raf = 0;
  const dt = last ? Math.min((now - last) / 1000, 1 / 20) : 1 / 60;
  last = now;
  let any = false;
  scenes.forEach((s) => {
    if (!s.c.isConnected) return s.drop(); // the Elementor editor replaced the widget
    if (!s.live()) return;
    any = true; s.t += dt; s.draw(dt);
  });
  if (any && !document.hidden && !reduce.matches) raf = requestAnimationFrame(frame);
  else last = 0;
}
function kick() {
  if (raf || document.hidden || reduce.matches) return;
  for (const s of scenes) if (s.live()) { last = 0; raf = requestAnimationFrame(frame); return; }
}
const redraw = () => scenes.forEach((s) => { s.resize(); s.draw(0); });
document.addEventListener("visibilitychange", () => { if (document.hidden && raf) { cancelAnimationFrame(raf); raf = 0; last = 0; } else kick(); });
document.addEventListener("az:modal", kick);
document.addEventListener("az:menu", kick);
if (reduce.addEventListener) reduce.addEventListener("change", () => { if (reduce.matches && raf) { cancelAnimationFrame(raf); raf = 0; } scenes.forEach((s) => s.draw(0)); kick(); });
// a window moved to a screen with another pixel density: draw again at the new density
function watchDensity() {
  if (!window.matchMedia) return;
  const q = window.matchMedia(`(resolution: ${window.devicePixelRatio || 1}dppx)`);
  const on = () => { q.removeEventListener ? q.removeEventListener("change", on) : q.removeListener(on); redraw(); watchDensity(); };
  q.addEventListener ? q.addEventListener("change", on) : q.addListener(on);
}
watchDensity();

export function initWaves(root = document) {
  root.querySelectorAll("canvas[data-az-waves]").forEach((c) => {
    if (c.__az) return;
    c.__az = new Scene(c);
    scenes.add(c.__az);
    c.__az.draw(0);
  });
  kick();
}

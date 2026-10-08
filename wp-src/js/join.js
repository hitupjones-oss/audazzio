// Join the Wave: four steps (you, your idea, scale, send), checked as the visitor goes, then sent to
// WordPress (POST /wp-json/audazzio/v1/join), which grades and files the inquiry. On the static preview
// nothing is sent: the form grades the answers with the same table and shows the team's view.
import { $, $$, fresh, reduced } from "./ui.js";

const TABLE = () => { try { return JSON.parse(($("#az-join-config") || {}).textContent || "{}"); } catch { return {}; } };
const LABELS = { name: "Name", position: "Position", company: "Company", email: "Email", phone: "Phone", details: "In your words" };

function grade(d) {
  const T = TABLE(), qs = T.questions || {};
  let sum = 0, max = 0;
  for (const [k, q] of Object.entries(qs)) {
    const pts = Object.values(q.options).map((o) => o[1]);
    max += Math.max(...pts);
    if (q.type === "checkbox") sum += Math.max(0, ...(d[k] || []).map((v) => (q.options[v] || [0, 0])[1]));
    else sum += (q.options[d[k]] || [0, 0])[1];
  }
  const len = (d.details || "").trim().length;
  sum += len >= 160 ? 5 : len >= 60 ? 3 : 1;
  const dom = (d.email.split("@")[1] || "").toLowerCase();
  sum += dom && !(T.free || []).includes(dom) ? 5 : 0;
  max += 10;
  const score = Math.round((sum / max) * 100);
  let g = "D";
  for (const [k, v] of Object.entries(T.grades || {})) if (score >= v[0]) { g = k; break; }
  const nature = (T.natures || {})[d.org] || "General inquiry";
  return { score, grade: g, label: (T.grades[g] || [])[1], advice: (T.grades[g] || [])[2], nature };
}

/** Mark a field or question as wrong (or right again). The message lands a moment later, so screen readers announce it. */
function err(el, msg) {
  const host = el.closest(".az-field, .az-q, .az-check") || el;
  host.classList.toggle("is-bad", !!msg);
  const m = $(".az-field__err", host);
  if (m) {
    m.__msg = msg || "";
    if (!msg) m.textContent = "";
    else if (m.textContent !== msg) { m.textContent = ""; setTimeout(() => { if (m.__msg) m.textContent = m.__msg; }, 60); }
  }
  (host.matches(".az-q") ? $$("input", host) : [el]).forEach((i) => i.setAttribute("aria-invalid", msg ? "true" : "false"));
  return !msg;
}

function read(f) {
  const fd = new FormData(f), d = {};
  for (const [k, v] of fd.entries()) {
    if (k.endsWith("[]")) (d[k.slice(0, -2)] ||= []).push(v);
    else d[k] = typeof v === "string" ? v.trim() : v;
  }
  return d;
}

const focusBad = (step) => { const bad = $(".is-bad input, .is-bad textarea", step); bad && bad.focus(); };

/** Check one step; with focus, the first wrong answer gets the focus. */
function check(f, n, focus = true) {
  const step = $(`[data-az-jstep="${n}"]`, f);
  if (!step) return true;
  let ok = true;
  const field = (name, test, msg) => { const el = $(`[name="${name}"]`, step); if (el) { const v = el.value.trim(), m = test(v); ok = err(el, m === true ? "" : typeof m === "string" ? m : msg) && ok; } };
  if (n === 1) {
    field("name", (v) => v.length > 1, "Please add your name.");
    field("position", (v) => v.length > 1, "Please add your position.");
    field("company", (v) => v.length > 1, "Please add your company or organization.");
    // WordPress drops letters it cannot use in an address (josé@ becomes jos@), so the server refuses them too
    field("email", (v) => (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v) ? false : /[^\x21-\x7e]/.test(v) ? "Please use an email address without accents or special characters." : true), "Please check the email address.");
    field("phone", (v) => v.replace(/\D/g, "").length >= 7, "Please add a phone number.");
  }
  if (n === 2 || n === 3) {
    $$(".az-q", step).forEach((q) => {
      const any = $$("input", q).some((i) => i.checked);
      ok = err(q, any ? "" : q.dataset.azQ === "uses" ? "Pick at least one." : "Please choose one.") && ok;
    });
    if (n === 2) field("details", (v) => v.length >= 20, "A sentence or two, please (20 characters or more).");
  }
  if (n === 4) { const c = $('[name="consent"]', step); if (c) ok = err(c, c.checked ? "" : "Please tick the box so we can reply.") && ok; }
  if (!ok && focus) focusBad(step);
  return ok;
}

function review(f) {
  const d = read(f), T = TABLE(), out = [];
  for (const k of ["name", "position", "company", "email", "phone"]) out.push([LABELS[k], d[k]]);
  for (const [k, q] of Object.entries(T.questions || {})) {
    const v = q.type === "checkbox" ? (d[k] || []).map((x) => q.options[x][0]).join(", ") : (q.options[d[k]] || [""])[0];
    out.push([q.label.replace(/\?.*$/, "?").replace(/ Pick any\.$/, ""), v]);
  }
  out.push([LABELS.details, d.details]);
  const dl = $("[data-az-review]", f);
  dl.replaceChildren(...out.flatMap(([a, b]) => { const dt = document.createElement("dt"), dd = document.createElement("dd"); dt.textContent = a; dd.textContent = b || "–"; return [dt, dd]; }));
}

export function join(root = document) {
  fresh(root, "[data-az-form]", "__az").forEach((f) => {
    let n = 1, t0 = 0, busy = false;
    const total = 4, back = $("[data-az-back]", f), next = $("[data-az-next]", f), send = $("[data-az-send]", f), msg = $(".az-join__msg", f);
    f.addEventListener("focusin", () => { if (!t0) t0 = Date.now(); }, { once: true });
    const go = (to, quiet) => {
      n = to;
      $$("[data-az-jstep]", f).forEach((s) => { const on = +s.dataset.azJstep === n; s.hidden = !on; s.classList.toggle("is-on", on); });
      $$("[data-az-dot]", f).forEach((d) => { const k = +d.dataset.azDot; d.classList.toggle("is-on", k === n); d.classList.toggle("is-done", k < n); });
      f.style.setProperty("--az-join-p", String((n - 1) / (total - 1)));
      back.hidden = n === 1; next.hidden = n === total; send.hidden = n !== total;
      if (n === total) review(f);
      if (quiet) return;
      const head = $(`[data-az-jstep="${n}"] .az-join__q`, f);
      if (head) { head.tabIndex = -1; head.focus({ preventScroll: true }); }
      const box = f.closest(".az-dialog__sheet");
      if (box) box.scrollTo({ top: 0, behavior: reduced() ? "auto" : "smooth" });
      else { const r = f.getBoundingClientRect(); if (r.top < 0) f.scrollIntoView({ behavior: reduced() ? "auto" : "smooth", block: "start" }); }
    };
    next.addEventListener("click", () => { if (check(f, n)) go(n + 1); });
    back.addEventListener("click", () => go(Math.max(1, n - 1)));
    // Enter moves on a step from any answer, as Continue does (Send only sends from the last step)
    f.addEventListener("keydown", (e) => { if (e.key === "Enter" && e.target.tagName === "INPUT" && n < total) { e.preventDefault(); next.click(); } });
    f.addEventListener("change", (e) => { const h = e.target.closest(".is-bad"); if (h) err(e.target.closest(".az-q") || e.target, ""); });
    f.addEventListener("input", (e) => { const h = e.target.closest(".az-field.is-bad"); if (h) err(e.target, ""); });

    // The server names the answer it could not take: back to its step, with the message on it.
    const problem = (text, name) => {
      text = text || "Something went wrong. Please try again.";
      const el = name && /^[a-z0-9_-]+$/.test(name) && $(`[name="${name}"], [name="${name}[]"]`, f);
      const at = el && el.closest("[data-az-jstep]");
      if (at) {
        const host = el.closest(".az-q") || el;
        go(+at.dataset.azJstep, true);
        err(host, text);
        (host === el ? el : $("input", host)).focus();
        return;
      }
      msg.textContent = text;
      msg.hidden = false;
      if (!f.contains(document.activeElement)) send.focus();
    };

    f.addEventListener("submit", async (e) => {
      e.preventDefault();
      if (busy) return;
      // before the last step this is Enter in an answer: check the step and move on
      if (n < total) { if (check(f, n)) go(n + 1); return; }
      for (let k = 1; k <= total; k++) {
        if (check(f, k, k === n)) continue;
        if (k !== n) { go(k, true); focusBad($(`[data-az-jstep="${k}"]`, f)); }
        return;
      }
      const d = read(f);
      d.t = t0 ? Date.now() - t0 : 0;
      d.source = location.href;
      d.consent = d.consent ? 1 : 0;
      // the button keeps the focus while it works, so it is never disabled
      busy = true; send.setAttribute("aria-disabled", "true"); send.classList.add("is-busy"); msg.hidden = true;
      const cfg = window.AZ_CONFIG || {};
      let ok = false, text = cfg.thanks || "", j = {};
      if (cfg.static) {
        await new Promise((r) => setTimeout(r, 700));
        ok = true;
        const g = grade(d);
        const box = $("[data-az-grade]", f);
        box.innerHTML = `<p class="az-label">Preview: nothing was sent</p><p><b>How Audazzio’s team would see this inquiry:</b> grade <b>${g.grade}</b> (${g.label}, ${g.score}/100) · ${g.nature}</p><p>${g.advice}</p>`;
        box.hidden = false;
      } else {
        try {
          const r = await fetch(cfg.rest, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(d) });
          j = (await r.json().catch(() => null)) || {};
          ok = r.ok && j.ok !== false;
          text = j.message || text;
        } catch {}
      }
      busy = false; send.removeAttribute("aria-disabled"); send.classList.remove("is-busy");
      if (!ok) return problem(j.message, j.field);
      if (text) $("[data-az-thanks]", f).textContent = text;
      $$(".az-join__steps, .az-join__nav, .az-join__top", f).forEach((x) => (x.hidden = true));
      const done = $("[data-az-done]", f);
      done.hidden = false; done.focus({ preventScroll: true });
      f.classList.add("is-done");
      if (!f.closest(".az-dialog__sheet")) { const r = f.getBoundingClientRect(); if (r.top < 0 || r.top > innerHeight / 2) f.scrollIntoView({ block: "start", behavior: reduced() ? "auto" : "smooth" }); }
    });
    go(1, true);
  });
}

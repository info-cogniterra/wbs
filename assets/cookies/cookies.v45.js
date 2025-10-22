/*! Cogniterra Cookie Bar v45 – clean UTF-8, single-file, no deps */
(() => {
  const KEY = "ct_cc_v2"; const VER = 2;
  const DEBUG = /(?:\?|&)debug_cookies=1(?:&|$)/.test(location.search);
  try { window.CookieBar && window.CookieBar.destroy?.(); } catch(_) {}
  const oldHost = document.getElementById("ct-cc-host"); if (oldHost) oldHost.remove();
  const state = { categories: { necessary: true, analytics: false, marketing: false } };
  const read = () => { try { const raw = localStorage.getItem(KEY); if (!raw) return null; const obj = JSON.parse(raw); return (obj && obj.v === VER) ? obj : null; } catch { return null; } };
  const save = (payload) => { try { localStorage.setItem(KEY, JSON.stringify(payload)); window.dispatchEvent(new CustomEvent("cookieConsentChange", { detail: payload })); } catch {} };
  const reset = () => { try { localStorage.removeItem(KEY); } catch {} };

  const host = document.createElement("div"); host.id = "ct-cc-host";
  host.style.cssText = "position:fixed;left:0;right:0;bottom:0;z-index:2147483647;";
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", () => document.body.appendChild(host), { once: true });
  else document.body.appendChild(host);

  const root = host.attachShadow ? host.attachShadow({ mode: "open" }) : host;
  const el = (tag, attrs = {}, children = []) => { const n = document.createElement(tag);
    Object.entries(attrs).forEach(([k, v]) => { if (k === "style") n.setAttribute("style", v); else if (k === "text") n.textContent = v; else n.setAttribute(k, v); });
    children.forEach(c => n.appendChild(c)); return n; };

  const style = el("style", { text: `
:host, * { box-sizing: border-box; }
:host { --bg:#0b1a13; --txt:#fff; --muted:#cfd6d2; --gold:#ffd76a; --green:#1F6A3A; --ring: rgba(255,255,255,.2); }
.ct-bar { position: fixed; inset: auto 0 0 0; display:flex; gap:16px; align-items:center; padding:14px 16px;
  background: linear-gradient(180deg, #0c1d15 0%, var(--bg) 100%); color: var(--txt); box-shadow: 0 -6px 24px rgba(0,0,0,.35);
  font: 14px/1.5 system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial; }
.ct-copy { flex:1 1 auto; } .ct-copy strong { font-weight:800; margin-right:.35em; } .ct-copy a { color: var(--gold); text-decoration: underline; }
.ct-actions { display:flex; gap:10px; flex:0 0 auto; }
.ct-btn { border-radius:999px; padding:10px 16px; font-weight:800; letter-spacing:.2px; cursor:pointer;
  transition: transform .06s ease, box-shadow .2s, opacity .2s; border:1px solid transparent; background:transparent; color:var(--txt); }
.ct-btn:is(:hover,:focus-visible){ outline:none; box-shadow:0 0 0 3px var(--ring); }
.ct-btn--ghost { border-color: rgba(255,255,255,.18); background: rgba(255,255,255,.06); }
.ct-btn--outline { border-color: rgba(255,255,255,.35); background: transparent; }
.ct-btn--primary { border:0; color:#0a1711; background: linear-gradient(90deg, var(--gold), var(--green)); box-shadow:0 2px 10px rgba(31,106,58,.35); }
.ct-btn--primary:hover { opacity:.95; box-shadow:0 4px 18px rgba(31,106,58,.45); }
@media (max-width: 860px){ .ct-bar { flex-wrap:wrap; padding-bottom: max(14px, env(safe-area-inset-bottom)); } .ct-actions { width:100%; justify-content: flex-end; } }
.ct-ol { position: fixed; inset:0; display:none; place-items:center; background:rgba(0,0,0,.55); z-index:2147483647; } .ct-ol[open] { display:grid; }
.ct-md { width:min(720px, calc(100vw - 24px)); max-height:min(86vh, 760px); overflow:auto; background:#fff; color:#111; border-radius:20px; padding:22px;
  border:1px solid rgba(0,0,0,.06); box-shadow:0 20px 50px rgba(0,0,0,.35); font: 14px/1.5 system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial; }
.ct-md h2 { margin:0 0 4px; font-size:22px; } .ct-md p.help { margin:0 0 14px; color:#333; }
.ct-row { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:10px 0; } .ct-row .l { max-width:75%; }
.ct-row small { display:block; margin-top:4px; color:#555; } .ct-row input[type=checkbox] { width:20px; height:20px; }
.ct-md .cta { display:flex; gap:10px; justify-content:flex-end; margin-top:16px; } .ct-md .cta .ct-btn--primary { color:#0a1711; }
@media (max-width:640px){ .ct-md { width:100vw; height:auto; max-height:85vh; border-radius:16px 16px 0 0; padding-bottom:max(22px, env(safe-area-inset-bottom)); } }
`});

  const copy = el("div", { class: "ct-copy" }, [
    el("strong", { text: "Zlepšeme společně váš zážitek." }),
    el("span",   { text: " Používáme cookies pro funkce, měření a lepší obsah. " }),
    el("a",      { href: "/cookies/", text: "Více o cookies" })
  ]);
  const btnDecline  = el("button", { class: "ct-btn ct-btn--outline",  text: "Pouze nezbytné" });
  const btnSettings = el("button", { class: "ct-btn ct-btn--ghost",    text: "Nastavení" });
  const btnAccept   = el("button", { class: "ct-btn ct-btn--primary",  text: "Povolit vše" });
  const actions = el("div", { class: "ct-actions" }, [btnDecline, btnSettings, btnAccept]);
  const bar = el("div", { class: "ct-bar", role:"dialog", "aria-live":"polite" }, [copy, actions]);

  const ol = el("div", { class: "ct-ol", "aria-hidden":"true" });
  const md = el("div", { class:"ct-md", role:"dialog", "aria-modal":"true", "aria-labelledby":"ct-md-h" });
  const h2 = el("h2", { id:"ct-md-h", text:"Nastavení souborů cookie" });
  const help = el("p", { class:"help", text:"Zvolte, které typy cookies povolit. Nezbytné jsou nutné pro správné fungování webu." });
  const row = (title, note, key, locked=false) => { const input = el("input", { type:"checkbox" });
    if (locked) { input.checked = true; input.disabled = true; } input.checked = !!(key==='necessary' ? true : false);
    input.addEventListener("change", () => {}); const left = el("div", { class:"l" }, [ el("strong", { text:title }), el("small", { text:note }) ]);
    return el("div", { class:"ct-row" }, [left, input]); };
  const rNec = row("Nezbytné", "Základní funkce webu (zabezpečení, dostupnost).", "necessary", true);
  const rAna = row("Analytické", "Měření návštěvnosti a zlepšení obsahu.", "analytics");
  const rMkt = row("Marketingové", "Personalizace a měření kampaní.", "marketing");
  const btnCancel = el("button", { class:"ct-btn ct-btn--outline", text:"Zrušit" });
  const btnSave   = el("button", { class:"ct-btn ct-btn--primary", text:"Uložit nastavení" });
  const modalCta  = el("div", { class:"cta" }, [btnCancel, btnSave]);
  md.append(h2, help, rNec, rAna, rMkt, modalCta); ol.appendChild(md); root.append(style, bar, ol);

  const lockScroll = () => { document.documentElement.style.overflow = "hidden"; };
  const unlockScroll = () => { document.documentElement.style.overflow = ""; };
  const showOverlay = () => { ol.setAttribute("open",""); ol.removeAttribute("aria-hidden"); lockScroll(); btnSave.focus(); };
  const hideOverlay = () => { ol.removeAttribute("open"); ol.setAttribute("aria-hidden","true"); unlockScroll(); };
  ol.addEventListener("click", (e) => { if (e.target === ol) hideOverlay(); }); btnCancel.addEventListener("click", hideOverlay);

  function doSave(analytics, marketing){
    const c = { v: VER, ts: new Date().toISOString(), necessary: true, analytics: !!analytics, marketing: !!marketing };
    localStorage.setItem(KEY, JSON.stringify(c)); window.dispatchEvent(new CustomEvent("cookieConsentChange", { detail: c }));
    host.style.display = "none"; unlockScroll(); DEBUG && console.log("[cookies] saved", c);
  }
  btnAccept.addEventListener("click", () => doSave(true, true));
  btnDecline.addEventListener("click", () => doSave(false, false));
  btnSettings.addEventListener("click", showOverlay);
  btnSave.addEventListener("click", () => {
    // read checkboxes state from modal
    const checks = md.querySelectorAll("input[type=checkbox]");
    const analytics = !checks[1].disabled && checks[1].checked;
    const marketing = !checks[2].disabled && checks[2].checked;
    doSave(analytics, marketing);
  });

  // init
  const existing = (DEBUG ? (localStorage.removeItem(KEY), null) : read());
  if (existing) host.style.display = "none";
  // API
  window.CookieBar = { reset: () => localStorage.removeItem(KEY), read, show: () => { try { localStorage.removeItem(KEY); host.style.display = ""; } catch {} }, openSettings: showOverlay, destroy: () => { try { host.remove(); } catch {} } };
})();


(function(){
  function h(tag, props, ...kids){
    const el = document.createElement(tag);
    if (props) Object.assign(el, props);
    kids.flat().forEach(k => el.appendChild(typeof k === "string" ? document.createTextNode(k) : k));
    return el;
  }
  function normalizePlaceId(pid){
    try { pid = decodeURIComponent(pid||""); } catch(_) {}
    pid = (pid||"").trim();
    if (!pid) return "";
    if (!/^places\//.test(pid)) pid = "places/" + pid.replace(/^\/+/, "");
    return pid;
  }
  async function fetchReviews(placeId, apiKey){
    const url = `https://places.googleapis.com/v1/${placeId}?languageCode=cs`;
    const resp = await fetch(url, {
      headers: {
        "X-Goog-Api-Key": apiKey,
        "X-Goog-FieldMask": "reviews,reviews.text,reviews.originalText,reviews.rating,reviews.publishTime,reviews.authorAttribution"
      }
    });
    if (!resp.ok) {
      const t = await resp.text();
      throw new Error(`Places API error ${resp.status}: ${t}`);
    }
    const data = await resp.json();
    return (data && data.reviews) || [];
  }
  function Star(){
    const svg = document.createElementNS("http://www.w3.org/2000/svg","svg");
    svg.setAttribute("viewBox","0 0 24 24");
    const p = document.createElementNS("http://www.w3.org/2000/svg","path");
    p.setAttribute("d","M12 .587l3.668 7.431 8.2 1.193-5.934 5.786 1.401 8.168L12 18.896l-7.335 3.869 1.401-8.168L.132 9.211l8.2-1.193z");
    svg.appendChild(p);
    return svg;
  }
  function render(host, reviews){
    host.innerHTML = "";
    if (!reviews.length){
      host.appendChild(h("div", {className:"text-sm opacity-70"}, "Zatím zde nejsou recenze z Google."));
      return;
    }
    reviews.slice(0, 12).forEach(r => {
      const name  = (r.authorAttribution && r.authorAttribution.displayName) || "Anonym";
      const when  = r.publishTime ? new Date(r.publishTime).toLocaleDateString("cs-CZ") : "";
      const text  = (r.originalText && r.originalText.text) || (r.text && r.text.text) || "";
      const card  = h("div", {className:"review-card"});
      const title = h("div", {style:"font-weight:600; font-size:16px; margin-bottom:2px;"}, name);
      const stars = h("div", {className:"review-stars"});
      const rating = Math.round(r.rating || 0);
      for (let i=0;i<rating;i++){ const s=h("span",{className:"review-star"}); s.appendChild(Star()); stars.appendChild(s); }
      const meta  = h("div", {style:"font-size:13px; opacity:.7; margin-bottom:6px;"}, when);
      const body  = h("div", {style:"line-height:1.6"}, text);
      card.appendChild(title); card.appendChild(stars); card.appendChild(meta); card.appendChild(body);
      host.appendChild(card);
    });
  }
  async function run(){
    const host = document.getElementById("google-reviews-root"); if (!host) return;
    const key = window.GOOGLE_API_KEY, raw = window.GOOGLE_PLACE_ID;
    if (!key || !raw){ console.error("[REVIEWS] Missing GOOGLE_API_KEY or GOOGLE_PLACE_ID"); return; }
    const pid = normalizePlaceId(raw);
    try { const reviews = await fetchReviews(pid, key); render(host, reviews); }
    catch(e){ console.error("[REVIEWS]", e); }
  }
  if (document.readyState==="loading") document.addEventListener("DOMContentLoaded", run); else run();
})();

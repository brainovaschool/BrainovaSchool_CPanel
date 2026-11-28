"use strict";

/* Theme colour picker — lets a user pick Primary/Secondary/(optional)
   Accent colours that recolour the sidebar highlight, buttons and badges.
   Independent of the light/dark toggle in theme.js: this sets its
   overrides directly on document.body's inline style, which wins over
   both the :root defaults and the .default-theme/.dark-theme class rules
   those tokens are declared in (aurora-glass.css), in either mode, and
   survives theme.js swapping those classes back and forth. */

(function () {
  var STORAGE_KEY = "bn_theme_colors";

  var PALETTE = [
    "0097B2", "06B6D4", "0EA5E9", "3B82F6", "6366F1", "8B7BF0",
    "8B5CF6", "A855F7", "D946EF", "EC4899", "F43F5E", "EF4444",
    "F97316", "F59E0B", "84CC16", "22C55E", "10B981", "14B8A6",
    "64748B", "334155"
  ].map(function (h) { return "#" + h; });

  var PRESETS = [
    { n: "Brainova turquoise", p: "#0097B2", s: "#8B7BF0", a: null },
    { n: "Ocean", p: "#0EA5E9", s: "#6366F1", a: "#22D3EE" },
    { n: "Berry", p: "#EC4899", s: "#8B5CF6", a: "#F472B6" },
    { n: "Sunset", p: "#F97316", s: "#EF4444", a: "#FB923C" },
    { n: "Forest", p: "#10B981", s: "#14B8A6", a: "#34D399" }
  ];

  var DEFAULTS = { p: "#0097B2", s: "#8B7BF0", a: null };

  function hexToRgb(hex) {
    var h = hex.replace("#", "");
    if (h.length === 3) { h = h.split("").map(function (c) { return c + c; }).join(""); }
    var num = parseInt(h, 16);
    return [(num >> 16) & 255, (num >> 8) & 255, num & 255];
  }

  function rgbToHex(rgb) {
    return "#" + rgb.map(function (v) {
      return Math.max(0, Math.min(255, Math.round(v))).toString(16).padStart(2, "0");
    }).join("");
  }

  // Blends `hex` toward `targetHex` by fraction t (0 = hex, 1 = target).
  function mix(hex, targetHex, t) {
    var a = hexToRgb(hex), b = hexToRgb(targetHex);
    return rgbToHex(a.map(function (v, i) { return v + (b[i] - v) * t; }));
  }

  function lighten(hex, t) { return mix(hex, "#ffffff", t); }

  function rgbTriplet(hex) { return hexToRgb(hex).join(", "); }

  function loadSaved() {
    try {
      var raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return null;
      var parsed = JSON.parse(raw);
      if (parsed && typeof parsed.p === "string" && typeof parsed.s === "string") return parsed;
    } catch (e) { /* ignore malformed storage */ }
    return null;
  }

  function save(theme) {
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(theme)); } catch (e) { /* storage unavailable */ }
  }

  function applyTheme(theme) {
    var body = document.body;
    var p = theme.p, s = theme.s;
    var accentLight = theme.a || lighten(p, 0.4);

    body.style.setProperty("--bn-turquoise", p);
    body.style.setProperty("--bn-turquoise-light", accentLight);
    body.style.setProperty("--bn-turquoise-text", mix(p, "#000000", 0.25));
    body.style.setProperty("--bn-turquoise-rgb", rgbTriplet(p));

    body.style.setProperty("--bn-purple", s);
    body.style.setProperty("--bn-purple-light", lighten(s, 0.3));
    body.style.setProperty("--bn-purple-rgb", rgbTriplet(s));

    // Legacy, pre-Aurora-Glass token — still consumed directly by
    // style.css/style2.css in ~100 places (pagination, misc buttons,
    // text accents) that aurora-glass.css doesn't otherwise override.
    body.style.setProperty("--primary-color", p);
  }

  function resetTheme() {
    var body = document.body;
    ["--bn-turquoise", "--bn-turquoise-light", "--bn-turquoise-text", "--bn-turquoise-rgb",
      "--bn-purple", "--bn-purple-light", "--bn-purple-rgb", "--primary-color"
    ].forEach(function (name) { body.style.removeProperty(name); });
  }

  // ---- Apply saved (or default) theme as early as this script runs ----
  var current = loadSaved() || Object.assign({}, DEFAULTS);
  applyTheme(current);

  // ---- UI wiring (runs once the DOM for the header control exists) ----
  document.addEventListener("DOMContentLoaded", function () {
    var trigger = document.getElementById("bnThemeBtn");
    var panel = document.getElementById("bnThemePanel");
    var grid = document.getElementById("bnThemeGrid");
    var presetRow = document.getElementById("bnThemePresets");
    var noAccentBtn = document.getElementById("bnThemeNoAccent");
    var resetBtn = document.getElementById("bnThemeReset");
    if (!trigger || !panel || !grid) return;

    var activeSlot = "p";

    function slotValue(slot) {
      if (slot === "a") return current.a || null;
      return current[slot];
    }

    function renderGrid() {
      grid.innerHTML = "";
      PALETTE.forEach(function (hex) {
        var btn = document.createElement("button");
        btn.type = "button";
        btn.className = "bn-theme-swatch";
        btn.style.background = hex;
        btn.title = hex;
        ["p", "s", "a"].forEach(function (slot) {
          if (slotValue(slot) && slotValue(slot).toLowerCase() === hex.toLowerCase()) {
            btn.classList.add("is-selected");
            var badge = document.createElement("span");
            badge.className = "bn-theme-swatch-badge";
            badge.textContent = slot.toUpperCase();
            btn.appendChild(badge);
          }
        });
        btn.addEventListener("click", function () {
          current = Object.assign({}, current);
          current[activeSlot] = hex;
          commit();
        });
        grid.appendChild(btn);
      });
    }

    function renderSlots() {
      panel.querySelectorAll(".bn-theme-slot").forEach(function (el) {
        var slot = el.getAttribute("data-slot");
        el.classList.toggle("is-active", slot === activeSlot);
        var dot = el.querySelector(".bn-theme-slot-dot");
        if (dot) dot.style.background = slot === "a" ? (current.a || lighten(current.p, 0.4)) : current[slot];
      });
    }

    function renderPresets() {
      presetRow.innerHTML = "";
      PRESETS.forEach(function (preset) {
        var btn = document.createElement("button");
        btn.type = "button";
        btn.className = "bn-theme-preset";
        btn.textContent = preset.n;
        btn.addEventListener("click", function () {
          current = { p: preset.p, s: preset.s, a: preset.a };
          commit();
        });
        presetRow.appendChild(btn);
      });
    }

    function commit() {
      applyTheme(current);
      save(current);
      renderGrid();
      renderSlots();
    }

    panel.querySelectorAll(".bn-theme-slot").forEach(function (el) {
      el.addEventListener("click", function () {
        activeSlot = el.getAttribute("data-slot");
        renderSlots();
      });
    });

    if (noAccentBtn) {
      noAccentBtn.addEventListener("click", function () {
        current = Object.assign({}, current, { a: null });
        commit();
      });
    }

    if (resetBtn) {
      resetBtn.addEventListener("click", function () {
        current = Object.assign({}, DEFAULTS);
        try { localStorage.removeItem(STORAGE_KEY); } catch (e) { /* ignore */ }
        resetTheme();
        applyTheme(current);
        renderGrid();
        renderSlots();
      });
    }

    // Swatch/preset clicks re-render the grid mid-event (innerHTML reset),
    // which detaches e.target before it reaches the document-level
    // outside-click listener below — panel.contains(e.target) then sees a
    // node with no parent and wrongly reports "outside". Stopping
    // propagation here, on the panel itself, keeps any click that started
    // inside it from ever reaching that listener, regardless of what the
    // handler went on to do to the DOM.
    panel.addEventListener("click", function (e) { e.stopPropagation(); });

    trigger.addEventListener("click", function (e) {
      e.stopPropagation();
      var willOpen = panel.hasAttribute("hidden");
      if (willOpen) { panel.removeAttribute("hidden"); } else { panel.setAttribute("hidden", ""); }
      trigger.setAttribute("aria-expanded", willOpen ? "true" : "false");
    });

    document.addEventListener("click", function (e) {
      if (!panel.hasAttribute("hidden") && !panel.contains(e.target) && e.target !== trigger) {
        panel.setAttribute("hidden", "");
        trigger.setAttribute("aria-expanded", "false");
      }
    });

    renderGrid();
    renderSlots();
    renderPresets();
  });
})();

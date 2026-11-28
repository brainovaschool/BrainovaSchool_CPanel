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

  // Soft pastel palette (matching the reference picker's own candy tones,
  // not saturated "Tailwind-500" blocks) - mostly pale swatches at the
  // light end, with a handful of richer "anchor" tones mixed in (the kind
  // the reference used as its default P/S/A picks) for when a bolder pick
  // is wanted.
  var PALETTE = [
    "F6D2DC", "F5AAC8", "DAC4E8", "C5BFE3", "C2DEDE", "8BD5C3",
    "BADBF0", "97B6E0", "F0B38B", "EFD3B0", "F4A48C", "F3C669",
    "FAF19F", "E68FBA", "8BB5B2", "B0D8DC", "D0EFCA", "DBA396",
    "E7C1ED", "F26A6F", "937CAF", "BA9BE1", "7A9FDC", "575A60"
  ].map(function (h) { return "#" + h; });

  var PRESETS = [
    { n: "Brainova turquoise", p: "#0097B2", s: "#8B7BF0", a: null },
    { n: "Lavender & mint", p: "#BA9BE1", s: "#8BD5C3", a: "#F5AAC8" },
    { n: "Peach & sky", p: "#F0B38B", s: "#96D4EF", a: "#FAF19F" },
    { n: "Rose & sage", p: "#E68FBA", s: "#8BB5B2", a: "#EEDFE0" },
    { n: "Ocean", p: "#7A9FDC", s: "#C4E7E6", a: "#B6CAED" },
    { n: "Sunset", p: "#F26A6F", s: "#F3C669", a: "#F5A9C7" }
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

  // WCAG relative luminance / contrast ratio, so pale picks still get
  // readable text instead of eyeballing it.
  function relLuminance(hex) {
    var rgb = hexToRgb(hex).map(function (v) {
      v /= 255;
      return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
    });
    return 0.2126 * rgb[0] + 0.7152 * rgb[1] + 0.0722 * rgb[2];
  }

  function contrastRatio(hexA, hexB) {
    var la = relLuminance(hexA) + 0.05, lb = relLuminance(hexB) + 0.05;
    return la > lb ? la / lb : lb / la;
  }

  // Some of the paler pastel swatches are too light for white text to read
  // on (the sidebar's active-nav highlight is a solid fill of whichever
  // colour is picked) - rather than darkening the swatch itself (which
  // would show the user a colour they didn't actually pick), keep the
  // pick exactly as chosen and fall back to dark ink text only once white
  // genuinely stops working. 3:1 (not the stricter 4.5:1 for small body
  // text) matches WCAG's own bar for large/bold UI label text, and keeps
  // today's shipped turquoise-with-white-text look exactly as it already
  // is (its ratio clears 3:1 comfortably) - this is only meant to catch
  // the truly pale end of the palette.
  function pickOnColor(hex) {
    return contrastRatio(hex, "#ffffff") >= 3 ? "#ffffff" : "#101a2b";
  }

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
    // Pastel bases are already light, so the gradient's 2nd stop only
    // needs a small lift to stay visible instead of washing out to
    // near-white (the old 0.4 was tuned for saturated colours).
    var accentLight = theme.a || lighten(p, 0.15);

    body.style.setProperty("--bn-turquoise", p);
    body.style.setProperty("--bn-turquoise-light", accentLight);
    body.style.setProperty("--bn-turquoise-text", mix(p, "#000000", 0.4));
    body.style.setProperty("--bn-turquoise-rgb", rgbTriplet(p));
    body.style.setProperty("--bn-on-primary", pickOnColor(p));

    body.style.setProperty("--bn-purple", s);
    body.style.setProperty("--bn-purple-light", lighten(s, 0.15));
    body.style.setProperty("--bn-purple-rgb", rgbTriplet(s));
    body.style.setProperty("--bn-on-secondary", pickOnColor(s));

    // Legacy, pre-Aurora-Glass token — still consumed directly by
    // style.css/style2.css in ~100 places (pagination, misc buttons,
    // text accents) that aurora-glass.css doesn't otherwise override.
    body.style.setProperty("--primary-color", p);

    // A pale, 3-stop tint of Primary for big "light box" panels (the
    // Academics Boost hero/course-grid cards, same gradient pattern the
    // Homework page uses) - these don't need their own text contrast
    // (the ink/body text drawn on them is already dark and fixed), just a
    // gentle, consistently-light wash so it never gets dark enough to
    // fight with that text.
    body.style.setProperty("--bn-primary-tint-1", mix(p, "#ffffff", 0.93));
    body.style.setProperty("--bn-primary-tint-2", mix(p, "#ffffff", 0.86));
    body.style.setProperty("--bn-primary-tint-3", mix(p, "#ffffff", 0.74));
    body.style.setProperty("--bn-primary-tint-border", mix(p, "#ffffff", 0.45));
  }

  function resetTheme() {
    var body = document.body;
    ["--bn-turquoise", "--bn-turquoise-light", "--bn-turquoise-text", "--bn-turquoise-rgb", "--bn-on-primary",
      "--bn-purple", "--bn-purple-light", "--bn-purple-rgb", "--bn-on-secondary", "--primary-color",
      "--bn-primary-tint-1", "--bn-primary-tint-2", "--bn-primary-tint-3", "--bn-primary-tint-border"
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
        if (dot) dot.style.background = slot === "a" ? (current.a || lighten(current.p, 0.15)) : current[slot];
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

(function () {
  "use strict";

  var storageKey = "speakready.user.fontScale.v1";
  var defaultScale = 1;
  var minimumScale = 0.8;
  var maximumScale = 1.2;
  var step = 0.1;

  function normalizeScale(value) {
    var numericValue = Number(value);
    if (!Number.isFinite(numericValue)) return defaultScale;

    numericValue = Math.round(numericValue * 10) / 10;
    return Math.min(maximumScale, Math.max(minimumScale, numericValue));
  }

  function readStoredScale() {
    try {
      var storedScale = window.localStorage.getItem(storageKey);
      return storedScale === null ? defaultScale : normalizeScale(storedScale);
    } catch (error) {
      return defaultScale;
    }
  }

  function persistScale(scale) {
    try {
      window.localStorage.setItem(storageKey, String(scale));
    } catch (error) {
      // The control remains functional for the current page when storage is unavailable.
    }
  }

  function formatScale(scale) {
    return Math.round(scale * 100) + "%";
  }

  function applyScale(value, persist) {
    var scale = normalizeScale(value);
    var root = document.documentElement;

    root.dataset.fontScale = String(scale);
    root.style.setProperty("--sr-font-scale", String(scale));
    root.style.fontSize = (100 * scale) + "%";

    if (persist) persistScale(scale);

    document.querySelectorAll("[data-font-size-value]").forEach(function (valueElement) {
      valueElement.textContent = formatScale(scale);
    });

    document.querySelectorAll("[data-font-size-action]").forEach(function (action) {
      var actionName = action.getAttribute("data-font-size-action");
      var disabled = (actionName === "decrease" && scale <= minimumScale)
        || (actionName === "increase" && scale >= maximumScale);

      action.disabled = disabled;
      action.setAttribute("aria-disabled", disabled ? "true" : "false");
    });

    document.querySelectorAll("[data-font-size-toggle]").forEach(function (toggle) {
      toggle.setAttribute("aria-label", "Adjust font size (currently " + formatScale(scale) + ")");
      toggle.setAttribute("title", "Adjust font size (" + formatScale(scale) + ")");
    });

    positionOpenMenus();

    return scale;
  }

  function setMenuOpen(wrapper, open, restoreFocus) {
    if (!wrapper) return;

    var toggle = wrapper.querySelector("[data-font-size-toggle]");
    var menu = wrapper._srFontSizeMenu || wrapper.querySelector("[data-font-size-menu]");
    if (!toggle || !menu) return;

    if (open) {
      // The fixed, blurred headers create their own stacking contexts. Keep the
      // open menu at body level so page cards cannot cover its touch targets.
      document.body.appendChild(menu);
      menu.hidden = false;
      positionMenu(toggle, menu);
    } else {
      menu.hidden = true;
      menu.style.left = "";
      menu.style.top = "";
      menu.style.right = "";
      if (menu.parentNode !== wrapper) wrapper.appendChild(menu);
    }
    toggle.setAttribute("aria-expanded", open ? "true" : "false");
    toggle.classList.toggle("is-active", open);

    if (open) {
      var firstAction = menu.querySelector("[data-font-size-action]:not(:disabled)");
      if (firstAction) {
        window.requestAnimationFrame(function () {
          firstAction.focus({ preventScroll: true });
        });
      }
    } else if (restoreFocus !== false) {
      window.requestAnimationFrame(function () {
        toggle.focus({ preventScroll: true });
      });
    }
  }

  function positionMenu(toggle, menu) {
    var gap = 8;
    var toggleRect = toggle.getBoundingClientRect();
    var menuRect = menu.getBoundingClientRect();
    var viewportWidth = document.documentElement.clientWidth;
    var viewportHeight = window.innerHeight;
    var left = Math.max(gap, Math.min(toggleRect.right - menuRect.width, viewportWidth - menuRect.width - gap));
    var top = toggleRect.bottom + gap;

    if (top + menuRect.height > viewportHeight - gap && toggleRect.top - menuRect.height - gap >= gap) {
      top = toggleRect.top - menuRect.height - gap;
    }

    menu.style.left = Math.round(left) + "px";
    menu.style.top = Math.round(Math.max(gap, Math.min(top, viewportHeight - menuRect.height - gap))) + "px";
    menu.style.right = "auto";
  }

  function positionOpenMenus() {
    document.querySelectorAll("[data-font-size-control]").forEach(function (wrapper) {
      var menu = wrapper._srFontSizeMenu;
      var toggle = wrapper.querySelector("[data-font-size-toggle]");
      if (toggle && menu && !menu.hidden) {
        positionMenu(toggle, menu);
      }
    });
  }

  function closeAllMenus(except) {
    document.querySelectorAll("[data-font-size-control]").forEach(function (wrapper) {
      if (wrapper !== except) setMenuOpen(wrapper, false, false);
    });
  }

  function setup() {
    var scale = applyScale(readStoredScale(), false);

    document.querySelectorAll("[data-font-size-control]").forEach(function (wrapper) {
      var toggle = wrapper.querySelector("[data-font-size-toggle]");
      var menu = wrapper.querySelector("[data-font-size-menu]");
      if (!toggle || !menu || wrapper.dataset.fontSizeReady === "true") return;

      wrapper.dataset.fontSizeReady = "true";
      wrapper._srFontSizeMenu = menu;
      menu._srFontSizeWrapper = wrapper;
      menu.hidden = true;

      toggle.addEventListener("click", function (event) {
        event.preventDefault();
        event.stopPropagation();
        var willOpen = menu.hidden;
        closeAllMenus(wrapper);
        setMenuOpen(wrapper, willOpen);
      });

      menu.addEventListener("click", function (event) {
        var clickedElement = event.target instanceof Element ? event.target : event.target.parentElement;
        var action = clickedElement && clickedElement.closest ? clickedElement.closest("[data-font-size-action]") : null;
        if (!action || action.disabled) return;

        event.preventDefault();
        event.stopPropagation();

        var actionName = action.getAttribute("data-font-size-action");
        var nextScale = actionName === "reset"
          ? defaultScale
          : scale + (actionName === "increase" ? step : -step);

        scale = applyScale(nextScale, true);

        if (actionName === "reset") {
          setMenuOpen(wrapper, false);
        }
      });
    });

    document.addEventListener("click", function (event) {
      var target = event.target instanceof Element ? event.target : event.target.parentElement;
      if (target && target.closest("[data-font-size-control], [data-font-size-menu]")) return;
      closeAllMenus();
    });

    window.addEventListener("resize", positionOpenMenus);
    if (window.visualViewport && window.visualViewport.addEventListener) {
      window.visualViewport.addEventListener("resize", positionOpenMenus);
    }

    document.addEventListener("keydown", function (event) {
      if (event.key !== "Escape") return;

      var openMenu = document.querySelector("[data-font-size-menu]:not([hidden])");
      if (!openMenu) return;

      event.preventDefault();
      setMenuOpen(openMenu._srFontSizeWrapper, false);
    });

    window.SpeakReadyFontSize = {
      get: function () {
        return normalizeScale(document.documentElement.dataset.fontScale || scale);
      },
      set: function (value) {
        scale = applyScale(value, true);
        return scale;
      },
      reset: function () {
        scale = applyScale(defaultScale, true);
        return scale;
      }
    };
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", setup, { once: true });
  } else {
    setup();
  }
})();

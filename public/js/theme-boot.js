(function () {
  var storageKey = "theme";
  var darkMetaColor = "#08080f";
  var lightMetaColor = "#f7f9fc";

  function getStoredTheme() {
    try {
      return localStorage.getItem(storageKey);
    } catch (error) {
      return null;
    }
  }

  function setStoredTheme(theme) {
    try {
      localStorage.setItem(storageKey, theme);
    } catch (error) {
      // Ignore unavailable storage and keep the in-page theme responsive.
    }
  }

  function normalizeTheme(theme) {
    return theme === "dark" ? "dark" : "light";
  }

  function setThemeColor(theme) {
    var meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute("content", theme === "light" ? lightMetaColor : darkMetaColor);
  }

  function syncThemeIcons(theme) {
    var isDark = theme !== "light";
    [
      ["suni", "mooni"],
      ["dbSunI", "dbMoonI"],
      ["mobSunI", "mobMoonI"]
    ].forEach(function (pair) {
      var sun = document.getElementById(pair[0]);
      var moon = document.getElementById(pair[1]);
      if (!sun || !moon) return;
      sun.style.display = isDark ? "none" : "";
      moon.style.display = isDark ? "" : "none";
    });

    var darkToggle = document.getElementById("darkModeToggle");
    if (darkToggle) darkToggle.checked = isDark;
  }

  function applyTheme(theme, persist) {
    var resolvedTheme = normalizeTheme(theme);
    var root = document.documentElement;
    root.classList.toggle("lm", resolvedTheme === "light");
    root.classList.toggle("dm", resolvedTheme === "dark");
    root.dataset.theme = resolvedTheme;
    setThemeColor(resolvedTheme);

    if (persist) setStoredTheme(resolvedTheme);
    if (document.body) {
      document.body.classList.toggle("lm", resolvedTheme === "light");
      document.body.classList.toggle("dm", resolvedTheme === "dark");
    }
    syncThemeIcons(resolvedTheme);

    if (typeof window.updateChartColors === "function") {
      window.updateChartColors();
    }

    return resolvedTheme;
  }

  var initialTheme = normalizeTheme(getStoredTheme());
  applyTheme(initialTheme, false);

  window.SpeakReadyTheme = {
    get: function () {
      return normalizeTheme(document.documentElement.dataset.theme || getStoredTheme());
    },
    apply: applyTheme,
    toggle: function () {
      return applyTheme(this.get() === "light" ? "dark" : "light", true);
    },
    syncIcons: function () {
      syncThemeIcons(this.get());
    }
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () {
      applyTheme(window.SpeakReadyTheme.get(), false);
    });
  }
})();

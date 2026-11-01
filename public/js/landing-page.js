(function () {
  "use strict";

  var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
  var desktopNavigation = window.matchMedia("(min-width: 992px)");
  var standaloneDisplay = window.matchMedia("(display-mode: standalone)");
  var deferredInstallPrompt = null;

  function ready(callback) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", callback, { once: true });
      return;
    }

    callback();
  }

  function bindMediaChange(mediaQuery, callback) {
    if (typeof mediaQuery.addEventListener === "function") {
      mediaQuery.addEventListener("change", callback);
    } else if (typeof mediaQuery.addListener === "function") {
      mediaQuery.addListener(callback);
    }
  }

  function initNavigation() {
    var toggle = document.getElementById("guestNavToggle");
    var panel = document.getElementById("guestNavPanel");

    if (!toggle || !panel) return;

    var open = false;

    function setOpen(nextOpen, restoreFocus) {
      open = Boolean(nextOpen) && !desktopNavigation.matches;
      toggle.setAttribute("aria-expanded", String(open));
      toggle.setAttribute("aria-label", open ? "Close navigation" : "Open navigation");
      panel.classList.toggle("is-open", open);
      panel.setAttribute("aria-hidden", String(!open && !desktopNavigation.matches));
      panel.hidden = !open && !desktopNavigation.matches;
      document.body.classList.toggle("menu-open", open);

      if (open) {
        window.requestAnimationFrame(function () {
          var firstItem = panel.querySelector("a[href], button:not([disabled])");
          if (firstItem) firstItem.focus();
        });
      }

      if (restoreFocus) toggle.focus();
    }

    function syncForViewport() {
      if (desktopNavigation.matches) {
        open = false;
        toggle.setAttribute("aria-expanded", "false");
        panel.hidden = false;
        panel.removeAttribute("aria-hidden");
        panel.classList.remove("is-open");
        document.body.classList.remove("menu-open");
        return;
      }

      setOpen(false, false);
    }

    toggle.addEventListener("click", function () {
      setOpen(!open, false);
    });

    panel.addEventListener("click", function (event) {
      if (event.target.closest('a[href^="#"]')) setOpen(false, false);
    });

    document.addEventListener("click", function (event) {
      if (!open || panel.contains(event.target) || toggle.contains(event.target)) return;
      setOpen(false, false);
    });

    document.addEventListener("keydown", function (event) {
      if (event.key !== "Escape" || !open) return;
      event.preventDefault();
      setOpen(false, true);
    });

    bindMediaChange(desktopNavigation, syncForViewport);
    syncForViewport();
  }

  function initThemeToggle() {
    var toggles = [
      document.getElementById("thbtn"),
      document.getElementById("thbtnMobile")
    ].filter(Boolean);
    if (!toggles.length) return;

    function syncToggle(toggle, theme) {
      var resolvedTheme = theme === "dark" ? "dark" : "light";
      var isDark = resolvedTheme === "dark";
      var actionTheme = isDark ? "light" : "dark";
      var icon = toggle.querySelector("[data-theme-icon]");

      toggle.setAttribute("aria-label", "Switch to " + actionTheme + " theme");
      toggle.setAttribute("title", "Switch to " + actionTheme + " theme");
      toggle.setAttribute("aria-pressed", String(isDark));

      if (!icon) return;

      icon.setAttribute("data-theme-icon", isDark ? "sun" : "moon");
      icon.setAttribute("aria-hidden", "true");
      icon.classList.toggle("fa-sun", isDark);
      icon.classList.toggle("fa-moon", !isDark);

      if (!icon.matches("i") && !icon.querySelector("svg")) {
        icon.textContent = isDark ? "☀" : "☾";
      }
    }

    function sync(theme) {
      toggles.forEach(function (toggle) {
        syncToggle(toggle, theme);
      });
    }

    var themeApi = window.SpeakReadyTheme;
    sync(themeApi && typeof themeApi.get === "function" ? themeApi.get() : document.documentElement.dataset.theme);

    toggles.forEach(function (toggle) {
      toggle.addEventListener("click", function () {
        var api = window.SpeakReadyTheme;
        var theme;

        if (api && typeof api.toggle === "function") {
          theme = api.toggle();
        } else {
          theme = document.documentElement.dataset.theme === "dark" ? "light" : "dark";
          document.documentElement.dataset.theme = theme;
          document.documentElement.classList.toggle("lm", theme === "light");
          document.documentElement.classList.toggle("dm", theme === "dark");
        }

        sync(theme);
      });
    });
  }

  function initNavbarState() {
    var navbar = document.getElementById("nbar");
    if (!navbar) return;

    function update() {
      navbar.classList.toggle("is-scrolled", window.scrollY > 12);
    }

    window.addEventListener("scroll", update, { passive: true });
    update();
  }

  function initReveals() {
    var items = Array.from(document.querySelectorAll(".reveal"));
    if (!items.length) return;

    if (reduceMotion.matches || !("IntersectionObserver" in window)) {
      items.forEach(function (item) {
        item.classList.add("is-visible");
      });
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add("is-visible");
        observer.unobserve(entry.target);
      });
    }, {
      rootMargin: "0px 0px -10% 0px",
      threshold: 0.12
    });

    items.forEach(function (item) {
      observer.observe(item);
    });
  }

  function initActiveNavigation() {
    var links = Array.from(document.querySelectorAll('#nbar a[href^="#"], #guestNavPanel a[href^="#"]'));
    if (!links.length) return;

    var sections = [];
    var linksBySection = new Map();

    links.forEach(function (link) {
      var hash = link.hash;
      if (!hash || hash === "#") return;

      var id = decodeURIComponent(hash.slice(1));
      var section = document.getElementById(id);
      if (!section) return;

      if (!linksBySection.has(id)) {
        linksBySection.set(id, []);
        sections.push(section);
      }

      linksBySection.get(id).push(link);
    });

    if (!sections.length) return;

    function setCurrent(id) {
      linksBySection.forEach(function (sectionLinks, sectionId) {
        sectionLinks.forEach(function (link) {
          if (sectionId === id) {
            link.setAttribute("aria-current", "location");
          } else {
            link.removeAttribute("aria-current");
          }
        });
      });
    }

    setCurrent(sections[0].id);

    if (!("IntersectionObserver" in window)) return;

    var observer = new IntersectionObserver(function (entries) {
      var visible = entries
        .filter(function (entry) { return entry.isIntersecting; })
        .sort(function (a, b) {
          return Math.abs(a.boundingClientRect.top) - Math.abs(b.boundingClientRect.top);
        });

      if (visible[0]) setCurrent(visible[0].target.id);
    }, {
      rootMargin: "-30% 0px -60% 0px",
      threshold: 0
    });

    sections.forEach(function (section) {
      observer.observe(section);
    });
  }

  function initBackToTop() {
    var button = document.getElementById("backToTopBtn");
    if (!button) return;

    button.hidden = false;

    function update() {
      var visible = window.scrollY > 640;
      button.classList.toggle("is-visible", visible);
      button.setAttribute("aria-hidden", String(!visible));
      button.tabIndex = visible ? 0 : -1;
    }

    button.addEventListener("click", function () {
      window.scrollTo({
        top: 0,
        behavior: reduceMotion.matches ? "auto" : "smooth"
      });
    });

    window.addEventListener("scroll", update, { passive: true });
    update();
  }

  function isStandalone() {
    return standaloneDisplay.matches || window.navigator.standalone === true;
  }

  function isIos() {
    return /iphone|ipad|ipod/i.test(window.navigator.userAgent)
      || (window.navigator.platform === "MacIntel" && window.navigator.maxTouchPoints > 1);
  }

  function initInstallExperience() {
    var installButton = document.getElementById("heroInstallBtn");
    var dialog = document.getElementById("pwaInstallDialog");
    var message = document.getElementById("pwaInstallMessage");
    var confirmButton = document.getElementById("pwaInstallConfirm");
    var closeButton = document.getElementById("pwaInstallClose");
    var cancelButton = document.getElementById("pwaInstallCancel");

    if (!installButton) return;

    var returnFocusTo = null;

    function syncInstallAction() {
      var installed = isStandalone();
      installButton.hidden = installed;
      installButton.setAttribute("aria-hidden", String(installed));
    }

    function closeDialog() {
      if (!dialog) return;

      if (typeof dialog.close === "function" && dialog.open) {
        dialog.close();
      } else {
        dialog.removeAttribute("open");
        dialog.hidden = true;
      }
    }

    function showDialog(copy, canInstall) {
      if (!dialog) return;

      returnFocusTo = document.activeElement;
      if (message) message.textContent = copy;
      if (confirmButton) confirmButton.hidden = !canInstall;
      dialog.hidden = false;

      if (typeof dialog.showModal === "function") {
        if (!dialog.open) dialog.showModal();
      } else {
        dialog.setAttribute("open", "");
      }

      window.requestAnimationFrame(function () {
        (canInstall && confirmButton ? confirmButton : closeButton || dialog).focus();
      });
    }

    installButton.addEventListener("click", function () {
      if (isStandalone()) {
        syncInstallAction();
        return;
      }

      if (deferredInstallPrompt) {
        showDialog("Install SpeakReady AI for quick access from your home screen or app menu.", true);
        return;
      }

      if (isIos()) {
        showDialog("To install on iPhone or iPad, open Safari's Share menu and choose Add to Home Screen.", false);
        return;
      }

      showDialog("Installation is not available in this browser right now. Try your browser's Install app option if it appears.", false);
    });

    if (confirmButton) {
      confirmButton.addEventListener("click", async function () {
        if (!deferredInstallPrompt) {
          closeDialog();
          return;
        }

        var promptEvent = deferredInstallPrompt;
        deferredInstallPrompt = null;
        closeDialog();

        try {
          await promptEvent.prompt();
          await promptEvent.userChoice;
        } catch (error) {
          // Browsers can withdraw an install prompt while the page is open.
        }

        syncInstallAction();
      });
    }

    if (closeButton) closeButton.addEventListener("click", closeDialog);
    if (cancelButton) cancelButton.addEventListener("click", closeDialog);

    if (dialog) {
      dialog.addEventListener("click", function (event) {
        if (event.target === dialog) closeDialog();
      });

      dialog.addEventListener("close", function () {
        if (returnFocusTo && typeof returnFocusTo.focus === "function") returnFocusTo.focus();
        returnFocusTo = null;
      });
    }

    window.addEventListener("beforeinstallprompt", function (event) {
      event.preventDefault();
      deferredInstallPrompt = event;
      syncInstallAction();
    });

    window.addEventListener("appinstalled", function () {
      deferredInstallPrompt = null;
      closeDialog();
      syncInstallAction();
    });

    bindMediaChange(standaloneDisplay, syncInstallAction);
    syncInstallAction();
  }

  function renderCount(element, value) {
    var decimals = Math.max(0, Number(element.dataset.countDecimals || 0));
    var prefix = element.dataset.countPrefix || "";
    var suffix = element.dataset.countSuffix || "";
    var formatted = new Intl.NumberFormat(undefined, {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals
    }).format(value);

    element.textContent = prefix + formatted + suffix;
  }

  function animateCount(element) {
    var target = Number(element.dataset.count);
    if (!Number.isFinite(target)) return;

    if (reduceMotion.matches) {
      renderCount(element, target);
      return;
    }

    var duration = 900;
    var startedAt = null;

    function step(timestamp) {
      if (startedAt === null) startedAt = timestamp;
      var progress = Math.min(1, (timestamp - startedAt) / duration);
      var eased = 1 - Math.pow(1 - progress, 3);
      renderCount(element, target * eased);

      if (progress < 1) {
        window.requestAnimationFrame(step);
      } else {
        renderCount(element, target);
      }
    }

    window.requestAnimationFrame(step);
  }

  function initCounters() {
    var counters = Array.from(document.querySelectorAll("[data-count]"));
    if (!counters.length) return;

    if (reduceMotion.matches || !("IntersectionObserver" in window)) {
      counters.forEach(animateCount);
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        animateCount(entry.target);
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.4 });

    counters.forEach(function (counter) {
      observer.observe(counter);
    });
  }

  function registerServiceWorker() {
    if (!("serviceWorker" in navigator)) return;

    window.addEventListener("load", function () {
      navigator.serviceWorker.register("/sw.js?v=46").catch(function () {
        // Service worker support is an enhancement; keep the landing page usable without it.
      });
    }, { once: true });
  }

  ready(function () {
    initNavigation();
    initThemeToggle();
    initNavbarState();
    initReveals();
    initActiveNavigation();
    initBackToTop();
    initInstallExperience();
    initCounters();
  });

  registerServiceWorker();
})();

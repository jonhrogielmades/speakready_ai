(function () {
  var storageKey = "speakready.user.fontScale.v1";
  var defaultScale = 1;
  var minimumScale = 0.8;
  var maximumScale = 1.2;

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

  var scale = readStoredScale();
  var root = document.documentElement;
  root.dataset.fontScale = String(scale);
  root.style.setProperty("--sr-font-scale", String(scale));
  root.style.fontSize = (100 * scale) + "%";
})();

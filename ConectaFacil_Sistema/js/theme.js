(function () {
  var KEY = 'cf-theme';

  function iconsBase() {
    var b = document.documentElement.getAttribute('data-icons-base');
    if (b) return b;
    return 'icons/';
  }

  function isDark() {
    return document.documentElement.getAttribute('data-theme') === 'dark';
  }

  function apply(theme) {
    if (theme === 'dark') {
      document.documentElement.setAttribute('data-theme', 'dark');
    } else {
      document.documentElement.removeAttribute('data-theme');
    }
    try {
      localStorage.setItem(KEY, theme === 'dark' ? 'dark' : 'light');
    } catch (e) {}
    syncButtons();
  }

  function toggle() {
    apply(isDark() ? 'light' : 'dark');
  }

  function syncButtons() {
    var dark = isDark();
    var base = iconsBase();
    document.querySelectorAll('.theme-toggle-btn').forEach(function (btn) {
      btn.setAttribute('aria-pressed', dark ? 'true' : 'false');
      btn.setAttribute('aria-label', dark ? 'Ativar modo claro' : 'Ativar modo escuro');
      var label = btn.querySelector('.theme-toggle-label');
      if (label) label.textContent = dark ? 'Modo claro' : 'Modo escuro';
      var icon = btn.querySelector('.theme-toggle-icon');
      if (icon && icon.tagName === 'IMG') {
        icon.src = base + (dark ? 'sun.svg' : 'moon.svg');
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.theme-toggle-btn').forEach(function (btn) {
      btn.addEventListener('click', toggle);
    });
    try {
      var s = localStorage.getItem(KEY);
      if (s === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
      else if (s === 'light') document.documentElement.removeAttribute('data-theme');
    } catch (e) {}
    syncButtons();
  });
})();

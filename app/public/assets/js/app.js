// Domácí rozpočet - společné chování rozhraní

(function initTheme() {
  var buttons = document.querySelectorAll('#theme-toggle button');
  var saved = localStorage.getItem('theme') || 'system';

  function apply(value) {
    if (value === 'system') {
      document.documentElement.removeAttribute('data-theme');
    } else {
      document.documentElement.setAttribute('data-theme', value);
    }
    buttons.forEach(function (b) {
      b.classList.toggle('active', b.dataset.themeValue === value);
    });
  }

  apply(saved);

  buttons.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var value = btn.dataset.themeValue;
      localStorage.setItem('theme', value);
      apply(value);
      fetch('/actions/theme_save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'theme=' + encodeURIComponent(value),
      }).catch(function () {});
    });
  });
})();

// Vysouvací navigace pro tablet a mobil. Na desktopu zůstává postranní menu stále viditelné.
(function initResponsiveSidebar() {
  var sidebar = document.getElementById('app-sidebar');
  var toggle = document.querySelector('[data-sidebar-toggle]');
  var closeControls = document.querySelectorAll('[data-sidebar-close]');
  if (!sidebar || !toggle || !window.matchMedia) return;

  var media = window.matchMedia('(max-width: 1024px)');
  var lastFocused = null;

  function setOpen(open, returnFocus) {
    if (!media.matches) open = false;
    document.body.classList.toggle('sidebar-open', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    toggle.setAttribute('aria-label', open ? 'Zavřít navigační menu' : 'Otevřít navigační menu');

    if (open) {
      lastFocused = document.activeElement;
      var closeButton = sidebar.querySelector('[data-sidebar-close]');
      if (closeButton) closeButton.focus();
    } else if (returnFocus && lastFocused && typeof lastFocused.focus === 'function') {
      toggle.focus();
    }
  }

  toggle.addEventListener('click', function () {
    setOpen(!document.body.classList.contains('sidebar-open'), true);
  });

  closeControls.forEach(function (control) {
    control.addEventListener('click', function () {
      setOpen(false, true);
    });
  });

  sidebar.addEventListener('click', function (event) {
    if (media.matches && event.target.closest('.nav-link')) setOpen(false, false);
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && document.body.classList.contains('sidebar-open')) {
      setOpen(false, true);
    }
  });

  function closeOnDesktop() { setOpen(false, false); }
  if (typeof media.addEventListener === 'function') {
    media.addEventListener('change', closeOnDesktop);
  } else {
    media.addListener(closeOnDesktop);
  }
})();

// Přizpůsobení sekce Přehled: volba zobrazených doplňkových widgetů zůstává uložená v prohlížeči.
(function initDashboardCustomizer() {
  var dashboard = document.querySelector('.dashboard');
  if (!dashboard) return;

  // Nové rozvržení grafů má vlastní výchozí stav, aby se zobrazil i uživatelům
  // se starším uloženým nastavením původního panelu kategorií.
  var storageKey = 'dashboard-widget-visibility-v2';
  var defaults = { secondary: true, trend: true, insights: true, categories: true };
  var toggles = dashboard.querySelectorAll('[data-dashboard-toggle]');
  var reset = dashboard.querySelector('[data-dashboard-reset]');
  var preferences = {};

  try {
    preferences = JSON.parse(localStorage.getItem(storageKey) || '{}');
  } catch (error) {
    preferences = {};
  }

  function visibleFor(key) {
    return typeof preferences[key] === 'boolean' ? preferences[key] : defaults[key] !== false;
  }

  function syncChartLayout() {
    var chartGrid = dashboard.querySelector('.dashboard-charts-grid');
    if (!chartGrid) return;

    var trendVisible = visibleFor('trend');
    var categoriesVisible = visibleFor('categories');
    chartGrid.hidden = !trendVisible && !categoriesVisible;
    chartGrid.classList.toggle('dashboard-charts-grid--single', trendVisible !== categoriesVisible);
  }

  function applyWidget(key, visible, announce) {
    document.querySelectorAll('[data-dashboard-widget="' + key + '"]').forEach(function (widget) {
      widget.hidden = !visible;
    });
    toggles.forEach(function (toggle) {
      if (toggle.dataset.dashboardToggle === key) toggle.checked = visible;
    });
    if (key === 'trend' || key === 'categories') syncChartLayout();
    if (announce) {
      window.dispatchEvent(new CustomEvent('dashboardwidgetchange', { detail: { key: key, visible: visible } }));
    }
  }

  function applyAll(announce) {
    Object.keys(defaults).forEach(function (key) {
      applyWidget(key, visibleFor(key), announce);
    });
  }

  function save() {
    localStorage.setItem(storageKey, JSON.stringify(preferences));
  }

  toggles.forEach(function (toggle) {
    toggle.addEventListener('change', function () {
      var key = toggle.dataset.dashboardToggle;
      preferences[key] = toggle.checked;
      save();
      applyWidget(key, toggle.checked, true);
    });
  });

  if (reset) {
    reset.addEventListener('click', function () {
      preferences = {};
      localStorage.removeItem(storageKey);
      applyAll(true);
    });
  }

  applyAll(false);
})();

// Potvrzovací dialog před odstraněním - používá <dialog> nebo confirm() jako fallback
document.addEventListener('click', function (e) {
  var el = e.target.closest('[data-confirm]');
  if (!el) return;
  if (window.APP_SETTINGS && window.APP_SETTINGS.confirmDelete === false) return;
  var msg = el.getAttribute('data-confirm');
  if (!window.confirm(msg)) {
    e.preventDefault();
    e.stopPropagation();
  }
});

// Dialog pro odstranění položky - volitelně nabídne smazání přiložených souborů
document.addEventListener('click', function (e) {
  var el = e.target.closest('[data-delete-transaction]');
  if (!el) return;
  e.preventDefault();
  var dialog = document.getElementById('delete-transaction-dialog');
  if (!dialog) return;
  document.getElementById('delete-transaction-id').value = el.getAttribute('data-delete-transaction');

  if (window.APP_SETTINGS && window.APP_SETTINGS.confirmDelete === false) {
    dialog.querySelector('form').submit();
    return;
  }

  var name = el.getAttribute('data-transaction-name');
  document.getElementById('delete-transaction-name').textContent = name
    ? 'Opravdu chcete odstranit položku „' + name + '“? Tuto akci nelze vrátit zpět.'
    : 'Opravdu chcete tuto položku odstranit? Tuto akci nelze vrátit zpět.';
  var filesRow = document.getElementById('delete-files-row');
  filesRow.style.display = el.getAttribute('data-has-attachments') === '1' ? '' : 'none';
  dialog.showModal();
});

// Zrušení všech filtrů jedním tlačítkem
document.addEventListener('click', function (e) {
  var el = e.target.closest('[data-clear-filters]');
  if (!el) return;
  e.preventDefault();
  window.location.href = el.getAttribute('data-clear-filters');
});

// Automatické skrytí flash zpráv po chvíli
window.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.flash').forEach(function (f) {
    setTimeout(function () {
      f.style.transition = 'opacity .4s ease';
      f.style.opacity = '0';
      setTimeout(function () { f.remove(); }, 400);
    }, 4500);
  });
});

// Výběr kategorie: skupiny (optgroup) se filtrují podle zvoleného typu položky (příjem/výdaj)
function initCategoryTypeFilter(selectId, typeRadioName) {
  var select = document.getElementById(selectId);
  if (!select) return;
  var radios = document.querySelectorAll('input[name="' + typeRadioName + '"]');
  if (!radios.length) return;

  function currentType() {
    for (var i = 0; i < radios.length; i++) {
      if (radios[i].checked) return radios[i].value;
    }
    return null;
  }

  function refresh() {
    var type = currentType();
    var groups = select.querySelectorAll('optgroup');
    var selectedHidden = false;
    groups.forEach(function (g) {
      var show = g.dataset.type === type;
      g.hidden = !show;
      if (!show && select.selectedOptions[0] && g.contains(select.selectedOptions[0])) {
        selectedHidden = true;
      }
    });
    if (selectedHidden) select.value = '';
  }

  radios.forEach(function (r) { r.addEventListener('change', refresh); });
  refresh();
}
window.initCategoryTypeFilter = initCategoryTypeFilter;

// Přepínání viditelnosti bloků formuláře podle stavu (např. status = částečně zaplaceno)
function initConditional(triggerSelector, targetSelector, showWhenFn) {
  var trigger = document.querySelector(triggerSelector);
  var target = document.querySelector(targetSelector);
  if (!trigger || !target) return;
  function refresh() { target.style.display = showWhenFn(trigger.value) ? '' : 'none'; }
  trigger.addEventListener('change', refresh);
  refresh();
}
window.initConditional = initConditional;

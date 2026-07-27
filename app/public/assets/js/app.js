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

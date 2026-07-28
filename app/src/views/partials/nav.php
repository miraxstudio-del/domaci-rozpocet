<?php
$navItems = [
    'prehled'     => ['icon' => '📊', 'label' => 'Přehled'],
    'polozky'     => ['icon' => '📋', 'label' => 'Příjmy a výdaje'],
    'polozka'     => ['icon' => '➕', 'label' => 'Přidat položku'],
    'pravidelne'  => ['icon' => '🔁', 'label' => 'Pravidelné platby'],
    'rozpocty'    => ['icon' => '🎯', 'label' => 'Rozpočty'],
    'doklady'     => ['icon' => '🧾', 'label' => 'Doklady'],
    'statistiky'  => ['icon' => '📈', 'label' => 'Statistiky'],
    'mesice'      => ['icon' => '🗓️', 'label' => 'Měsíce'],
    'export'      => ['icon' => '⬇️', 'label' => 'Export'],
    'zalohy'      => ['icon' => '💾', 'label' => 'Zálohy'],
    'nastaveni'   => ['icon' => '⚙️', 'label' => 'Nastavení'],
    'o-programu'  => ['icon' => 'ℹ️', 'label' => 'O programu'],
];
$household = get_setting('household_name', 'Naše domácnost');
?>
<aside class="sidebar" id="app-sidebar" aria-label="Hlavní navigace">
  <div class="sidebar-brand">
    <span class="logo">🏡</span>
    <div class="sidebar-brand-copy">
      Domácí rozpočet
      <small><?= h($household) ?></small>
    </div>
    <button class="sidebar-close" type="button" data-sidebar-close aria-label="Zavřít navigační menu"><span aria-hidden="true">×</span></button>
  </div>
  <form method="get" action="/index.php" class="sidebar-search">
    <input type="hidden" name="p" value="polozky">
    <input type="search" name="q" placeholder="Hledat položky..." value="<?= h($_GET['q'] ?? '') ?>">
  </form>
  <nav class="nav-group">
    <?php foreach ($navItems as $key => $item): ?>
      <a class="nav-link <?= $activeNav === $key ? 'active' : '' ?>" href="/index.php?p=<?= $key ?>" title="<?= h($item['label']) ?>" aria-label="<?= h($item['label']) ?>">
        <span class="ic" aria-hidden="true"><?= $item['icon'] ?></span><span class="nav-label"><?= h($item['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-collapse-footer">
    <button class="sidebar-collapse-button" type="button" data-sidebar-collapse aria-controls="app-sidebar" aria-expanded="true" aria-label="Sbalit levé menu" title="Sbalit levé menu">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6-6 6 6 6"/></svg>
    </button>
  </div>
</aside>

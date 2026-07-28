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
<aside class="sidebar">
  <div class="sidebar-brand">
    <span class="logo">🏡</span>
    <div>
      Domácí rozpočet
      <small><?= h($household) ?></small>
    </div>
  </div>
  <form method="get" action="/index.php" class="sidebar-search">
    <input type="hidden" name="p" value="polozky">
    <input type="search" name="q" placeholder="Hledat položky..." value="<?= h($_GET['q'] ?? '') ?>">
  </form>
  <nav class="nav-group">
    <?php foreach ($navItems as $key => $item): ?>
      <a class="nav-link <?= $activeNav === $key ? 'active' : '' ?>" href="/index.php?p=<?= $key ?>">
        <span class="ic"><?= $item['icon'] ?></span> <?= h($item['label']) ?>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-footer">
    <div class="theme-toggle" id="theme-toggle">
      <button type="button" data-theme-value="light">☀️ Světlý</button>
      <button type="button" data-theme-value="dark">🌙 Tmavý</button>
      <button type="button" data-theme-value="system">🖥️ Auto</button>
    </div>
  </div>
</aside>

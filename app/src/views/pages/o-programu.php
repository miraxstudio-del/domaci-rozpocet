<?php
$pageTitle = 'O programu';
$activeNav = 'o-programu';
?>
<div class="topbar">
  <div>
    <h1>ℹ️ O programu</h1>
    <div class="subtitle">Krátký příběh o tom, jak tahle aplikace vznikla</div>
  </div>
</div>

<div class="card">
  <h3>🙋‍♂️ Proč tahle aplikace vlastně existuje</h3>
  <p>
    Jednoho večera přišla manželka s tím, že by chtěla mít konečně
    <strong>pořádný přehled o rodinném rozpočtu</strong> — kolik utratíme za
    jídlo, kolik za energie, kam mizí peníze na konci měsíce a proč ta
    dovolená pokaždé stojí víc, než jsme plánovali. Sešit a excelovská
    tabulka to dlouho držely nad vodou, ale nakonec zvítězilo jednoduché
    zadání: <em>„Udělej mi na to nějaký pořádný program.“</em>
  </p>
  <p>
    A protože odmítnout takový úkol se prostě nedá (a přiznejme si, kdo by
    si to dovolil 😄), vznikl <strong>Domácí rozpočet</strong> — jednoduchá,
    přehledná a hlavně česká aplikace, která běží přímo na vašem počítači,
    nikam nic neposílá a stará se jen o jedno: abyste vy i vaše domácnost
    měli konečně jasno v penězích.
  </p>
  <p class="text-muted" style="font-size:13.5px;">
    Takže až příště budete klikat na „Přidat položku“, vězte, že za tímhle
    tlačítkem stojí jedna docela obyčejná domácnost, jeden notebook a
    jeden manžel, který se rozhodl, že tabulky v Excelu už fakt stačily. 🧾❤️
  </p>
</div>

<div class="grid grid-cols-2 about-details-grid" style="align-items:stretch;">
  <div class="card">
    <h3>📋 Základní údaje</h3>
    <table style="width:100%;font-size:14px;">
      <tbody>
        <tr><td class="text-muted" style="padding:6px 0;width:160px;">Název aplikace</td><td><strong><?= h(APP_NAME) ?></strong></td></tr>
        <tr><td class="text-muted" style="padding:6px 0;">Verze</td><td><strong>v<?= h(APP_VERSION) ?></strong></td></tr>
        <tr><td class="text-muted" style="padding:6px 0;">Vytvořeno</td><td>červenec 2026</td></tr>
        <tr><td class="text-muted" style="padding:6px 0;">Autor</td><td><a href="https://www.miraxstudio.cz" target="_blank" rel="noopener">Mirax Studio</a></td></tr>
        <tr><td class="text-muted" style="padding:6px 0;">Cena</td><td><strong>zdarma</strong>, navždy 🎉</td></tr>
        <tr><td class="text-muted" style="padding:6px 0;">Licence</td><td>open-source, volně k šíření a úpravám</td></tr>
      </tbody>
    </table>
  </div>

  <div class="card">
    <h3>💡 Pár slov na závěr</h3>
    <p class="text-muted" style="font-size:13.5px;">
      Aplikace je úplně zdarma a může ji používat kdokoliv, kdo si chce
      udělat pořádek ve svých financích — ať už jde o domácnost, rodinu
      nebo drobné podnikání vedle toho. Žádná registrace, žádné reklamy,
      žádné odesílání dat kamkoliv na internet. Jen vy, vaše čísla a
      klidnější spaní.
    </p>
    <p class="text-muted" style="font-size:13.5px;">
      Přejeme ať se vám s ní hospodaří dobře — a manželce ať se konečně
      sbíhají čísla na konci měsíce. 😉
    </p>
    <a class="btn secondary sm" href="https://www.miraxstudio.cz" target="_blank" rel="noopener">🌐 www.miraxstudio.cz</a>
  </div>
</div>

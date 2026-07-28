# Vydání přenosné aplikace

Zdrojový repozitář obsahuje pouze kontrolovatelný zdrojový kód. Spustitelný PHP
runtime a hotový ZIP pro uživatele patří do **GitHub Releases**, ne do historie
zdrojového repozitáře. Díky tomu je klonování rychlé a každý může snadno
zkontrolovat změny v kódu.

## Příprava release

1. Pracujte s čistým pracovním stromem (`git status`).
2. Z oficiálního webu [PHP for Windows](https://windows.php.net/download/)
   stáhněte podporovaný **PHP 8.2 x64 NTS** runtime a ověřte jeho kontrolní
   součet podle zdroje PHP.
3. Rozbalte runtime lokálně do `app/php/`. Tato složka je schválně ignorovaná
   Gitem; do repozitáře ji nepřidávejte.
4. V kořeni projektu spusťte:

   ```powershell
   powershell -ExecutionPolicy Bypass -File .\scripts\build-release.ps1 -Version 1.01
   ```

5. Ve složce `dist/` vzniknou dva soubory:

   - `Domaci-rozpocet-v1.01-windows-x64.zip` – přenositelná aplikace
   - `Domaci-rozpocet-v1.01-windows-x64.sha256` – ověřovací součet ZIPu

6. Rozbalte ZIP do nové dočasné složky a vyzkoušejte `START.bat` i `STOP.bat`.
   Balíček nesmí obsahovat databázi domácnosti, doklady, zálohy, exporty ani
   jiné osobní údaje.
7. Na GitHubu vytvořte vydání z tagu `v1.01`, přiložte oba soubory z `dist/`
   a do poznámky k vydání napište SHA-256 součet.

## Doporučený text k vydání

**Domácí rozpočet v1.01 – Vizuální vylepšení**

Toto je samostatný přenositelný balíček pro Windows x64. Rozbalte jej a
spusťte `START.bat`; nic se neinstaluje. Data domácnosti zůstávají lokálně na
vašem počítači. Před spuštěním si můžete ověřit SHA-256 součet podle přiloženého
souboru `.sha256`.

## Bezpečnostní zásady

- Release se sestavuje pouze z Gitem sledovaných souborů plus ověřeného PHP
  runtime.
- Složky s uživatelskými daty jsou ignorované a build skript je nekopíruje.
- Nikdy nevydávejte ZIP z pracovní složky ručně; použijte vždy tento skript.
- Zdrojový kód, licence a změny zůstávají veřejně auditovatelné v repozitáři.
